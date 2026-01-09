# Changelog

All notable changes to Reel It will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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