# WP Vids Reel

A WordPress plugin for showcasing videos in a slider using the block editor with video upload functionality.

## Description

WP Vids Reel allows you to create beautiful video sliders using the WordPress block editor. You can upload videos directly to the WordPress media library or select existing videos, and display them in an interactive slider with customizable options.

## Features

- **Gutenberg Block Integration**: Native block editor support for easy placement
- **Video Upload**: Direct video upload to WordPress media library
- **Media Library Integration**: Select existing videos from your media library
- **Responsive Design**: Works perfectly on all screen sizes
- **Customizable Options**: Autoplay, controls, thumbnails, and slider speed
- **Touch Support**: Swipe gestures for mobile devices
- **Keyboard Navigation**: Arrow keys and spacebar support
- **Accessibility**: WCAG compliant with proper ARIA labels
- **Performance Optimized**: Lazy loading and efficient code
- **Security Enhanced**: Multiple layers of validation and protection

## Installation

1. Download the plugin files from the GitHub repository:
   ```
   git clone https://github.com/MerlinStacks/VidSliderWP.git
   ```

2. Or download the ZIP file from the releases page and upload it via WordPress admin

3. In your WordPress admin, go to **Plugins > Add New**
4. Click **Upload Plugin** and select the downloaded ZIP file
5. Install and activate the plugin

## Usage

### Using the Block Editor

1. Edit a post or page where you want to add a video slider
2. Click the **+** icon to add a new block
3. Search for "Video Slider" or look for it in the "Media" category
4. Click on the **Video Slider** block to add it to your page
5. In the block settings panel, you can:
   - Upload new videos directly
   - Select videos from your media library
   - Configure autoplay, controls, thumbnails, and slider speed
6. Preview your slider and publish your page

### Block Settings

- **Autoplay**: Automatically advance to the next video
- **Show Controls**: Display video player controls
- **Show Thumbnails**: Display thumbnail navigation
- **Slider Speed**: Time between slides in milliseconds (1000-10000)

## Supported Video Formats

- MP4 (video/mp4) - Most widely supported format
- WebM (video/webm) - Open-source format, good compression
- OGG (video/ogg) - Open-source format
- QuickTime (video/quicktime) - Apple format
- AVI (video/x-msvideo) - Legacy format

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 79+
- Mobile browsers (iOS Safari 11+, Chrome Mobile 60+)

## Customization

### CSS Classes

You can customize the appearance using these CSS classes:

```css
/* Main container */
.wp-vids-reel-container {}

/* Slider area */
.wp-vids-reel-slider {}

/* Individual slides */
.wp-vids-reel-slide {}

/* Video element */
.wp-vids-reel-video {}

/* Navigation buttons */
.wp-vids-reel-prev,
.wp-vids-reel-next {}

/* Thumbnail navigation */
.wp-vids-reel-thumbnails {}
.wp-vids-reel-thumbnail {}

/* Video title overlay */
.wp-vids-reel-video-title {}
```

### JavaScript API

The plugin exposes a JavaScript API for advanced customization:

```javascript
// Get all sliders
const sliders = document.querySelectorAll('.wp-vids-reel-container');

// Listen for slide changes
document.addEventListener('wpVidsReelSlideChange', function(e) {
    console.log('Changed to slide:', e.detail.slideIndex);
});

// Programmatically change slides
const slider = document.querySelector('.wp-vids-reel-container');
if (slider && slider.wpVidsReel) {
    slider.wpVidsReel.goToSlide(2);
}
```

## Development

### Plugin Structure

```
wp-vids-reel/
├── wp-vids-reel.php              # Main plugin file
├── includes/
│   ├── class-wp-vids-reel.php           # Main plugin class
│   ├── class-wp-vids-reel-loader.php    # Hook loader
│   └── class-wp-vids-reel-i18n.php      # Internationalization
├── admin/
│   ├── class-wp-vids-reel-admin.php     # Admin functionality
│   ├── class-wp-vids-reel-settings.php  # Settings page
│   ├── css/
│   │   └── wp-vids-reel-admin.css       # Admin styles
│   └── js/
│       └── wp-vids-reel-admin.js        # Admin scripts
├── public/
│   ├── class-wp-vids-reel-public.php    # Frontend functionality
│   ├── css/
│   │   └── wp-vids-reel-public.css      # Frontend styles
│   └── js/
│       └── wp-vids-reel-public.js       # Frontend scripts
├── blocks/
│   ├── class-wp-vids-reel-blocks.php    # Block registration
│   ├── css/
│   │   ├── block-editor.css              # Block editor styles
│   │   └── block-style.css               # Frontend block styles
│   └── js/
│       └── block-editor.js               # Block editor scripts
├── uninstall.php                          # Cleanup on uninstall
└── README.md                              # This file
```

### Building the Plugin

The plugin uses standard WordPress development practices. No build process is required.

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Security

This plugin implements multiple security measures:

- **File Validation**: Dual MIME type checking (browser + server detection)
- **Content Scanning**: Scans uploaded files for malicious patterns
- **Access Control**: User capability verification for all operations
- **Nonce Protection**: All AJAX requests require valid nonces
- **Input Sanitization**: Comprehensive input validation and sanitization
- **Audit Logging**: Security event logging for monitoring

## Changelog

### 1.0.0
- Initial release
- Gutenberg block integration
- Video upload functionality
- Media library integration
- Responsive design
- Accessibility features
- Touch and keyboard support
- Admin settings page
- Enhanced security measures

## Support

For support and feature requests, please visit the GitHub repository:
https://github.com/MerlinStacks/VidSliderWP

## License

This plugin is licensed under the GPL v2 or later.

```
WP Vids Reel
Copyright (C) 2024 Your Name

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA
```

## Credits

- Built with WordPress best practices
- Uses modern JavaScript and CSS
- Accessibility-focused design
- Security-first development approach