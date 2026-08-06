# londonparkour_v8

Classic-hierarchy WordPress theme, ported from the `ldnpark2601` Storybook
design system. Page structure comes from a single ACF Flexible Content field
whose layouts are the folders under `blocks/`; Gutenberg is disabled.

## Quick start

```bash
docker compose up -d          # from the repo root
bin/bootstrap.sh
bin/wp lp seed
npm install && npm run build
```

http://localhost:8102 · `admin` / `admin` · QA page at `/blocks-qa/`

**Read `bin/README.md` before touching content or the database.** The short
version: the database is disposable and never shared; demo content is code.

## Layout

| Path | Holds |
|---|---|
| `app/setup/` | CPTs, ACF field definitions and generation, seeding, theme supports |
| `app/includes/` | The Flexible Content dispatcher, menus, HTML helpers |
| `blocks/<slug>/` | One block: markup, `fields.php`, `example.json` |
| `parts/elements/` | One file per piece of HTML. The only way to emit markup. |
| `parts/components/` | Composed pieces built from elements |
| `parts/site/` | Nav and footer |
| `templates/` | WP page templates — a `Template Name:` header, one level deep |
| `bin/` | Tooling and demo content — see its README |
| `docs/` | The port's contract, findings and history |

## Rules that are enforced

- **`lp_part( 'elements/button', $args )` is the only way to emit an element.**
  `bin/audit-reuse.sh` fails the build on hand-rolled markup.
- **Never a raw `<svg>`** outside `parts/brand/` — use `lp_icon( $id, $classes )`.
- **`lp_classes()` joins WHOLE literal class strings**, never builds one.
  Tailwind v4 text-scans source, so a built string is not compiled.
- **ACF field groups are generated.** Edit PHP, run `bin/wp lp acf:build`.
  Editing `acf-json/` by hand is overwritten.
- **Copy defaults live in the partial**, in its `$args['x'] ?? '…'` fallback.
  Control defaults (`source`, `source_limit`, `spacing_*`) live in `fields.php`.

## Verify anything

```bash
php -l <file>
bash bin/audit-reuse.sh          # must print ✓
bin/wp lp acf:build --check      # must print Success
bin/wp lp render <layout_name>   # one block, no database
bin/wp lp part <slug>            # one partial
npm run build
```

`lp render` proves markup, not data flow. A block that reads a CPT also needs
checking against seeded records — that is what `/blocks-qa/` is for. Note that
`--args` REPLACES a block's fixture rather than merging into it, so comparing
source modes is done on the page, not on the command line.

Render-sweep everything at once:

```bash
for d in blocks/*/; do l=$(basename "$d" | tr '-' '_'); bin/wp lp render "$l" >/dev/null || echo "FAIL $l"; done
for f in parts/components/*.php; do bin/wp lp part "components/$(basename "$f" .php)" >/dev/null || echo "FAIL $f"; done
```

## Documentation

| File | What it owns |
|---|---|
| `docs/PORT-BRIEF.md` | The porting contract. Hand it verbatim to any agent. |
| `docs/PORT-FINDINGS.md` | Discrepancies found, and which were deliberately not fixed |
| `docs/CONSOLIDATION.md` | The shared-atom analysis behind `parts/` |
| `docs/HANDOFF.md` | Current state and what remains |
| `docs/specs/`, `docs/plans/` | Design and implementation records |
