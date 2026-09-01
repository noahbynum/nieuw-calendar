(function () {
  "use strict";

  function boot() {
    if (typeof window.NieuwCalendar === "undefined") return;
    var roots = document.querySelectorAll(".nieuw-calendar");
    if (!roots.length) return;
    roots.forEach(mount);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }

  var DATA = null;
  var LABELS_SUN = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
  var LABELS_MON = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
  var MONTHS = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December",
  ];
  var WEEKDAYS_LONG = [
    "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday",
  ];

  var ICONS = {
    chevronLeft:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>',
    chevronRight:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>',
    calendar:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>',
    list:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h.01"/><path d="M3 12h.01"/><path d="M3 19h.01"/><path d="M8 5h13"/><path d="M8 12h13"/><path d="M8 19h13"/></svg>',
    download:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15V3"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>',
    pin:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>',
    x:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
  };

  var COVER_SVG =
    '<svg class="nc-cover-pattern" viewBox="0 0 160 100" preserveAspectRatio="xMidYMid slice" aria-hidden="true">' +
    '<path d="M-10 80 C30 20, 70 20, 110 80 S 190 140, 230 80" fill="none" stroke="white" stroke-width="0.8"/>' +
    '<path d="M-20 50 C40 -10, 80 40, 140 20 S 200 60, 240 10" fill="none" stroke="white" stroke-width="0.6"/>' +
    '<circle cx="128" cy="28" r="10" fill="none" stroke="white" stroke-width="0.7"/>' +
    "</svg>";

  function pad(n) {
    return String(n).padStart(2, "0");
  }

  function ymd(d) {
    return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate());
  }

  function parseDay(s) {
    var parts = String(s || "").split("-").map(Number);
    return new Date(parts[0] || 1970, (parts[1] || 1) - 1, parts[2] || 1);
  }

  function sameDay(a, b) {
    return (
      a.getFullYear() === b.getFullYear() &&
      a.getMonth() === b.getMonth() &&
      a.getDate() === b.getDate()
    );
  }

  function startOfDay(d) {
    return new Date(d.getFullYear(), d.getMonth(), d.getDate());
  }

  function addDays(d, n) {
    return new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
  }

  function hexToRgb(hex) {
    var h = String(hex || "").replace("#", "").trim();
    if (!h) return null;
    var full = h.length === 3 ? h.split("").map(function (c) { return c + c; }).join("") : h;
    if (full.length !== 6) return null;
    var n = parseInt(full, 16);
    if (Number.isNaN(n)) return null;
    return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
  }

  function contrastText(hex) {
    var rgb = hexToRgb(hex);
    if (!rgb) return "#1c1915";
    function lin(c) {
      c = c / 255;
      return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    }
    var L = 0.2126 * lin(rgb.r) + 0.7152 * lin(rgb.g) + 0.0722 * lin(rgb.b);
    return L > 0.55 ? "#1c1915" : "#fbf8f2";
  }

  function darken(hex, amount) {
    var rgb = hexToRgb(hex);
    if (!rgb) return "#1c1915";
    var a = typeof amount === "number" ? amount : 0.45;
    return (
      "rgb(" +
      Math.round(rgb.r * a) +
      "," +
      Math.round(rgb.g * a) +
      "," +
      Math.round(rgb.b * a) +
      ")"
    );
  }

  function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
      if (c === "&") return "&#38;";
      if (c === "<") return "&#60;";
      if (c === ">") return "&#62;";
      if (c === '"') return "&#34;";
      return "&#39;";
    });
  }

  function truncate(value, max) {
    var t = String(value || "").trim();
    if (t.length <= max) return t;
    return t.slice(0, max - 1).replace(/\s+\S*$/, "").replace(/\s+$/, "") + "…";
  }

  function t(key, fallback) {
    var i18n = (DATA && DATA.i18n) || {};
    return i18n[key] || fallback;
  }

  function eventStartDay(event) {
    return parseDay(event.startDate);
  }

  function eventEndDay(event) {
    var start = eventStartDay(event);
    var end = parseDay(event.endDate || event.startDate);
    return end < start ? start : end;
  }

  function isPastEvent(event, now) {
    now = now || new Date();
    var end = eventEndDay(event);
    return startOfDay(addDays(end, 1)) < startOfDay(now);
  }

  function eventColor(event, settings) {
    if (event.color) return event.color;
    var cats = event.categories || [];
    for (var i = 0; i < cats.length; i++) {
      if (cats[i].color) return cats[i].color;
    }
    return (settings && settings.primary) || "#2f5d50";
  }

  function formatTime(time, timeFormat) {
    if (!time) return "";
    var parts = String(time).split(":");
    var h = Number(parts[0]);
    var m = Number(parts[1] || 0);
    if (Number.isNaN(h)) return time;
    if (String(timeFormat) === "24") {
      return pad(h) + ":" + pad(m);
    }
    var suffix = h >= 12 ? "PM" : "AM";
    var hour = h % 12 || 12;
    return hour + ":" + pad(m) + " " + suffix;
  }

  function formatMonthDayYear(d) {
    var short = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    return short[d.getMonth()] + " " + d.getDate() + ", " + d.getFullYear();
  }

  function formatEventWhen(event, settings) {
    if (event.when) return event.when;
    var startDay = eventStartDay(event);
    var endDay = eventEndDay(event);
    var multi = !sameDay(startDay, endDay);
    var startLabel = formatMonthDayYear(startDay);
    var endLabel = formatMonthDayYear(endDay);
    if (event.allDay) {
      return multi ? startLabel + " – " + endLabel : startLabel + " · " + t("allDay", "All day");
    }
    var tf = settings.time_format || settings.timeFormat || "12";
    var startT = formatTime(event.startTime, tf);
    var endT = event.endTime ? formatTime(event.endTime, tf) : "";
    if (multi) {
      return (startLabel + " " + startT + " – " + endLabel + (endT ? " " + endT : "")).trim();
    }
    if (startT && endT) return startLabel + " · " + startT + " – " + endT;
    if (startT) return startLabel + " · " + startT;
    return startLabel;
  }

  function monthTitle(cursor) {
    return MONTHS[cursor.getMonth()] + " " + cursor.getFullYear();
  }

  function listHeading(day) {
    return WEEKDAYS_LONG[day.getDay()] + ", " + MONTHS[day.getMonth()] + " " + day.getDate();
  }

  function monthGrid(cursor, firstDay) {
    var year = cursor.getFullYear();
    var month = cursor.getMonth();
    var first = new Date(year, month, 1);
    var startPad = (first.getDay() - firstDay + 7) % 7;
    var gridStart = new Date(year, month, 1 - startPad);
    var last = new Date(year, month + 1, 0);
    var endPad = (firstDay + 6 - last.getDay() + 7) % 7;
    var gridEnd = new Date(year, month, last.getDate() + endPad);
    var days = [];
    var d = new Date(gridStart);
    while (d <= gridEnd) {
      days.push(new Date(d));
      d.setDate(d.getDate() + 1);
    }
    var weeks = [];
    for (var i = 0; i < days.length; i += 7) weeks.push(days.slice(i, i + 7));
    return weeks;
  }

  function copyTheme(fromEl, toEl) {
    var cs = window.getComputedStyle(fromEl);
    [
      "primary",
      "secondary",
      "text",
      "bg",
      "header",
      "header-text",
      "border",
      "button",
      "button-text",
      "radius",
      "font",
      "font-heading",
      "surface",
      "muted",
      "popup",
      "popup-text",
      "popup-muted",
    ].forEach(function (k) {
      var v = cs.getPropertyValue("--nc-" + k).trim();
      if (v) toEl.style.setProperty("--nc-" + k, v);
    });
    var font = cs.getPropertyValue("--nc-font").trim();
    if (font) toEl.style.fontFamily = font;
  }

  function coverHtml(event, extraClass, settings) {
    var cls = "nc-cover " + (extraClass || "");
    if (event.image) {
      return (
        '<div class="' +
        cls +
        '" style="background-image:url(\'' +
        esc(event.image) +
        "')\" aria-hidden=\"true\"></div>"
      );
    }
    var color = eventColor(event, settings);
    return (
      '<div class="' +
      cls +
      '" style="background:linear-gradient(160deg,' +
      esc(color) +
      "," +
      darken(color, 0.42) +
      ')" aria-hidden="true">' +
      COVER_SVG +
      "</div>"
    );
  }

  function catPills(event) {
    return (event.categories || [])
      .map(function (c) {
        var col = c.color || "#2f5d50";
        return (
          '<span class="nc-cat" style="background:' +
          esc(col) +
          ";color:" +
          contrastText(col) +
          '">' +
          esc(c.name) +
          "</span>"
        );
      })
      .join("");
  }

  function mount(root) {
    DATA = window.NieuwCalendar;
    var settings = DATA.settings || {};
    var firstDay = Number(settings.first_day || settings.firstDayOfWeek || 0) === 1 ? 1 : 0;
    var radius = Number(settings.border_radius || settings.borderRadius || 12);
    var chipRadius = Math.max(4, radius / 3);
    var view = root.getAttribute("data-view") === "list" ? "list" : "month";
    var cursor = new Date();
    cursor.setDate(1);
    cursor.setHours(0, 0, 0, 0);
    var cat = "all";
    var selectedId = null;
    var overlay = null;
    var dialog = null;
    var lastFocus = null;

    function eventsVisible() {
      return (DATA.events || []).filter(function (e) {
        if (cat !== "all") {
          var ids = (e.categories || []).map(function (c) {
            return String(c.id);
          });
          if (ids.indexOf(String(cat)) === -1) return false;
        }
        var showPast = settings.show_past_events;
        if (showPast === 0 || showPast === "0" || showPast === false) {
          if (isPastEvent(e)) return false;
        }
        return true;
      });
    }

    function listEvents(visible) {
      var y = cursor.getFullYear();
      var m = cursor.getMonth();
      return visible.filter(function (e) {
        var sParts = String(e.startDate || "").split("-").map(Number);
        var eParts = String(e.endDate || e.startDate || "").split("-").map(Number);
        var start = new Date(sParts[0], (sParts[1] || 1) - 1, 1);
        var end = new Date(eParts[0], (eParts[1] || 1) - 1, 1);
        var cur = new Date(y, m, 1);
        return start <= cur && end >= cur;
      });
    }

    function findEvent(id) {
      var list = DATA.events || [];
      for (var i = 0; i < list.length; i++) {
        if (String(list[i].id) === String(id)) return list[i];
      }
      return null;
    }

    function render() {
      var visible = eventsVisible();
      var html = '<div class="nc-main">';
      html += '<div class="nc-toolbar">';
      html +=
        '<div class="nc-month-nav">' +
        '<button type="button" class="nc-btn nc-btn-ghost nc-btn-icon" data-act="prev" aria-label="' +
        esc(t("prev", "Previous month")) +
        '">' +
        ICONS.chevronLeft +
        "</button>" +
        '<h2 class="nc-month-label">' +
        esc(monthTitle(cursor)) +
        "</h2>" +
        '<button type="button" class="nc-btn nc-btn-ghost nc-btn-icon" data-act="next" aria-label="' +
        esc(t("next", "Next month")) +
        '">' +
        ICONS.chevronRight +
        "</button></div>";
      html +=
        '<div class="nc-toggle" role="tablist">' +
        '<button type="button" class="nc-toggle-btn' +
        (view === "month" ? " is-on" : "") +
        '" data-act="month" role="tab" aria-selected="' +
        (view === "month" ? "true" : "false") +
        '">' +
        ICONS.calendar +
        esc(t("month", "Month")) +
        "</button>" +
        '<button type="button" class="nc-toggle-btn' +
        (view === "list" ? " is-on" : "") +
        '" data-act="list" role="tab" aria-selected="' +
        (view === "list" ? "true" : "false") +
        '">' +
        ICONS.list +
        esc(t("list", "List")) +
        "</button></div></div>";

      var cats = DATA.categories || [];
      html += '<div class="nc-filters">';
      html +=
        '<button type="button" class="nc-pill' +
        (cat === "all" ? " is-on" : "") +
        '" data-cat="all">' +
        esc(t("all", "All")) +
        "</button>";
      cats.forEach(function (c) {
        html +=
          '<button type="button" class="nc-pill' +
          (String(cat) === String(c.id) ? " is-on" : "") +
          '" data-cat="' +
          esc(c.id) +
          '">' +
          (c.color
            ? '<span class="nc-dot" style="background:' + esc(c.color) + '" aria-hidden="true"></span>'
            : "") +
          esc(c.name) +
          "</button>";
      });
      html += "</div>";

      html += view === "month" ? monthHtml(visible) : listHtml(listEvents(visible));

      var tz = String(settings.timezone || "").replace(/_/g, " ");
      if (tz) {
        html +=
          '<p class="nc-tz">' +
          esc(t("times", "Times shown in")) +
          " " +
          esc(tz) +
          ".</p>";
      }
      html += "</div>";

      root.innerHTML = html;

      root.querySelectorAll("[data-act]").forEach(function (btn) {
        btn.addEventListener("click", function () {
          var act = btn.getAttribute("data-act");
          if (act === "prev") cursor = new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1);
          if (act === "next") cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
          if (act === "month") view = "month";
          if (act === "list") view = "list";
          render();
        });
      });
      root.querySelectorAll("[data-cat]").forEach(function (btn) {
        btn.addEventListener("click", function () {
          cat = btn.getAttribute("data-cat");
          render();
        });
      });
      root.querySelectorAll("[data-event]").forEach(function (btn) {
        btn.addEventListener("click", function (ev) {
          ev.preventDefault();
          selectedId = btn.getAttribute("data-event");
          openModal();
        });
      });
    }

    function monthHtml(visible) {
      var weeks = monthGrid(cursor, firstDay);
      var labels = firstDay === 1 ? LABELS_MON : LABELS_SUN;
      var today = new Date();
      var html = '<div class="nc-grid"><div class="nc-weekdays">';
      labels.forEach(function (d) {
        html += "<span>" + esc(d) + "</span>";
      });
      html += "</div><div class=\"nc-weeks\">";

      weeks.forEach(function (week) {
        var weekStart = week[0];
        var weekEnd = week[6];
        var overlapping = visible.filter(function (event) {
          return eventStartDay(event) <= weekEnd && eventEndDay(event) >= weekStart;
        });
        var lanes = [];
        overlapping.forEach(function (event) {
          var placed = false;
          for (var i = 0; i < lanes.length; i++) {
            var last = lanes[i][lanes[i].length - 1];
            if (eventStartDay(event) > eventEndDay(last)) {
              lanes[i].push(event);
              placed = true;
              break;
            }
          }
          if (!placed) lanes.push([event]);
        });
        var maxLanes = 3;

        html += '<div class="nc-week">';
        week.forEach(function (day) {
          var inMonth = day.getMonth() === cursor.getMonth();
          var isToday = sameDay(day, today);
          html +=
            '<div class="nc-day' +
            (inMonth ? "" : " is-out") +
            (isToday ? " is-today" : "") +
            '"><span class="nc-day-num">' +
            day.getDate() +
            "</span></div>";
        });

        html += '<div class="nc-lanes">';
        lanes.slice(0, maxLanes).forEach(function (lane) {
          html += '<div class="nc-lane">';
          lane.forEach(function (event) {
            var s = eventStartDay(event);
            var e = eventEndDay(event);
            var startIdx = 0;
            var i;
            for (i = 0; i < 7; i++) {
              if (sameDay(week[i], s) || week[i] >= s) {
                startIdx = i;
                break;
              }
              startIdx = 6;
            }
            var endIdxRaw = -1;
            for (i = 0; i < 7; i++) {
              if (sameDay(week[i], e)) {
                endIdxRaw = i;
                break;
              }
            }
            var endIdx = endIdxRaw === -1 ? (e < week[0] ? 0 : 6) : endIdxRaw;
            var left = (startIdx / 7) * 100;
            var width = ((endIdx - startIdx + 1) / 7) * 100;
            var color = eventColor(event, settings);
            var fg = contrastText(color);
            html +=
              '<button type="button" class="nc-chip" data-event="' +
              esc(event.id) +
              '" title="' +
              esc(event.title) +
              '" style="left:calc(' +
              left +
              "% + 2px);width:calc(" +
              width +
              "% - 4px);background:" +
              esc(color) +
              ";color:" +
              fg +
              ";border-radius:" +
              chipRadius +
              'px">' +
              esc(event.title) +
              "</button>";
          });
          html += "</div>";
        });
        if (lanes.length > maxLanes) {
          html +=
            '<div class="nc-more">+' +
            (lanes.length - maxLanes) +
            " " +
            esc(t("more", "more")) +
            "</div>";
        }
        html += "</div></div>";
      });

      html += "</div></div>";
      return html;
    }

    function listHtml(items) {
      if (!items.length) {
        return (
          '<div class="nc-list-empty">' +
          '<p class="nc-list-empty-title">' +
          esc(t("emptyTitle", "No events in this view")) +
          "</p>" +
          '<p class="nc-list-empty-body">' +
          esc(t("emptyBody", "Try another category, or add an event from the dashboard.")) +
          "</p></div>"
        );
      }
      var groups = [];
      items.forEach(function (event) {
        var day = eventStartDay(event);
        var last = groups[groups.length - 1];
        if (last && sameDay(last.day, day)) last.items.push(event);
        else groups.push({ day: day, items: [event] });
      });
      var html = '<div class="nc-list">';
      groups.forEach(function (group) {
        html +=
          "<section><h3 class=\"nc-list-heading\">" +
          esc(listHeading(group.day)) +
          '</h3><ul class="nc-list-items">';
        group.items.forEach(function (event) {
          var color = eventColor(event, settings);
          html +=
            "<li><button type=\"button\" class=\"nc-card\" data-event=\"" +
            esc(event.id) +
            '">' +
            coverHtml(event, "nc-cover-list", settings) +
            '<span class="nc-stripe" style="background:' +
            esc(color) +
            '"></span>' +
            '<span class="nc-card-body"><span class="nc-card-row">' +
            '<span class="nc-card-title">' +
            esc(event.title) +
            "</span>" +
            catPills(event) +
            "</span>" +
            '<span class="nc-when">' +
            esc(formatEventWhen(event, settings)) +
            "</span>" +
            (event.location
              ? '<span class="nc-loc">' +
                ICONS.pin +
                "<span>" +
                esc(truncate(event.location, 120)) +
                "</span></span>"
              : "") +
            "</span></button></li>";
        });
        html += "</ul></section>";
      });
      html += "</div>";
      return html;
    }

    function onKey(ev) {
      if (ev.key === "Escape") closeModal();
    }

    function closeModal() {
      selectedId = null;
      if (overlay) {
        overlay.remove();
        overlay = null;
      }
      if (dialog) {
        dialog.remove();
        dialog = null;
      }
      document.removeEventListener("keydown", onKey);
      document.body.classList.remove("nc-modal-open");
      if (lastFocus && typeof lastFocus.focus === "function") {
        lastFocus.focus();
      }
      lastFocus = null;
    }

    function openModal() {
      var event = findEvent(selectedId);
      if (!event) return;
      closeModal();
      selectedId = event.id;
      lastFocus = document.activeElement;
      var color = eventColor(event, settings);
      overlay = document.createElement("div");
      overlay.className = "nc-overlay";
      overlay.addEventListener("click", closeModal);
      dialog = document.createElement("div");
      dialog.className = "nc-dialog";
      dialog.setAttribute("role", "dialog");
      dialog.setAttribute("aria-modal", "true");
      dialog.setAttribute("aria-labelledby", "nc-dialog-title");
      copyTheme(root, dialog);
      dialog.style.borderRadius = Math.max(0, radius) + "px";
      var cats = catPills(event);
      if (event.colorOverride) {
        cats +=
          '<span class="nc-cat" style="background:' +
          esc(color) +
          ";color:" +
          contrastText(color) +
          '">' +
          esc(t("custom", "Custom color")) +
          "</span>";
      }
      dialog.innerHTML =
        coverHtml(event, "nc-cover-dialog", settings) +
        '<div class="nc-dialog-body">' +
        '<div class="nc-dialog-top">' +
        '<h2 class="nc-dialog-title" id="nc-dialog-title">' +
        esc(event.title) +
        "</h2>" +
        '<button type="button" class="nc-dialog-close" aria-label="' +
        esc(t("close", "Close")) +
        '">' +
        ICONS.x +
        "</button></div>" +
        '<p class="nc-dialog-when">' +
        esc(formatEventWhen(event, settings)) +
        "</p>" +
        (event.location
          ? '<p class="nc-dialog-loc">' +
            ICONS.pin +
            "<span>" +
            esc(truncate(event.location, 180)) +
            "</span></p>"
          : "") +
        (cats ? '<div class="nc-dialog-cats">' + cats + "</div>" : "") +
        (event.description
          ? '<p class="nc-dialog-desc">' + esc(event.description) + "</p>"
          : "") +
        "</div>";
      dialog.querySelector(".nc-dialog-close").addEventListener("click", closeModal);
      dialog.addEventListener("click", function (ev) {
        ev.stopPropagation();
      });
      document.body.appendChild(overlay);
      document.body.appendChild(dialog);
      document.body.classList.add("nc-modal-open");
      document.addEventListener("keydown", onKey);
      var scrollHide;
      dialog.addEventListener("scroll", function () {
        dialog.classList.add("is-scrolling");
        clearTimeout(scrollHide);
        scrollHide = setTimeout(function () {
          if (dialog) dialog.classList.remove("is-scrolling");
        }, 700);
      });
      dialog.querySelector(".nc-dialog-close").focus();
    }

    render();
  }
})();
