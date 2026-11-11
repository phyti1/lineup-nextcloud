# Lineup for Nextcloud

Embeds [Lineup](https://lineup.insidertips.ch) in Nextcloud and signs the
logged-in Nextcloud user in automatically (single sign-on).

Lineup is a hosted planning tool for clubs and teams, for example rowing clubs:
members sign up for trainings and events, coaches see who is coming, plan
training groups and keep track of attendance and absences.

## Requirements

- Nextcloud 33, 34 or 35
- A free account and organization on [lineup.insidertips.ch](https://lineup.insidertips.ch)
- Every Nextcloud user who should use Lineup needs an **email address** in
  Nextcloud (it links the Nextcloud user to the Lineup profile)

## Installation

Install **Lineup** from the Nextcloud App Store: `Apps > Integration > Lineup > Download and enable`,
or with occ:

```bash
occ app:install lineup
```

## Connect to Lineup

1. In Lineup, open your organization page and create a **Nextcloud connection**.
   Lineup shows a connection code starting with `lnc1.`.
2. In Nextcloud, open `Administration settings > Lineup`, paste the code and
   click **Connect**.
3. Lineup appears in the app menu for all users.

The page then only shows "Connected to &lt;url&gt;". To switch to another
connection, paste a new code; **Remove connection** disconnects Nextcloud.

Until a connection exists, users see a short hint; admins get a link to the settings.

## How it works

```
Nextcloud page (PageController) ──renders──► <iframe src="<lineup>/?embed=nc">
        ▲ postMessage(token)                        │  Lineup frontend
        │ (origin- and source-checked)              │
   js/embed.js ◄── absencetool-ready / -request-token ┘
        │
   GET /apps/lineup/token   (Nextcloud session + CSRF token → HS256 JWT)
        │  signed with the connection secret, header kid = connection id
        ▼
   Lineup backend  looks up the connection by kid, verifies the signature
                   → maps the user by email
```

Token (connection mode):

| Part    | Content |
|---------|---------|
| Header  | `{"alg":"HS256","typ":"JWT","kid":"<connection id>"}` |
| Payload | `iss` = `nextcloud-lineup`, `iat`, `exp`, `email`, `name`, `nextcloud_uid`, `nextcloud_url` |

## Security model

- The connection code contains a secret shared only between this Nextcloud
  and Lineup. It is stored as a **sensitive** app config value, never rendered
  back to the settings page and never sent to the browser.
- The browser only receives short-lived signed tokens (default 10 minutes);
  the embedded app asks for a fresh one via `postMessage` when needed.
- `postMessage` is restricted to the configured Lineup origin on both ends.
- The token endpoint requires a logged-in user and the CSRF request token.
  The settings endpoints are admin-only and CSRF-protected.
- The iframe has no `sandbox` attribute on purpose: Lineup opens a popup to link
  accounts when needed.
- Lineup decides which Nextcloud instances may frame it; the hosted service
  handles this per connection.

## Configuration via occ

| Key             | Meaning |
|-----------------|---------|
| `frontend_url`  | Lineup address, default `https://lineup.insidertips.ch` |
| `connection_id` | Connection id from the code (sent as `kid`) |
| `jwt_secret`    | Shared secret (sensitive) |
| `token_ttl`     | Token lifetime in seconds, default `600` |

### Legacy setup (self-hosted Lineup with a global secret)

Installs from before 1.1.0 keep working unchanged: if `jwt_secret` is set but
`connection_id` is not, tokens are signed exactly as in 1.0.0 (no `kid`,
`iss` = `nextcloud-absencetool`).

```bash
occ config:app:set lineup frontend_url --value="https://lineup.example.com"
occ config:app:set lineup jwt_secret --value="<same as the backend's NEXTCLOUD_JWT_SECRET>" --sensitive
occ config:app:set lineup token_ttl --value="600"
```

Pasting a connection code in the settings replaces a legacy configuration.

## Development

```bash
make lint       # php -l and info.xml validation against the App Store schema
make appstore   # build/artifacts/lineup.tar.gz (top-level folder lineup/, runtime files only)
```

The repository root is the app folder. For a local test, mount or copy it as
`custom_apps/lineup` into a Nextcloud instance and run `occ app:enable lineup`.

## Releasing

1. Bump `<version>` in `appinfo/info.xml` and add a section to `CHANGELOG.md`.
2. Create a GitHub release with the tag `v<version>` (e.g. `v1.1.0`).
3. The workflow `.github/workflows/appstore-build-publish.yml` builds the
   tarball, signs it, attaches it to the release and uploads it to the App Store.

Required repository secrets: `APP_PRIVATE_KEY` (the app signing key) and
`APPSTORE_TOKEN` (from apps.nextcloud.com). The signed certificate must exist in
[nextcloud/app-certificate-requests](https://github.com/nextcloud/app-certificate-requests)
under `lineup/lineup.crt`.

## License

AGPL-3.0-or-later, see [COPYING](COPYING).
