(function ($) {
    'use strict';

    // Admin functionality for Reel It
    const ReelItAdmin = {

        init: function () {
            this.initVideoUpload();
            this.initMediaLibrary();
            this.initSettingsPage();
            this.initBulkActions();
        },

        // Video upload functionality
        initVideoUpload: function () {
            const uploadArea = $('.reel-it-upload-area');
            const fileInput = $('#reel-it-video-upload');

            if (!uploadArea.length || !fileInput.length) {
                return;
            }

            // Handle drag and drop
            uploadArea.on('dragover dragenter', function (e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });

            uploadArea.on('dragleave dragend drop', function (e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });

            uploadArea.on('drop', function (e) {
                e.preventDefault();
                const files = e.originalEvent.dataTransfer.files;

                if (files.length > 0) {
                    ReelItAdmin.handleFileUpload(files[0]);
                }
            });

            // Handle click to upload
            uploadArea.on('click', function () {
                fileInput.click();
            });

            fileInput.on('change', function (e) {
                const files = e.target.files;

                if (files.length > 0) {
                    ReelItAdmin.handleFileUpload(files[0]);
                }
            });
        },

        // Handle file upload
        handleFileUpload: function (file) {
            // Check if it's a video file
            const allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo'];

            if (!allowedTypes.includes(file.type)) {
                ReelItAdmin.showNotice('Please select a valid video file.', 'error');
                return;
            }

            // Check file size (50MB limit)
            const maxSize = 50 * 1024 * 1024; // 50MB in bytes

            if (file.size > maxSize) {
                ReelItAdmin.showNotice('Video file is too large. Maximum size is 50MB.', 'error');
                return;
            }

            // Create form data
            const formData = new FormData();
            formData.append('action', 'reel_it_upload_video');
            formData.append('nonce', reel_it_admin.nonce);
            formData.append('video_file', file);

            // Show progress
            ReelItAdmin.showProgress(0);

            // Create AJAX request with progress tracking
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    ReelItAdmin.updateProgress(percentComplete);
                }
            });

            xhr.addEventListener('load', function () {
                ReelItAdmin.hideProgress();

                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            ReelItAdmin.showNotice(reelItSettings.strings.videoUploaded, 'success');
                            ReelItAdmin.addVideoToList(response.data);
                        } else {
                            ReelItAdmin.showNotice(response.data.message || reelItSettings.strings.uploadFailed, 'error');
                        }
                    } catch (e) {
                        ReelItAdmin.showNotice(reelItSettings.strings.uploadFailedInvalid, 'error');
                    }
                } else {
                    ReelItAdmin.showNotice(reelItSettings.strings.uploadFailedServer, 'error');
                }
            });

            xhr.addEventListener('error', function () {
                ReelItAdmin.hideProgress();
                ReelItAdmin.showNotice(reelItSettings.strings.uploadFailedNetwork, 'error');
            });

            xhr.open('POST', reel_it_admin.ajax_url);
            xhr.send(formData);
        },

        // Media library integration
        initMediaLibrary: function () {
            const mediaButton = $('.reel-it-media-button');

            if (!mediaButton.length) {
                return;
            }

            mediaButton.on('click', function (e) {
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

                frame.on('select', function () {
                    const selection = frame.state().get('selection');

                    selection.each(function (attachment) {
                        const videoData = {
                            id: attachment.id,
                            title: attachment.get('title'),
                            url: attachment.get('url'),
                            thumbnail: attachment.get('thumbnail') ? attachment.get('thumbnail').url : null
                        };

                        ReelItAdmin.addVideoToList(videoData);
                    });
                });

                frame.open();
            });
        },

        // Add video to list
        addVideoToList: function (videoData) {
            const videoList = $('.reel-it-video-list');
            const videoItem = ReelItAdmin.createVideoItem(videoData);

            videoList.append(videoItem);

            // Update hidden field with video data
            ReelItAdmin.updateVideoData();
        },

        // Create video item HTML
        createVideoItem: function (videoData) {
            const thumbnailHtml = videoData.thumbnail ?
                `<img src="${videoData.thumbnail}" alt="${videoData.title}">` :
                '<div class="placeholder"><span class="dashicons dashicons-video-alt3"></span></div>';

            return $(`
                <div class="reel-it-video-item" data-video-id="${videoData.id}">
                    <div class="reel-it-video-thumbnail">
                        ${thumbnailHtml}
                    </div>
                    <div class="reel-it-video-info">
                        <div class="reel-it-video-title">${videoData.title}</div>
                        <div class="reel-it-video-meta">ID: ${videoData.id}</div>
                    </div>
                    <div class="reel-it-video-actions">
                        <button type="button" class="button button-small reel-it-edit-video">Edit</button>
                        <button type="button" class="button button-small reel-it-remove-video">Remove</button>
                    </div>
                </div>
            `);
        },

        // Update video data field
        updateVideoData: function () {
            const videoItems = $('.reel-it-video-item');
            const videos = [];

            videoItems.each(function () {
                const $item = $(this);
                videos.push({
                    id: $item.data('video-id'),
                    title: $item.find('.reel-it-video-title').text(),
                    url: $item.data('video-url'),
                    thumbnail: $item.data('video-thumbnail')
                });
            });

            $('#reel-it-videos-data').val(JSON.stringify(videos));
        },

        // Settings page functionality
        initSettingsPage: function () {
            // Color picker
            // Removed as it is not used in the modern design
            // $('.reel-it-color-picker').wpColorPicker();

            // Range sliders
            $('.reel-it-range-slider').on('input', function () {
                const value = $(this).val();
                const display = $(this).siblings('.reel-it-range-value');
                display.text(value);
            });

            // Toggle switches
            $('.reel-it-toggle').on('change', function () {
                const $toggle = $(this);
                const $target = $($toggle.data('target'));

                if ($target.length) {
                    $target.toggle($toggle.is(':checked'));
                }
            });
        },

        // Bulk actions
        initBulkActions: function () {
            const bulkActions = $('#reel-it-bulk-actions');
            const bulkActionButton = $('#reel-it-do-bulk-action');

            if (!bulkActions.length || !bulkActionButton.length) {
                return;
            }

            bulkActionButton.on('click', function (e) {
                e.preventDefault();

                const action = bulkActions.val();
                const selectedVideos = $('.reel-it-video-item input[type="checkbox"]:checked');

                if (!action) {
                    ReelItAdmin.showNotice(reelItSettings.strings.selectAction, 'warning');
                    return;
                }

                if (selectedVideos.length === 0) {
                    ReelItAdmin.showNotice(reelItSettings.strings.selectAtLeastOneVideo, 'warning');
                    return;
                }

                ReelItAdmin.performBulkAction(action, selectedVideos);
            });
        },

        // Perform bulk action
        performBulkAction: function (action, selectedVideos) {
            const videoIds = [];

            selectedVideos.each(function () {
                videoIds.push($(this).closest('.reel-it-video-item').data('video-id'));
            });

            const formData = new FormData();
            formData.append('action', 'reel_it_bulk_action');
            formData.append('nonce', reel_it_admin.nonce);
            formData.append('bulk_action', action);
            formData.append('video_ids', JSON.stringify(videoIds));

            $.ajax({
                url: reel_it_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        ReelItAdmin.showNotice(response.data.message, 'success');

                        // Remove videos from list if action was delete
                        if (action === 'delete') {
                            selectedVideos.closest('.reel-it-video-item').remove();
                            ReelItAdmin.updateVideoData();
                        }
                    } else {
                        ReelItAdmin.showNotice(response.data.message || reelItSettings.strings.actionFailed, 'error');
                    }
                },
                error: function () {
                    ReelItAdmin.showNotice(reelItSettings.strings.actionFailedServer, 'error');
                }
            });
        },

        // Show progress indicator
        showProgress: function (percent) {
            const progressHtml = `
                <div class="reel-it-progress">
                    <div class="reel-it-progress-bar">
                        <div class="reel-it-progress-fill" style="width: ${percent}%"></div>
                    </div>
                    <div class="reel-it-progress-text">${reelItSettings.strings.uploading} ${Math.round(percent)}%</div>
                </div>
            `;

            $('.reel-it-upload-area').after(progressHtml);
        },

        // Update progress
        updateProgress: function (percent) {
            $('.reel-it-progress-fill').css('width', percent + '%');
            $('.reel-it-progress-text').text(reelItSettings.strings.uploading + ' ' + Math.round(percent) + '%');
        },

        // Hide progress
        hideProgress: function () {
            $('.reel-it-progress').remove();
        },

        // Show notice
        showNotice: function (message, type) {
            const notice = $(`
                <div class="reel-it-notice ${type}">
                    ${message}
                </div>
            `);

            $('.wrap h1').after(notice);

            // Auto-hide after 5 seconds
            setTimeout(function () {
                notice.fadeOut(function () {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function () {
        ReelItAdmin.init();

        // Handle video removal
        $(document).on('click', '.reel-it-remove-video', function () {
            if (confirm(reelItSettings.strings.confirmRemoveVideo)) {
                $(this).closest('.reel-it-video-item').remove();
                ReelItAdmin.updateVideoData();
                ReelItAdmin.showNotice(reelItSettings.strings.videoRemoved, 'success');
            }
        });

        // Handle video editing
        $(document).on('click', '.reel-it-edit-video', function () {
            const videoItem = $(this).closest('.reel-it-video-item');
            const videoId = videoItem.data('video-id');

            // Open media editor for this attachment
            wp.media.attachment(videoId).fetch().then(function (attachment) {
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
