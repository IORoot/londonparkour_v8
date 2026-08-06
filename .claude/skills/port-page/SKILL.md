---
name: port-page
description: Port one page from the ldnpark2601 Storybook into this WordPress theme — dispatch the porting agent, run the gates, seed a real page, and accept or reject the result. Use when porting any remaining page template (ClassesMap, ClassDetail, TutorialsIndex, TutorialsSeries, TutorialDetail, Contact, DocsFaq), or any future Storybook page. Triggers on — port a page, port ClassDetail, next template, B4, B5, B6, finish the port.
---

# Porting one page

You are the **coordinator**, not the porter. A Sonnet subagent writes the
template; you make every decision reserved to the coordinator, then gate the
result. Never accept an agent's "done" on its word.

Run this once per page. Pages within a batch are independent — dispatch 2–4
agents in parallel, then gate each returned port separately.

## 1. Decide before dispatching

The agent sees one file; you see all of them. Settle these first so it never
has to guess. Record each in the dispatch prompt.

- [ ] Read the page's row in the plan's B-table
      (`~/.claude/plans/review-handoff-md-then-plan-mighty-garden.md`) and its
      `docs/HANDOFF.md` batch note. Both carry page-specific decisions already
      taken — pass them through.
- [ ] Read the Storybook source end to end:
      `/Users/wearebold/Sites/Storybook/ldnpark2601/src/stories/…`. Note every
      `import` and every `data-component="…"` — those values are the porting
      checklist and the QA grep target.
- [ ] `ls -R themes/londonparkour_v8/parts/` — confirm each import already has
      a part. The dependency closure was verified closed, so anything missing
      is a finding, not a licence to port from the Storybook.
- [ ] Decide any **promotion**. A shape appearing in 3+ files gets promoted to
      `parts/` by you, before dispatch. One appearing twice gets ported inline
      and reported. Agents never promote.
- [ ] Decide the **data model**: native WP data wherever WordPress has a home
      for the value; ACF only for what it does not. A body keyed to per-section
      ids is a repeater with ids *derived* via `sanitize_title()`, never
      `the_content()` and never ids stored twice (precedent: `single.php`,
      `templates/legal.php`).
- [ ] Check `blocks/` before agreeing to any new markup — B6's sections are
      Flexible Content blocks, not template markup.

## 2. Dispatch

One Sonnet agent per page. The prompt must contain:

1. `themes/londonparkour_v8/docs/PORT-BRIEF.md` **verbatim** — read it and
   paste it, do not summarise or link it.
2. The source path and the destination path.
3. Every decision from step 1, stated as a decision, not a question.
4. The verify block from step 3, with the instruction to run it before
   reporting done.

Add, verbatim: *"Do not invent a colour, a copy string, or an href the source
does not have. Do not create a new file under `parts/`. If you find a shape
that looks reusable, port it inline as the source has it and report it — say
which shape and what you would call it."*

## 3. Gate the returned port — run these yourself

```bash
cd themes/londonparkour_v8
php -l <each file the agent touched>
bash bin/audit-reuse.sh          # must print ✓
bin/wp lp acf:build --check      # must print Success
bin/wp lp render <layout>        # new blocks only
npm run build
```

Then the checks the gates cannot make. **Markup passing is not data flow
passing** — PORT-FINDINGS §13.

- [ ] **Seed a real page.** Add it to `bin/demo-content/` and, for a page
      template, to `lp_seed_template_pages()` with `_wp_page_template` set.
      `bin/wp lp seed --fresh`, then
      `curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8102/<slug>/`
      → `200`.
- [ ] **Read the rendered HTML**, not just the status code. `curl` it and look
      at the output.
- [ ] **Diff class strings against the source.** Every class attribute matches
      the Storybook. Any class the agent could not carry over must be reported
      with a reason, never silently dropped or substituted.
- [ ] **Landmarks**: exactly one `<main>`, nav and footer outside it, the
      `<h1>` inside. The Storybook's `pageLandmarks.test.js` is the spec.
- [ ] **Escaping**: `esc_html()` / `esc_url()` / `esc_attr()` /
      `wp_kses_post()` on every output. The deterministic gates are blind here.
- [ ] **Query safety** on any new `WP_Query` — bounded `posts_per_page`, no
      unsanitised query args, no N+1 in a loop.
- [ ] **Render-sweep** if any part changed:
      ```bash
      for f in parts/components/*.php; do
        bin/wp lp part "components/$(basename "$f" .php)" >/dev/null || echo "FAIL $f"
      done
      ```
- [ ] **Colour check** against the Storybook's `docs/phase7/surface-axis.md` —
      canonical, never re-derived. On a dark band use the `neutral-content`
      family; on the page ground the signal role is `text-accent`, not
      `text-primary` (1.54:1). No arbitrary colour utilities.

Reject and re-dispatch on any failure. Do not fix the agent's work silently —
the failure is signal about the brief.

## 4. Accept

- [ ] One commit for this page. The message carries the reasoning for every
      judgement call — that log is how later sessions learn why something was
      done.
- [ ] Append any finding to `docs/PORT-FINDINGS.md` with its measurement.
      A finding without a measurement is a guess.
- [ ] Update the page's row in `docs/HANDOFF.md` and the plan's B-table.

## Deferred to Phase D — do not do it per page

Four-theme visual diff against the running Storybook, the stale-Tailwind-scan
console probe, and the consolidation pass all run once at the end. Per-page
verification stops at the curled HTML.
