jQuery(document).ready(function($) {
    // Feed Management JavaScript
    window.editFeed = function(feedId, feedName, feedDescription) {
        $('#feed-name').val(feedName);
        $('#feed-description').val(feedDescription);
        $('#reel-it-new-feed-form').show();
        $('#reel-it-feed-editor').hide();
    };

    window.deleteFeed = function(feedId, feedName) {
        if (confirm(reelItSettings.strings.confirmDelete.replace('%s', feedName))) {
            $.post(reelItSettings.ajaxUrl, {
                action: 'wpvideos_delete_feed',
                nonce: reelItSettings.nonce,
                feed_id: feedId
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || reelItSettings.strings.feedDeleteFailed);
                }
            });
        }
    };

    window.createFeed = function() {
        var feedName = $('#feed-name').val();
        var feedDescription = $('#feed-description').val();

        if (!feedName.trim()) {
            alert(reelItSettings.strings.feedNameRequired);
            return;
        }

        $.post(reelItSettings.ajaxUrl, {
            action: 'wpvideos_create_feed',
            nonce: reelItSettings.nonce,
            name: feedName,
            description: feedDescription
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message || reelItSettings.strings.feedCreateFailed);
            }
        });
    };

    // Initialize feed management
    $('.reel-it-feed-item').on('click', function() {
        var feedId = $(this).data('feed-id');
        var feedName = $(this).find('.reel-it-feed-name').text();
        var feedDescription = $(this).find('.reel-it-feed-description').text();
        
        editFeed(feedId, feedName, feedDescription);
    });
});
