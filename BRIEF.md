# Feedback Pilgrim PHP SDK brief

This file is the brief of the PHP SDK. The common brief of all the SDKs is `_contract/BRIEF.md` in the `feedbackpilgrim-sdks` folder.

## Scope

- The package is `feedbackpilgrim/feedbackpilgrim-php`. The namespace is `FeedbackPilgrim`.
- The client calls the public API of one instance: `/api/public/v1`. It does not call the widget init operation `POST /api/v1/init`.
- The SDK makes the identity hash and verifies the V2 webhook signature (`<timestamp>.<raw body>`, 300 seconds by default).
- The Laravel integration has a service provider, a configuration file and a facade.
- The SDK supports PHP 8.2 to 8.4 and Laravel 11 to 13.

## Code style

The owner wants code that the best maintainers of each ecosystem write. Apply this to all code that you write, and copy this section into each SDK `BRIEF.md` that you make.

- **PHP and Laravel: the style of Taylor Otwell.** Expressive, fluent APIs that read like a sentence. Small classes with one job. Clear method names. No needless interfaces or layers. Use the framework (form requests, resources, attributes, config) before custom code. Laravel conventions for names and folders.
- **Ruby: the style of DHH.** Convention over configuration. Beautiful, short code that reads like prose. Plain Ruby objects, no needless abstraction, no defensive boilerplate. Small public API with good defaults.
- **Python: the style of Kenneth Reitz (`requests`) and the Python core developers.** "For humans": a small, obvious API with good defaults. PEP 8, full type hints, dataclasses for responses. Flat is better than nested.
- **JavaScript and TypeScript: the style of Sindre Sorhus.** Small, focused modules. ESM, native `fetch`, strict TypeScript types, zero runtime dependencies where possible.
- **For all languages:**
  - Optimize for the developer who reads the code and the developer who calls the API.
  - Delete code that does not earn its place. Prefer three clear lines to one clever line.
  - Names say what a thing does. Comments say why, not what.
  - Each public method has one example in the README.

