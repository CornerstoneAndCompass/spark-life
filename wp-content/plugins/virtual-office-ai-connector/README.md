# MyMomo - Connector (WordPress plugin)

The reusable WordPress plugin that connects any client's WordPress site to the
**MyMomo** app. Drop it on a site, generate an API key, pair it in the
MyMomo dashboard, and you can manage the site's content, SEO, sections,
media and menus remotely.

This repo is the **deployable / reusable copy** of the plugin so it can be added
to new client sites quickly. (Dev source of truth lives in the `Virtual-Office`
monorepo at `wordpress-plugin/`; this copy tracks the latest release.)

- **Current version:** 1.13.0
- **Author:** Cornerstone & Compass
- **Slug / folder name:** `virtual-office-ai-connector`

## What it does

- **Secure REST bridge** - per-site API key (`X-VOA-Key` header), rate limiting
  (60 req/min), origin checks. ~30 endpoints for posts, pages, media, menus,
  options, theme/customizer, plugins, WooCommerce, etc.
- **Built-in SEO** - meta title/description, Open Graph, and JSON-LD schema via a
  Yoast-style meta box (`_voa_seo_*` post meta). No third-party SEO plugin needed;
  it automatically backs off if Yoast or Rank Math is active.
- **Page-builder agnostic section updates** - MyMomo sends the target
  `sections_meta_key` with each page update, so the connector writes sections to
  whatever fields plugin the site uses. See "Per-site config" below.
- **Self-updating** - checks the central worker (`VOA_UPDATE_URL`) for new
  releases, so every connected site stays current automatically.
- Single PHP file, no external dependencies, multisite-compatible.

## Install on a site

**Option A - upload the zip** (easiest)
1. Build it: `./build.sh` → produces `dist/virtual-office-ai-connector.zip`.
2. WP admin → **Plugins → Add New → Upload Plugin** → choose the zip → **Activate**.

**Option B - clone/copy the folder**
- Copy this folder into the site's `wp-content/plugins/virtual-office-ai-connector/`
  (the folder name must be exactly `virtual-office-ai-connector`).

Then: **Settings → MyMomo** → copy the **API Key** + **Site URL** →
paste into the MyMomo dashboard → test the connection.

## Per-site config (sections / CC Fields)

Each site uses its own fields/section plugin, identified by a post-meta key. When
configuring the site in MyMomo, set the **sections meta key** so MyMomo can
push section/content updates into the right place:

| Site | Fields plugin | `sections_meta_key` |
|---|---|---|
| Reliant Business Insurance | Reliant Fields | `_rbf_sections` |
| Cali Clean | Cali Fields | `_ccf_sections` *(confirm in that plugin)* |

The connector itself is generic - it just writes the array it's given to the key
it's told to use, so no code changes are needed per site.

## Notes

- Shared infra (same for every client site): worker `api.virtualofficeai.com.au`,
  updater `cc-api.benjamin-a2d.workers.dev`. Nothing client-specific is hardcoded.
- API keys are stored in `wp_options` and can be regenerated from the settings page.
