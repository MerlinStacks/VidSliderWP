# Contributing to Reel It

Thank you for your interest in contributing to Reel It! This document provides guidelines and information for contributors.

## Getting Started

### Prerequisites

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Local development environment (Local by Flywheel, XAMPP, etc.)
- Git
- Code editor (VS Code recommended)

### Setting Up Development Environment

1. **Clone the repository**
   ```bash
   git clone https://github.com/MerlinStacks/VidSliderWP.git
   cd VidSliderWP
   ```

2. **Set up WordPress locally**
   - Install WordPress locally using your preferred method
   - Create a symbolic link from your WordPress plugins directory to the cloned repository
   - Or copy the plugin files to your WordPress plugins directory

3. **Activate the plugin**
   - Log in to your WordPress admin
   - Go to Plugins > Installed Plugins
   - Activate "Reel It"

## Development Guidelines

### Code Standards

Follow WordPress coding standards:
- [PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- [CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)

### File Organization

- Keep classes in separate files
- Use proper naming conventions
- Follow the existing directory structure
- Include proper file headers with documentation

### Security

- Always sanitize user input
- Escape output properly
- Use WordPress nonce verification for AJAX requests
- Follow WordPress security best practices
- Test for common vulnerabilities

### Testing

- Test your changes thoroughly
- Test with different WordPress versions
- Test with different PHP versions
- Test with various video formats
- Test accessibility features

## Submitting Changes

### Branch Naming

Use descriptive branch names:
- `feature/video-thumbnail-generation`
- `bugfix/autoplay-issue`
- `enhancement/mobile-touch-gestures`

### Commit Messages

Follow conventional commit format:
```
type(scope): description

[optional body]

[optional footer]
```

Examples:
- `feat(blocks): add video thumbnail generation`
- `fix(slider): resolve autoplay issue on mobile`
- `docs(readme): update installation instructions`

### Pull Request Process

1. **Create a new branch** from `main`
2. **Make your changes** following the guidelines above
3. **Test thoroughly** in different environments
4. **Update documentation** if needed
5. **Create a pull request** with:
   - Clear title and description
   - Related issue numbers (if any)
   - Testing instructions
   - Screenshots (if applicable)

### Pull Request Template

```markdown
## Description
Brief description of changes made.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tested with WordPress 5.8+
- [ ] Tested with PHP 7.4+
- [ ] Tested with different video formats
- [ ] Tested accessibility features
- [ ] Tested security measures

## Checklist
- [ ] Code follows WordPress coding standards
- [ ] Documentation is updated
- [ ] Security considerations addressed
- [ ] Performance impact considered
```

## Bug Reports

When reporting bugs, please include:

1. **Environment Information**
   - WordPress version
   - PHP version
   - Browser and version
   - Plugin version

2. **Steps to Reproduce**
   - Clear, numbered steps
   - Expected behavior
   - Actual behavior

3. **Additional Information**
   - Screenshots or videos
   - Error messages
   - Console errors
   - Related plugins/themes

## Feature Requests

When requesting features:

1. **Use the feature request template**
2. **Provide clear use cases**
3. **Consider the impact on existing users**
4. **Suggest implementation ideas** (optional)

## Security Issues

For security issues, please:
1. **Do not open a public issue**
2. **Email security details to**: security@example.com
3. **Provide detailed information** about the vulnerability
4. **Allow time for response** before disclosing publicly

## Code Review Process

All contributions require code review:

1. **Automated checks** must pass
2. **At least one maintainer** must review
3. **All feedback** must be addressed
4. **Tests must pass** before merge

## Release Process

Releases are managed by maintainers:

1. **Version bump** in plugin files
2. **Update changelog**
3. **Create GitHub release**
4. **Tag the release**
5. **Update WordPress.org** (if applicable)

## Community Guidelines

- Be respectful and constructive
- Welcome new contributors
- Provide helpful feedback
- Focus on what's best for the community
- Show empathy towards other community members

## Getting Help

- **Documentation**: Check the README and inline comments
- **Issues**: Search existing issues before creating new ones
- **Discussions**: Use GitHub Discussions for questions
- **Discord**: Join our community Discord (link in README)

## Recognition

Contributors are recognized in:
- README.md contributors section
- Release notes
- Annual contributor highlights

Thank you for contributing to Reel It!