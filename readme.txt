=== Nieuw Calendar ===
Contributors: nieuwark
Tags: calendar, events, ical
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.2.5
License: MIT
License URI: https://opensource.org/licenses/MIT

Nieuw Calendar by Nieuw Ark — month and list views, categories, color coding, and iCal export.

== Description ==

Nieuw Calendar is a focused events calendar for WordPress. The public calendar matches the Nieuw Calendar demo.

* Custom event form (not the block editor) matching the Nieuw Calendar dashboard
* Title, description, all-day, start/end date and time
* Location / venue
* Category chips with colors, plus create-on-save
* Per-event color override
* Draft / pending / private / published visibility
* Featured image from the Media Library
* Settings for fonts, colors, opacity, border radius, and timezone
* Shortcode `[nieuw_calendar]` with month and list views matching the demo
* Spanning event chips, grouped list cards, and an event detail overlay
* Frontend category filtering
* iCal / .ics export at `/?nieuw_calendar_ical=1`

== Installation ==

1. Upload the `nieuw-calendar` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Add events under **Nieuw Calendar → Events** (use **Add event**, not the WordPress post editor).
4. Place `[nieuw_calendar]` on any page.
5. Visit **Settings → Permalinks** and click **Save**.

== Changelog ==

= 1.2.5 =
* A second plugin folder no longer crashes the site (first loaded copy wins).
* Calendar remounts after Elementor paints the shortcode.
* Assets load when the page or Elementor data contains the shortcode.
* Shows “Loading calendar…” until JavaScript mounts.
* To update: delete every Nieuw Calendar row, then upload this zip. Events and settings stay.

= 1.2.4 =
* Safer reinstall: deleting the plugin no longer wipes calendar settings. Admin screens show the plugin version.

= 1.2.3 =
* Event popup background, text, and muted text are set in Settings. Shared border radius clips the whole popup. Scrollbar is a thin Grok-style overlay that appears only while scrolling.

= 1.2.2 =
* Event popup colors are now in Settings. Border radius applies to the popup. Popup scrollbar is thin and only visible while scrolling.

= 1.2.1 =
* Remove the public Nieuw Ark heading, title, Subscribe .ics, and Dashboard controls so the calendar sits in the page.

= 1.2.0 =
* Public month and list views now match the Nieuw Calendar demo (spanning chips, list cards, event overlay, fonts, and theme colors).

= 1.1.1 =
* Stop the plugin from taking over a page at /events/.

= 1.1.0 =
* Replace the WordPress block editor with the Nieuw event form and events list.

= 1.0.0 =
* Initial release.
