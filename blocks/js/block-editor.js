( function( blocks, element, components, editor, data, i18n ) {
    const { __ } = i18n;
    const { registerBlockType } = blocks;
    const { InspectorControls, MediaUpload, MediaUploadCheck } = editor;
    const { PanelBody, Button, TextControl, RangeControl, ToggleControl, SelectControl, Spinner, Notice } = components;
    const { createElement: el, Fragment } = element;
    const { useSelect } = data;

    // Video Item Component
    const VideoItem = ({ video, onRemove, onEdit }) => {
        return el('div', { className: 'wp-vids-reel-video-item' },
            el('div', { className: 'wp-vids-reel-video-preview' },
                video.thumbnail ? 
                    el('img', { src: video.thumbnail, alt: video.title }) :
                    el('div', { className: 'wp-vids-reel-video-placeholder' },
                        el('span', { className: 'dashicons dashicons-video-alt3' })
                    )
            ),
            el('div', { className: 'wp-vids-reel-video-info' },
                el('div', { className: 'wp-vids-reel-video-title' }, video.title),
                el('div', { className: 'wp-vids-reel-video-mime' }, video.mime)
            ),
            el('div', { className: 'wp-vids-reel-video-actions' },
                el(Button, {
                    isSmall: true,
                    isDestructive: true,
                    onClick: onRemove,
                    icon: 'trash',
                    label: __('Remove video', 'wp-vids-reel')
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

        return el('div', { className: 'wp-vids-reel-video-uploader' },
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
                icon: isLoading ? 'spinner' : 'upload'
            }, isLoading ? __('Uploading...', 'wp-vids-reel') : __('Upload Video', 'wp-vids-reel'))
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
            formData.append('action', 'wp_vids_reel_query_videos');
            formData.append('nonce', wpVidsReelBlock.nonce);
            formData.append('search', searchTerm);
            formData.append('page', page);

            fetch(wpVidsReelBlock.ajaxUrl, {
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
            .catch(error => {
                console.error('Error loading video library:', error);
            })
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

        return el('div', { className: 'wp-vids-reel-video-selector' },
            // Search bar
            el('div', { className: 'wp-vids-reel-search' },
                el(TextControl, {
                    label: __('Search videos', 'wp-vids-reel'),
                    value: search,
                    onChange: handleSearch,
                    placeholder: __('Search by title...', 'wp-vids-reel')
                })
            ),

            // Upload button
            el(VideoUploader, { onUpload, isLoading }),

            // Video library
            el('div', { className: 'wp-vids-reel-video-library' },
                loadingLibrary ? 
                    el('div', { className: 'wp-vids-reel-loading' }, el(Spinner)) :
                    videoLibrary.length === 0 ?
                        el(Notice, { status: 'info', isDismissible: false }, 
                            __('No videos found. Try uploading one or changing your search.', 'wp-vids-reel')
                        ) :
                        el('div', { className: 'wp-vids-reel-video-grid' },
                            videoLibrary.map(video => 
                                el('div', { 
                                    key: video.id, 
                                    className: 'wp-vids-reel-library-item' 
                                },
                                    el('div', { className: 'wp-vids-reel-library-preview' },
                                        video.thumbnail ?
                                            el('img', { src: video.thumbnail, alt: video.title }) :
                                            el('div', { className: 'wp-vids-reel-video-placeholder' },
                                                el('span', { className: 'dashicons dashicons-video-alt3' })
                                            )
                                    ),
                                    el('div', { className: 'wp-vids-reel-library-info' },
                                        el('div', { className: 'wp-vids-reel-library-title' }, video.title)
                                    ),
                                    el(Button, {
                                        isSmall: true,
                                        isPrimary: true,
                                        onClick: () => handleVideoSelect(video),
                                        disabled: videos.find(v => v.id === video.id)
                                    }, videos.find(v => v.id === video.id) ? __('Added', 'wp-vids-reel') : __('Add', 'wp-vids-reel'))
                                )
                            )
                        )
            )
        );
    };

    // Register the block
    registerBlockType('wp-vids-reel/video-slider', {
        title: wpVidsReelBlock.strings.blockTitle,
        description: wpVidsReelBlock.strings.blockDescription,
        icon: 'format-video',
        category: 'media',
        keywords: [__('video', 'wp-vids-reel'), __('slider', 'wp-vids-reel'), __('reel', 'wp-vids-reel')],
        
        attributes: {
            videos: {
                type: 'array',
                default: []
            },
            autoplay: {
                type: 'boolean',
                default: wpVidsReelBlock.defaults.autoplay || false
            },
            showControls: {
                type: 'boolean',
                default: wpVidsReelBlock.defaults.showControls || true
            },
            showThumbnails: {
                type: 'boolean',
                default: wpVidsReelBlock.defaults.showThumbnails || true
            },
            sliderSpeed: {
                type: 'number',
                default: wpVidsReelBlock.defaults.sliderSpeed || 5000
            }
        },

        edit: function(props) {
            const { attributes, setAttributes, className } = props;
            const { videos, autoplay, showControls, showThumbnails, sliderSpeed } = attributes;
            
            const [isUploading, setIsUploading] = React.useState(false);
            const [uploadError, setUploadError] = React.useState('');

            // Handle video upload
            const handleVideoUpload = (file) => {
                setIsUploading(true);
                setUploadError('');

                const formData = new FormData();
                formData.append('action', 'wp_vids_reel_upload_video');
                formData.append('nonce', wpVidsReelBlock.nonce);
                formData.append('video_file', file);

                fetch(wpVidsReelBlock.ajaxUrl, {
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
                        setUploadError(data.data.message || __('Upload failed', 'wp-vids-reel'));
                    }
                })
                .catch(error => {
                    setUploadError(__('Upload failed', 'wp-vids-reel'));
                    console.error('Upload error:', error);
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

            return el('div', { className: className },
                // Inspector Controls
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Slider Settings', 'wp-vids-reel'), initialOpen: true },
                        el(ToggleControl, {
                            label: wpVidsReelBlock.strings.autoplay,
                            checked: autoplay,
                            onChange: (value) => setAttributes({ autoplay: value })
                        }),
                        el(ToggleControl, {
                            label: wpVidsReelBlock.strings.showControls,
                            checked: showControls,
                            onChange: (value) => setAttributes({ showControls: value })
                        }),
                        el(ToggleControl, {
                            label: wpVidsReelBlock.strings.showThumbnails,
                            checked: showThumbnails,
                            onChange: (value) => setAttributes({ showThumbnails: value })
                        }),
                        el(RangeControl, {
                            label: wpVidsReelBlock.strings.sliderSpeed,
                            value: sliderSpeed,
                            onChange: (value) => setAttributes({ sliderSpeed: value }),
                            min: 1000,
                            max: 10000,
                            step: 500
                        })
                    ),

                    // Video Selection Panel
                    el(PanelBody, { title: wpVidsReelBlock.strings.selectVideos, initialOpen: true },
                        uploadError && el(Notice, { 
                            status: 'error', 
                            onRemove: () => setUploadError('') 
                        }, uploadError),
                        
                        el(VideoSelector, {
                            videos,
                            onSelect: (newVideos) => setAttributes({ videos: newVideos }),
                            onUpload: handleVideoUpload,
                            isLoading: isUploading
                        })
                    )
                ),

                // Block Preview
                el('div', { className: 'wp-vids-reel-block-preview' },
                    videos.length === 0 ?
                        el('div', { className: 'wp-vids-reel-no-videos' },
                            el('div', { className: 'wp-vids-reel-no-videos-icon' },
                                el('span', { className: 'dashicons dashicons-video-alt3' })
                            ),
                            el('p', {}, wpVidsReelBlock.strings.noVideosSelected)
                        ) :
                        el('div', { className: 'wp-vids-reel-selected-videos' },
                            el('h4', {}, __('Selected Videos', 'wp-vids-reel')),
                            videos.map((video, index) =>
                                el(VideoItem, {
                                    key: video.id,
                                    video,
                                    onRemove: () => handleVideoRemove(video)
                                })
                            )
                        )
                )
            );
        },

        save: function(props) {
            // Block is rendered on the server
            return null;
        }
    });

}(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.editor,
    window.wp.data,
    window.wp.i18n
));