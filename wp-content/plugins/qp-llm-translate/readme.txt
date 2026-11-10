=== QP LLM Translate ===
Contributors: qpholdings
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.3.2

Translate WordPress news, jobs, projects, and investor documents with DeepSeek, OpenAI, or Google Gemini in the background.

== Description ==

* Settings: provider, API key, model, source/target languages, post types.
* Auto-queue only when a Vietnamese original is first published. Updates never queue a translation automatically.
* “Translate now” button on the post editor.
* Background worker: WP-Cron plus a non-blocking loopback ping so the editor does not wait for the LLM.
* Creates/updates Polylang translation posts when Polylang is active, and stores `_qp_translation_lang` for the QP Holdings theme.

== Installation ==

1. Upload `qp-llm-translate.zip` in Plugins → Add New → Upload Plugin.
2. Activate.
3. Settings → QP LLM Translate: choose provider, paste API key, save.
4. Open a `qp_news`, `qp_job`, or `qp_investor_doc` post and click Translate now, or publish a new item.

== Changelog ==

= 1.3.2 =
* Hotfix: Optimized YouTube Shorts lightbox player to eliminate side pillarbox bars and fit 9:16 aspect ratio dynamically.
* Hotfix: Removed duplicate embed helper declarations to ensure 100% PHP compatibility.

= 1.3.1 =
* Comprehensive project translations (categories, investment totals, scales, progress dates, addresses, hero headings).
* Canonical project slug matcher and Polylang post bridge.
* Auto-synchronize latest theme fixes into active theme.

= 1.3.0 =
* Fixed project archive display and YouTube Shorts vertical player embedding.
* Refine corporate translation prompt and add deterministic post-processing normalization for end-of-article corporate contact boxes (taglines, address components, stock ticker labels, reference sources, and editorial credits).

= 1.0.7 =
* Translate news category and source-reference text fields alongside title, excerpt, and article content.
* Protect queued news translations against later edits to those fields; explicitly translate the information box at the end of articles.

= 1.0.6 =
* Prevent Save/Update (including REST and WP-CLI) from queuing unintended translations.
* Require an explicit manual request before changing an existing translation; reject legacy and stale jobs before they can overwrite newer edits.

= 1.0.5 =
* Translate post titles with the body. In-place title translation for EN/ZH posts (including English titles on Chinese-language copies).
* Enable `qp_investor_doc` on existing installs.
* Reject Chinese jobs that leave a long Latin title untranslated.

= 1.0.4 =
* Translate qp_job ACF fields (title, summary, experience, location, salary, detail sections) onto Polylang copies.
* Enable the `qp_job` post type on existing installs.

= 1.0.0 =
* Initial release.
