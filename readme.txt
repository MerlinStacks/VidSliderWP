=== Reel It - Shoppable Video Slider ===
Contributors: sldevs
Tags: video, slider, gallery, woocommerce, block
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.3.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern, high-performance video slider for WordPress. Showcase videos in galleries, tag WooCommerce products, and display anywhere.

== Description ==

Reel It is the ultimate video slider solution for WordPress, designed for 2025. It allows you to create stunning, touch-enabled video galleries that look great on any device. Whether you are a content creator, a shop owner, or a developer, Reel It provides the tools you need to engage your audience.

**Key Features:**

*   **Modern Video Galleries**: Create unlimited video feeds and organize your content effortlessly.
*   **WooCommerce Value**: Tag products directly in your videos. Perfect for "Shoppable Video" experiences.
*   **Dual Display Modes**: Use the native **Gutenberg Block** for a seamless editing experience, or the **Shortcode** `[reel_it]` for page builders and legacy editors.
*   **Performance First**: Built with extensive caching, lazy loading, and LCP optimizations to ensure your site stays fast.
*   **Mobile Optimized**: smooth touch gestures and responsive design that adapts to any screen size.
*   **SaaS-Style Admin**: A beautiful, easy-to-use dashboard for managing your feeds and settings.

**Why Reel It?**
Unlike other sliders that bloat your site, Reel It is lightweight and purpose-built for video. It handles native aspect ratios (vertical, square, or landscape) gracefully and prioritizes user experience.

== Installation ==

1.  Upload `reel-it` to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to **Reel It** in the admin menu to create your first gallery.
4.  Add videos to your gallery.
5.  Insert the gallery into any page using the "VidSliderWP" block or the shortcode provided in the admin dashboard.

== Frequently Asked Questions ==

= How do I add a video? =
Go to **Reel It > Galleries**, create a feed (or edit an existing one), and click "Manage Videos". You can select existing videos from your Media Library or upload new ones.

= Can I tag WooCommerce products? =
Yes! If WooCommerce is active, you will see a "Tag Products" button next to each video in the "Manage Videos" modal. Search for your product and select it. A "Shop" overlay will appear on the video frontend.

= Does it work with Elementor/Divi/Beaver Builder? =
Yes. Use the shortcode provided in the gallery list (e.g., `[reel_it feed_id="123" use_feed="true"]`) inside any text or code module.

= Is it mobile friendly? =
Absolutely. The slider supports touch swipe gestures and responsive layouts out of the box.

== Screenshots ==

1.  **Gallery Management**: The modern dashboard where you organize your video feeds.
2.  **Video Manager**: Easily add, remove, and reorder videos.
3.  **Product Tagging**: Search and link WooCommerce products to your videos.
4.  **Frontend Display**: A sleek video slider with a shoppable product card.

== Changelog ==

= 1.3.0 =
*   **New**: Complete Block Editor overhaul with a modern, "App-like" UI.
*   **New**: Full Width Container option.
*   **New**: Advanced Viewport Controls (Button selection, Partial slide toggle).
*   **New**: Dedicated Mobile Viewport configuration.
*   **Improvement**: Enhanced frontend performance using CSS variables for layout.

= 1.2.0 =
*   **New**: Added WooCommerce Product Tagging integration.
*   **New**: Added `[reel_it]` shortcode support for use in page builders.
*   **New**: Complete overhaul of the Admin UI with a modern, SaaS-like design.
*   **New**: Added "Copy Shortcode" button to the Galleries list.
*   **Improvement**: Enhanced mobile touch gestures and responsiveness.
*   **Improvement**: Major performance optimizations (LCP prioritization for first video, transient caching).
*   **Improvement**: Refactored codebase for better stability and security.
*   **Fix**: Resolved issues with video playback on some mobile devices.

= 1.1.1 =
*   Added video feed management system.
*   Added database tables for feeds.
*   Added drag-and-drop reordering support.

= 1.1.0 =
*   Changed video container to match natural video dimensions.
*   Implemented autoplay for the first video.
*   Added scroll-snap functionality.

= 1.0.0 =
*   Initial release.
