# Contributing

Thank you for your help. This file tells you how to prepare a change.

## Set up the project

1. Install PHP 8.2 or later and Composer.
2. Clone the repository.
3. Install the dependencies:

```bash
composer install
```

## Make a change

1. Create a branch from `main`.
2. Write a test for the change first. Then write the code.
3. Run the tests:

```bash
composer test
```

4. Run the static analysis:

```bash
composer analyse
```

5. Format the code:

```bash
composer format
```

6. Add a line to the `Unreleased` section of `CHANGELOG.md`.
7. Open a pull request. Complete the pull request template.

## Pull request titles

The title of each pull request must follow [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/). The `pr-title` check fails when the title does not follow it.

Use one of these types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore` or `revert`. The scope in parentheses is optional. Examples:

- `feat: add the testimonials resource`
- `fix(laravel): read the token from the config cache`
- `docs: add a webhook example to the README`

GitHub makes the release notes from the titles of the merged pull requests. A clear title gives a clear release note.

## Release

The `Release` workflow (`.github/workflows/release.yml`) runs the checks, then creates the git tag `v<version>` and the GitHub release. Packagist reads the new tag through its GitHub integration. The repository has no Packagist secret. The workflow does not call Packagist.

1. Set `VERSION` in `src/FeedbackPilgrim.php` to the new version.
2. In `CHANGELOG.md`, move the lines under `Unreleased` to a new section `## [<version>] - <date>`.
3. Open a pull request with these changes. Merge it.
4. Start the workflow: `gh workflow run release.yml --ref main -f version=<version>`.
5. Replace the notes of the new GitHub release with the section of `CHANGELOG.md`: `gh release edit v<version> --notes-file <file>`.

### First release

Before the first release, submit the repository one time at <https://packagist.org/packages/submit>, with the URL `https://github.com/amedina068/feedbackpilgrim-php`. Log in to Packagist with the GitHub account. Then the Packagist GitHub hook updates the package for each new tag.

## Triage

Each new issue gets the `needs-triage` label. The maintainer reads the issue, adds a type label (for example `bug`, `enhancement` or `question`) and removes `needs-triage`.

## Rules

- Each response is a typed, read-only value object. Do not return raw arrays.
- Do not add a dependency if the PHP standard library can do the job.
- Do not add telemetry.
- Never put a token, an identity secret, a webhook secret or another secret in the code, the tests or the logs.
- The integration tests must stay read-only. They list the posts and the tags only.

## Write the documentation

Write documentation, comments and commit messages in Simple English (ASD-STE100):

- Use short sentences. An instruction has a maximum of 20 words. A description has a maximum of 25 words.
- Use the active voice. Use the imperative for instructions.
- Use one word for one meaning.
- Do not use contractions or semicolons.
- Use "can", "will" and "must". Do not use "should", "may", "might", "could" or "would".

## Report a bug

Open an issue with the bug template. To report a security problem, read [SECURITY.md](SECURITY.md). Do not open a public issue for a security problem.

## Keep the shared test vectors

`tests/Fixtures/*-vectors.json` come from the app with `gen-vectors.php`. Do not edit them by hand. If the app changes the identity hash or the webhook signature, make the vectors again and copy them here.
