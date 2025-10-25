(function($) {
    'use strict';

    // Video Slider Class
    class VideoSlider {
        constructor(element) {
            this.container = $(element);
            this.slider = this.container.find('.wp-vids-reel-slider');
            this.slides = this.container.find('.wp-vids-reel-slide');
            this.videos = this.container.find('.wp-vids-reel-video');
            this.navigation = this.container.find('.wp-vids-reel-navigation');
            this.prevBtn = this.container.find('.wp-vids-reel-prev');
            this.nextBtn = this.container.find('.wp-vids-reel-next');
            this.thumbnails = this.container.find('.wp-vids-reel-thumbnails');
            this.thumbnailBtns = this.container.find('.wp-vids-reel-thumbnail');
            
            this.currentSlide = 0;
            this.totalSlides = this.slides.length;
            this.sliderSpeed = parseInt(this.container.data('slider-speed')) || 5000;
            this.autoplayTimer = null;
            this.isTransitioning = false;
            this.slideWidth = 0;
            this.isScrolling = false;
            
            this.init();
        }
        
        init() {
            if (this.totalSlides <= 1) {
                return; // No need for slider functionality with only one slide
            }
            
            // Set up smooth scrolling
            this.setupSmoothScrolling();
            
            // Calculate slide width after DOM is ready
            $(window).on('load resize', () => {
                this.calculateSlideWidth();
            });
            
            this.bindEvents();
            
            // Autoplay is disabled for horizontal layout since all videos are visible
            // this.startAutoplay();
            
            // Pause on hover - not needed for horizontal layout
            // this.container.on('mouseenter', () => this.pauseAutoplay());
            // this.container.on('mouseleave', () => this.startAutoplay());
            
            // Handle video events
            this.videos.on('play', () => this.pauseAutoplay());
            // Remove video ended event to prevent automatic sliding
            
            // Initialize keyboard navigation
            this.initKeyboardNavigation();
            
            // Initialize touch/swipe support
            this.initTouchSupport();
        }
        
        setupSmoothScrolling() {
            // Add smooth scrolling behavior
            this.slider.css({
                'scroll-behavior': 'smooth',
                'scroll-snap-type': 'x mandatory'
            });
            
            // Add scroll snap to slides
            this.slides.css({
                'scroll-snap-align': 'start'
            });
        }
        
        calculateSlideWidth() {
            // Calculate the width of each slide including margins
            if (this.slides.length > 0) {
                const firstSlide = this.slides.first();
                this.slideWidth = firstSlide.outerWidth(true);
            }
        }
        
        bindEvents() {
            this.prevBtn.on('click', (e) => {
                e.preventDefault();
                this.prevSlide();
            });
            
            this.nextBtn.on('click', (e) => {
                e.preventDefault();
                this.nextSlide();
            });
            
            this.thumbnailBtns.on('click', (e) => {
                e.preventDefault();
                const slideIndex = $(e.currentTarget).data('slide');
                this.goToSlide(slideIndex);
            });
        }
        
        goToSlide(index) {
            if (this.isScrolling || index === this.currentSlide) {
                return;
            }
            
            this.isScrolling = true;
            
            // Calculate scroll position
            const scrollPosition = index * this.slideWidth;
            
            // Scroll to the slide
            this.slider.animate({
                scrollLeft: scrollPosition
            }, 500, () => {
                this.isScrolling = false;
                this.currentSlide = index;
                
                // Update thumbnails
                this.thumbnailBtns.removeClass('active');
                this.thumbnailBtns.eq(index).addClass('active');
            });
        }
        
        nextSlide() {
            const nextIndex = Math.min(this.currentSlide + 1, this.totalSlides - 1);
            this.goToSlide(nextIndex);
        }
        
        prevSlide() {
            const prevIndex = Math.max(this.currentSlide - 1, 0);
            this.goToSlide(prevIndex);
        }
        
        startAutoplay() {
            // Autoplay is disabled for horizontal layout since all videos are visible
            // This method is kept for compatibility but does nothing
            return;
        }
        
        pauseAutoplay() {
            // Autoplay is disabled for horizontal layout since all videos are visible
            // This method is kept for compatibility but does nothing
            if (this.autoplayTimer) {
                clearTimeout(this.autoplayTimer);
                this.autoplayTimer = null;
            }
        }
        
        initKeyboardNavigation() {
            $(document).on('keydown', (e) => {
                // Only handle keys when this slider is in focus or visible
                if (!$.contains(this.container[0], document.activeElement) &&
                    !this.container.is(':visible')) {
                    return;
                }
                
                switch (e.keyCode) {
                    case 37: // Left arrow
                        e.preventDefault();
                        this.prevSlide();
                        break;
                    case 39: // Right arrow
                        e.preventDefault();
                        this.nextSlide();
                        break;
                    case 32: // Space
                        e.preventDefault();
                        // Find the currently visible video in the viewport
                        const sliderRect = this.slider[0].getBoundingClientRect();
                        const visibleSlide = this.slides.filter(function() {
                            const slideRect = this.getBoundingClientRect();
                            return !(slideRect.right < sliderRect.left || slideRect.left > sliderRect.right);
                        }).first();
                        
                        if (visibleSlide.length) {
                            const currentVideo = visibleSlide.find('.wp-vids-reel-video')[0];
                            if (currentVideo) {
                                if (currentVideo.paused) {
                                    currentVideo.play();
                                } else {
                                    currentVideo.pause();
                                }
                            }
                        }
                        break;
                }
            });
        }
        
        initTouchSupport() {
            let touchStartX = 0;
            let touchEndX = 0;
            
            this.slider.on('touchstart', (e) => {
                touchStartX = e.originalEvent.touches[0].clientX;
            });
            
            this.slider.on('touchend', (e) => {
                touchEndX = e.originalEvent.changedTouches[0].clientX;
                this.handleSwipe();
            });
            
            const handleSwipe = () => {
                const swipeThreshold = 50;
                const diff = touchStartX - touchEndX;
                
                if (Math.abs(diff) > swipeThreshold) {
                    if (diff > 0) {
                        this.nextSlide(); // Swipe left
                    } else {
                        this.prevSlide(); // Swipe right
                    }
                }
            };
            
            this.handleSwipe = handleSwipe;
        }
        
        destroy() {
            this.pauseAutoplay();
            this.prevBtn.off('click');
            this.nextBtn.off('click');
            this.thumbnailBtns.off('click');
            this.videos.off('play');
            $(document).off('keydown');
            $(window).off('load resize');
            this.slider.off('touchstart touchend');
        }
    }
    
    // Initialize sliders when DOM is ready
    $(document).ready(function() {
        const sliders = [];
        
        $('.wp-vids-reel-container').each(function() {
            sliders.push(new VideoSlider(this));
        });
        
        // Clean up sliders when page is unloaded
        $(window).on('beforeunload', function() {
            sliders.forEach(slider => slider.destroy());
        });
        
        // Reinitialize sliders when new content is loaded (for dynamic content)
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                        $(mutation.addedNodes).find('.wp-vids-reel-container').each(function() {
                            if (!$(this).data('wp-vids-reel-initialized')) {
                                sliders.push(new VideoSlider(this));
                                $(this).data('wp-vids-reel-initialized', true);
                            }
                        });
                    }
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    });
    
    // Handle lazy loading for videos
    function initLazyLoading() {
        const videoElements = $('.wp-vids-reel-video[data-src]');
        
        if ('IntersectionObserver' in window) {
            const videoObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const video = $(entry.target);
                        const src = video.data('src');
                        
                        if (src) {
                            video.attr('src', src);
                            video.removeAttr('data-src');
                            videoObserver.unobserve(entry.target);
                        }
                    }
                });
            }, {
                rootMargin: '50px'
            });
            
            videoElements.each(function() {
                videoObserver.observe(this);
            });
        } else {
            // Fallback for browsers that don't support IntersectionObserver
            videoElements.each(function() {
                const video = $(this);
                const src = video.data('src');
                
                if (src) {
                    video.attr('src', src);
                    video.removeAttr('data-src');
                }
            });
        }
    }
    
    // Initialize lazy loading
    $(document).ready(initLazyLoading);
    
})(jQuery);