# Spark Life Electrical — WordPress build

Full WordPress site for **Spark Life Electrical Contractors** (Frankston / Mornington
Peninsula), built from the original static design in this repo. Custom theme + the
in-house **CC Fields** section plugin + the **MyMomo Connector**, with every service
as a **Services custom post type** entry.

## Structure

```
index.html, css/, js/, assets/     # the original static design — kept as the visual reference
wp-content/
  themes/sparklife-theme/          # the theme
    assets/css/main.css            #   design system, ported and extended for WP
    assets/js/main.js              #   sticky header, drawer, accordion, reveal, count-up, AJAX forms
    cc-fields/sections/*.php       #   markup for all 19 section types (overrides the plugin's own)
    inc/cpt-service.php            #   Services CPT + categories + per-service meta box
    inc/sections.php               #   section-type definitions (ccf_registered_sections filter)
    inc/seed-content.php           #   feeds the seeder from data/content.json
    inc/seo.php                    #   titles, canonical, OG, JSON-LD (Electrician/LocalBusiness)
    inc/icons.php                  #   inline SVG icon set
    data/content.json              #   all page + service content and global variables
  plugins/cc-fields/               # CC Fields — generic section engine + global variables
  plugins/virtual-office-ai-connector/   # MyMomo Connector (folder name must stay this)
deploy.sh                          # lftp SFTP deploy (reads creds from an env file)
```

## How the pieces fit together

**CC Fields** stays generic and auto-updating. Everything site-specific lives in the theme:

- Section **types** are registered by `inc/sections.php` through the `ccf_registered_sections`
  filter, which replaces the plugin's bundled set — so the page editor only offers sections
  this theme can render.
- Section **markup** lives in `themes/sparklife-theme/cc-fields/sections/{type}.php`. The CC
  Fields renderer prefers a theme template over its own, so nothing in the plugin is patched.
- Page **content** comes from `data/content.json` via the `ccf_seed_*` filters.

### Section types

`hero` · `pagehero` · `marquee` · `services_bento` · `services_list` · `why_stats` ·
`process_steps` · `about_badge` · `service_row` · `tick_list` · `reviews` · `service_areas` ·
`quote_form` · `faq` · `gallery_grid` · `text_block` · `embed_map` · `cta_band` · `spacer`

Each takes a **Background** option (white / paper / ink / blue) and a heading with an
optional highlighted phrase, which renders as the blue underlined word from the design.

## Services custom post type

Every service is a `service` post at `/services/{slug}/`. **This is the single source of
truth** — the nav mega-menu, the footer, the home bento grid, the services list, the
marquee and the enquiry-form dropdown all read from it. Unpublish or trash a service and
it disappears from all of them at once, and its page 404s. (Verified: dropping 2 of 16
services took every listing from 16 → 14 with no other edits.)

Each service carries a small meta box (sidebar of the edit screen):

| Field | What it does |
|---|---|
| Tagline | Lead line under the title on the service hero |
| Card summary | Text on cards and grids (falls back to the excerpt) |
| Icon | Icon on cards and in the mega-menu — "Auto" picks one from the title |
| Price from | Optional "From $149" pill |
| Image URL | Fallback when there's no featured image |
| Card badge | Small pill on the card, e.g. "Most booked ⚡" |
| Feature on home page | Renders as the tall dark tile in the bento grid |
| Accent card (blue) | Renders as the blue tile in the bento grid |

Long-form content on a service page is CC Fields sections, same as a page. A service with
**no** sections still renders a complete page — `single-service.php` composes one from the
service's own title, tagline, editor content and featured image, plus the standard quote
form, related services and CTA. So adding a service in wp-admin never leaves a half-built page.

The 16 seeded services are the typical residential/commercial set. Trim whatever doesn't apply.

## Seeded content

9 pages — home, services, about, projects, reviews, service-areas, contact, privacy, terms —
plus 16 services, and 23 global variables. Run **CC Fields → Seed / Rebuild** in wp-admin.
It is idempotent: pages and services are matched on slug + post type and updated in place.

## MyMomo Connector

Version 1.16.1, installed at `wp-content/plugins/virtual-office-ai-connector/` (the folder
name must stay exactly that — the updater keys off the slug).

After activating: **Settings → MyMomo** → copy the API key + site URL into the MyMomo
dashboard. When configuring this site in MyMomo, set:

```
sections_meta_key = _ccf_sections
```

That is how MyMomo writes section updates into CC Fields. Verified working: a `POST` to
`/wp-json/voa/v1/recreate` with that key updated a page's sections and the theme rendered
them immediately.

The connector also ships its own SEO meta (`_voa_seo_*`). `inc/seo.php` detects that and
stands down per-tag so nothing is emitted twice; it also stands down entirely if Yoast or
Rank Math is ever installed.

## Deploy

```bash
./deploy.sh /path/to/sftp.env
```

The env file needs `SFTP_HOST`, `SFTP_PORT`, `SFTP_USER`, `SFTP_PASS` — see
`sftp.env.example`. WPStaq is SFTP-only and lands at the account home with WordPress under
`wordpress/`, so the remote base is `wordpress/wp-content`.

After deploying: activate the theme and both plugins, run **CC Fields → Seed / Rebuild**,
then clear the host's page cache.

## Before this goes live

The static demo used placeholders, and they carried through to the seed data. Update these
in **CC Fields → Global Variables** (or in `data/content.json` before seeding):

- ~~`company_phone` / `company_tel`~~ — set to **0402 028 871**
- `company_abn` — currently **00 000 000 000**
- `company_email` — `info@spark-life.com.au`, confirm it exists and receives mail
- `company_address` — 6 Magnolia Court, Frankston VIC 3199
- `rec_license` — **REC 27391**, confirm it's the real registration number
- `founded_year`, `review_score`, `review_count` — currently 2018 / 4.9 / 200
- `ga4_id` — blank, so no analytics is emitted until it's set
- `facebook_url`, `instagram_url`, `google_reviews_url` — blank

Also outstanding:

- **Job photos.** There are none in the repo, so the Projects page uses written job stories
  and its `gallery_grid` section is empty (it renders nothing until images are added). Drop
  photos into `assets/img/` and fill the gallery section, and add featured images to services.
- **Reviews** are written from the design's placeholders. Replace with real Google reviews
  before launch — they're marked up as `Review` schema, so they need to be genuine.
- **Form delivery.** CC Fields emails via `wp_mail()`. Confirm the host sends mail reliably,
  or point it at an SMTP service.

## CC Fields changes made here

The seeder gained three generic capabilities, needed to seed a custom post type. Bumped to
**1.1.1** — worth folding back into the central `cc-fields` repo:

- `post_type` per definition (defaults to `page`), so CPTs can be seeded alongside pages
- `seo` block → `_ccf_seo_*` post meta (this already existed in the Call The Plumber Guy copy)
- `meta` and `terms` blocks → arbitrary post meta and taxonomy terms

## Local development

There's no docker setup here. To smoke-test locally, WordPress + the SQLite drop-in and
`wp-cli` is enough — symlink `wp-content/themes/sparklife-theme`, `wp-content/plugins/cc-fields`
and `wp-content/plugins/virtual-office-ai-connector` into a WordPress install, activate them,
then run the seeder with:

```bash
wp eval '$m = new Ccf_Migration(); print_r($m->seed());'
```
