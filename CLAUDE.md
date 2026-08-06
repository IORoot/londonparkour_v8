# londonparkour_v8

A `wp-content`-shaped repo: `docker-compose.yml` + `plugins/` + `themes/`. All
real work is in `themes/londonparkour_v8/` — a **classic** WordPress theme
(ACF Pro, PHP templates, Vite + Tailwind v4 + daisyUI). Not a block theme; do
not reach for `theme.json` patterns, Gutenberg blocks or full-site editing.

It is an in-progress port of the `ldnpark2601` Storybook design system
(`/Users/wearebold/Sites/Storybook/ldnpark2601`, clean at `9e0dffc`) into
WordPress. **Fidelity is the product.** See "The one rule that governs
everything" below before writing markup.

## Read these before doing anything

| File | What it owns |
|---|---|
| `themes/londonparkour_v8/docs/HANDOFF.md` | **Start here.** Current state, what is done, what is left, and why each deferred item was deferred |
| `themes/londonparkour_v8/docs/PORT-BRIEF.md` | The porting contract. Hand it **verbatim** to every porting agent |
| `themes/londonparkour_v8/docs/PORT-FINDINGS.md` | Discrepancies found and deliberately not fixed, numbered §1–§20 |
| `themes/londonparkour_v8/bin/README.md` | The content contract — the database is disposable, content is code |
| `~/.claude/plans/review-handoff-md-then-plan-mighty-garden.md` | The approved plan: phases A–D, batches B1–B6, delegation model |

`git log` carries the reasoning for every decision and several commits correct
older claims in those docs. Read it before trusting a doc that looks stale.

## The one rule that governs everything

**Tailwind class strings are copied byte-for-byte from the Storybook source.**
They are signed-off design decisions, not code to improve. A "tidier" class
string is a defect.

This inverts the usual instinct, so it is worth stating what it forbids:

- Do **not** run `/simplify`, `/ponytail-review`, `impeccable`,
  `design-taste-frontend`, `emil-design-engineering` or `frontend-design`
  over `parts/`, `blocks/` or page templates. They make design judgements this
  port exists to prevent. They are fine on `app/`, `inc/` and `bin/` —
  WP-authored code, which rule 1 does not protect.
- Byte-exact duplication between a ported file and its source is **correct**.
  Duplication between two *ported* files is a promotion candidate, and
  promotion is a coordinator decision, never an agent's (PORT-BRIEF rule 3a).
- If no design token fits, **report the gap**. Never invent a colour, a copy
  string, or an `href` the source does not have.

A class name is never built from fragments — Tailwind v4 text-scans source, so
`class="btn-<?php echo $v; ?>"` compiles to nothing and fails silently. Whole
literal strings, from a PHP lookup array. `bin/audit-reuse.sh` enforces this.

## Environment

```bash
docker compose up -d                       # from the repo root
themes/londonparkour_v8/bin/bootstrap.sh   # idempotent
themes/londonparkour_v8/bin/wp lp seed     # demo content + /blocks-qa/
```

- Site **http://localhost:8102**, admin `admin` / `admin`
- **WP-CLI is not on the host.** It runs in the `cli` sidecar — always
  `bin/wp <args>`, never bare `wp`
- `docker compose down -v` destroys the database. That is the supported
  recovery path, not a disaster — bootstrap + seed rebuild in under a minute
- The database is **never** committed or shared. Content lives in
  `bin/demo-content/`, `bin/demo-media/` and each block's `example.json`

## Key mechanisms

- `lp_part( 'elements/button', $args )` — the only way to emit an element.
  One file per piece of HTML
- `lp_icon( $id, $classes )` — sprite icons. Never a raw `<svg>` outside
  `parts/brand/`
- `lp_classes( ...$strings )` — joins **whole** literal class strings
- `lp_render_sections()` — dispatches ACF Flexible Content rows to
  `blocks/{layout}/{layout}.php`. No registry to edit
- `bin/wp lp render <layout>` — render one block from its `example.json`
  without touching the database
- `bin/wp lp part <slug> [--args=<json>]` — the same for any partial
- Copy defaults live in the partial (`$args['x'] ?? '…'`), never in
  `fields.php`. Control defaults are the exception and do belong there

## Verify — no "done" without this output confirmed

```bash
cd themes/londonparkour_v8
php -l <each file touched>
bash bin/audit-reuse.sh          # must print ✓
bin/wp lp acf:build --check      # must print Success
bin/wp lp render <layout>        # new blocks only
npm run build
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8102/<page>/
```

`bin/audit-reuse.sh` is verified by injection — feed it a raw `<img>` or a
built class name and it fails; clean code passes. Trust it.

**Markup passing is not data flow passing.** `lp render` against an
`example.json` proves markup only. Every template needs a real seeded page,
curled, and its rendered output inspected. That distinction is how
PORT-FINDINGS §13 found five genuine bugs.

## Working with agents

The main loop coordinates and gates; a Sonnet subagent does each page port,
handed `docs/PORT-BRIEF.md` verbatim. Use the `/port-page` skill — it encodes
the loop. Two rules that keep it working:

- **Agents report shapes, they never promote them.** Two agents each inventing
  a different `classes-header-cluster` is worse than the duplication it
  replaces.
- **Never accept an agent's "done" on its word.** Re-run the gates yourself
  and render the page.

## Out of scope

Three.js, DOMPurify, and the licensing items in PORT-FINDINGS §5 (Scope Trial
font, `@tailwindplus/elements`, ACF Pro) — pre-launch commercial matters, not
port work.
