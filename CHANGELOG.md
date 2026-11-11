# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/).

## 1.1.0 - 2026-11-08

### Added
- Administration settings > Lineup: connect by pasting a connection code created in Lineup (`lnc1.…`). The secret is stored as a sensitive app config value and never shown again.
- Tokens of a connection carry the connection id (`kid` header) and the Nextcloud base URL (`nextcloud_url`).
- Hint page when Lineup is not connected yet, with a link to the settings for admins.
- German translations (informal and formal).
- First release for the Nextcloud App Store, with automated signed releases.

### Changed
- Requires Nextcloud 33 to 35.
- Default Lineup address is https://lineup.insidertips.ch.
- Uses PHP attributes for routes and typed app config.
- The token endpoint now requires the CSRF request token.

### Kept
- Existing installs configured via `occ` (`jwt_secret` + `frontend_url`, no `connection_id`) keep working and sign tokens exactly as in 1.0.0.

## 1.0.0

### Added
- Initial version: embeds Lineup in an iframe and signs the Nextcloud user in with a short-lived HS256 token (configured via `occ`).
