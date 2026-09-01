# Nieuw Calendar

WordPress events calendar by Nieuw Ark.

The public calendar matches the Nieuw Calendar demo: month grid with spanning event chips, grouped list view, category filters, event detail overlay, fonts, colors, and iCal export. Event popup background, text, and muted text plus the shared border radius are set under **Nieuw Calendar → Settings**. The popup uses a thin Grok-style scrollbar that appears only while scrolling.

To replace an older installed copy, deactivate and delete the plugin first, then upload this zip. Events and settings stay. See `REPLACE.txt`.

Events are added from **Nieuw Calendar → Add Event** — a dedicated form (title, dates, venue, categories, color, featured image). WordPress’s block editor is not used.

## Install

1. Download `nieuw-calendar.zip` from [Releases](https://github.com/noahbynum/nieuw-calendar/releases) or clone this repo.
2. In WordPress: **Plugins → Add New → Upload Plugin**.
3. Activate **Nieuw Calendar**.
4. Add events under **Nieuw Calendar → Events**.
5. Place `[nieuw_calendar]` on any page. Use `[nieuw_calendar view="list"]` to start in list view.

After installing or updating, visit **Settings → Permalinks** and click **Save** so rewrite rules refresh.

iCal feed: `https://yoursite.com/?nieuw_calendar_ical=1`

## Changelog

### 1.2.5

- A second plugin folder no longer crashes the site. The first loaded copy wins.
- Calendar script remounts after Elementor paints the shortcode (load, timeouts, MutationObserver).
- Assets enqueue when the page or Elementor data contains `[nieuw_calendar]`.
- Shortcode shows a brief “Loading calendar…” placeholder until JS mounts.
- WordPress upload still does not overwrite an existing folder. Delete every Nieuw Calendar row, then upload this zip. See `REPLACE.txt`.

### 1.2.4

- Deleting the plugin no longer wipes calendar settings.
- Admin screens show the plugin version.

### 1.2.3

- Event popup background, text, and muted text are set in Settings.
- Shared border radius clips the whole popup.
- Scrollbar is a thin overlay that appears only while scrolling.

### 1.2.2

- Event popup colors added to Settings.
- Border radius applies to the popup.
- Popup scrollbar is thin and only visible while scrolling.

### 1.2.1

- Remove the public Nieuw Ark heading, title, Subscribe .ics, and Dashboard controls so the calendar sits in the page.

### 1.2.0

- Public month and list views match the Nieuw Calendar demo (spanning chips, list cards, event overlay, fonts, and theme colors).

### 1.1.1

- Stop the plugin from taking over a page at `/events/`.

### 1.1.0

- Replace the WordPress block editor with the Nieuw event form and events list.

### 1.0.0

- Initial release.

## License

MIT — see [LICENSE](LICENSE).
