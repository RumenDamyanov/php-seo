# Security Policy

## Supported Versions

We actively support the latest major version of php-seo. Security updates will be provided for:

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |

## Reporting a Vulnerability

The security of php-seo is a top priority. If you discover a security vulnerability, please follow these steps:

### How to Report

**DO NOT** create a public GitHub issue for security vulnerabilities.

Instead, please email us directly at: **security@rumenx.com**

Include the following information in your report:

- Description of the vulnerability
- Steps to reproduce the issue
- Potential impact assessment
- Suggested fix (if you have one)
- Your contact information

### What to Expect

- **Acknowledgment**: We will acknowledge receipt of your report within 48 hours
- **Assessment**: We will assess the vulnerability and determine its impact within 7 days
- **Fix**: If confirmed, we will work on a fix and aim to release it within 30 days
- **Credit**: With your permission, we will credit you in our security advisory

### Security Update Process

1. **Private Fix**: We develop and test the fix privately
2. **Coordinated Disclosure**: We coordinate with you on the disclosure timeline
3. **Public Release**: We release the security update and publish an advisory
4. **Notification**: We notify users through our release notes and security channels

## Security Best Practices

When using php-seo, please follow these security guidelines:

### API Key Management

- **Never commit API keys** to version control
- Use environment variables or secure configuration management
- Rotate API keys regularly
- Use the minimum required permissions for AI provider accounts

### Content Validation

- Validate and sanitize all user inputs before processing
- Be cautious when processing content from untrusted sources
- Review AI-generated content before displaying it publicly

### Network Security

- Use HTTPS for all API communications
- Implement proper timeout and retry mechanisms
- Monitor API usage for unusual patterns

### Framework Integration

- Keep your framework and dependencies up to date
- Use framework-specific security features (CSRF protection, etc.)
- Follow your framework's security best practices

## Security Features

php-seo includes several built-in security features:

- **Input Sanitization**: All content is properly escaped in output
- **Rate Limiting**: Built-in rate limiting for AI API calls
- **Secure Defaults**: Conservative default configurations
- **Logging**: Optional security event logging

## Third-Party Security

We regularly monitor our dependencies for security vulnerabilities:

- Dependencies are kept up to date
- Security advisories are reviewed promptly
- Automated vulnerability scanning in CI/CD

## Contact Information

For security-related questions or concerns:

- **Email**: security@rumenx.com
- **GPG Key**: Available upon request
- **Response Time**: Within 48 hours

## Hall of Fame

We recognize and thank security researchers who help keep php-seo secure:

<!-- Security researchers who responsibly disclose vulnerabilities will be listed here -->

---

Thank you for helping keep php-seo and our users safe!