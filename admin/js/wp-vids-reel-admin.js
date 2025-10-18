(function($) {
    'use strict';

    // Admin functionality for WP Vids Reel
    const WpVidsReelAdmin = {
        
        init: function() {
            this.initVideoUpload();
            this.initMediaLibrary();
            this.initSettingsPage();
            this.initBulkActions();
        },
        
        // Video upload functionality
        initVideoUpload: function() {
            const uploadArea = $('.wp-vids-reel-upload-area');
            const fileInput = $('#wp-vids-reel-video-upload');
            
            if (!uploadArea.length || !fileInput.length) {
                return;
            }
            
            // Handle drag and drop
            uploadArea.on('dragover dragenter', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });
            
            uploadArea.on('dragleave dragend drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });
            
            uploadArea.on('drop', function(e) {
                e.preventDefault();
                const files = e.originalEvent.dataTransfer.files;
                
                if (files.length > 0) {
                    WpVidsReelAdmin.handleFileUpload(files[0]);
                }
            });
            
            // Handle click to upload
            uploadArea.on('click', function() {
                fileInput.click();
            });
            
            fileInput.on('change', function(e) {
                const files = e.target.files;
                
                if (files.length > 0) {
                    WpVidsReelAdmin.handleFileUpload(files[0]);
                }
            });
        },
        
        // Handle file upload
        handleFileUpload: function(file) {
            // Check if it's a video file
            const allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo'];
            
            if (!allowedTypes.includes(file.type)) {
                WpVidsReelAdmin.showNotice('Please select a valid video file.', 'error');
                return;
            }
            
            // Check file size (50MB limit)
            const maxSize = 50 * 1024 * 1024; // 50MB in bytes
            
            if (file.size > maxSize) {
                WpVidsReelAdmin.showNotice('Video file is too large. Maximum size is 50MB.', 'error');
                return;
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('action', 'wp_vids_reel_upload_video');
            formData.append('nonce', wp_vids_reel_admin.nonce);
            formData.append('video_file', file);
            
            // Show progress
            WpVidsReelAdmin.showProgress(0);
            
            // Create AJAX request with progress tracking
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    WpVidsReelAdmin.updateProgress(percentComplete);
                }
            });
            
            xhr.addEventListener('load', function() {
                WpVidsReelAdmin.hideProgress();
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            WpVidsReelAdmin.showNotice('Video uploaded successfully!', 'success');
                            WpVidsReelAdmin.addVideoToList(response.data);
                        } else {
                            WpVidsReelAdmin.showNotice(response.data.message || 'Upload failed.', 'error');
                        }
                    } catch (e) {
                        WpVidsReelAdmin.showNotice('Upload failed. Invalid response.', 'error');
                    }
                } else {
                    WpVidsReelAdmin.showNotice('Upload failed. Server error.', 'error');
                }
            });
            
            xhr.addEventListener('error', function() {
                WpVidsReelAdmin.hideProgress();
                WpVidsReelAdmin.showNotice('Upload failed. Network error.', 'error');
            });
            
            xhr.open('POST', wp_vids_reel_admin.ajax_url);
            xhr.send(formData);
        },
        
        // Media library integration
        initMediaLibrary: function() {
            const mediaButton = $('.wp-vids-reel-media-button');
            
            if (!mediaButton.length) {
                return;
            }
            
            mediaButton.on('click', function(e) {
                e.preventDefault();
                
                const frame = wp.media({
                    title: 'Select Videos',
                    button: {
                        text: 'Add to Slider'
                    },
                    multiple: true,
                    library: {
                        type: 'video'
                    }
                });
                
                frame.on('select', function() {
                    const selection = frame.state().get('selection');
                    
                    selection.each(function(attachment) {
                        const videoData = {
                            id: attachment.id,
                            title: attachment.get('title'),
                            url: attachment.get('url'),
                            thumbnail: attachment.get('thumbnail') ? attachment.get('thumbnail').url : null
                        };
                        
                        WpVidsReelAdmin.addVideoToList(videoData);
                    });
                });
                
                frame.open();
            });
        },
        
        // Add video to list
        addVideoToList: function(videoData) {
            const videoList = $('.wp-vids-reel-video-list');
            const videoItem = WpVidsReelAdmin.createVideoItem(videoData);
            
            videoList.append(videoItem);
            
            // Update hidden field with video data
            WpVidsReelAdmin.updateVideoData();
        },
        
        // Create video item HTML
        createVideoItem: function(videoData) {
            const thumbnailHtml = videoData.thumbnail ? 
                `<img src="${videoData.thumbnail}" alt="${videoData.title}">` :
                '<div class="placeholder"><span class="dashicons dashicons-video-alt3"></span></div>';
            
            return $(`
                <div class="wp-vids-reel-video-item" data-video-id="${videoData.id}">
                    <div class="wp-vids-reel-video-thumbnail">
                        ${thumbnailHtml}
                    </div>
                    <div class="wp-vids-reel-video-info">
                        <div class="wp-vids-reel-video-title">${videoData.title}</div>
                        <div class="wp-vids-reel-video-meta">ID: ${videoData.id}</div>
                    </div>
                    <div class="wp-vids-reel-video-actions">
                        <button type="button" class="button button-small wp-vids-reel-edit-video">Edit</button>
                        <button type="button" class="button button-small wp-vids-reel-remove-video">Remove</button>
                    </div>
                </div>
            `);
        },
        
        // Update video data field
        updateVideoData: function() {
            const videoItems = $('.wp-vids-reel-video-item');
            const videos = [];
            
            videoItems.each(function() {
                const $item = $(this);
                videos.push({
                    id: $item.data('video-id'),
                    title: $item.find('.wp-vids-reel-video-title').text(),
                    url: $item.data('video-url'),
                    thumbnail: $item.data('video-thumbnail')
                });
            });
            
            $('#wp-vids-reel-videos-data').val(JSON.stringify(videos));
        },
        
        // Settings page functionality
        initSettingsPage: function() {
            // Color picker
            $('.wp-vids-reel-color-picker').wpColorPicker();
            
            // Range sliders
            $('.wp-vids-reel-range-slider').on('input', function() {
                const value = $(this).val();
                const display = $(this).siblings('.wp-vids-reel-range-value');
                display.text(value);
            });
            
            // Toggle switches
            $('.wp-vids-reel-toggle').on('change', function() {
                const $toggle = $(this);
                const $target = $($toggle.data('target'));
                
                if ($target.length) {
                    $target.toggle($toggle.is(':checked'));
                }
            });
        },
        
        // Bulk actions
        initBulkActions: function() {
            const bulkActions = $('#wp-vids-reel-bulk-actions');
            const bulkActionButton = $('#wp-vids-reel-do-bulk-action');
            
            if (!bulkActions.length || !bulkActionButton.length) {
                return;
            }
            
            bulkActionButton.on('click', function(e) {
                e.preventDefault();
                
                const action = bulkActions.val();
                const selectedVideos = $('.wp-vids-reel-video-item input[type="checkbox"]:checked');
                
                if (!action) {
                    WpVidsReelAdmin.showNotice('Please select an action.', 'warning');
                    return;
                }
                
                if (selectedVideos.length === 0) {
                    WpVidsReelAdmin.showNotice('Please select at least one video.', 'warning');
                    return;
                }
                
                WpVidsReelAdmin.performBulkAction(action, selectedVideos);
            });
        },
        
        // Perform bulk action
        performBulkAction: function(action, selectedVideos) {
            const videoIds = [];
            
            selectedVideos.each(function() {
                videoIds.push($(this).closest('.wp-vids-reel-video-item').data('video-id'));
            });
            
            const formData = new FormData();
            formData.append('action', 'wp_vids_reel_bulk_action');
            formData.append('nonce', wp_vids_reel_admin.nonce);
            formData.append('bulk_action', action);
            formData.append('video_ids', JSON.stringify(videoIds));
            
            $.ajax({
                url: wp_vids_reel_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        WpVidsReelAdmin.showNotice(response.data.message, 'success');
                        
                        // Remove videos from list if action was delete
                        if (action === 'delete') {
                            selectedVideos.closest('.wp-vids-reel-video-item').remove();
                            WpVidsReelAdmin.updateVideoData();
                        }
                    } else {
                        WpVidsReelAdmin.showNotice(response.data.message || 'Action failed.', 'error');
                    }
                },
                error: function() {
                    WpVidsReelAdmin.showNotice('Action failed. Server error.', 'error');
                }
            });
        },
        
        // Show progress indicator
        showProgress: function(percent) {
            const progressHtml = `
                <div class="wp-vids-reel-progress">
                    <div class="wp-vids-reel-progress-bar">
                        <div class="wp-vids-reel-progress-fill" style="width: ${percent}%"></div>
                    </div>
                    <div class="wp-vids-reel-progress-text">Uploading... ${Math.round(percent)}%</div>
                </div>
            `;
            
            $('.wp-vids-reel-upload-area').after(progressHtml);
        },
        
        // Update progress
        updateProgress: function(percent) {
            $('.wp-vids-reel-progress-fill').css('width', percent + '%');
            $('.wp-vids-reel-progress-text').text('Uploading... ' + Math.round(percent) + '%');
        },
        
        // Hide progress
        hideProgress: function() {
            $('.wp-vids-reel-progress').remove();
        },
        
        // Show notice
        showNotice: function(message, type) {
            const notice = $(`
                <div class="wp-vids-reel-notice ${type}">
                    ${message}
                </div>
            `);
            
            $('.wrap h1').after(notice);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        WpVidsReelAdmin.init();
        
        // Handle video removal
        $(document).on('click', '.wp-vids-reel-remove-video', function() {
            if (confirm('Are you sure you want to remove this video?')) {
                $(this).closest('.wp-vids-reel-video-item').remove();
                WpVidsReelAdmin.updateVideoData();
                WpVidsReelAdmin.showNotice('Video removed.', 'success');
            }
        });
        
        // Handle video editing
        $(document).on('click', '.wp-vids-reel-edit-video', function() {
            const videoItem = $(this).closest('.wp-vids-reel-video-item');
            const videoId = videoItem.data('video-id');
            
            // Open media editor for this attachment
            wp.media.attachment(videoId).fetch().then(function(attachment) {
                wp.media({
                    frame: 'edit-attachments',
                    library: {
                        type: 'video'
                    },
                    attachment: attachment
                }).open();
            });
        });
    });
    
})(jQuery);