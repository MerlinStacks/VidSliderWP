# Changelog

All notable changes to Reel It will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [1.5.0] - 2026-03-05

### Improved
- **Lazy Video DOM**: Videos are now created on demand — zero `<video>` tags in the initial HTML payload, dramatically reducing page weight and DOM size.
- **Hover Preload**: On desktop, hovering a slide triggers a `<link rel="preload">` for the video source so click-to-play feels instant.
- **Viewport Unloading**: `IntersectionObserver` automatically pauses and destroys off-screen videos, freeing memory and network resources.
- **Query Efficiency**: Batch-primed post caches (`_prime_post_caches` + `update_meta_cache`) eliminate N+1 `get_post_meta()` queries during render.
- **Stale Slide Filtering**: Deleted attachments are pre-filtered before render so slide count and ARIA labels stay accurate.
- **Widget / Template Part Support**: Slider now renders correctly in widgets, template parts, and reusable blocks via a `force_enqueue` bypass that doesn't rely on `global $post`.
- **Render Pipeline Refactor**: Extracted `prepare_attributes()`, `resolve_videos()`, and `resolve_poster_url()` from the monolithic render method for testability and readability.
- **Slider Template**: Moved slider HTML to a dedicated `public/views/slider.php` template, separating logic from presentation.
- **AJAX Helper**: Centralised nonce verification and capability checks into a shared `Reel_It_Ajax_Helper` class, removing duplicate code across all AJAX handlers.
- **Noscript Fallback**: Added a `<noscript>` video element so content remains accessible with JavaScript disabled.
- **Responsive Posters**: Poster images now emit `srcset` and `sizes` attributes for sharper thumbnails on high-DPI screens.
- **Admin JS Utilities**: Extracted shared admin JavaScript helpers into `reel-it-utils.js` for reuse across settings and gallery pages.
- **Analytics API**: Added `prune_old_events()` method on the analytics class for programmatic data retention control.

## [1.4.2] - 2026-02-27

### Improved
- **Frontend Performance**: Admin PHP classes (5 files, ~1200 lines) no longer load on frontend requests — guarded behind `is_admin() || wp_doing_ajax()`.
- **Database Efficiency**: Eliminated a duplicate `get_option()` call during slider render by passing options through `prepare_attributes()`.
- **Database Efficiency**: Added `update_meta_cache()` batch priming to remove N+1 `get_post_meta()` queries per slide (poster IDs, linked products).
- **Image Decoding**: Added `decoding="async"` to poster `<img>` tags so the browser can decode images off the main thread, improving FCP/LCP.
- **Total Blocking Time**: Client-side poster generation now uses `requestIdleCallback` (with `setTimeout` fallback for Safari) instead of a fixed 300ms delay.
- **DNS Prefetch**: Added a `dns-prefetch` resource hint for the AJAX endpoint, shaving ~50-100ms off the first analytics POST.

## [1.4.1] - 2026-02-18

### Improved
- **Video Thumbnails**: Videos now display a real first-frame poster instead of a black rectangle, generated client-side via canvas snapshot. Zero server-side cost — no FFmpeg required.
- **Loading UX**: Replaced flat black video background with an animated shimmer placeholder that signals "loading" until the poster appears.

## [1.4.0] - 2026-02-18

### Added
- **Analytics Data Retention**: Automatic daily pruning of analytics events older than 90 days via WP-Cron, preventing unbounded table growth.
- **Plugin Deactivation Cleanup**: Cron jobs are now properly unscheduled on plugin deactivation.

### Improved
- **Analytics Performance**: Consolidated five separate database queries in `get_summary_stats()` into a single query with conditional aggregation.
- **Security**: Added `WPINC` direct-access guards to all AJAX handler files.
- **Input Sanitization**: Applied `wp_unslash()` to `$_GET['days']` in the analytics page.
- **CSS Architecture**: Extracted 18 inline styles from settings PHP into dedicated CSS utility classes in `reel-it-settings.css`.
- **File Structure**: Split `class-reel-it-settings.php` (703 → 317 lines) by extracting three render methods into `admin/views/` templates (`page-galleries.php`, `page-settings.php`, `page-analytics.php`).

### Fixed
- **Analytics Data Integrity**: Corrected `record_event()` format string handling for nullable `feed_id` and `product_id` fields. Values are now stored as `NULL` instead of being coerced to `0`.

### Removed
- Deleted unused `reel-it-overrides.css`.
- Removed dead render methods (`render_field`, `render_feeds_section_info`, `render_upload_settings_section`) from the settings class.

## [1.3.1] - 2026-02-15

### Improved
- **Scoped Admin Assets**: CSS and JS now only load on Reel It admin pages, preventing style bleed.
- **Code Architecture**: Extracted AJAX handlers into dedicated classes (`Reel_It_Ajax_Feeds`, `Reel_It_Ajax_Products`, `Reel_It_Ajax_Analytics`).
- **Analytics Hardening**: Added per-IP rate limiting (30 req/min) and transient-based event deduplication.
- **Admin UX**: Settings split into fast-loading tabs, removing full-page reloads.

### Fixed
- Multiple security and data-integrity bugs identified in comprehensive code audit.
- Video deletion now cleans up orphaned feed-video relationship metadata.
- Block editor CSS no longer leaks into other Gutenberg panels.

## [1.3.0] - 2026-02-13

### Added
- Complete Block Editor overhaul with a modern, "App-like" UI.
- Full Width Container option for edge-to-edge layouts.
- Advanced Viewport Controls (Button selection, Partial slide toggle).
- Dedicated Mobile Viewport configuration.

### Improved
- Frontend performance using CSS custom properties for layout instead of inline JavaScript.

## [1.2.0] - 2025-12-16

### Added
- **WooCommerce Product Tagging**: Link products to videos for a shoppable experience.
- **Shortcode Support**: Use `[reel_it]` to display galleries in page builders or classic editor.
- **Copy Shortcode Button**: Easily copy gallery shortcodes from the admin dashboard.
- **Admin UI Overhaul**: A completely redesigned, modern "SaaS-style" interface for settings and gallery management.

### Improved
- **Performance**: Implemented transient caching for feed queries to reduce database load.
- **LCP Optimization**: Added `preload="auto"` and `fetchpriority="high"` to the first video for faster visual loading.
- **Mobile Experience**: Refined touch gestures and responsive styling for the product card overlay.
- **Code Quality**: Centralized helper methods and removed redundant logic.

### Fixed
- Fixed layout issues where the product card could overflow on small screens.
- Fixed CSS lint warnings by removing empty rulesets.

## [1.1.1] - 2025-11-27

### Added
- Video feed management system - organize videos into reusable collections
- Database tables for feeds and feed-video relationships
- Admin interface for creating, editing, and managing video feeds
- Feed selection dropdown in block editor instead of direct video selection
- Support for adding/removing videos from feeds with drag-and-drop reordering

### Changed
- Modified CSS in test-video-slider.html, public/css/reel-it-public.css, and blocks/css/block-style.css
- Videos now display with their native proportions (16:9, 4:3, 1:1, vertical, etc.)
- Maintained responsive design while allowing natural video dimensions
- Updated block editor to support feed selection
- Enhanced admin settings page with feed management capabilities

## [1.1.0] - 2025-10-25

### Changed
- Changed video container to match video dimensions instead of using fixed aspect ratio
- Updated slider layout to display all videos in a horizontal row
- Implemented autoplay for the first video
- Added smooth scrolling behavior with scroll-snap functionality
- Updated JavaScript to work with the new horizontal layout
- Enhanced responsive design for better mobile experience

## [1.0.0] - 2024-10-18

### Added
- Initial release of Reel It
- Gutenberg block integration for video sliders
- Direct video upload functionality to WordPress media library
- Media library integration for selecting existing videos
- Responsive design for all screen sizes
- Customizable slider options:
  - Autoplay functionality
  - Video player controls
  - Thumbnail navigation
  - Adjustable slider speed
- Touch and swipe support for mobile devices
- Keyboard navigation (arrow keys and spacebar)
- Accessibility features with proper ARIA labels
- Admin settings page for default configurations
- Support for multiple video formats:
  - MP4
  - WebM
  - OGG
  - QuickTime
  - AVI
- Performance optimizations with lazy loading
- Enhanced security measures:
  - Dual MIME type validation
  - File content scanning
  - User capability verification
  - Nonce protection for AJAX requests
  - Security audit logging
- Comprehensive error handling and validation
- Uninstall cleanup functionality

### Security
- Implemented file upload validation with server-side MIME type detection
- Added content scanning for malicious patterns
- Enhanced user access control and capability checks
- Added comprehensive input sanitization and output escaping
- Implemented security event logging
- Added protection against common upload vulnerabilities

### Documentation
- Complete README with installation and usage instructions
- CONTRIBUTING.md with development guidelines
- Comprehensive inline code documentation
- Security best practices documentation

### Technical
- Follows WordPress coding standards
- Compatible with WordPress 5.8+
- Requires PHP 7.4+
- Uses modern JavaScript and CSS practices
- Modular class structure for maintainability
- Proper internationalization support

---

## Development Guidelines

### Version Numbering
- Major version for breaking changes
- Minor version for new features
- Patch version for bug fixes

### Release Process
1. Update version numbers in all files
2. Update CHANGELOG.md
3. Create GitHub release
4. Tag the release
5. Update WordPress.org repository (if applicable)

### Security Updates
- Security issues will be addressed in patch releases
- Critical vulnerabilities may trigger immediate releases
- All security updates will be documented in changelog

---

## Roadmap

### Future Releases

#### [1.1.0] - Planned
- Video thumbnail generation
- Bulk video upload functionality
- Additional video format support
- Performance improvements
- Enhanced mobile experience

#### [1.2.0] - Planned
- Video analytics integration
- External video source support (YouTube, Vimeo)
- Advanced transition effects
- Custom color schemes
- Video playlist functionality

#### [2.0.0] - Planned
- Complete UI redesign
- Advanced customization options
- API endpoints for third-party integration
- Multi-language support
- Premium features and add-ons

---

## Support

For support, feature requests, or bug reports, please visit:
- GitHub Repository: https://github.com/MerlinStacks/VidSliderWP
- Issues: https://github.com/MerlinStacks/VidSliderWP/issues
- Discussions: https://github.com/MerlinStacks/VidSliderWP/discussions

---

## Contributors

Thank you to all contributors who have helped make Reel It better!

- [@MerlinStacks](https://github.com/MerlinStacks) - Project creator and lead developer

---

## License

This project is licensed under the GPL v2 or later License. See the [LICENSE](LICENSE) file for details.