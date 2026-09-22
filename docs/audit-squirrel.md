<?xml version="1.0" encoding="UTF-8"?>
<audit version="0.0.97">
<site url="https://staging.londonparkour.com" crawled="25" date="2026-09-22T14:08:38.566Z"/>
<score overall="47" grade="F">
 <group name="SEO" score="48" errors="67" warnings="160"/>
 <group name="Performance" score="40" errors="30" warnings="124"/>
 <group name="Security" score="85" errors="0" warnings="4"/>
 <group name="Agents" score="34" errors="0" warnings="28"/>
 <cat name="Accessibility" score="48"/>
 <cat name="Performance" score="40"/>
 <cat name="Structured Data" score="39"/>
 <cat name="Content" score="74"/>
 <cat name="Agent Experience" score="34"/>
 <cat name="Core SEO" score="76"/>
 <cat name="Links" score="81"/>
 <cat name="Security" score="81"/>
 <cat name="Images" score="91"/>
 <cat name="Crawlability" score="96"/>
 <cat name="E-E-A-T" score="80"/>
 <cat name="Video" score="44"/>
 <cat name="Social Media" score="100"/>
 <cat name="Analytics" score="100"/>
 <cat name="Internationalization" score="100"/>
 <cat name="Site Integrity" score="100"/>
 <cat name="Legal Compliance" score="100"/>
 <cat name="Local SEO" score="100"/>
 <cat name="Mobile" score="100"/>
 <cat name="URL Structure" score="100"/>
</score>
<summary passed="2880" warnings="384" failed="97"/>
<issues>
 <rule id="schema/entity-split-identity" severity="error" category="Structured Data" group="seo" status="fail" docs="https://docs.squirrelscan.com/rules/schema/entity-split-identity">
  1 entity is declared under more than one @id
  Pages (1): /classes/adult-beginners-outdoor/
  Items (5/8):
   - id:https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-09-26 (Adult Beginners East (SportsEvent)) [types: [&quot;SportsEvent&quot;], id: https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-09-26, pages: 1, occurrences: 1, sharesIdentityWith: [&quot;https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-09-26&quot;,&quot;https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-10-03&quot;,&quot;https://staging.londo…] (from: /classes/adult-beginners-outdoor/)
   - id:https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-10-03 (Adult Beginners East (SportsEvent)) [types: [&quot;SportsEvent&quot;], id: https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-10-03, pages: 1, occurrences: 1, sharesIdentityWith: [&quot;https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-09-26&quot;,&quot;https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-10-03&quot;,&quot;https://staging.londo…] (from: /classes/adult-beginners-outdoor/)
   - id:https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-10-10 (Adult Beginners East (SportsEvent)) [types: [&quot;SportsEvent&quot;], id: https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-10-10, pages: 1, occurrences: 1, sharesIdentityWith: [&quot;https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-09-26&quot;,&quot;https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-10-03&quot;,&quot;https://staging.londo…] (from: /classes/adult-beginners-outdoor/)
   - id:https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-10-17 (Adult Beginners East (SportsEvent)) [types: [&quot;SportsEvent&quot;], id: https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-10-17, pages: 1, occurrences: 1, sharesIdentityWith: [&quot;https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-09-26&quot;,&quot;https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-10-03&quot;,&quot;https://staging.londo…] (from: /classes/adult-beginners-outdoor/)
   - id:https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-10-24 (Adult Beginners East (SportsEvent)) [types: [&quot;SportsEvent&quot;], id: https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-10-24, pages: 1, occurrences: 1, sharesIdentityWith: [&quot;https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-09-26&quot;,&quot;https://staging.londonparkour.com/classes/adult-beginners-outdoor/#session-2026-10-03&quot;,&quot;https://staging.londo…] (from: /classes/adult-beginners-outdoor/)
 </rule>
 <rule id="a11y/aria-command-name" severity="error" category="Accessibility" group="seo" status="fail" docs="https://docs.squirrelscan.com/rules/a11y/aria-command-name">
  5 command element(s) without accessible names
  Pages (1): /
  Items (1):
   - a[href=&quot;https://staging.londonparkour....&quot;] (from: /)
 </rule>
 <rule id="a11y/aria-hidden-focus" severity="error" category="Accessibility" group="seo" status="fail" docs="https://docs.squirrelscan.com/rules/a11y/aria-hidden-focus">
  2 to 5 focusable element(s) inside aria-hidden; 1 focusable element(s) inside aria-hidden appear to be an anti-spam honeypot
  Pages (2): /, /contact/
  Items (4):
   - a (self is focusable) (from: /)
   - input (self is focusable) (from: /contact/)
   - input#lp-company (from: /contact/)
   - input#dispatch-hp-2 (from: /)
 </rule>
 <rule id="a11y/aria-input-field-name" severity="error" category="Accessibility" group="seo" status="fail" docs="https://docs.squirrelscan.com/rules/a11y/aria-input-field-name">
  1 input field(s) without accessible names
  Pages (2): /, /contact/
  Items (2):
   - input[name=&quot;b_52e3850402e37a2847a8183fe_ed12edf43d&quot;] (from: /)
   - input[name=&quot;lp_company&quot;] (from: /contact/)
 </rule>
 <rule id="a11y/aria-progressbar-name" severity="error" category="Accessibility" group="seo" status="fail" docs="https://docs.squirrelscan.com/rules/a11y/aria-progressbar-name">
  1 progressbar element(s) without accessible names
  Pages (1): /tutorials/vault-landing/
  Items (1):
   - progress (from: /tutorials/vault-landing/)
 </rule>
 <rule id="a11y/frame-title" severity="error" category="Accessibility" group="seo" status="fail" docs="https://docs.squirrelscan.com/rules/a11y/frame-title">
  1 iframe(s) without title attribute
  Pages (5/25): /, /about/, /blog/, /classes-map/, /classes/
  Items (1):
   - iframe (www.googletagmanager.com) (from: /, /about/, /blog-category/article/, /blog-tag/reference/, /blog/; +5 more)
 </rule>
 <rule id="a11y/label-content-name-mismatch" severity="error" category="Accessibility" group="seo" status="fail" docs="https://docs.squirrelscan.com/rules/a11y/label-content-name-mismatch">
  1 to 84 element(s) where visible text doesn&apos;t match accessible name
  Pages (5): /, /tutorials-category/, /classes/adult-beginners-outdoor/, /series/2020-demonstrations/, /tutorials/vault-landing/
  Items (4):
   - button: visible=&quot;next class wed - 23r&quot; vs aria-label=&quot;reserve a place — ev&quot; (from: /)
   - button: visible=&quot;‹&quot; vs aria-label=&quot;previous lessons&quot; (from: /series/2020-demonstrations/, /tutorials-category/)
   - button: visible=&quot;›&quot; vs aria-label=&quot;next lessons&quot; (from: /series/2020-demonstrations/, /tutorials-category/)
   - button: visible=&quot;✕&quot; vs aria-label=&quot;close video&quot; (from: /classes/adult-beginners-outdoor/, /tutorials/vault-landing/)
 </rule>
 <rule id="a11y/form-labels" severity="error" category="Accessibility" group="seo" status="fail" docs="https://docs.squirrelscan.com/rules/a11y/form-labels">
  1 form input(s) without labels
  Pages (2): /, /contact/
  Items (2):
   - b_52e3850402e37a2847a8183fe_ed12edf43d (from: /)
   - lp_company (from: /contact/)
 </rule>
 <rule id="security/third-party-cookies" severity="info" category="Security" group="security" status="warn" docs="https://docs.squirrelscan.com/rules/security/third-party-cookies">
  1 known tracking domain(s) detected
  Pages (5/25): /, /about/, /blog/, /classes-map/, /classes/
  Items (1):
   - www.googletagmanager.com (iframe) (from: /, /about/, /blog-category/article/, /blog-tag/reference/, /blog/; +5 more)
 </rule>
 <rule id="images/optimized" severity="info" category="Images" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/images/optimized">
  Images may not be optimized - consider using an image CDN
  Pages (5/9): /, /about/, /blog/, /classes/, /private-coaching/
 </rule>
 <rule id="images/svg-inline" severity="info" category="Images" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/images/svg-inline">
  4 to 5 large inline SVG(s) (&gt;4KB each)
  Pages (5/25): /, /about/, /blog/, /classes-map/, /classes/
  Items (5/25):
   - / (4 large inline SVG(s) (&gt;4KB each))
   - /about/ (5 large inline SVG(s) (&gt;4KB each))
   - /blog-category/article/ (4 large inline SVG(s) (&gt;4KB each))
   - /blog-tag/reference/ (4 large inline SVG(s) (&gt;4KB each))
   - /blog/ (4 large inline SVG(s) (&gt;4KB each))
 </rule>
 <rule id="perf/inp-hints" severity="info" category="Performance" group="performance" status="warn" docs="https://docs.squirrelscan.com/rules/perf/inp-hints">
  6 blocking scripts (consider async/defer)
  Pages (5/6): /, /classes/, /coupons/, /private-coaching/, /workshops/
  Items (5/6):
   - / (6 blocking scripts (consider async/defer))
   - /classes/ (6 blocking scripts (consider async/defer))
   - /classes/adult-beginners-outdoor/ (6 blocking scripts (consider async/defer))
   - /coupons/ (6 blocking scripts (consider async/defer))
   - /private-coaching/ (6 blocking scripts (consider async/defer))
 </rule>
 <rule id="legal/subprocessor-disclosure" severity="info" category="Legal Compliance" group="security" status="warn" docs="https://docs.squirrelscan.com/rules/legal/subprocessor-disclosure">
  No sub-processor / data-processing (DPA) disclosure found
 </rule>
 <rule id="ax/llms-txt" severity="info" category="Agent Experience" group="ai" status="warn" docs="https://docs.squirrelscan.com/rules/ax/llms-txt">
  No /llms.txt found — consider adding one so AI agents can discover your key content
 </rule>
 <rule id="ax/markdown-response" severity="info" category="Agent Experience" group="ai" status="warn" docs="https://docs.squirrelscan.com/rules/ax/markdown-response">
  No Markdown response — consider honoring Accept: text/markdown or publishing a .md variant so agents get clean content
 </rule>
 <rule id="crawl/sitemap-4xx" severity="warning" category="Crawlability" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/crawl/sitemap-4xx">
  1 sitemap URL(s) return 4XX
  Pages (1): /docs/
  Items (1):
   - /docs/ [status: 403]
 </rule>
 <rule id="crawl/canonical-chain" severity="warning" category="Crawlability" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/crawl/canonical-chain">
  Page redirects before content is served
  Pages (1): /legal/
  Items (1):
   - /legal/ (https://staging.londonparkour.com/legal/ (301) → https://staging.londonparkour.com/docs/terms-of-service/ (200)) [finalUrl: https://staging.londonparkour.com/docs/terms-of-service/, chain: {&quot;sourceUrl&quot;:&quot;https://staging.londonparkour.com/legal/&quot;,&quot;finalUrl&quot;:&quot;https://staging.londonparkour.com/docs/terms-of-service/&quot;,&quot;hops&quot;:[{&quot;url&quot;:&quot;https://staging.londonparkour.com/legal/&quot;,&quot;statusCode&quot;:30…] (from: /legal/)
 </rule>
 <rule id="crawl/html-size" severity="warning" category="Crawlability" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/crawl/html-size">
  HTML is 1515KB — approaching Googlebot 2MB limit
  Pages (1): /tutorials-category/
 </rule>
 <rule id="core/meta-title" severity="warning" category="Core SEO" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/core/meta-title">
  Title too short
  Pages (5/7): /contact/, /workshops/, /blog-category/article/, /blog-tag/reference/, /support-category/classes/
  Items (5/7):
   - /blog-category/article/ (Article | London Parkour (24 chars)) (from: /blog-category/article/)
   - /blog-tag/reference/ (reference | London Parkour (26 chars)) (from: /blog-tag/reference/)
   - /contact/ (Contact London Parkour (22 chars)) (from: /contact/)
   - /support-category/classes/ (Classes | London Parkour (24 chars)) (from: /support-category/classes/)
   - /tutorial-category/balancing/ (Balancing | Parkour Tutorials (29 chars)) (from: /tutorial-category/balancing/)
 </rule>
 <rule id="core/meta-description" severity="warning" category="Core SEO" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/core/meta-description">
  Description too short
  Pages (5/9): /classes/, /legal/, /private-coaching/, /tutorials-category/, /waiver/
  Items (5/9):
   - /blog/33x-home-pressup-pushup-variations/ (Ranging from the obvious to the ridiculous. Here&apos;s (90 chars)) (from: /blog/33x-home-pressup-pushup-variations/)
   - /classes/ (Every session on the board for the week ahead. Coa (111 chars)) (from: /classes/)
   - /legal/ (The rules that apply when you book and train with  (108 chars)) (from: /legal/)
   - /private-coaching/ (Private sessions move at your pace — a first wall, (117 chars)) (from: /private-coaching/)
   - /series/2020-demonstrations/ (228 coached parkour videos in 2020 Demonstration L (78 chars)) (from: /series/2020-demonstrations/)
 </rule>
 <rule id="core/favicon" severity="warning" category="Core SEO" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/core/favicon">
  No favicon found
  Pages (1): /
 </rule>
 <rule id="security/csp" severity="warning" category="Security" group="security" status="warn" docs="https://docs.squirrelscan.com/rules/security/csp">
  No Content-Security-Policy header
 </rule>
 <rule id="security/hsts" severity="warning" category="Security" group="security" status="warn" docs="https://docs.squirrelscan.com/rules/security/hsts">
  Missing Strict-Transport-Security header
 </rule>
 <rule id="security/x-frame-options" severity="warning" category="Security" group="security" status="warn" docs="https://docs.squirrelscan.com/rules/security/x-frame-options">
  No clickjacking protection
 </rule>
 <rule id="security/form-captcha" severity="warning" category="Security" group="security" status="warn" docs="https://docs.squirrelscan.com/rules/security/form-captcha">
  1 public form(s) without CAPTCHA
  Pages (1): /
  Items (1):
   - [action=&quot;https://londonparkour.us17.list-manage.com/subscribe/post?u=52e3850402e37a2847a8183fe&amp;id=ed12edf43d&amp;v_id=4263&amp;f_id=00dc79e0f0&quot;] (from: /)
 </rule>
 <rule id="links/orphan-pages" severity="warning" category="Links" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/links/orphan-pages">
  9 orphan page(s) with &lt;2 incoming links
  Pages (5/9): /legal/, /tutorials-category/, /tutorials-series/, /blog-category/article/, /blog-tag/reference/
  Items (5/9):
   - /blog-category/article/
   - /blog-tag/reference/
   - /legal/
   - /level/beginner/
   - /support-category/classes/
 </rule>
 <rule id="links/internal-links" severity="warning" category="Links" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/links/internal-links">
  Too many internal links (102, max 100); Too many internal links (104, max 100); Too many internal links (106, max 100); Too many internal links (332, max 100); Too many internal links (467, max 100)
  Pages (5): /legal/, /tutorials-category/, /waiver/, /docs/beginners-class/, /series/2020-demonstrations/
 </rule>
 <rule id="links/no-contextual-inbound" severity="warning" category="Links" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/links/no-contextual-inbound">
  2 page(s) are linked only from sitewide chrome
  Pages (2): /about/, /workshops/
  Items (2):
   - /about/
   - /workshops/
 </rule>
 <rule id="links/redirect-chains" severity="warning" category="Links" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/links/redirect-chains">
  1 page(s) redirect to another URL
  Pages (1): /legal/
  Items (1):
   - /legal/ (https://staging.londonparkour.com/legal/ (301) → https://staging.londonparkour.com/docs/terms-of-service/ (200)) [targetUrl: https://staging.londonparkour.com/docs/terms-of-service/, chain: {&quot;sourceUrl&quot;:&quot;https://staging.londonparkour.com/legal/&quot;,&quot;finalUrl&quot;:&quot;https://staging.londonparkour.com/docs/terms-of-service/&quot;,&quot;hops&quot;:[{&quot;url&quot;:&quot;https://staging.londonparkour.com/legal/&quot;,&quot;statusCode&quot;:30…]
 </rule>
 <rule id="links/weak-internal-links" severity="warning" category="Links" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/links/weak-internal-links">
  2 page(s) have only 1 internal link
  Pages (2): /tutorial-category/balancing/, /tutorial-tag/tutorial/
  Items (2):
   - /tutorial-category/balancing/
   - /tutorial-tag/tutorial/
 </rule>
 <rule id="content/duplicate-description" severity="warning" category="Content" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/content/duplicate-description">
  1 duplicate description(s) found across 6 pages
  Pages (5/6): /, /blog-category/article/, /blog-tag/reference/, /level/beginner/, /support-category/classes/
  Items (1):
   - practical movement is the practice of getting where you want (&quot;practical movement is the practice of ge...&quot; (6 pages)) [pageCount: 6] (from: /, /blog-category/article/, /blog-tag/reference/, /level/beginner/, /support-category/classes/; +1 more)
 </rule>
 <rule id="content/keyword-stuffing" severity="warning" category="Content" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/content/keyword-stuffing">
  1 to 5 word(s) may be overused
  Pages (5/13): /classes-map/, /coupons/, /tutorials-category/, /tutorials-series/, /waiver/
  Items (5/15):
   - blog (&quot;blog&quot; (3.6%)) [count: 7, density: 3.6458333333333335] (from: /blog-tag/reference/)
   - class (&quot;class&quot; (3.2%)) [count: 26, density: 3.1591737545565004] (from: /docs/beginners-class/, /level/beginner/, /support-category/classes/)
   - classes (&quot;classes&quot; (3.7%)) [count: 14, density: 3.7135278514588856] (from: /classes-map/, /coupons/)
   - climbup (&quot;climbup&quot; (3.2%)) [count: 13, density: 3.1707317073170733] (from: /tutorial-tag/tutorial/)
   - front (&quot;front&quot; (4.2%)) [count: 81, density: 4.155977424320164] (from: /series/2020-demonstrations/)
 </rule>
 <rule id="content/unrendered-markup" severity="warning" category="Content" group="seo" status="fail" docs="https://docs.squirrelscan.com/rules/content/unrendered-markup">
  38 unrendered markup occurrence(s) in visible text (markdown-emphasis, markdown-link, markdown-heading, escaped-html-tag): example **totally**; 8 unrendered markup occurrence(s) in visible text (escaped-html-tag): example &lt;a href=&quot;https://staging.londonparkour.com/classes/youth-class-west-10-14s/&quot;&gt;
  Pages (2): /level/beginner/, /support-category/classes/
  Items (2):
   - /support-category/classes/ (38 unrendered markup occurrence(s) in visible text (markdown-emphasis, markdown-link, markdown-heading, escaped-html-tag): example **totally**)
   - /level/beginner/ (8 unrendered markup occurrence(s) in visible text (escaped-html-tag): example &lt;a href=&quot;https://staging.londonparkour.com/classes/youth-class-west-10-14s/&quot;&gt;)
 </rule>
 <rule id="content/word-count" severity="warning" category="Content" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/content/word-count">
  Thin content: 259 words (min 300)
  Pages (1): /blog-tag/reference/
  Items (1):
   - /blog-tag/reference/ (Thin content: 259 words (min 300))
 </rule>
 <rule id="schema/json-ld-valid" severity="warning" category="Structured Data" group="seo" status="fail" docs="https://docs.squirrelscan.com/rules/schema/json-ld-valid">
  Invalid JSON-LD syntax
  Pages (5/25): /, /about/, /blog/, /classes-map/, /classes/
  Items (5/8):
   - Article:image (Article missing image) [message: Validation: Article.image must be a string or array of strings, severity: invalid, path: [&quot;image&quot;]] (from: /blog/33x-home-pressup-pushup-variations/)
   - Article:publisher.logo (Article missing publisher.logo) [message: Validation: Article.publisher.logo is required, severity: missing, path: [&quot;publisher&quot;,&quot;logo&quot;]] (from: /blog/33x-home-pressup-pushup-variations/)
   - Article:publisher.name (Article missing publisher.name) [message: Validation: Article.publisher.name is required, severity: missing, path: [&quot;publisher&quot;,&quot;name&quot;]] (from: /blog/33x-home-pressup-pushup-variations/)
   - LocalBusiness:address (LocalBusiness missing address) [message: Validation: LocalBusiness.address is required, severity: missing, path: [&quot;address&quot;]] (from: /, /about/, /blog-category/article/, /blog-tag/reference/, /blog/; +5 more)
   - parse-0 (Validation: LocalBusiness.address is required) (from: /, /about/, /blog-category/article/, /blog-tag/reference/, /blog/; +5 more)
 </rule>
 <rule id="schema/video" severity="warning" category="Structured Data" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/schema/video">
  Page has video but no VideoObject schema
  Pages (1): /about/
 </rule>
 <rule id="schema/entity-authors" severity="warning" category="Structured Data" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/schema/entity-authors">
  1 of 1 Person entity has no url and no sameAs
  Pages (1): /blog/33x-home-pressup-pushup-variations/
  Items (1):
   - syn:Person|name:andy pearson (Andy Pearson (Person)) [types: [&quot;Person&quot;], pages: 1, occurrences: 1] (from: /blog/33x-home-pressup-pushup-variations/)
 </rule>
 <rule id="schema/entity-conflicts" severity="warning" category="Structured Data" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/schema/entity-conflicts">
  5 properties disagree across pages, on 4 entities
  Pages (5/6): /, /blog/, /coupons/, /blog-category/article/, /blog-tag/reference/
  Items (5):
   - id:https://staging.londonparkour.com/#offer-pack-56654 url (Single Class — url: https://staging.londonparkour.com/ (1 pages) vs https://staging.londonparkour.com/coupons/ (1 pages)) [key: id:https://staging.londonparkour.com/#offer-pack-56654, property: url, valueCount: 2] (from: /, /coupons/)
   - id:https://staging.londonparkour.com/#offer-pack-56655 url (Five-Pack — url: https://staging.londonparkour.com/ (1 pages) vs https://staging.londonparkour.com/coupons/ (1 pages)) [key: id:https://staging.londonparkour.com/#offer-pack-56655, property: url, valueCount: 2] (from: /, /coupons/)
   - id:https://staging.londonparkour.com/#offer-pack-56656 url (Ten Pack — url: https://staging.londonparkour.com/ (1 pages) vs https://staging.londonparkour.com/coupons/ (1 pages)) [key: id:https://staging.londonparkour.com/#offer-pack-56656, property: url, valueCount: 2] (from: /, /coupons/)
   - id:https://staging.londonparkour.com/#organization image (London Parkour — image: https://staging.londonparkour.com/wp-content/uploads/2026/08/Tutorial-vaulting-speed_step-6-speed-step-exit_16_9.jpg (1 pages) vs https://staging.londonparkour.com/wp-content/uploads/2026/08/lp_wide_lg/alfredo-strides.jpg (20 pages) vs https://staging.londonparkour.com/wp-content/uploads/2026/08/lp_wide_lg/alfredo-tictacs.jpg (1 pages)) [key: id:https://staging.londonparkour.com/#organization, property: image, valueCount: 6] (from: /tutorials/vault-landing/, /, /blog-category/article/, /blog-tag/reference/, /blog/)
   - id:https://staging.londonparkour.com/#organization logo (London Parkour — logo: https://staging.londonparkour.com/wp-content/uploads/2026/08/Tutorial-vaulting-speed_step-6-speed-step-exit_16_9.jpg (1 pages) vs https://staging.londonparkour.com/wp-content/uploads/2026/08/lp_wide_lg/alfredo-strides.jpg (20 pages) vs https://staging.londonparkour.com/wp-content/uploads/2026/08/lp_wide_lg/alfredo-tictacs.jpg (1 pages)) [key: id:https://staging.londonparkour.com/#organization, property: logo, valueCount: 6] (from: /tutorials/vault-landing/, /, /blog-category/article/, /blog-tag/reference/, /blog/)
 </rule>
 <rule id="schema/entity-local-business-per-page" severity="warning" category="Structured Data" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/schema/entity-local-business-per-page">
  London Parkour is declared in full on 25 of 25 crawled pages
  Pages (5): /, /about/, /blog/, /blog-category/article/, /blog-tag/reference/
  Items (1):
   - id:https://staging.londonparkour.com/#organization (London Parkour (LocalBusiness, SportsClub)) [types: [&quot;LocalBusiness&quot;,&quot;SportsClub&quot;], id: https://staging.londonparkour.com/#organization, pages: 25, occurrences: 25] (from: /, /about/, /blog-category/article/, /blog-tag/reference/, /blog/)
 </rule>
 <rule id="schema/rating-scope" severity="warning" category="Structured Data" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/schema/rating-scope">
  AggregateRating on SportsClub &quot;London Parkour&quot; is not the subject of this article page — sitewide rating markup on a page that cannot be rated is a structured-data policy violation and risks a manual action, not just a lost rich result; AggregateRating on SportsClub &quot;London Parkour&quot; is not the subject of this terms page — sitewide rating markup on a page that cannot be rated is a structured-data policy violation and risks a manual action, not just a lost rich result; AggregateRating 4.9 from 42 reviews on SportsClub &quot;London Parkour&quot; is not visible anywhere on this article page — a rating readers cannot see is a structured-data policy violation and risks a manual action, not just a lost rich result; AggregateRating 4.9 from 42 reviews on SportsClub &quot;London Parkour&quot; is not visible anywhere on this faq page — a rating readers cannot see is a structured-data policy violation and risks a manual action, not just a lost rich result; AggregateRating 4.9 from 42 reviews on SportsClub &quot;London Parkour&quot; is not visible anywhere on this local page — a rating readers cannot see is a structured-data policy violation and risks a manual action, not just a lost rich result; AggregateRating 4.9 from 42 reviews on SportsClub &quot;London Parkour&quot; is not visible anywhere on this media page — a rating readers cannot see is a structured-data policy violation and risks a manual action, not just a lost rich result; AggregateRating 4.9 from 42 reviews on SportsClub &quot;London Parkour&quot; is not visible anywhere on this terms page — a rating readers cannot see is a structured-data policy violation and risks a manual action, not just a lost rich result
  Pages (5/24): /about/, /blog/, /classes-map/, /classes/, /contact/
  Items (5/7):
   - rated-entity-1 (AggregateRating 4.9 from 42 reviews on SportsClub &quot;London Parkour&quot;) [ratedEntityType: SportsClub, ratedEntityName: London Parkour, ratingValue: 4.9, ratingCount: 42] (from: /blog/33x-home-pressup-pushup-variations/)
   - rated-entity-1 (AggregateRating 4.9 from 42 reviews on SportsClub &quot;London Parkour&quot;) [ratedEntityType: SportsClub, ratedEntityName: London Parkour, ratingValue: 4.9, ratingCount: 42] (from: /legal/)
   - rated-entity-1 (AggregateRating 4.9 from 42 reviews on SportsClub &quot;London Parkour&quot;) [ratedEntityType: SportsClub, ratedEntityName: London Parkour, ratingValue: 4.9, ratingCount: 42] (from: /blog/33x-home-pressup-pushup-variations/)
   - rated-entity-1 (AggregateRating 4.9 from 42 reviews on SportsClub &quot;London Parkour&quot;) [ratedEntityType: SportsClub, ratedEntityName: London Parkour, ratingValue: 4.9, ratingCount: 42] (from: /classes/adult-beginners-outdoor/, /contact/)
   - rated-entity-1 (AggregateRating 4.9 from 42 reviews on SportsClub &quot;London Parkour&quot;) [ratedEntityType: SportsClub, ratedEntityName: London Parkour, ratingValue: 4.9, ratingCount: 42] (from: /about/, /blog-category/article/, /blog-tag/reference/, /blog/, /classes-map/; +5 more)
 </rule>
 <rule id="schema/coverage-outlier" severity="warning" category="Structured Data" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/schema/coverage-outlier">
  1 of 21 page(s) are missing structured data their same-type siblings have (local BreadcrumbList+LocalBusiness+SportsClub+WebPage+WebSite)
  Pages (1): /
  Items (1):
   - local:BreadcrumbList (20 of 21 local pages have BreadcrumbList schema, these 1 do not) [pageType: local, schemaType: BreadcrumbList, richResult: true, have: 20, total: 21, missing: 1] (from: /)
 </rule>
 <rule id="images/image-file-size" severity="warning" category="Images" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/images/image-file-size">
  22 image(s) exceed 200.0 KB
  Pages (5/13): /, /about/, /blog/, /classes-map/, /classes/
  Items (5/22):
   - /wp-content/uploads/2024/10/12k8cp3f2E.jpg [sizeBytes: 236561, size: 231.0 KB, status: 200, contentType: image/jpeg] (from: /, /tutorials-series/)
   - /wp-content/uploads/2024/10/J5Rpsd5F1_Q.jpg [sizeBytes: 216114, size: 211.0 KB, status: 200, contentType: image/jpeg] (from: /tutorial-tag/tutorial/)
   - /wp-content/uploads/2024/10/SHsGl08OGng.jpg [sizeBytes: 216037, size: 211.0 KB, status: 200, contentType: image/jpeg] (from: /tutorial-tag/tutorial/)
   - /wp-content/uploads/2024/10/VX4kdy51-dk.jpg [sizeBytes: 213084, size: 208.1 KB, status: 200, contentType: image/jpeg] (from: /tutorial-tag/tutorial/)
   - /wp-content/uploads/2024/10/hTZ7RBSt1HY.jpg [sizeBytes: 213080, size: 208.1 KB, status: 200, contentType: image/jpeg] (from: /tutorial-tag/tutorial/)
 </rule>
 <rule id="images/dimensions" severity="warning" category="Images" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/images/dimensions">
  24 to 25 image(s) missing width/height (causes CLS)
  Pages (2): /, /about/
  Items (5/13):
   - /wp-content/uploads/Logos/transparent_army_white.gif | &lt;img src=&quot;https://staging.londonparkour.com/wp-content/uploads/Logos/transparent_army_white.gif&quot; alt=&quot;Army&quot;&gt; (from: /, /about/)
   - /wp-content/uploads/Logos/transparent_guardian_white.gif | &lt;img src=&quot;https://staging.londonparkour.com/wp-content/uploads/Logos/transparent_guardian_white.gif&quot; alt=&quot;The Guardian&quot;&gt; (from: /, /about/)
   - /wp-content/uploads/Logos/transparent_imperial_white.gif | &lt;img src=&quot;https://staging.londonparkour.com/wp-content/uploads/Logos/transparent_imperial_white.gif&quot; alt=&quot;Imperial&quot;&gt; (from: /, /about/)
   - /wp-content/uploads/Logos/transparent_mod_white.gif | &lt;img src=&quot;https://staging.londonparkour.com/wp-content/uploads/Logos/transparent_mod_white.gif&quot; alt=&quot;Ministry of Defence&quot;&gt; (from: /, /about/)
   - /wp-content/uploads/Logos/transparent_ned_white.gif | &lt;img src=&quot;https://staging.londonparkour.com/wp-content/uploads/Logos/transparent_ned_white.gif&quot; alt=&quot;The Ned&quot;&gt; (from: /, /about/)
 </rule>
 <rule id="perf/lcp-hints" severity="warning" category="Performance" group="performance" status="warn" docs="https://docs.squirrelscan.com/rules/perf/lcp-hints">
  1 likely-LCP image loaded without preload
  Pages (2): /blog/33x-home-pressup-pushup-variations/, /coaches/andy-pearson/
  Items (2):
   - /blog/33x-home-pressup-pushup-variations/ (1 likely-LCP image loaded without preload)
   - /coaches/andy-pearson/ (1 likely-LCP image loaded without preload)
 </rule>
 <rule id="perf/ttfb" severity="warning" category="Performance" group="performance" status="fail" docs="https://docs.squirrelscan.com/rules/perf/ttfb">
  Very slow server response (12354ms); Very slow server response (12846ms); Very slow server response (3239ms); Very slow server response (3634ms); Very slow server response (4071ms); Very slow server response (4661ms); Very slow server response (4727ms); Very slow server response (4745ms); Very slow server response (4947ms); Very slow server response (5019ms); Very slow server response (5048ms); Very slow server response (5182ms); Very slow server response (5190ms); Very slow server response (5215ms); Very slow server response (5389ms); Very slow server response (5402ms); Very slow server response (5542ms); Very slow server response (5940ms); Very slow server response (6614ms); Very slow server response (7745ms); Very slow server response (8528ms); Very slow server response (8671ms); Very slow server response (8733ms); Very slow server response (8958ms)
  Pages (5/24): /about/, /blog/, /classes-map/, /classes/, /contact/
 </rule>
 <rule id="perf/cls-hints" severity="warning" category="Performance" group="performance" status="fail" docs="https://docs.squirrelscan.com/rules/perf/cls-hints">
  1 iframe(s) without dimensions; 24 to 25 image(s) without width/height (CLS risk)
  Pages (2): /, /about/
  Items (5/14):
   - https://www.youtube-nocookie.com/embed/raakvpb_q9E (from: /about/)
   - /wp-content/uploads/Logos/transparent_army_white.gif (from: /, /about/)
   - /wp-content/uploads/Logos/transparent_guardian_white.gif (from: /, /about/)
   - /wp-content/uploads/Logos/transparent_imperial_white.gif (from: /, /about/)
   - /wp-content/uploads/Logos/transparent_mod_white.gif (from: /, /about/)
 </rule>
 <rule id="perf/css-file-size" severity="warning" category="Performance" group="performance" status="warn" docs="https://docs.squirrelscan.com/rules/perf/css-file-size">
  2 CSS file(s) exceed 150.0 KB
  Pages (5/25): /, /about/, /blog/, /classes-map/, /classes/
  Items (2):
   - /wp-content/themes/londonparkour_v8/assets/dist/main-D1FhZr-t.css?v=2eca453f [sizeBytes: 283220, size: 276.6 KB, status: 200, contentType: text/css] (from: /about/, /blog-category/article/, /blog-tag/reference/, /blog/, /blog/33x-home-pressup-pushup-variations/; +19 more)
   - /wp-content/themes/londonparkour_v8/assets/dist/main-e8tC8FMZ.css?v=918bb34c [sizeBytes: 282871, size: 276.2 KB, status: 200, contentType: text/css] (from: /)
 </rule>
 <rule id="perf/dom-size" severity="warning" category="Performance" group="performance" status="fail" docs="https://docs.squirrelscan.com/rules/perf/dom-size">
  Excessive DOM size (4869 to 7995 nodes)
  Pages (2): /tutorials-category/, /series/2020-demonstrations/
  Items (2):
   - /series/2020-demonstrations/ (Excessive DOM size (4869 nodes))
   - /tutorials-category/ (Excessive DOM size (7995 nodes))
 </rule>
 <rule id="perf/total-byte-weight" severity="warning" category="Performance" group="performance" status="fail" docs="https://docs.squirrelscan.com/rules/perf/total-byte-weight">
  Total tracked resources: 32739KB (very heavy)
 </rule>
 <rule id="perf/bad-caching" severity="warning" category="Performance" group="performance" status="fail" docs="https://docs.squirrelscan.com/rules/perf/bad-caching">
  25/25 pages set no caching policy (no freshness lifetime and no validator)
  Pages (5): /, /about/, /blog/, /blog-category/article/, /blog-tag/reference/
 </rule>
 <rule id="perf/critical-request-chains" severity="warning" category="Performance" group="performance" status="warn" docs="https://docs.squirrelscan.com/rules/perf/critical-request-chains">
  1 critical request chain(s) found
  Pages (5/25): /, /about/, /blog/, /classes-map/, /classes/
  Items (2):
   - CSS: https://staging.londonparkour.com/wp-content/themes/londonparkour_v8/assets/dist/main-D1FhZr-t.css?v=2eca453f (from: /about/, /blog-category/article/, /blog-tag/reference/, /blog/, /blog/33x-home-pressup-pushup-variations/; +5 more)
   - CSS: https://staging.londonparkour.com/wp-content/themes/londonparkour_v8/assets/dist/main-e8tC8FMZ.css?v=918bb34c (from: /)
 </rule>
 <rule id="perf/lazy-above-fold" severity="warning" category="Performance" group="performance" status="warn" docs="https://docs.squirrelscan.com/rules/perf/lazy-above-fold">
  1 to 3 above-fold image(s) with lazy loading
  Pages (5/18): /, /about/, /blog/, /classes-map/, /classes/
  Items (5/44):
   - /wp-content/uploads/2024/10/2g9XgPf5Bg.jpg (from: /tutorials-series/)
   - /wp-content/uploads/2024/10/J5Rpsd5F1_Q.jpg (from: /tutorial-tag/tutorial/)
   - /wp-content/uploads/2024/10/S05RGvMCDEA.jpg (from: /tutorial-category/balancing/)
   - /wp-content/uploads/2024/10/SHsGl08OGng.jpg (from: /tutorial-tag/tutorial/)
   - /wp-content/uploads/2024/10/SUzGxEdv5aw.jpg (from: /tutorial-category/balancing/)
 </rule>
 <rule id="perf/unminified-js" severity="warning" category="Performance" group="performance" status="warn" docs="https://docs.squirrelscan.com/rules/perf/unminified-js">
  7 JavaScript file(s) appear unminified
  Pages (5/25): /, /about/, /blog/, /classes-map/, /classes/
  Items (5):
   - cbfs-appointment-calendar.js (15.5KB, ~2.6KB savings) [reason: high newlines (3.36%), long function names, formatted code, excessive whitespace] (from: /, /about/, /blog-category/article/, /blog-tag/reference/, /blog/; +5 more)
   - cbfs-booking.js (30.8KB, ~6.7KB savings) [reason: high newlines (3.19%), 11 comments, long variable names, long function names, formatted code, excessive whitespace] (from: /, /about/, /blog-category/article/, /blog-tag/reference/, /blog/; +5 more)
   - cbfs-calendar-core.js (9.8KB, ~1.7KB savings) [reason: high newlines (3.39%), long variable names, long function names, formatted code, excessive whitespace] (from: /, /about/, /blog-category/article/, /blog-tag/reference/, /blog/; +5 more)
   - cbfs-class-date-calendar.js (9.1KB, ~1.5KB savings) [reason: high newlines (3.53%), long function names, formatted code, excessive whitespace] (from: /, /about/, /blog-category/article/, /blog-tag/reference/, /blog/; +5 more)
   - cbfs-packs.js (3.1KB, ~0.5KB savings) [reason: high newlines (3.63%), formatted code, excessive whitespace] (from: /, /about/, /blog-category/article/, /blog-tag/reference/, /blog/; +5 more)
 </rule>
 <rule id="perf/animated-content" severity="warning" category="Performance" group="performance" status="warn" docs="https://docs.squirrelscan.com/rules/perf/animated-content">
  24 GIF image(s) found - consider video format
  Pages (2): /, /about/
  Items (5/10):
   - transparent_guardian_white.gif (from: /, /about/)
   - transparent_imperial_white.gif (from: /, /about/)
   - transparent_mod_white.gif (from: /, /about/)
   - transparent_ned_white.gif (from: /, /about/)
   - transparent_olympics_white.gif (from: /, /about/)
 </rule>
 <rule id="perf/cache-headers" severity="warning" category="Performance" group="performance" status="warn" docs="https://docs.squirrelscan.com/rules/perf/cache-headers">
  No caching headers found
  Pages (5/25): /, /about/, /blog/, /classes-map/, /classes/
 </rule>
 <rule id="perf/unminified-css" severity="warning" category="Performance" group="performance" status="warn" docs="https://docs.squirrelscan.com/rules/perf/unminified-css">
  1 CSS file(s) appear unminified
  Pages (5/25): /, /about/, /blog/, /classes-map/, /classes/
  Items (1):
   - inline style (67.4KB, ~0.4KB savings) [reason: 4 comments] (from: /, /about/, /blog-category/article/, /blog-tag/reference/, /blog/; +5 more)
 </rule>
 <rule id="social/og-image-size" severity="warning" category="Social Media" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/social/og-image-size">
  og:image may be too small (779x779)
  Pages (1): /coaches/andy-pearson/
  Items (1):
   - /wp-content/uploads/Team/profile-upscaled2.png (og:image: https://staging.londonparkour.com/wp-content/uploads/Team/profile-upscaled2.png) (from: /coaches/andy-pearson/)
 </rule>
 <rule id="a11y/color-contrast" severity="warning" category="Accessibility" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/a11y/color-contrast">
  2 potential color contrast issue(s)
  Pages (5/25): /, /about/, /blog/, /classes-map/, /classes/
  Items (2):
   - Very light text color: 1 instance(s) (from: /, /about/, /blog-category/article/, /blog-tag/reference/, /blog/; +5 more)
   - White text (verify background): 1 instance(s) (from: /, /about/, /blog-category/article/, /blog-tag/reference/, /blog/; +5 more)
 </rule>
 <rule id="a11y/heading-order" severity="warning" category="Accessibility" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/a11y/heading-order">
  1 heading level skip(s) detected
  Pages (5/10): /blog/, /tutorials-category/, /workshops/, /blog-category/article/, /blog-tag/reference/
  Items (1):
   - H3 after H1 (from: /blog-category/article/, /blog-tag/reference/, /blog/, /blog/33x-home-pressup-pushup-variations/, /level/beginner/; +5 more)
 </rule>
 <rule id="a11y/identical-links-same-purpose" severity="warning" category="Accessibility" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/a11y/identical-links-same-purpose">
  1 to 5 link text(s) lead to different destinations
  Pages (5/25): /, /about/, /blog/, /classes-map/, /classes/
  Items (5/13):
   - &quot;[email protected]&quot; → 2 different URLs (from: /contact/)
   - &quot;by category&quot; → 2 different URLs (from: /)
   - &quot;by series&quot; → 2 different URLs (from: /)
   - &quot;by tutorial&quot; → 2 different URLs (from: /)
   - &quot;more details&quot; → 3 different URLs (from: /classes-map/)
 </rule>
 <rule id="a11y/image-redundant-alt" severity="warning" category="Accessibility" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/a11y/image-redundant-alt">
  1 to 9 image(s) with redundant alt text
  Pages (5/11): /, /blog/, /coupons/, /private-coaching/, /tutorials-category/
  Items (5/19):
   - alt=&quot;33 Pressups&quot; matches filename (from: /blog-category/article/, /blog-tag/reference/, /blog/, /blog/33x-home-pressup-pushup-variations/)
   - alt=&quot;Alfredo Balance&quot; matches filename (from: /, /blog/)
   - alt=&quot;Alfredo Slides&quot; matches filename (from: /workshops/)
   - alt=&quot;Alfredo Strides&quot; matches filename (from: /)
   - alt=&quot;Alfredo Swings&quot; matches filename (from: /coupons/)
 </rule>
 <rule id="a11y/input-types" severity="warning" category="Accessibility" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/a11y/input-types">
  1 multi-field form(s) set no enterkeyhint
  Pages (2): /contact/, /tutorials/vault-landing/
  Items (2):
   - form[action=&quot;https://staging.londonparkour.com/tutorials/&quot;] (4-field form, no field sets enterkeyhint) | &lt;form method=&quot;get&quot; action=&quot;https://staging.londonparkour.com/tutorials/&quot; data-filter-form=&quot;&quot; class=&quot;flex flex-wrap bg-base-100 border-b border-base-300&quot; data-component=&quot;filter-grid&quot;&gt; [reason: missing-enterkeyhint, fields: 4] (from: /tutorials/vault-landing/)
   - form[action=&quot;https://staging.londonparkour.com/wp-admin/admin-post.php&quot;] (4-field form, no field sets enterkeyhint) | &lt;form method=&quot;post&quot; action=&quot;https://staging.londonparkour.com/wp-admin/admin-post.php&quot; aria-label=&quot;Contact enquiry form&quot;&gt; [reason: missing-enterkeyhint, fields: 4] (from: /contact/)
 </rule>
 <rule id="a11y/link-in-text-block" severity="warning" category="Accessibility" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/a11y/link-in-text-block">
  3 to 6 link(s) in text may lack underlines
  Pages (3): /, /classes-map/, /tutorials/vault-landing/
  Items (5/12):
   - &quot;01
		
		Vauxhall
					2 CLASSE...&quot; (from: /classes-map/)
   - &quot;02
		
		Old Street
					3 CLAS...&quot; (from: /classes-map/)
   - &quot;03
		
		Kilburn Park
					1 CL...&quot; (from: /classes-map/)
   - &quot;1:04
						LESSON 04
									...&quot; (from: /tutorials/vault-landing/)
   - &quot;1:06
						LESSON 09
									...&quot; (from: /tutorials/vault-landing/)
 </rule>
 <rule id="a11y/link-text" severity="warning" category="Accessibility" group="seo" status="fail" docs="https://docs.squirrelscan.com/rules/a11y/link-text">
  5 link(s) with no accessible text; 1 link(s) with generic text
  Pages (1): /
  Items (5/6):
   - /classes/adult-be (empty) (from: /)
   - /classes/adult-ou (empty) (from: /)
   - /classes/evening- (empty) (from: /)
   - /classes/kids-cla (empty) (from: /)
   - /classes/youth-cl (empty) (from: /)
 </rule>
 <rule id="eeat/author-byline" severity="warning" category="E-E-A-T" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/eeat/author-byline">
  Only 25% of content pages have author attribution
 </rule>
 <rule id="eeat/privacy-policy" severity="warning" category="E-E-A-T" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/eeat/privacy-policy">
  No Privacy Policy page found
 </rule>
 <rule id="video/video-schema" severity="warning" category="Video" group="seo" status="warn" docs="https://docs.squirrelscan.com/rules/video/video-schema">
  Video content without VideoObject schema
  Pages (1): /about/
 </rule>
 <rule id="ax/agent-blocking" severity="warning" category="Agent Experience" group="ai" status="warn" docs="https://docs.squirrelscan.com/rules/ax/agent-blocking">
  GPTBot is blocked (HTTP 403) while the browser gets 200 — often an intentional training opt-out, but confirm it was deliberate
 </rule>
 <rule id="ax/token-weight" severity="warning" category="Agent Experience" group="ai" status="warn" docs="https://docs.squirrelscan.com/rules/ax/token-weight">
  Raw HTML exceeds a generous 100,000-token budget for a single page fetch — too heavy for an agent&apos;s context window; Visible text is under 15% of the page HTML — agents pay token cost mostly for markup, scripts, and styles
  Pages (5/25): /, /about/, /blog/, /classes-map/, /classes/
  Items (5/27):
   - /series/2020-demonstrations/ (~243,245 estimated tokens (972,978 bytes)) (from: /series/2020-demonstrations/)
   - /tutorials-category/ (~387,764 estimated tokens (1,551,056 bytes)) (from: /tutorials-category/)
   - / (~8% of HTML is visible text (~84,395 est. tokens)) (from: /)
   - /about/ (~7% of HTML is visible text (~60,490 est. tokens)) (from: /about/)
   - /blog-category/article/ (~6% of HTML is visible text (~47,901 est. tokens)) (from: /blog-category/article/)
 </rule>
</issues>
</audit>


