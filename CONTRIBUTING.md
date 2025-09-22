# Contributing to php-seo

Thank you for considering contributing to php-seo! We welcome contributions from the community and are excited to work with you.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Making Changes](#making-changes)
- [Testing](#testing)
- [Code Style](#code-style)
- [Pull Request Process](#pull-request-process)
- [Reporting Issues](#reporting-issues)
- [Feature Requests](#feature-requests)

## Code of Conduct

This project and everyone participating in it is governed by our [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally
3. Set up the development environment
4. Create a new branch for your feature or fix
5. Make your changes
6. Test your changes
7. Submit a pull request

## Development Setup

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/php-seo.git
cd php-seo

# Install dependencies
composer install

# Copy environment configuration (if needed)
cp .env.example .env
```

### Development Tools

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Generate HTML coverage report
composer test-coverage-html

# Check code style
composer style

# Fix code style automatically
composer style-fix

# Run static analysis
composer analyze

# Run all quality checks
composer quality
```

## Making Changes

### Branch Naming

Use descriptive branch names:

- `feature/ai-provider-anthropic` - for new features
- `fix/meta-tag-escaping` - for bug fixes
- `docs/api-reference` - for documentation updates
- `refactor/analyzer-architecture` - for code refactoring

### Commit Messages

Follow conventional commit format:

```
type(scope): description

[optional body]

[optional footer]
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

Examples:
```
feat(ai): add Anthropic Claude provider support

Add support for Anthropic's Claude models via their API.
Includes rate limiting, error handling, and fallback support.

Closes #123
```

## Testing

### Writing Tests

- Write tests for all new functionality
- Maintain or improve test coverage
- Use descriptive test names
- Test both success and failure scenarios

### Test Structure

```php
<?php

test('it generates SEO title from page content', function () {
    $seoManager = new SeoManager();
    $content = '<h1>Welcome to Our Site</h1><p>Great content here.</p>';
    
    $seoManager->analyze($content);
    $title = $seoManager->generateTitle();
    
    expect($title)->toBeString()
        ->and($title)->not->toBeEmpty()
        ->and(strlen($title))->toBeLessThanOrEqual(60);
});
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
./vendor/bin/pest tests/Unit/SeoManagerTest.php

# Run tests with coverage
composer test-coverage

# Run tests in watch mode (if available)
./vendor/bin/pest --watch
```

## Code Style

We follow PSR-12 coding standards with some additional rules:

### PHP Standards

- Use strict types: `declare(strict_types=1);`
- Use type hints for all parameters and return types
- Use proper PHPDoc comments
- Follow PSR-4 autoloading standards

### Code Organization

- Keep classes focused and single-purpose
- Use dependency injection
- Prefer composition over inheritance
- Write self-documenting code

### Example

```php
<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Generators;

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Contracts\GeneratorInterface;

/**
 * Generator for creating SEO-optimized content.
 */
class ExampleGenerator implements GeneratorInterface
{
    public function __construct(
        private readonly SeoConfig $config
    ) {
    }

    /**
     * Generate content from page data.
     *
     * @param array<string, mixed> $pageData
     * @return string
     */
    public function generate(array $pageData): string
    {
        // Implementation here
    }
}
```

## Pull Request Process

### Before Submitting

1. **Update documentation** - Update README, CHANGELOG, or other docs if needed
2. **Add tests** - Ensure your changes are tested
3. **Check quality** - Run `composer quality` to verify everything passes
4. **Update CHANGELOG** - Add your changes to the unreleased section

### Pull Request Template

```markdown
## Description
Brief description of the changes.

## Type of Change
- [ ] Bug fix (non-breaking change that fixes an issue)
- [ ] New feature (non-breaking change that adds functionality)
- [ ] Breaking change (fix or feature that causes existing functionality to change)
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] Added new tests for this change
- [ ] Updated existing tests

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] CHANGELOG updated
```

### Review Process

1. **Automated Checks** - All CI checks must pass
2. **Code Review** - At least one maintainer review required
3. **Testing** - Changes are tested in various environments
4. **Documentation** - Documentation is reviewed for accuracy

## Reporting Issues

### Bug Reports

Use the bug report template and include:

- PHP version
- Framework version (if applicable)
- Steps to reproduce
- Expected vs actual behavior
- Error messages or logs
- Minimal code example

### Security Issues

**Do not report security vulnerabilities through public GitHub issues.**

Please email security@rumenx.com instead. See our [Security Policy](SECURITY.md) for details.

## Feature Requests

Before submitting a feature request:

1. Check if it already exists in issues
2. Consider if it fits the project scope
3. Think about backwards compatibility
4. Provide a clear use case

Include in your request:
- Clear description of the feature
- Use case and motivation
- Possible implementation approach
- Examples of usage

## AI Provider Integration

When contributing AI provider integrations:

1. Follow the `ProviderInterface` contract
2. Include comprehensive error handling
3. Add rate limiting support
4. Provide configuration examples
5. Include tests with mocked responses
6. Document API requirements and costs

## Documentation Guidelines

- Use clear, concise language
- Include code examples
- Keep documentation up to date
- Use proper Markdown formatting
- Test all code examples

## Getting Help

- **GitHub Discussions** - For questions and general discussion
- **GitHub Issues** - For bug reports and feature requests
- **Documentation** - Check the wiki and README first

## Recognition

Contributors are recognized in:
- CHANGELOG.md for their contributions
- GitHub contributors page
- Special recognition for significant contributions

---

Thank you for contributing to php-seo! 🚀