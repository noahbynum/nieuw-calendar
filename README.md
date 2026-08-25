# Nieuw Calendar

WordPress events calendar by Nieuw Ark.

The public calendar matches the Nieuw Calendar demo: month grid with spanning event chips, grouped list view, category filters, event detail overlay, fonts, colors, and iCal export.

Events are added from **Nieuw Calendar → Add Event** — a dedicated form (title, dates, venue, categories, color, featured image). WordPress’s block editor is not used.

## Install

1. Download `nieuw-calendar.zip` from [Releases](https://github.com/noahbynum/nieuw-calendar/releases) or clone this repo.
2. In WordPress: **Plugins → Add New → Upload Plugin**.
3. Activate **Nieuw Calendar**.
4. Add events under **Nieuw Calendar → Events**.
5. Place `[nieuw_calendar]` on any page. Use `[nieuw_calendar view="list"]` to start in list view. Use `[nieuw_calendar header="0"]` to hide the Nieuw Calendar heading if the page already has one.

After installing or updating, visit **Settings → Permalinks** and click **Save** so rewrite rules refresh.

iCal feed: `https://yoursite.com/?nieuw_calendar_ical=1`

## License

MIT — see [LICENSE](LICENSE).
