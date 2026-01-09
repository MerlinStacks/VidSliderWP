(function ($) {
    'use strict';

    // Analytics helper - generates anonymous session ID
    function getSessionId() {
        let sid = localStorage.getItem('reel_it_sid');
        if (!sid) {
            sid = 'ri_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('reel_it_sid', sid);
        }
        return sid;
    }

    // Track analytics event
    function trackEvent(eventType, videoId, feedId, watchTime, productId) {
        if (typeof reel_it_public === 'undefined') return;

        $.post(reel_it_public.ajax_url, {
            action: 'reel_it_track_event',
            event_type: eventType,
            video_id: videoId,
            feed_id: feedId || 0,
            watch_time: watchTime || 0,
            product_id: productId || 0,
            session_id: getSessionId()
        });
    }

    class VideoSlider {
        constructor(element) {
            this.container = $(element);
            this.slider = this.container.find('.reel-it-slider');
            this.feedId = this.container.data('feed-id') || 0;
            // Selectors
            this.slides = this.container.find('.reel-it-slide');
            this.videos = this.container.find('.reel-it-video');
            this.prevBtn = this.container.find('.reel-it-prev');
            this.nextBtn = this.container.find('.reel-it-next');
            this.thumbnailBtns = this.container.find('.reel-it-thumbnail');

            // State
            this.currentSlide = 0;
            this.totalSlides = this.videos.length;
            this.isScrolling = false;
            this.sequentialAutoplay = true;
            this.scrollTimeout = null;
            this.trackedPlays = new Set(); // Prevent duplicate play tracking per session
            this.trackedCompletions = new Set();

            if (this.totalSlides === 0) return;

            this.init();
        }

        init() {
            // Set initial active state
            this.slides.removeClass('active');

            // Find which video is playing or should be active (e.g. if one has autoplay attribute)
            let activeIndex = 0;
            this.videos.each((i, el) => {
                if (!el.paused && !el.ended) {
                    activeIndex = i;
                }
            });

            this.slides.eq(activeIndex).addClass('active');
            this.currentSlide = activeIndex;

            // Remove loading state
            this.container.removeClass('loading');

            this.bindEvents();

            // Check for initial autoplay on the first video
            const firstVideo = this.videos[0];
            if (firstVideo && !firstVideo.paused) {
                $(firstVideo).closest('.reel-it-video-container').addClass('playing');
            }
        }

        bindEvents() {
            // 1. Click Handling (Delegate)
            // We handle clicks on the custom overlay or the play button.
            this.container.on('click', '.reel-it-video-overlay, .reel-it-play-button', (e) => {
                e.stopPropagation();
                e.preventDefault(); // Prevent standard click actions on buttons

                const $container = $(e.currentTarget).closest('.reel-it-video-container');
                const video = $container.find('video')[0];

                if (video) {
                    if (video.paused) {
                        this.playVideo(video);
                    } else {
                        video.pause();
                    }
                }
            });

            // 2. Navigation
            this.prevBtn.on('click', (e) => {
                e.preventDefault();
                this.step(-1);
            });

            this.nextBtn.on('click', (e) => {
                e.preventDefault();
                this.step(1);
            });

            this.thumbnailBtns.on('click', (e) => {
                e.preventDefault();
                const index = $(e.currentTarget).data('slide');
                this.navToSlide(index);
            });

            // 3. Video Events (Direct binding)
            // We bind to each video element found.
            this.videos.each((index, video) => {
                $(video).on('play', () => this.onVideoPlay(index));
                $(video).on('pause', () => this.onVideoPause(index));
                $(video).on('ended', () => this.onVideoEnded(index));
            });

            // 4. Scroll Listener
            this.slider.on('scroll', () => {
                if (!this.isScrolling) {
                    if (this.scrollTimeout) clearTimeout(this.scrollTimeout);
                    this.scrollTimeout = setTimeout(() => {
                        this.checkActiveSlide();
                    }, 150);
                }
            });

            // 5. Handle video clicks directly (for when overlay is hidden)
            this.container.on('click', '.reel-it-video', (e) => {
                // Intentionally empty logic here unless we force custom toggle.
            });

            // 6. Handle product card clicks - track and allow link to work
            this.container.on('click', '.reel-it-product-card', (e) => {
                e.stopPropagation();

                // Track product click
                const $card = $(e.currentTarget);
                const $video = $card.closest('.reel-it-slide').find('.reel-it-video');
                const videoId = $video.data('video-id');

                // Extract product ID from href (WooCommerce product pages have predictable URLs)
                // or from data attribute if available
                const href = $card.attr('href');
                const productId = $card.data('product-id') || 0;

                if (videoId) {
                    trackEvent('product_click', videoId, this.feedId, 0, productId);
                }
            });
        }

        playVideo(video) {
            if (!video) return;

            // Always mute to ensure play() works without user gesture (autoplay policy)
            video.muted = true;

            const playPromise = video.play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    // Playback started successfully
                    const $container = $(video).closest('.reel-it-video-container');
                    $container.addClass('playing');

                    // Force a repaint to fix rendering glitches (black screen/loading spinner stuck)
                    // This toggles display which forces the layout engine to re-calculate the element
                    video.style.display = 'none';
                    video.offsetHeight; // Force reflow
                    video.style.display = 'block';

                }).catch(() => {
                    // UI should reflect paused state
                    const $container = $(video).closest('.reel-it-video-container');
                    $container.removeClass('playing');
                    // Fallback using controls if autoplay fails
                    video.controls = true;
                });
            }
        }

        onVideoPlay(index) {
            // 1. Update UI classes
            const video = this.videos[index];
            const $container = $(video).closest('.reel-it-video-container');
            const videoId = $(video).data('video-id');

            $container.addClass('playing');
            this.slides.removeClass('active').eq(index).addClass('active');

            this.currentSlide = index;

            // Track play event (once per video per session)
            if (videoId && !this.trackedPlays.has(videoId)) {
                this.trackedPlays.add(videoId);
                trackEvent('play', videoId, this.feedId);
            }

            // 2. Pause other videos
            this.videos.each((i, otherVideo) => {
                if (i !== index && !otherVideo.paused) {
                    otherVideo.pause();
                    $(otherVideo).closest('.reel-it-video-container').removeClass('playing');
                }
            });

            // 3. Scroll into view
            this.ensureVisible(index);
        }

        onVideoPause(index) {
            const video = this.videos[index];
            $(video).closest('.reel-it-video-container').removeClass('playing');
        }

        onVideoEnded(index) {
            // Track completion
            const video = this.videos[index];
            const videoId = $(video).data('video-id');
            const watchTime = Math.round(video.currentTime);

            if (videoId && !this.trackedCompletions.has(videoId)) {
                this.trackedCompletions.add(videoId);
                trackEvent('complete', videoId, this.feedId, watchTime);
            }

            if (this.sequentialAutoplay) {
                this.step(1, true); // Go to next slide and Autoplay
            }
        }

        step(direction, autoPlay = false) {
            let nextIndex = this.currentSlide + direction;

            // Loop
            if (nextIndex >= this.totalSlides) nextIndex = 0;
            if (nextIndex < 0) nextIndex = this.totalSlides - 1;

            this.navToSlide(nextIndex, autoPlay);
        }

        navToSlide(index, autoPlay = false) {
            this.currentSlide = index;
            this.ensureVisible(index);

            if (autoPlay) {
                const video = this.videos[index];
                if (video) {
                    this.playVideo(video);
                }
            }
        }

        ensureVisible(index) {
            const slide = this.slides[index];
            if (!slide) return;

            const sliderEl = this.slider[0];
            const slideEl = slide; // DOM element

            const slideLeft = slideEl.offsetLeft;
            const slideWidth = slideEl.offsetWidth;
            const scrollLeft = sliderEl.scrollLeft;
            const containerWidth = sliderEl.clientWidth;

            // Check if fully visible
            const isFullyVisible = (slideLeft >= scrollLeft) &&
                (slideLeft + slideWidth <= scrollLeft + containerWidth);

            if (!isFullyVisible) {
                this.isScrolling = true;

                // Center the item
                const targetScroll = slideLeft - (containerWidth / 2) + (slideWidth / 2);

                this.slider.animate({
                    scrollLeft: targetScroll
                }, 400, () => {
                    this.isScrolling = false;
                });
            }
        }

        checkActiveSlide() {
            // Visual active state update based on scroll position - purely cosmetic
            const sliderEl = this.slider[0];
            const center = sliderEl.scrollLeft + (sliderEl.clientWidth / 2);

            let bestIndex = this.currentSlide;
            let minDiff = Infinity;

            this.slides.each((i, slide) => {
                const slideCenter = slide.offsetLeft + (slide.offsetWidth / 2);
                const diff = Math.abs(center - slideCenter);
                if (diff < minDiff) {
                    minDiff = diff;
                    bestIndex = i;
                }
            });

            // Only switch visual active if nothing is playing
            let anyPlaying = false;
            this.videos.each((i, v) => { if (!v.paused) anyPlaying = true; });

            if (!anyPlaying && bestIndex !== this.currentSlide) {
                // updates can go here if desired
            }
        }

        destroy() {
            this.container.off();
            this.prevBtn.off();
            this.nextBtn.off();
            this.thumbnailBtns.off();
            this.videos.off();
            this.slider.off();
        }
    }

    $(document).ready(function () {
        setTimeout(function () {
            const sliders = [];

            function initSliders(context) {
                $(context).find('.reel-it-container').each(function () {
                    const $container = $(this);
                    if (!$container.data('reel-it-initialized') && $container.find('.reel-it-video').length > 0) {
                        const slider = new VideoSlider(this);
                        sliders.push(slider);
                        $container.data('reel-it-initialized', true);
                    }
                });
            }

            // Initial init
            initSliders(document);

            // Mutation Observer for dynamic content
            if (window.MutationObserver) {
                const observer = new MutationObserver(function (mutations) {
                    let shouldInit = false;
                    mutations.forEach(function (mutation) {
                        if (mutation.addedNodes.length > 0) {
                            shouldInit = true; // optimization
                        }
                    });
                    if (shouldInit) initSliders(document.body);
                });
                observer.observe(document.body, { childList: true, subtree: true });
            }

            $(window).on('beforeunload', function () {
                sliders.forEach(s => s.destroy());
            });

        }, 100);

        // Lazy load videos and pause when out of viewport
        const observerOptions = { rootMargin: '50px 0px', threshold: 0.5 };

        if ('IntersectionObserver' in window) {
            // Viewport visibility observer - pause videos when scrolled out of view
            const viewportObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const video = entry.target;
                    if (!entry.isIntersecting) {
                        // Video left viewport - pause if playing
                        if (!video.paused) {
                            video.pause();
                            video.dataset.wasPlaying = 'true';
                        }
                    } else {
                        // Video entered viewport - resume if it was playing
                        if (video.dataset.wasPlaying === 'true') {
                            video.play().catch(() => { });
                            delete video.dataset.wasPlaying;
                        }
                    }
                });
            }, { threshold: 0.5 });

            // Lazy source loader for data-src pattern
            const lazyObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const video = entry.target;
                        const src = video.getAttribute('data-src');
                        if (src) {
                            video.src = src;
                            video.removeAttribute('data-src');
                        }
                        observer.unobserve(video);
                    }
                });
            }, observerOptions);

            // Observe all videos
            document.querySelectorAll('.reel-it-video').forEach(function (video) {
                viewportObserver.observe(video);
                if (video.hasAttribute('data-src')) {
                    lazyObserver.observe(video);
                }
            });
        }
    });

})(jQuery);