# Visual / above-fold findings — staging.londonparkour.com

**Method:** Playwright Chromium, `http_credentials` (staging user; password not recorded here), UA Chrome/128. Viewport screenshots only (not full page). Saved 2026-09-11.

| File | Viewport | Status |
|---|---|---|
| `screenshots/home-mobile-390.png` | 390×844 | 200 |
| `screenshots/home-desktop-1440.png` | 1440×900 | 200 |
| `screenshots/classes-mobile-390.png` | 390×844 | 200 |
| `screenshots/classes-desktop-1440.png` | 1440×900 | 200 |
| `screenshots/contact-mobile-390.png` | 390×844 | 200 |
| `screenshots/contact-desktop-1440.png` | 1440×900 | 200 |

No horizontal overflow on any of the six. H1 is in the fold on all six.

---

## Homepage

**H1 (both viewports):** “the world is your playground.”

**Above-fold CTA**

| Viewport | Primary | Secondary |
|---|---|---|
| Mobile 390 | Yellow `BOOK YOUR FIRST CLASS — £15` (opens `lp-booking-drawer`, not an `<a>`) | “NEXT CLASS” board → same drawer. “Watch a session ↗” is a YouTube text link, low contrast on the photo. |
| Desktop 1440 | Same £15 button + next-class board side by side | Nav `FIND A CLASS`. Proof row at the bottom of the hero: “2,400+ TRAINED · 11 YEARS · 3 LONDON SITES · 4.9 ★ (43)”. |

**What works:** One clear paid-class CTA, price in the label, next session (Adult Beginners East / Old Street / £15 / 18 left) is in the fold on both breakpoints. Hero photo is strong on desktop.

**Issues**

1. **H1 is a slogan, not a query.** Title tag is “London Parkour \| Practical Movement Training & Classes”. The visible H1 does not say parkour, classes, or London. Fine for brand; weak for “parkour classes london” (a live GSC head term). Eyeballs get the kicker “01 - PRACTICAL MOVEMENT CLASSES / EST 2018” in 11px yellow — easy to miss.
2. **Mobile CTA stack is cramped.** H1 + body + button + watch link + full next-class board + “↓ SCROLL” all sit in 844px. The board’s “Reserve a place” row is at the fold edge; the proof stats (2,400+ / 4.9) are **below** the fold on mobile.
3. **“Watch a session” contrast.** White/70% label on a mid-tone concrete photo. Desktop is worse because it sits over sky+brick with no plate.
4. **GPS deco** “N 51.5074° / W 0.1278°” links to `https://google.com`, not a Maps/place URL.
5. **Closing-band CTA hops to `/classes/`.** `href="/book/"` **301s to `/classes/`** (recheck 2026-09-11). Hero still uses the drawer. Copy there also says beginners are “Tuesday and Thursday at 18:30 in Vauxhall”, which does not match the live board (Saturday Old Street) or location schema (Vauxhall Sundays).

---

## /classes/

**H1:** “This week's sessions.”

**Title:** “Parkour Classes in London \| Weekly Timetable” (city + service are in the title, not the H1).

**Above-fold CTA**

| Viewport | What is in the fold |
|---|---|
| Mobile 390 | Breadcrumb + “MAP VIEW ↗”. H1 + timetable explainer. Four view tabs (Agenda / Map / Private / Workshops). Week switcher (Week 37). **No book button. No class card.** First session (Wed Evening Intermediate) is below the fold. |
| Desktop 1440 | Nav `FIND A CLASS` (opens booking drawer). Week switcher. First session card just enters the fold: Old Street · Evening Intermediate Outdoor · £15. |

**Issues**

1. **Mobile has no booking CTA above the fold.** The only yellow controls are week prev/next. A “near me / this week” searcher must scroll to see a class, then again to book.
2. H1 is diary language (“this week”), not “Parkour classes in London”. The supporting line does name £15, cap of twelve, and live spaces — that copy is doing the SEO/conversion job the H1 is not.
3. Workshops tab shows “1 DATES” (grammar).
4. Agenda claims “80 SESSIONS” in the tab while the week label says “5 CLASSES THIS WEEK” — two different counts, both visible on first paint.

---

## /contact/

**H1:** “Let's talk movement.”

**Above-fold CTA**

| Viewport | In fold |
|---|---|
| Mobile 390 | “FIND A SITE ↗” (text link to `/classes-map/`). Form starts (Name + Email). **No Submit, no email address, no phone, no NAP.** |
| Desktop 1440 | Same H1. Form (Name, Email, Subject, Message). Aside “REACH US NOW”: `hello@londonparkour.com`, three site names, “Wed, Sat, Sun · 09:00–20:00”, yellow **EMAIL US**. Nav `FIND A CLASS`. |

**Issues**

1. **No `tel:` anywhere in the HTML** of this page (or the homepage). Click-to-call is absent on mobile, where local “near me” traffic originates.
2. Mobile fold is brand H1 + the start of a form. The actual send button and the Reach-us panel are below the fold.
3. H1 does not say contact, London, or parkour. Supporting line (“classes, private coaching, gift cards or partnerships”) is the only intent signal.
4. Visible email on desktop is `hello@londonparkour.com`. JSON-LD on the same page uses `contact@londonparkour.com`. Curl HTML obfuscates the address via Cloudflare `__cf_email__` — email crawlers and copy-paste from “view source” do not see a mailto.
5. Hours in the aside (“Wed, Sat, Sun · 09:00–20:00”) are office/reply hours, not class hours, with no label making that distinction.

---

## Cross-page

- Desktop global `FIND A CLASS` is the strongest persistent CTA; **it does not exist in the mobile header** (logo + hamburger only).
- Booking that works uses a drawer (`commandfor="lp-booking-drawer"`). Booking that is an `<a href="/book/">` is broken. Visual QA of the hero therefore overstates conversion readiness.
- Review count in the homepage hero footer is **4.9 (43)**; schema `aggregateRating.reviewCount` is **42**. Unverified against GBP (see `local.md`).

---

## Fixes that matter

1. Keep the £15 drawer CTA; **stop linking `/book/` until it is a real booking URL**.
2. Put a book control in the mobile classes fold (first session card or a sticky “Book £15”).
3. Add a `tel:` in the contact fold (and footer). Do not rely on Cloudflare-obfuscated email as the only NAP phone substitute.
4. If SEO needs the live query “parkour classes london” on the homepage, put that phrase in a visible subhead, not by rewriting the signed-off H1 without a design pass.
5. Fix the Vauxhall Tuesday/Thursday sentence in the closing CTA — it contradicts the board and the location pages.
