(function (blocks, element, components, blockEditor, data, i18n) {
    const { __ } = i18n;
    const { registerBlockType } = blocks;
    const { InspectorControls, MediaUpload, MediaUploadCheck } = blockEditor;
    const { PanelBody, Button, TextControl, RangeControl, ToggleControl, SelectControl, Spinner, Notice, SearchControl, Placeholder, ButtonGroup } = components;
    const { createElement: el, Fragment } = element;
    const { useSelect } = data;

    // Helper: Viewport Control (Buttons + Half Toggle)
    const ViewportControl = ({ label, value, onChange, max = 6 }) => {
        const baseValue = Math.floor(value) || 1;
        const hasHalf = (value % 1) !== 0;

        const handleBaseChange = (newBase) => {
            onChange(newBase + (hasHalf ? 0.5 : 0));
        };

        const handleHalfToggle = (isHalf) => {
            onChange(baseValue + (isHalf ? 0.5 : 0));
        };

        return el('div', { className: 'reel-it-viewport-control', style: { marginBottom: '24px' } },
            el('p', { className: 'components-base-control__label', style: { marginBottom: '8px' } }, label),
            el(ButtonGroup, { style: { marginBottom: '12px', display: 'flex', gap: '4px' } },
                Array.from({ length: max }, (_, i) => i + 1).map(num =>
                    el(Button, {
                        key: num,
                        isPrimary: baseValue === num,
                        isSecondary: baseValue !== num,
                        onClick: () => handleBaseChange(num),
                        style: { flex: 1, justifyContent: 'center' }
                    }, num)
                )
            ),
            el(ToggleControl, {
                label: __('Show partial video (+0.5)', 'reel-it'),
                checked: hasHalf,
                onChange: handleHalfToggle,
                help: hasHalf ? __('Showing ' + (baseValue + 0.5) + ' videos', 'reel-it') : __('Showing integers only', 'reel-it')
            })
        );
    };

    // Video Item Component
    const VideoItem = ({ video, onRemove, onEdit }) => {
        return el('div', { className: 'reel-it-video-item' },
            el('div', { className: 'reel-it-video-preview' },
                video.thumbnail ?
                    el('img', { src: video.thumbnail, alt: video.title }) :
                    el('div', { className: 'reel-it-video-placeholder' },
                        el('span', { className: 'dashicons dashicons-video-alt3' })
                    )
            ),
            el('div', { className: 'reel-it-video-info' },
                el('div', { className: 'reel-it-video-title', title: video.title }, video.title),
                el('div', { className: 'reel-it-video-mime' }, video.mime.replace('video/', ''))
            ),
            el('div', { className: 'reel-it-video-actions' },
                el(Button, {
                    isSmall: true,
                    isDestructive: true,
                    onClick: onRemove,
                    icon: 'trash',
                    label: __('Remove video', 'reel-it')
                })
            )
        );
    };

    // Video Uploader Component
    const VideoUploader = ({ onUpload, isLoading }) => {
        const fileInputRef = React.useRef(null);

        const handleFileSelect = (event) => {
            const file = event.target.files[0];
            if (file) {
                onUpload(file);
            }
        };

        return el('div', { className: 'reel-it-video-uploader' },
            el('input', {
                type: 'file',
                ref: fileInputRef,
                accept: 'video/*',
                onChange: handleFileSelect,
                style: { display: 'none' }
            }),
            el(Button, {
                isPrimary: true,
                onClick: () => fileInputRef.current.click(),
                disabled: isLoading,
                icon: isLoading ? 'spinner' : 'upload',
                className: 'reel-it-upload-button'
            }, isLoading ? __('Uploading...', 'reel-it') : __('Upload Video', 'reel-it'))
        );
    };

    // Feed Selector Component
    const FeedSelector = ({ feedId, onFeedChange }) => {
        const [feeds, setFeeds] = React.useState([]);
        const [loadingFeeds, setLoadingFeeds] = React.useState(false);

        // Load feeds
        const loadFeeds = React.useCallback(() => {
            setLoadingFeeds(true);

            const formData = new FormData();
            formData.append('action', 'reel_it_get_feeds');
            formData.append('nonce', reelItBlock.nonce);

            fetch(reelItBlock.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        setFeeds(data.data.feeds || []);
                    }
                })
                .catch(() => { })
                .finally(() => {
                    setLoadingFeeds(false);
                });
        }, []);

        // Load initial feeds
        React.useEffect(() => {
            loadFeeds();
        }, [loadFeeds]);

        // Handle feed selection
        const handleFeedChange = (feedId) => {
            onFeedChange(parseInt(feedId));
        };

        return el('div', { className: 'reel-it-feed-selector' },
            el('div', { className: 'reel-it-feed-selector-header' },
                el('label', {}, __('Select Video Feed', 'reel-it')),
                el('select', {
                    value: feedId,
                    onChange: (e) => handleFeedChange(e.target.value),
                    disabled: loadingFeeds
                },
                    loadingFeeds ?
                        el('option', { value: '', disabled: true }, __('Loading feeds...', 'reel-it')) :
                        [
                            el('option', { value: 0, key: 'default' }, __('Select a feed...', 'reel-it')),
                            ...feeds.map(feed =>
                                el('option', {
                                    value: feed.id,
                                    key: feed.id
                                }, feed.name)
                            )
                        ]
                ),
                el(Button, {
                    isSmall: true,
                    isTertiary: true,
                    icon: 'update',
                    onClick: () => loadFeeds(),
                    disabled: loadingFeeds,
                    label: __('Refresh Feeds', 'reel-it')
                })
            )
        );
    };

    // Video Selector Component
    const VideoSelector = ({ videos, onSelect, onUpload, isLoading }) => {
        const [search, setSearch] = React.useState('');
        const [videoLibrary, setVideoLibrary] = React.useState([]);
        const [loadingLibrary, setLoadingLibrary] = React.useState(false);
        const [currentPage, setCurrentPage] = React.useState(1);

        // Load video library
        const loadVideoLibrary = React.useCallback((searchTerm = '', page = 1) => {
            setLoadingLibrary(true);

            const formData = new FormData();
            formData.append('action', 'reel_it_query_videos');
            formData.append('nonce', reelItBlock.nonce);
            formData.append('search', searchTerm);
            formData.append('page', page);

            fetch(reelItBlock.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (page === 1) {
                            setVideoLibrary(data.data.videos);
                        } else {
                            setVideoLibrary(prev => [...prev, ...data.data.videos]);
                        }
                    }
                })
                .catch(() => { })
                .finally(() => {
                    setLoadingLibrary(false);
                });
        }, []);

        // Load initial videos
        React.useEffect(() => {
            loadVideoLibrary();
        }, [loadVideoLibrary]);

        // Handle search
        const handleSearch = (value) => {
            setSearch(value);
            setCurrentPage(1);
            loadVideoLibrary(value, 1);
        };

        // Handle video selection from library
        const handleVideoSelect = (video) => {
            if (!videos.find(v => v.id === video.id)) {
                onSelect([...videos, video]);
            }
        };

        return el('div', { className: 'reel-it-video-selector' },
            // Search bar
            el('div', { className: 'reel-it-search' },
                el(SearchControl, {
                    label: __('Search videos', 'reel-it'),
                    value: search,
                    onChange: handleSearch,
                    placeholder: __('Search by title...', 'reel-it')
                })
            ),

            // Upload button
            el(VideoUploader, { onUpload, isLoading }),

            // Video library
            el('div', { className: 'reel-it-video-library' },
                loadingLibrary ?
                    el('div', { className: 'reel-it-loading' }, el(Spinner)) :
                    videoLibrary.length === 0 ?
                        el(Notice, { status: 'info', isDismissible: false },
                            __('No videos found. Try uploading one.', 'reel-it')
                        ) :
                        el('div', { className: 'reel-it-video-grid' },
                            videoLibrary.map(video =>
                                el('div', {
                                    key: video.id,
                                    className: 'reel-it-library-item'
                                },
                                    el('div', { className: 'reel-it-library-preview' },
                                        video.thumbnail ?
                                            el('img', { src: video.thumbnail, alt: video.title }) :
                                            el('div', { className: 'reel-it-video-placeholder' },
                                                el('span', { className: 'dashicons dashicons-video-alt3' })
                                            )
                                    ),
                                    el('div', { className: 'reel-it-library-info' },
                                        el('div', { className: 'reel-it-library-title', title: video.title }, video.title)
                                    ),
                                    el(Button, {
                                        isSmall: true,
                                        variant: 'secondary',
                                        onClick: () => handleVideoSelect(video),
                                        disabled: videos.find(v => v.id === video.id)
                                    }, videos.find(v => v.id === video.id) ? __('Added', 'reel-it') : __('Add', 'reel-it'))
                                )
                            )
                        )
            )
        );
    };

    // Feed Preview Component (New)
    const FeedPreview = ({ feedId, videosPerRow }) => {
        const [posts, setPosts] = React.useState([]);
        const [loading, setLoading] = React.useState(false);
        const [error, setError] = React.useState('');
        const containerRef = React.useRef(null);

        React.useEffect(() => {
            if (!feedId) return;
            setLoading(true);
            setPosts([]);
            setError('');

            const formData = new FormData();
            formData.append('action', 'reel_it_get_feed_videos');
            formData.append('nonce', reelItBlock.nonce);
            formData.append('feed_id', feedId);

            fetch(reelItBlock.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        setPosts(data.data.videos || []);
                    } else {
                        setError(data.data.message || __('Failed to load feed', 'reel-it'));
                    }
                })
                .catch(err => setError(__('Network error', 'reel-it')))
                .finally(() => setLoading(false));

        }, [feedId]);

        const scroll = (direction) => {
            if (containerRef.current) {
                const scrollAmount = containerRef.current.clientWidth;
                containerRef.current.scrollBy({
                    left: direction * scrollAmount,
                    behavior: 'smooth'
                });
            }
        };

        if (loading) return el(Spinner);
        if (error) return el('div', { className: 'reel-it-error' }, error);
        if (posts.length === 0) return el('div', { className: 'reel-it-no-videos' }, __('No videos in this gallery.', 'reel-it'));

        // Slider Style mimicking frontend
        const sliderStyle = {
            display: 'flex',
            flexWrap: 'nowrap',
            overflowX: 'auto',
            overflowY: 'hidden',
            width: '100%',
            scrollBehavior: 'smooth',
            scrollSnapType: 'x mandatory',
            gap: '0',
            scrollbarWidth: 'none', // Hide scrollbar for cleaner look
            msOverflowStyle: 'none'
        };

        const vpr = parseFloat(videosPerRow) || 3;
        const itemWidth = `calc(100% / ${vpr})`;

        return el('div', { className: 'reel-it-preview-wrapper', style: { position: 'relative', marginTop: '15px' } },
            // Left Arrow
            el(Button, {
                icon: 'arrow-left-alt2',
                className: 'reel-it-preview-arrow-prev',
                onClick: () => scroll(-1),
                style: {
                    position: 'absolute',
                    left: '10px',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    zIndex: 10,
                    background: 'rgba(0,0,0,0.5)',
                    color: 'white',
                    borderRadius: '50%',
                    border: 'none',
                    width: '30px',
                    height: '30px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer'
                }
            }),
            // Right Arrow
            el(Button, {
                icon: 'arrow-right-alt2',
                className: 'reel-it-preview-arrow-next',
                onClick: () => scroll(1),
                style: {
                    position: 'absolute',
                    right: '10px',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    zIndex: 10,
                    background: 'rgba(0,0,0,0.5)',
                    color: 'white',
                    borderRadius: '50%',
                    border: 'none',
                    width: '30px',
                    height: '30px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer'
                }
            }),
            el('div', {
                className: 'reel-it-feed-slider-preview',
                style: sliderStyle,
                ref: containerRef
            },
                posts.map(video =>
                    el('div', {
                        key: video.video_id,
                        className: 'reel-it-preview-card',
                        style: {
                            flex: `0 0 ${itemWidth}`,
                            width: itemWidth,
                            padding: '0 7.5px',
                            boxSizing: 'border-box',
                            scrollSnapAlign: 'start'
                        }
                    },
                        el('div', {
                            className: 'reel-it-preview-thumb', style: {
                                backgroundColor: '#000',
                                width: '100%',
                                aspectRatio: '16/9',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                position: 'relative'
                            }
                        },
                            video.thumbnail ?
                                el('img', { src: video.thumbnail, style: { width: '100%', height: '100%', objectFit: 'cover' } }) :
                                el('span', { className: 'dashicons dashicons-video-alt3', style: { color: 'white', fontSize: '32px' } }),
                            // Add play button overlay simulation
                            el('div', {
                                style: {
                                    position: 'absolute',
                                    top: '50%',
                                    left: '50%',
                                    transform: 'translate(-50%, -50%)',
                                    width: '40px',
                                    height: '40px',
                                    borderRadius: '50%',
                                    border: '2px solid white',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center'
                                }
                            },
                                el('span', { className: 'dashicons dashicons-arrow-right-alt2', style: { color: 'white', fontSize: '24px' } })
                            )
                        ),
                        el('div', { style: { padding: '8px 0', fontSize: '12px', fontWeight: '500', textAlign: 'center' } }, video.post_title)
                    )
                )
            )
        );
    };

    // Register the block
    registerBlockType('reel-it/video-slider', {
        title: reelItBlock.strings.blockTitle,
        description: reelItBlock.strings.blockDescription,
        icon: 'format-video',
        category: 'media',
        keywords: [__('video', 'reel-it'), __('slider', 'reel-it'), __('reel', 'reel-it')],
        supports: {
            align: ['wide', 'full']
        },

        attributes: {
            videos: {
                type: 'array',
                default: []
            },
            feedId: {
                type: 'number',
                default: 0
            },
            videosPerRow: {
                type: 'number',
                default: reelItBlock.defaults.videosPerRow || 3
            },
            videosPerRowMobile: {
                type: 'number',
                default: 1.5
            },
            useFeed: {
                type: 'boolean',
                default: false
            },
            width: {
                type: 'string',
                default: ''
            },
            fullWidth: {
                type: 'boolean',
                default: false
            }
        },

        edit: function (props) {
            const { attributes, setAttributes, className } = props;
            const { videos, videosPerRow, videosPerRowMobile, useFeed, feedId, width, fullWidth } = attributes;
            const { useBlockProps } = blockEditor;

            const [isUploading, setIsUploading] = React.useState(false);
            const [uploadError, setUploadError] = React.useState('');

            // Parse width into value and unit
            const getWidthParts = (w) => {
                if (!w) return { value: '', unit: 'px' };
                const match = w.match(/^(\d*\.?\d+)(.*)$/);
                return match ? { value: match[1], unit: match[2] || 'px' } : { value: '', unit: 'px' };
            };

            const { value: widthVal, unit: widthUnit } = getWidthParts(width);

            const updateWidth = (newVal, newUnit) => {
                if (newVal === '' || newVal === undefined) {
                    setAttributes({ width: '' });
                } else {
                    setAttributes({ width: `${newVal}${newUnit}` });
                }
            };

            // Handle video upload
            const handleVideoUpload = (file) => {
                setIsUploading(true);
                setUploadError('');

                const formData = new FormData();
                formData.append('action', 'reel_it_upload_video');
                formData.append('nonce', reelItBlock.nonce);
                formData.append('video_file', file);

                fetch(reelItBlock.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            setAttributes({
                                videos: [...videos, data.data]
                            });
                        } else {
                            setUploadError(data.data.message || __('Upload failed', 'reel-it'));
                        }
                    })
                    .catch(() => {
                        setUploadError(__('Upload failed', 'reel-it'));
                    })
                    .finally(() => {
                        setIsUploading(false);
                    });
            };

            // Handle video removal
            const handleVideoRemove = (videoToRemove) => {
                setAttributes({
                    videos: videos.filter(video => video.id !== videoToRemove.id)
                });
            };

            // Handle video reorder
            const handleVideoReorder = (dragIndex, hoverIndex) => {
                const draggedVideo = videos[dragIndex];
                const newVideos = [...videos];
                newVideos.splice(dragIndex, 1);
                newVideos.splice(hoverIndex, 0, draggedVideo);
                setAttributes({ videos: newVideos });
            };

            const blockProps = useBlockProps({
                className: className,
                style: { width: fullWidth ? '100%' : (width || undefined) }
            });

            return el('div', blockProps,
                // Inspector Controls
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Layout Settings', 'reel-it'), initialOpen: true },
                        // Width Controls
                        el('div', { style: { marginBottom: '24px' } },
                            el(ToggleControl, {
                                label: __('Full Width Container', 'reel-it'),
                                checked: fullWidth,
                                onChange: (val) => setAttributes({ fullWidth: val }),
                                help: __('Force container to be 100% width.', 'reel-it')
                            }),

                            !fullWidth && el('div', { className: 'components-base-control' },
                                el('label', { className: 'components-base-control__label', style: { marginBottom: '8px', display: 'block' } }, __('Custom Width', 'reel-it')),
                                el('div', { style: { display: 'flex', gap: '10px' } },
                                    el(TextControl, {
                                        value: widthVal,
                                        type: 'number',
                                        onChange: (v) => updateWidth(v, widthUnit),
                                        placeholder: 'Auto',
                                        style: { flex: 1 }
                                    }),
                                    el(SelectControl, {
                                        value: widthUnit,
                                        options: [
                                            { label: 'px', value: 'px' },
                                            { label: '%', value: '%' },
                                            { label: 'vw', value: 'vw' },
                                            { label: 'vh', value: 'vh' }
                                        ],
                                        onChange: (u) => updateWidth(widthVal, u),
                                        style: { width: '80px' }
                                    })
                                )
                            )
                        ),

                        // Desktop Viewport Control
                        el(ViewportControl, {
                            label: __('Videos in Viewport (Desktop)', 'reel-it'),
                            value: videosPerRow,
                            onChange: (val) => setAttributes({ videosPerRow: val })
                        }),

                        // Mobile Viewport Control
                        el(ViewportControl, {
                            label: __('Videos in Viewport (Mobile)', 'reel-it'),
                            value: videosPerRowMobile,
                            onChange: (val) => setAttributes({ videosPerRowMobile: val }),
                            max: 3 // Restrict mobile to fewer logical max
                        })
                    ),

                    el(PanelBody, { title: __('Slider Configuration', 'reel-it'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Use Video Gallery', 'reel-it'),
                            checked: useFeed,
                            onChange: (value) => setAttributes({ useFeed: value })
                        })
                    ),

                    // Video/Feed Selection Panel
                    el(PanelBody, { title: useFeed ? __('Select Feed', 'reel-it') : reelItBlock.strings.selectVideos, initialOpen: true },
                        uploadError && el(Notice, {
                            status: 'error',
                            onRemove: () => setUploadError('')
                        }, uploadError),

                        useFeed ?
                            el(FeedSelector, {
                                feedId,
                                onFeedChange: (id) => setAttributes({ feedId: id })
                            }) :
                            el(VideoSelector, {
                                videos,
                                onSelect: (newVideos) => setAttributes({ videos: newVideos }),
                                onUpload: handleVideoUpload,
                                isLoading: isUploading
                            })
                    )
                ),

                el('div', { className: 'reel-it-block-preview' },
                    ((useFeed && !feedId) || (!useFeed && videos.length === 0)) ?
                        el(Placeholder, {
                            icon: 'format-video',
                            label: __('Video Slider', 'reel-it'),
                            instructions: useFeed ? __('Select a video feed to display', 'reel-it') : __('Select videos from the library or upload new ones to create your slider.', 'reel-it')
                        }) :
                        el('div', { className: 'reel-it-selected-videos' },
                            useFeed ?
                                el('div', { className: 'reel-it-feed-wrapper' },
                                    el('h4', {}, __('Gallery Preview', 'reel-it')),
                                    el(FeedPreview, { feedId, videosPerRow })
                                ) :
                                el(Fragment, {},
                                    el('h4', {}, __('Selected Videos', 'reel-it')),
                                    videos.map((video, index) =>
                                        el(VideoItem, {
                                            key: video.id,
                                            video,
                                            onRemove: () => handleVideoRemove(video)
                                        })
                                    )
                                )
                        )
                )
            );
        },

        save: function (props) {
            // Block is rendered on the server
            return null;
        }
    });

}(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.blockEditor,
    window.wp.data,
    window.wp.i18n
));
