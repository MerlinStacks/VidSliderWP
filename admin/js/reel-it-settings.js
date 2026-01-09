/**
 * Reel It Settings Page - Enhanced UX
 * Updated for modern 2025 standards
 */

jQuery(document).ready(function ($) {
    'use strict';

    // Helper: Escape HTML to prevent XSS
    function escapeHtml(text) {
        if (!text && text !== 0) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    // Helper: Show standard WP admin notices (or custom toaster)
    function showNotification(message, type = 'success') {
        // Remove existing notifications
        $('.reel-it-notification').remove();

        const iconMap = {
            'success': 'dashicons-yes-alt',
            'error': 'dashicons-warning',
            'info': 'dashicons-info'
        };

        const $notification = $(`
            <div class="reel-it-notification reel-it-notification-${type}">
                <span class="dashicons ${iconMap[type]}"></span>
                <span class="reel-it-notification-message">${escapeHtml(message)}</span>
            </div>
        `);

        $('body').append($notification);

        // Animate in
        setTimeout(() => $notification.addClass('show'), 10);

        // Auto dismiss
        setTimeout(() => {
            $notification.removeClass('show');
            setTimeout(() => $notification.remove(), 300);
        }, 3000);
    }

    // 1. TABS: Enhanced Tab Navigation
    function initTabs() {
        // Use document delegation for tabs to ensure they work even if DOM updates
        $(document).on('click', '.reel-it-tab', function (e) {
            e.preventDefault();
            const tabId = $(this).data('tab');
            activateTab(tabId);
        });

        function activateTab(tabId) {
            $('.reel-it-tab').removeClass('active');
            $('.reel-it-tab-pane').removeClass('active');

            $(`.reel-it-tab[data-tab="${tabId}"]`).addClass('active');
            $(`#${tabId}`).addClass('active');

            localStorage.setItem('reelItActiveTab', tabId);
        }

        // Init State
        if ($('.reel-it-tab').length) {
            const lastTab = localStorage.getItem('reelItActiveTab');
            if (lastTab && $(`#${lastTab}`).length) {
                activateTab(lastTab);
            } else {
                activateTab($('.reel-it-tab').first().data('tab'));
            }
        }
    }

    // 2. FEED MANAGEMENT (AJAX)
    function initFeedManager() {
        // Create/Update Feed - Use document delegation for robustness
        $(document).on('click', '#reel-it-create-feed', function (e) {
            e.preventDefault();

            const $btn = $(this);
            // Safe access to values
            const $nameInput = $('#feed-name');
            const $descInput = $('#feed-description');

            if (!$nameInput.length) {
                return;
            }

            const name = $nameInput.val().trim();
            const desc = $descInput.val().trim();
            const isEdit = $btn.data('mode') === 'edit';
            const feedId = $btn.data('feed-id');

            if (!name) {
                showNotification(reelItSettings.strings.feedNameRequired || 'Name is required', 'error');
                $nameInput.focus();
                return;
            }

            // Lock UI
            $btn.prop('disabled', true).addClass('loading');

            const action = isEdit ? 'reel_it_update_feed' : 'reel_it_create_feed';
            const data = {
                action: action,
                nonce: reelItSettings.nonce,
                name: name,
                description: desc
            };

            if (isEdit) data.feed_id = feedId;

            $.post(reelItSettings.ajaxUrl, data, function (response) {
                if (response.success) {
                    showNotification(response.data.message || 'Success', 'success');
                    resetFeedForm();
                    // Just reload to be 100% sure the list is fresh and UI is clean (SaaS feel)
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification(response.data.message || 'Error occurred', 'error');
                }
            }).fail(function () {
                showNotification('Connection error. Please try again.', 'error');
            }).always(function () {
                $btn.prop('disabled', false).removeClass('loading');
            });
        });

        // Delete Feed
        $(document).on('click', '.reel-it-delete-feed', function (e) {
            e.preventDefault();
            const feedId = $(this).data('feed-id');
            const feedName = $(this).data('name');

            if (!confirm(reelItSettings.strings.confirmDelete.replace('%s', feedName))) return;

            const $item = $(this).closest('.reel-it-feed-item');
            $item.css('opacity', '0.5');

            $.post(reelItSettings.ajaxUrl, {
                action: 'reel_it_delete_feed',
                nonce: reelItSettings.nonce,
                feed_id: feedId
            }, function (response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    $item.slideUp(300, function () { $(this).remove(); });
                } else {
                    showNotification(response.data.message, 'error');
                    $item.css('opacity', '1');
                }
            });
        });

        // Edit Feed (Populate Form)
        $(document).on('click', '.reel-it-edit-feed', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const id = $btn.data('feed-id');
            const name = $btn.data('name');
            const desc = $btn.data('description');

            $('#feed-name').val(name).focus(); // Auto focus
            $('#feed-description').val(desc);

            const $submitBtn = $('#reel-it-create-feed');
            $submitBtn.text(reelItSettings.strings.updateFeed || 'Update Gallery')
                .data('mode', 'edit')
                .data('feed-id', id);

            $('#reel-it-cancel-edit').show();

            // Highlight form
            $('.reel-it-feed-editor').addClass('highlight-edit');
            setTimeout(() => $('.reel-it-feed-editor').removeClass('highlight-edit'), 1000);

            // Ensure sidebar is visible (if we had mobile styles)
        });

        // Cancel Edit
        $(document).on('click', '#reel-it-cancel-edit', function (e) {
            e.preventDefault();
            resetFeedForm();
        });
    }

    function resetFeedForm() {
        $('#feed-name').val('');
        $('#feed-description').val('');
        const $submitBtn = $('#reel-it-create-feed');
        $submitBtn.text(reelItSettings.strings.createFeed || 'Create Gallery')
            .data('mode', 'create')
            .removeData('feed-id');
        $('#reel-it-cancel-edit').hide();
    }

    // 3. UI/UX ENHANCEMENTS for 2025
    function initModernUI() {
        // Range Slider Updates
        $(document).on('input', '.reel-it-range-slider', function () {
            $(this).next('.reel-it-range-value').text($(this).val() + 'ms');
        });
    }

    // 4. VIDEO MANAGER MODAL (New)
    function initVideoModal() {
        let currentFeedId = 0;

        // Open Modal
        $(document).on('click', '.reel-it-manage-videos', function (e) {
            e.preventDefault();
            currentFeedId = $(this).data('feed-id');
            const feedName = $(this).data('name');

            $('#reel-it-modal-title').text('Manage Videos: ' + feedName);
            $('#reel-it-video-modal').fadeIn(200);

            loadFeedVideos(currentFeedId);
        });

        // Close Modal
        $(document).on('click', '.reel-it-modal-close, .reel-it-modal-close-btn, .reel-it-modal-overlay', function (e) {
            e.preventDefault();
            $('#reel-it-video-modal').fadeOut(200);
            // Refresh feed list in background to update counts/thumbs
            setTimeout(() => location.reload(), 300);
        });

        // Add Videos (WP Media)
        $(document).on('click', '#reel-it-add-videos, .reel-it-add-first-video', function (e) {
            e.preventDefault();

            const frame = wp.media({
                title: 'Select Videos to Add',
                button: { text: 'Add to Gallery' },
                multiple: true,
                library: { type: 'video' }
            });

            frame.on('select', function () {
                const selection = frame.state().get('selection');
                let promises = [];

                $('.reel-it-video-toolbar .spinner').addClass('is-active');

                selection.each(function (attachment) {
                    const promise = $.post(reelItSettings.ajaxUrl, {
                        action: 'reel_it_add_video_to_feed',
                        nonce: reelItSettings.nonce,
                        feed_id: currentFeedId,
                        video_id: attachment.id
                    });
                    promises.push(promise);
                });

                $.when.apply($, promises).done(function () {
                    showNotification('Videos added successfully', 'success');
                    loadFeedVideos(currentFeedId);
                }).always(function () {
                    $('.reel-it-video-toolbar .spinner').removeClass('is-active');
                });
            });

            frame.open();
        });

        // Remove Video
        $(document).on('click', '.reel-it-remove-video-btn', function (e) {
            e.preventDefault();
            if (!confirm(reelItSettings.strings.confirmRemoveVideo)) return;

            const videoId = $(this).data('video-id');
            const $item = $(this).closest('.reel-it-manage-video-item');

            $item.css('opacity', '0.5');

            $.post(reelItSettings.ajaxUrl, {
                action: 'reel_it_remove_video_from_feed',
                nonce: reelItSettings.nonce,
                feed_id: currentFeedId,
                video_id: videoId
            }, function (response) {
                if (response.success) {
                    $item.fadeOut(300, function () { $(this).remove(); });
                    showNotification('Video removed', 'success');
                } else {
                    showNotification('Failed to remove video', 'error');
                    $item.css('opacity', '1');
                }
            });
        });

        // Load Videos Function
        function loadFeedVideos(feedId) {
            const $container = $('#reel-it-video-list-container');
            const $noVideos = $('#reel-it-no-videos-message');

            $container.html('<div style="grid-column:1/-1; text-align:center; padding:2rem;"><span class="spinner is-active" style="float:none;"></span></div>');
            $noVideos.hide();

            $.post(reelItSettings.ajaxUrl, {
                action: 'reel_it_get_feed_videos',
                nonce: reelItSettings.nonce,
                feed_id: feedId
            }, function (response) {
                $container.empty();

                if (response.success && response.data.videos && response.data.videos.length > 0) {
                    response.data.videos.forEach(video => {
                        const thumbContent = video.thumbnail
                            ? `<img src="${escapeHtml(video.thumbnail)}" alt="${escapeHtml(video.post_title)}">`
                            : `<span class="dashicons dashicons-video-alt3" style="font-size:32px; height:32px; width:32px; color:#fff;"></span>`;

                        const html = `
                            <div class="reel-it-manage-video-item" data-video-id="${video.video_id}">
                                <div class="reel-it-drag-handle" title="Drag to reorder">
                                    <span class="dashicons dashicons-move"></span>
                                </div>
                                <div class="reel-it-manage-video-thumb">
                                    ${thumbContent}
                                </div>
                                <div class="reel-it-manage-video-info">
                                    <span class="reel-it-manage-video-title" title="${escapeHtml(video.post_title)}">${escapeHtml(video.post_title)}</span>
                                    <div class="reel-it-manage-video-actions">
                                        <button type="button" class="reel-it-tag-product-btn" data-video-id="${video.video_id}">
                                            <span class="dashicons dashicons-tag"></span> Tag Products
                                        </button>
                                        <button type="button" class="reel-it-remove-video-btn" data-video-id="${video.video_id}">
                                            <span class="dashicons dashicons-trash"></span> Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        $container.append(html);
                    });

                    // Initialize sortable for drag-and-drop reordering
                    initVideoSortable();
                } else {
                    $noVideos.show();
                }
            });
        }

        // Initialize jQuery UI Sortable for video reordering
        function initVideoSortable() {
            const $container = $('#reel-it-video-list-container');

            if ($container.hasClass('ui-sortable')) {
                $container.sortable('destroy');
            }

            $container.sortable({
                handle: '.reel-it-drag-handle',
                items: '.reel-it-manage-video-item',
                placeholder: 'reel-it-sortable-placeholder',
                tolerance: 'pointer',
                cursor: 'grabbing',
                opacity: 0.8,
                update: function (event, ui) {
                    saveVideoOrder();
                }
            });
        }

        // Save video order to database
        function saveVideoOrder() {
            const $container = $('#reel-it-video-list-container');
            const videoOrders = [];

            $container.find('.reel-it-manage-video-item').each(function (index) {
                videoOrders.push({
                    video_id: $(this).data('video-id'),
                    sort_order: index
                });
            });

            // Show saving indicator
            $('.reel-it-video-toolbar .spinner').addClass('is-active');

            $.post(reelItSettings.ajaxUrl, {
                action: 'reel_it_update_video_order',
                nonce: reelItSettings.nonce,
                feed_id: currentFeedId,
                video_orders: videoOrders
            }, function (response) {
                if (response.success) {
                    showNotification('Video order updated', 'success');
                } else {
                    showNotification('Failed to update order', 'error');
                }
            }).always(function () {
                $('.reel-it-video-toolbar .spinner').removeClass('is-active');
            });
        }
    }

    // 5. PRODUCT TAGGING (New)
    function initProductTagging() {
        let currentVideoId = 0;
        let taggedProducts = [];

        // Open Product Modal
        $(document).on('click', '.reel-it-tag-product-btn', function (e) {
            e.preventDefault();
            currentVideoId = $(this).data('video-id');
            $('#reel-it-product-modal').fadeIn(200);

            // Allow clicking inside second modal without closing first (handled by z-index)
            // But we need to make sure interaction is captured.

            loadTaggedProducts(currentVideoId);
            $('#reel-it-product-search').val('').focus();
            $('#reel-it-product-results').hide();
        });

        // Close Product Modal
        $(document).on('click', '.reel-it-product-modal-close, .reel-it-product-modal-close-btn', function (e) {
            e.preventDefault();
            $('#reel-it-product-modal').fadeOut(200);
        });

        // Search Products
        let searchTimeout;
        $('#reel-it-product-search').on('input', function () {
            clearTimeout(searchTimeout);
            const term = $(this).val().trim();
            const $results = $('#reel-it-product-results');
            const $spinner = $('#reel-it-product-spinner');

            if (term.length < 3) {
                $results.hide();
                return;
            }

            $spinner.addClass('is-active');

            searchTimeout = setTimeout(() => {
                $.post(reelItSettings.ajaxUrl, {
                    action: 'reel_it_search_products',
                    nonce: reelItSettings.nonce,
                    term: term
                }, function (response) {
                    $results.empty();
                    if (response.success && response.data.results.length) {
                        response.data.results.forEach(p => {
                            // Filter out already tagged
                            if (taggedProducts.find(tp => tp.id == p.id)) return;

                            const html = `
                                <li data-id="${p.id}" data-text="${escapeHtml(p.text)}" data-image="${escapeHtml(p.image)}">
                                    <img src="${p.image}" alt="">
                                    <span>${escapeHtml(p.text)}</span>
                                </li>
                            `;
                            $results.append(html);
                        });
                        $results.show();
                    } else {
                        $results.hide();
                    }
                }).always(() => {
                    $spinner.removeClass('is-active');
                });
            }, 300);
        });

        // Select Product
        $(document).on('click', '#reel-it-product-results li', function () {
            const p = {
                id: $(this).data('id'),
                text: $(this).data('text'),
                image: $(this).data('image')
            };

            // Enforce Single Product - Replace existing
            taggedProducts = [p];
            renderTaggedProducts();

            $('#reel-it-product-search').val('').focus();
            $('#reel-it-product-results').hide();
        });

        // Remove Tag
        $(document).on('click', '.reel-it-remove-tag', function () {
            const id = $(this).data('id');
            taggedProducts = taggedProducts.filter(p => p.id != id);
            renderTaggedProducts();
        });

        // Save Tags
        $('#reel-it-save-tags').on('click', function (e) {
            e.preventDefault();
            const $btn = $(this);
            $btn.prop('disabled', true).text(reelItSettings.strings.saving || 'Saving...');

            const productIds = taggedProducts.map(p => p.id);

            $.post(reelItSettings.ajaxUrl, {
                action: 'reel_it_save_video_products',
                nonce: reelItSettings.nonce,
                video_id: currentVideoId,
                products: productIds
            }, function (response) {
                if (response.success) {
                    showNotification('Tags saved successfully', 'success');
                    $('#reel-it-product-modal').fadeOut(200);
                } else {
                    showNotification('Error saving tags', 'error');
                }
            }).always(() => {
                $btn.prop('disabled', false).text('Save Tags');
            });
        });

        function loadTaggedProducts(videoId) {
            $('#reel-it-tagged-list').html('<span class="spinner is-active" style="float:none;"></span>');

            $.post(reelItSettings.ajaxUrl, {
                action: 'reel_it_get_video_products',
                nonce: reelItSettings.nonce,
                video_id: videoId
            }, function (response) {
                if (response.success) {
                    taggedProducts = response.data.products;
                    renderTaggedProducts();
                } else {
                    taggedProducts = [];
                    renderTaggedProducts();
                }
            });
        }

        function renderTaggedProducts() {
            const $list = $('#reel-it-tagged-list');
            const $noTags = $('#reel-it-no-tags');

            $list.empty();

            if (taggedProducts.length === 0) {
                $noTags.show();
                return;
            }

            $noTags.hide();

            taggedProducts.forEach(p => {
                const html = `
                    <div class="reel-it-product-chip">
                        <img src="${p.image}" alt="">
                        <span>${escapeHtml(p.text)}</span>
                        <span class="dashicons dashicons-dismiss reel-it-remove-tag" data-id="${p.id}"></span>
                    </div>
                `;
                $list.append(html);
            });
        }
    }

    // 6. COPY SHORTCODE
    function initCopyShortcode() {
        $(document).on('click', '.reel-it-copy-shortcode', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const $code = $btn.siblings('.reel-it-shortcode');
            const shortcode = $code.text();

            if (navigator.clipboard) {
                navigator.clipboard.writeText(shortcode).then(() => {
                    showNotification('Shortcode copied to clipboard!', 'success');
                    // Visual feedback
                    const originalIcon = $btn.html();
                    $btn.html('<span class="dashicons dashicons-yes"></span>');
                    setTimeout(() => $btn.html(originalIcon), 2000);
                }).catch(() => {
                    showNotification('Failed to copy shortcode', 'error');
                });
            } else {
                // Fallback
                const textArea = document.createElement("textarea");
                textArea.value = shortcode;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    showNotification('Shortcode copied to clipboard!', 'success');
                } catch (err) {
                    showNotification('Failed to copy shortcode', 'error');
                }
                document.body.removeChild(textArea);
            }
        });
    }

    // Init
    initTabs();
    initFeedManager();
    initModernUI();
    initVideoModal();
    initProductTagging();
    initCopyShortcode();

});
