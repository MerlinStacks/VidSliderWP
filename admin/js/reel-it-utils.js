/**
 * Shared admin utility helpers for the Reel It plugin.
 *
 * Used by the gallery/settings UI to safely render AJAX response data.
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
