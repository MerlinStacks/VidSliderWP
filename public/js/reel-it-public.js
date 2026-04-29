(function () {
    'use strict';

    /**
     * Generates or retrieves a per-session anonymous ID for analytics.
     * Uses crypto.randomUUID() when available for unpredictable IDs.
     *
     * @return {string} Session identifier.
     */
    function getSessionId() {
        let sid = sessionStorage.getItem('reel_it_sid');
        if (!sid) {
            if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
                sid = 'ri_' + crypto.randomUUID();
            } else {
                sid = 'ri_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }
            sessionStorage.setItem('reel_it_sid', sid);
        }
        return sid;
    }

    /**
     * Fire-and-forget analytics POST via fetch.
     * Why fetch instead of $.post: removes the jQuery dependency (~87KB).
     */
    function trackEvent(eventType, videoId, feedId, watchTime, productId) {
        if (typeof reel_it_public === 'undefined') return;

        const body = new URLSearchParams({
            action: 'reel_it_track_event',
            nonce: reel_it_public.nonce,
            event_type: eventType,
            video_id: videoId,
            feed_id: feedId || 0,
            watch_time: watchTime || 0,
            product_id: productId || 0,
            session_id: getSessionId()
        });

        fetch(reel_it_public.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
            keepalive: true
        }).catch(function () {
            /* Why: silently swallow analytics failures — they must never break UX */
        });
    }

    /**
     * Creates a <video> element on demand inside a slide container.
     * Why: Avoids rendering any <video> tags on initial page load for PageSpeed.
     *
     * @param {HTMLElement} vc - The .reel-it-video-container element.
     * @return {HTMLVideoElement|null} The created video element, or null if src missing.
     */
    function createVideo(vc) {
        /* Avoid duplicates */
        if (vc.querySelector('video.reel-it-video')) {
            return vc.querySelector('video.reel-it-video');
        }

        const src = vc.dataset.videoSrc;
        if (!src) return null;

        const video = document.createElement('video');
        video.className = 'reel-it-video';
        video.setAttribute('playsinline', '');
        video.setAttribute('preload', 'auto');
        video.dataset.videoSrc = src;
        video.dataset.videoId = vc.dataset.videoId || '';

        /* Why: mute ensures play() succeeds without prior user gesture (autoplay policy) */
        video.muted = true;
        video.src = src;

        /* Insert before overlay so overlay renders on top */
        const overlay = vc.querySelector('.reel-it-video-overlay');
        if (overlay) {
            vc.insertBefore(video, overlay);
        } else {
            vc.appendChild(video);
        }

        return video;
    }

    /**
     * Removes a <video> element from a slide container to free memory.
     * Restores the poster image visibility.
     *
     * @param {HTMLElement} vc - The .reel-it-video-container element.
     */
    function destroyVideo(vc) {
        const video = vc.querySelector('video.reel-it-video');
        if (!video) return;

        video.pause();
        video.removeAttribute('src');
        video.load(); /* Why: releases network/decode resources immediately */
        video.remove();

        vc.classList.remove('playing', 'muted');

        /* Restore poster visibility */
        const poster = vc.querySelector('.reel-it-poster');
        if (poster) {
            /* Why: force-reset transition so poster appears instantly, not with a 200ms fade */
            poster.style.transition = 'none';
            poster.style.opacity = '';
            poster.style.pointerEvents = '';
            void poster.offsetHeight; /* Force reflow */
            poster.style.transition = '';
        }
    }

    class VideoSlider {
        constructor(element) {
            this.container = element;
            this.slider = element.querySelector('.reel-it-slider');
            this.feedId = element.dataset.feedId || 0;

            this.slides = Array.from(element.querySelectorAll('.reel-it-slide'));
            this.prevBtn = element.querySelector('.reel-it-prev');
            this.nextBtn = element.querySelector('.reel-it-next');
            this.thumbnailBtns = Array.from(element.querySelectorAll('.reel-it-thumbnail'));

            this.currentSlide = 0;
            this.totalSlides = this.slides.length;
            this.isScrolling = false;
            this.sequentialAutoplay = true;
            this.scrollTimeout = null;
            this.trackedPlays = new Set();
            this.trackedCompletions = new Set();

            /* Why: store bound handlers so we can remove them in destroy() */
            this._boundHandlers = [];

            if (this.totalSlides === 0) return;

            this.init();
        }

        init() {
            this.slides.forEach(function (s) { s.classList.remove('active'); });
            this.slides[0].classList.add('active');
            this.currentSlide = 0;

            this.container.classList.remove('loading');
            this.bindEvents();

            /* Why: autoplay creates the first video on demand and plays it muted,
               matching the old <video autoplay muted> behaviour */
            if (this.container.dataset.autoplay === '1') {
                const vc = this._vc(0);
                if (vc) {
                    const video = createVideo(vc);
                    if (video) {
                        this.playVideo(video);
                        this._bindVideoEvents(vc, video);
                    }
                }
            }
        }

        /**
         * Helper to addEventListener with automatic cleanup tracking.
         */
        _on(el, event, handler, options) {
            el.addEventListener(event, handler, options);
            this._boundHandlers.push({ el, event, handler, options });
        }

        /**
         * Returns the .reel-it-video-container for a given slide index.
         *
         * @param {number} index
         * @return {HTMLElement|null}
         */
        _vc(index) {
            const slide = this.slides[index];
            return slide ? slide.querySelector('.reel-it-video-container') : null;
        }

        /**
         * Returns the active <video> element in a slide, if one exists.
         *
         * @param {number} index
         * @return {HTMLVideoElement|null}
         */
        _video(index) {
            const vc = this._vc(index);
            return vc ? vc.querySelector('video.reel-it-video') : null;
        }

        bindEvents() {
            /* 1. Play/Pause via overlay or play button */
            this._on(this.container, 'click', (e) => {
                const overlay = e.target.closest('.reel-it-video-overlay, .reel-it-play-button');
                if (!overlay) return;

                e.stopPropagation();
                e.preventDefault();

                const vc = overlay.closest('.reel-it-video-container');
                if (!vc) return;

                const existingVideo = vc.querySelector('video.reel-it-video');
                if (existingVideo && !existingVideo.paused) {
                    existingVideo.pause();
                    return;
                }

                /* Create video on demand and play */
                const video = createVideo(vc);
                if (video) {
                    this.playVideo(video);
                    this._bindVideoEvents(vc, video);
                }
            });

            /* 2. Navigation */
            if (this.prevBtn) {
                this._on(this.prevBtn, 'click', (e) => {
                    e.preventDefault();
                    this.step(-1);
                });
            }
            if (this.nextBtn) {
                this._on(this.nextBtn, 'click', (e) => {
                    e.preventDefault();
                    this.step(1);
                });
            }

            this.thumbnailBtns.forEach((btn) => {
                this._on(btn, 'click', (e) => {
                    e.preventDefault();
                    const index = parseInt(btn.dataset.slide, 10);
                    this.navToSlide(index);
                });
            });

            /* 3. Scroll listener */
            this._on(this.slider, 'scroll', () => {
                if (!this.isScrolling) {
                    if (this.scrollTimeout) clearTimeout(this.scrollTimeout);
                    this.scrollTimeout = setTimeout(() => {
                        this.checkActiveSlide();
                    }, 150);
                }
            });

            /* 4. Product card click tracking */
            this._on(this.container, 'click', (e) => {
                const card = e.target.closest('.reel-it-product-card');
                if (!card) return;

                e.stopPropagation();

                const slide = card.closest('.reel-it-slide');
                const vc = slide ? slide.querySelector('.reel-it-video-container') : null;
                const videoId = vc ? vc.dataset.videoId : null;
                const productId = card.dataset.productId || 0;

                if (videoId) {
                    trackEvent('product_click', videoId, this.feedId, 0, productId);
                }
            });

            /* 5. Keyboard navigation */
            this._on(this.container, 'keydown', (e) => {
                if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.step(1);
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.step(-1);
                }
            });

            /* 6. Mute/unmute toggle */
            this._on(this.container, 'click', (e) => {
                const btn = e.target.closest('.reel-it-control-button');
                if (!btn) return;

                e.stopPropagation();
                const vc = btn.closest('.reel-it-video-container');
                const video = vc ? vc.querySelector('.reel-it-video') : null;
                if (!video) return;

                video.muted = !video.muted;
                vc.classList.toggle('muted', video.muted);
            });

            /* 7. Hover preload: prime browser cache on desktop so click-to-play feels instant */
            if (window.matchMedia('(hover: hover)').matches) {
                this.slides.forEach((slide) => {
                    this._on(slide, 'mouseenter', () => {
                        const vc = slide.querySelector('.reel-it-video-container');
                        if (!vc || vc.dataset.preloaded) return;
                        const src = vc.dataset.videoSrc;
                        if (!src) return;

                        const link = document.createElement('link');
                        link.rel = 'preload';
                        link.as = 'video';
                        link.href = src;
                        document.head.appendChild(link);
                        vc.dataset.preloaded = 'true';
                    });
                });
            }
        }

        /**
         * Attaches play/pause/ended listeners to a dynamically created video.
         * Why: videos are created on demand, so we can't bind at constructor time.
         *
         * @param {HTMLElement} vc - The video container element.
         * @param {HTMLVideoElement} video - The video element.
         */
        _bindVideoEvents(vc, video) {
            /* Avoid double-binding */
            if (video.dataset.eventsBound) return;
            video.dataset.eventsBound = 'true';

            const slideIndex = this._slideIndexForVc(vc);

            this._on(video, 'play', () => this.onVideoPlay(slideIndex));
            this._on(video, 'pause', () => this.onVideoPause(slideIndex));
            /* Why: capture currentTime in closure — the video element may be destroyed
               by IntersectionObserver before onVideoEnded fires */
            this._on(video, 'ended', () => {
                const watchTime = Math.round(video.currentTime);
                this.onVideoEnded(slideIndex, watchTime);
            });
        }

        /**
         * Finds the slide index for a given video container.
         *
         * @param {HTMLElement} vc
         * @return {number}
         */
        _slideIndexForVc(vc) {
            const slide = vc.closest('.reel-it-slide');
            return slide ? this.slides.indexOf(slide) : -1;
        }

        playVideo(video) {
            if (!video) return;

            /* Why: mute ensures play() succeeds without prior user gesture (autoplay policy) */
            video.muted = true;

            const playPromise = video.play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    const vc = video.closest('.reel-it-video-container');
                    vc.classList.add('playing', 'muted');

                    const controls = vc.querySelector('.reel-it-controls-container');
                    if (controls) controls.classList.add('visible');
                }).catch(() => {
                    const vc = video.closest('.reel-it-video-container');
                    vc.classList.remove('playing');
                    video.controls = true;
                });
            }
        }

        onVideoPlay(index) {
            if (index < 0) return;
            const vc = this._vc(index);
            const video = this._video(index);
            if (!vc || !video) return;

            const videoId = vc.dataset.videoId;

            vc.classList.add('playing');

            this.slides.forEach(function (s) { s.classList.remove('active'); });
            this.slides[index].classList.add('active');
            this.currentSlide = index;

            /* Track play event (once per video per session) */
            if (videoId && !this.trackedPlays.has(videoId)) {
                this.trackedPlays.add(videoId);
                trackEvent('play', videoId, this.feedId);
            }

            /* Pause & destroy other videos to save memory */
            this.slides.forEach((slide, i) => {
                if (i !== index) {
                    const otherVc = slide.querySelector('.reel-it-video-container');
                    if (otherVc) destroyVideo(otherVc);
                }
            });

            this.ensureVisible(index);
        }

        onVideoPause(index) {
            if (index < 0) return;
            const vc = this._vc(index);
            if (vc) vc.classList.remove('playing');
        }

        onVideoEnded(index, watchTime) {
            if (index < 0) return;
            const vc = this._vc(index);
            if (!vc) return;

            const videoId = vc.dataset.videoId;
            if (typeof watchTime !== 'number') watchTime = 0;

            if (videoId && !this.trackedCompletions.has(videoId)) {
                this.trackedCompletions.add(videoId);
                trackEvent('complete', videoId, this.feedId, watchTime);
            }

            /* Clean up finished video */
            destroyVideo(vc);

            if (this.sequentialAutoplay) {
                this.step(1, true);
            }
        }

        step(direction, autoPlay) {
            let nextIndex = this.currentSlide + direction;

            if (nextIndex >= this.totalSlides) nextIndex = 0;
            if (nextIndex < 0) nextIndex = this.totalSlides - 1;

            this.navToSlide(nextIndex, autoPlay);
        }

        navToSlide(index, autoPlay) {
            this.currentSlide = index;
            this.ensureVisible(index);

            if (autoPlay) {
                const vc = this._vc(index);
                if (vc) {
                    const video = createVideo(vc);
                    if (video) {
                        this.playVideo(video);
                        this._bindVideoEvents(vc, video);
                    }
                }
            }
        }

        ensureVisible(index) {
            const slide = this.slides[index];
            if (!slide) return;

            const sliderEl = this.slider;
            const slideLeft = slide.offsetLeft;
            const slideWidth = slide.offsetWidth;
            const scrollLeft = sliderEl.scrollLeft;
            const containerWidth = sliderEl.clientWidth;

            const isFullyVisible = (slideLeft >= scrollLeft) &&
                (slideLeft + slideWidth <= scrollLeft + containerWidth);

            if (!isFullyVisible) {
                this.isScrolling = true;
                const targetScroll = slideLeft - (containerWidth / 2) + (slideWidth / 2);

                sliderEl.scrollTo({
                    left: targetScroll,
                    behavior: 'smooth'
                });

                /* Why: 400ms matches the previous jQuery animate duration */
                setTimeout(() => { this.isScrolling = false; }, 400);
            }
        }

        /**
         * Updates visual active-slide state based on scroll position.
         * Only runs when no video is currently playing.
         */
        checkActiveSlide() {
            const sliderEl = this.slider;
            const center = sliderEl.scrollLeft + (sliderEl.clientWidth / 2);

            let bestIndex = this.currentSlide;
            let minDiff = Infinity;

            this.slides.forEach(function (slide, i) {
                const slideCenter = slide.offsetLeft + (slide.offsetWidth / 2);
                const diff = Math.abs(center - slideCenter);
                if (diff < minDiff) {
                    minDiff = diff;
                    bestIndex = i;
                }
            });

            /* Check if any dynamically-created video is playing */
            const anyPlaying = this.slides.some(function (slide) {
                const v = slide.querySelector('video.reel-it-video');
                return v && !v.paused;
            });

            if (!anyPlaying && bestIndex !== this.currentSlide) {
                this.currentSlide = bestIndex;
                this.slides.forEach(function (s) { s.classList.remove('active'); });
                this.slides[bestIndex].classList.add('active');
            }
        }

        destroy() {
            /* Destroy any active videos */
            this.slides.forEach(function (slide) {
                const vc = slide.querySelector('.reel-it-video-container');
                if (vc) destroyVideo(vc);
            });

            this._boundHandlers.forEach(function (h) {
                h.el.removeEventListener(h.event, h.handler, h.options);
            });
            this._boundHandlers = [];
        }
    }

    /* Why: module-scoped so both init and beforeunload can access it */
    var viewportObs = null;

    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            const sliders = [];

            function initSliders(context) {
                context.querySelectorAll('.reel-it-container').forEach(function (el) {
                    /* Why: check for slides (not videos) since we no longer render <video> in initial DOM */
                    if (!el.dataset.reelItInitialized && el.querySelectorAll('.reel-it-slide').length > 0) {
                        const slider = new VideoSlider(el);
                        sliders.push(slider);
                        el.dataset.reelItInitialized = 'true';
                    }
                });
            }

            initSliders(document);

            window.addEventListener('beforeunload', function () {
                sliders.forEach(function (s) { s.destroy(); });
                if (viewportObs) viewportObs.disconnect();
            });

        }, 100);

        /* Pause & destroy videos when their container scrolls out of viewport */
        if ('IntersectionObserver' in window) {
            viewportObs = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    const vc = entry.target;
                    if (!entry.isIntersecting) {
                        /* Why: guard against destroying freshly-autoplayed videos before
                           the user sees them (BUG-01 race condition) */
                        const video = vc.querySelector('video.reel-it-video');
                        if (video && video.currentTime < 1) return;
                        destroyVideo(vc);
                    }
                });
            }, { threshold: 0.1 });

            /* Observe all video containers (not videos, since they don't exist yet) */
            document.querySelectorAll('.reel-it-video-container').forEach(function (vc) {
                viewportObs.observe(vc);
            });
        }

        /* Mark server-provided poster images as loaded so shimmer stops */
        document.querySelectorAll('.reel-it-poster').forEach(function (img) {
            if (img.dataset.needsPoster) return; /* Skip — will be handled by generatePosters */
            if (img.complete && img.naturalWidth > 0) {
                img.closest('.reel-it-video-container').classList.add('has-poster');
            } else {
                img.addEventListener('load', function () {
                    img.closest('.reel-it-video-container').classList.add('has-poster');
                });
            }
        });

        /* Why: requestIdleCallback defers canvas work to browser idle time,
           reducing TBT during initial render. Fallback to setTimeout for Safari. */
        if ('requestIdleCallback' in window) {
            requestIdleCallback(generatePosters, { timeout: 2000 });
        } else {
            setTimeout(generatePosters, 300);
        }
    });

    /**
     * Generate poster frames client-side for slides missing a server-provided thumbnail.
     * Why: Most shared hosts lack FFmpeg so wp_get_attachment_image_url returns false
     * for video attachments, leaving the poster <img> with an empty src.
     * This creates a lightweight snapshot via an offscreen video + canvas.
     */
    function generatePosters() {
        var posters = document.querySelectorAll('.reel-it-poster[data-needs-poster]');
        var queue = [];
        var active = 0;
        var MAX_CONCURRENT = 2;

        posters.forEach(function (img) {
            var vc = img.closest('.reel-it-video-container');
            if (!vc) return;
            var videoSrc = vc.dataset.videoSrc;
            if (!videoSrc) return;
            queue.push({ img: img, src: videoSrc, vc: vc });
        });

        /** Process next item in the queue, respecting concurrency limit. */
        function processNext() {
            if (queue.length === 0 || active >= MAX_CONCURRENT) return;
            active++;
            var item = queue.shift();
            createPoster(item.img, item.src, item.vc);
        }

        /**
         * Create an offscreen video, seek to ~25% of duration, paint to canvas,
         * set the poster <img> src to the data URL.
         * Why 25%: skips black leader frames and intro fades common in encoded videos.
         */
        function createPoster(targetImg, src, vc) {
            var tmpVideo = document.createElement('video');
            tmpVideo.preload = 'auto';
            tmpVideo.muted = true;
            tmpVideo.playsInline = true;
            tmpVideo.style.cssText = 'position:absolute;width:0;height:0;opacity:0;pointer-events:none';

            var cleaned = false;
            var timeoutId = setTimeout(function () { cleanup(); }, 10000);

            function cleanup() {
                if (cleaned) return;
                cleaned = true;
                clearTimeout(timeoutId);
                tmpVideo.removeAttribute('src');
                tmpVideo.load();
                if (tmpVideo.parentNode) tmpVideo.parentNode.removeChild(tmpVideo);
                active--;
                processNext();
            }

            function paintPoster() {
                try {
                    var w = tmpVideo.videoWidth || 360;
                    var h = tmpVideo.videoHeight || 640;
                    var canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    var ctx = canvas.getContext('2d');
                    ctx.drawImage(tmpVideo, 0, 0, w, h);

                    /* Why: detect fully black frames and skip them */
                    var sample = ctx.getImageData(
                        Math.floor(w * 0.25), Math.floor(h * 0.25), 1, 1
                    ).data;
                    var isBlack = (sample[0] + sample[1] + sample[2]) < 30;

                    if (!isBlack) {
                        var dataUrl = canvas.toDataURL('image/jpeg', 0.7);
                        targetImg.src = dataUrl;
                    }
                    targetImg.removeAttribute('data-needs-poster');
                    vc.classList.add('has-poster');
                } catch (e) {
                    /* Why: tainted canvas or missing frame — just stop the shimmer */
                    targetImg.removeAttribute('data-needs-poster');
                    vc.classList.add('has-poster');
                }
                cleanup();
            }

            /* Why: loadedmetadata guarantees duration is set; canplay may fire
               before duration is populated on mobile Safari (BUG-02) */
            tmpVideo.addEventListener('loadedmetadata', function () {
                var seekTo = Math.min(tmpVideo.duration * 0.25, 2);
                if (!isFinite(seekTo) || seekTo <= 0 || seekTo >= tmpVideo.duration) seekTo = 0.5;
                tmpVideo.currentTime = seekTo;
            });

            tmpVideo.addEventListener('seeked', paintPoster);
            tmpVideo.addEventListener('error', function () { cleanup(); });

            document.body.appendChild(tmpVideo);
            tmpVideo.src = src;
        }

        /* Kick off initial batch */
        for (var i = 0; i < MAX_CONCURRENT; i++) {
            processNext();
        }
    }

})();