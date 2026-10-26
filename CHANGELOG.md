# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-10-26

### Security

- Reject Gemini model names that contain path separators before they are placed in the `generateContent` URL.
- Document that PHP2Shell ([CVE-2026-109618](https://www.cve.org/CVERecord?id=CVE-2026-109618), [CVE-2026-109619](https://www.cve.org/CVERecord?id=CVE-2026-109619)) is in the PHP engine: this package cannot patch it. Run PHP **8.3.36+**, **8.4.27+**, or **8.5.12+** ([PHP Foundation advisory](https://thephp.foundation/blog/2026/10/21/todays-php-security-release-and-how-were-strengthening-php-security/), [CISA alert](https://www.cisa.gov/news-events/alerts/2026/10/21/critical-vulnerabilities-php-require-immediate-patching)).

### Changed

- Require PHP **^8.3**. PHP 8.2 reaches upstream end of life on 31 December 2026; stay on 1.0.x if you must remain on 8.2 (and use **8.2.35+** until then).
- CI tests PHP 8.3, 8.4, and 8.5. Coverage is collected on 8.4 only.
- Default models for short SEO text: OpenAI `gpt-5.4-mini`, Anthropic `claude-haiku-4-5`, Google `gemini-3.5-flash-lite`, xAI `grok-4.6`.
- Also accept current quality models: `gpt-5.4`, `claude-opus-5`, `claude-sonnet-5`, `gemini-3.8-flash`, and `grok-4.7`. Older model ids remain in each provider allowlist.
- Call the Gemini API on `v1beta`.

## [1.0.0]

### Added

- Initial AI-powered SEO package with OpenAI, Anthropic, Google, xAI, and Ollama providers.
