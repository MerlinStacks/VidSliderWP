/**
 * Shared admin utility helpers for the Reel It plugin.
 *
 * Why: escapeHtml() was duplicated in reel-it-admin.js and
 * reel-it-settings.js. Both files now import this shared version
 * via a wp_enqueue_script dependency.
 *
 * @since 1.6.0
 * @package Reel_It
 */

/* exported ReelItUtils */
var ReelItUtils = (function () {
    'use strict';

    /**
     * Escape HTML entities to prevent XSS in template literals.
     *
     * @param {string|number|null|undefined} text - Input to escape.
     * @return {string} Escaped string, or empty string for falsy input.
     */
    function escapeHtml(text) {
        if (!text && text !== 0) return '';
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    return {
        escapeHtml: escapeHtml
    };
})();
