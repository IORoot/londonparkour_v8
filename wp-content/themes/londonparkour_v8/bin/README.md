# bin/

Tooling. Everything here runs from `themes/londonparkour_v8/`.

## The rule

**The database is disposable. Never share it. The content definition is code.**

There is no SQL dump in this repository and there should never be one. `.sql`
files do not merge — auto-increment IDs and serialized data guarantee a conflict
no one can resolve by hand — and sharing a database forces an out-of-band
protocol about who is allowed to edit it. This project already made the opposite
bet in Phase 1: ACF field groups are generated from PHP so that the database is
never authoritative. Content follows the same rule.

Two developers do not need the same database. They need the same content
definition, in files that merge.

## Getting a working site

```bash
docker compose up -d          # from the repo root
bin/bootstrap.sh              # install, activate, build field groups
bin/wp lp seed                # demo content + the QA page
npm install && npm run build  # assets — assets/dist is gitignored
```

Site: http://localhost:8102 · admin `admin` / `admin` · QA page: `/blocks-qa/`

## Day to day

| Task | Command |
|---|---|
| Reset demo content | `bin/wp lp seed --fresh` |
| Nuke and rebuild | `docker compose down -v`, then the four commands above |
| Changed a field group | edit PHP → `bin/wp lp acf:build --sync` → commit `acf-json/` |
| Sync ACF to wp-admin | `bin/sync-acf-to-admin.sh` or `bin/wp lp acf:build --sync` |
| Changed a demo image | replace the file in `bin/demo-media/` → `bin/wp lp seed --fresh` → `bin/wp media regenerate --yes` |
| Verify a block in isolation | `bin/wp lp render <layout>` |
| Verify a partial | `bin/wp lp part components/<name>` |

`docker compose down -v` destroys the database. That is the supported recovery
path, not a disaster — bootstrap and seed rebuild it in under a minute.

## Where demo content lives

| Path | Owns |
|---|---|
| `blocks/*/example.json` | a block's manual-mode content |
| `blocks/*/example.media.json` | field dot-path → demo image filename |
| `bin/demo-content/*.json` | CPT records, native `post` records, and taxonomy terms |
| `bin/demo-media/*.jpeg` | the photographs, 2132×1200 |

`post.json` is the native blog type rather than a CPT, so its records carry
`date`, `excerpt` and `content` in the post row itself instead of in `fields`.
All three are optional and any record may use them.

**The Storybook's blog prose is truncated at source.** `BlogIndex`'s Version 7
excerpt and every string in `BlogDetail`'s `DEFAULT_BODY` end in `…` — the
design was built to prove layout, not to carry copy. Those strings are seeded
exactly as the source has them, ellipsis included, so a visual diff against the
Storybook matches. Do not "complete" them: inventing copy is the failure the
Port Brief warns about, and real article text is a client dependency.

**Anything you author in wp-admin that matters moves into these files, or it
does not exist for the other developer.** That is the whole contract. If you
tweaked a demo class in the admin and want it kept, edit
`bin/demo-content/lp_class.json` to match and re-seed.

Conflicts resolve as ordinary JSON and PHP merges, because that is all they are.

Records reference each other **by slug**, not by ID — a class names
`"location": "peckham-rye"` and seed resolves it. IDs differ between machines;
slugs do not.

Demo images are 2132×1200 for a reason: `lp_wide_lg` is 1920×1080 and
`lp_portrait_lg` is 1112×1200, so a smaller source makes WordPress skip the
largest crop and thins every srcset. If you swap one in, clear both bars.

## Safety

Every post `wp lp seed` creates carries `_lp_seed` post meta. Seed only ever
updates or deletes posts carrying that marker, so a page you wrote by hand
cannot be touched — even if its slug collides with a demo slug. In that case
seed warns and skips. `--fresh` deletes only marked records.

## The scripts

| File | What it does |
|---|---|
| `bootstrap.sh` | Idempotent install: core, theme, plugins, permalinks, menus, front page, posts page, field groups. Never touches content. |
| `wp` | WP-CLI wrapper. WP-CLI is not on the host; it runs in the `cli` sidecar. Always use this, never bare `wp`. |
| `audit-reuse.sh` | Fails the build on hand-rolled markup — a raw `<svg>`, a raw `<img>`, or a built class string. Verified by injection; trust it. |
| `demo-content/` | CPT records and terms, read by `wp lp seed`. |
| `demo-media/` | Demo photographs, read by `wp lp seed`. |

`wp lp seed` itself is not here — it needs WordPress bootstrapped, so it lives
in `app/setup/seed.php` like every other `wp lp *` command.

## The QA page

`/blocks-qa/` holds sixteen rows: every block once with its `example.json`
content, and the six source-backed blocks a second time reading real CPT
records, each twin directly after its manual counterpart.

That pairing is the point. The demo CPT records are derived from the same
`source_manual` data, so the two rows should read almost identically — any
visible divergence is a projection bug you can see rather than reason about.
It is how §13 of `docs/PORT-FINDINGS.md` was found. Known, accepted divergences
are listed at the end of that section; check there before filing a bug.

The page is generated by iterating `blocks/*/`, so a block added tomorrow
appears on it without anyone editing seed.
