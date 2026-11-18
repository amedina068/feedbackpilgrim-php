# Security policy

## Supported versions

| Version | Supported |
|---|---|
| 0.1.x | Yes |

## Report a security problem

Report security problems in private, through GitHub security advisories. Do not open a public issue, a discussion or a pull request.

1. Open the **Security** tab of the repository on GitHub.
2. Click **Report a vulnerability**.
3. Write the problem, the affected versions and the steps to reproduce it.

GitHub sends the report to the maintainers only. The maintainers reply in the advisory.

## Secrets

The SDK uses three secrets: the API token, the identity secret of the client and the signing secret of a webhook.

- Keep the three secrets on the server.
- Never put the identity secret in browser code or in a mobile app. Send only the identity hash to the browser.
- The API token can read and change all the data of its client. It does not expire.
- If a secret is exposed, revoke it on your Feedback Pilgrim instance. Then create a new one.
