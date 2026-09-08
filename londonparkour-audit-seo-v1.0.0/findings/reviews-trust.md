# Review / rating markup — findings

Owner: coordinator. This crosses schema, content and trust, so it is recorded
separately. Verified by WP-CLI and by extracting **visible** text (scripts and
styles removed) from the rendered homepage.

## The good news first

The `aggregateRating` is **real, not fabricated**. That was the thing worth
checking, and it passes:

- 42 published `lp_testimonial` records.
- **Zero** are marked `_lp_seed`, so these are genuinely imported reviews, not
  demo fixtures the seeder invented.
- Ratings: 41 × 5-star, 1 × 1-star. Mean = `(41×5 + 1) / 42` = 4.905, rounds to
  **4.9**. `lp_seo_aggregate_rating()` computes it correctly from the records
  and returns `null` when there are none.
- A rating **is** visible on the homepage, in the hero: `4.9 ★ (43)`.

So this is not the "invisible or invented review markup" failure mode. What
follows is narrower, but two of the four items are still worth fixing before
launch.

---

## High

### R1 — Visible review count (43) disagrees with the marked-up count (42)

Homepage visible text (hero band):

```
2,400+ TRAINED   11 YEARS   3 LONDON SITES   4.9 ★ (43)
```

Homepage JSON-LD:

```json
"aggregateRating": {
  "@type": "AggregateRating",
  "ratingValue": 4.9, "bestRating": 5, "worstRating": 1,
  "reviewCount": 42
}
```

**43 on the page, 42 in the markup.** Google's structured-data policy requires
the marked-up rating to match what the user sees. A one-off discrepancy is
unlikely to trigger a manual action on its own, but it is exactly the kind of
mismatch that invalidates a rich result, and it will drift further every time a
review is added, because the two numbers come from different places:

- Visible: a hand-typed string in the hero block's ACF `rating` field.
- Schema: computed live from the `lp_testimonial` post count.

The `★` glyph and the count are baked into one freeform string, so the visible
number can never track the data.

**Fix:** make the hero's rating display derive from the same source as the
schema. `lp_seo_aggregate_rating()` already returns `ratingValue` and
`reviewCount` — expose a small helper (e.g. `lp_rating_display()`) that both
the hero block and the JSON-LD read. Keep the ACF field as an override for the
case where the owner wants to quote a Google rating instead of on-site
testimonials, but then the schema must quote the same figure.

Note the design constraint: the hero's rating string is currently ACF content,
not a signed-off Tailwind class string, so changing how the *value* is sourced
does not touch the ported markup.

**Falsifiable check:** the integer in the hero's visible rating string equals
`reviewCount` in the page's JSON-LD, asserted in a test. Add a review and
confirm both move together.

### R2 — The hardcoded fallback claims 312 reviews

`blocks/hero/hero.php:68`:

```php
$lp_rating = (string) ( $args['rating'] ?? '4.9 ★ (312)' );
```

If the ACF `rating` field is ever empty — a new page using the hero block, a
re-seed, an editor clearing the field — the page will publicly claim **312
reviews** while the schema says 42. That is a fabricated trust signal shipped by
default.

This is a copy default living in the partial, which is the repo's documented
convention, so the pattern is right; the *value* is the problem. `312` appears
to be carried over from the Storybook's placeholder data.

**Fix:** the fallback should be empty (render no rating) or derived, never a
plausible-looking invented number. Rendering nothing is the safe default for a
trust claim.

**Falsifiable check:** clear the hero `rating` field on a page and confirm no
rating is displayed, rather than `4.9 ★ (312)`.

---

## Medium

### R3 — `aggregateRating` is emitted on every page, but the rating is only visible on the homepage

The `aggregateRating` lives on the organisation node in `lp_seo_graph()`, and
that node is emitted in the `@graph` on **every** public URL. The visible
rating exists only in the homepage hero.

So `/classes/`, `/docs/`, `/blog/`, `/coupons/`, the 609 tutorials and the
location pages all carry a marked-up 4.9/42 rating with nothing on the page to
back it. Google's guidance is that review markup should be on the page the
reviews relate to and should reflect visible content.

**Fix:** two defensible options — (a) restrict `aggregateRating` to the
homepage and any page that actually renders the testimonials block, by checking
for that block before attaching it in `lp_seo_organization_node()`; or (b) show
the rating in the footer site-wide so the claim is always substantiated. Option
(a) is the lower-risk change.

**Falsifiable check:** every URL whose JSON-LD contains `aggregateRating` also
contains a visible rating in its rendered text.

### R4 — Six testimonials have empty bodies but count toward `reviewCount`

6 of the 42 `lp_testimonial` records have no `post_content` at all — e.g. `#56896`
("LU") and `#56894` ("Emilie Lindkvist") have a 5-star rating and an author but
no review text.

They are real records with real ratings, so counting them is arguably
legitimate — a star rating without a written review is a normal thing. But they
cannot be displayed, so they widen the gap between "reviews we show" and
"reviews we claim".

**Fix:** decide the rule and apply it consistently in
`lp_seo_aggregate_rating()`. Counting rating-only reviews is fine; just make it
deliberate and make R1's visible figure use the same rule.

**Falsifiable check:** `reviewCount` equals the count produced by one
documented rule, asserted in a test.

---

## Low

### R5 — The testimonials block hardcodes five stars regardless of the record's rating

`blocks/testimonials/testimonials.php:128-131` and `157-160`:

```php
<span class="flex items-center gap-0.5" aria-label="5 out of 5 stars">
<?php for ( $lp_star = 1; $lp_star <= 5; $lp_star++ ) {
        lp_icon( 'icon-star', 'w-3 h-3 text-accent' );
```

The loop always draws five filled stars and the `aria-label` always says
"5 out of 5 stars", ignoring the `rating` ACF field. The block's docblock says
it selects 5-star testimonials, so in practice the display is currently
accurate — but the one 1-star review in the data would render as 5 stars if it
were ever selected, and screen-reader users would be told the wrong value.

Low severity because the current query filters to 5-star quotes. Recording it
because it is the mechanism by which R1 and R4 would get worse.

**Fix:** drive the star count and the `aria-label` from the record's `rating`
field. The class strings stay byte-identical; only the loop bound and the label
string become dynamic — and per the repo's Tailwind rule, note that the
`aria-label` is not a class name, so building it dynamically is safe.

**Falsifiable check:** render a 1-star testimonial and confirm one filled star
and `aria-label="1 out of 5 stars"`.
