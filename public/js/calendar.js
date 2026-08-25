(function () {
  const roots = document.querySelectorAll(".nieuw-calendar");
  if (!roots.length || typeof NieuwCalendar === "undefined") return;

  const data = NieuwCalendar;
  const settings = data.settings || {};
  const firstDay = Number(settings.first_day || 0);
  const labelsSun = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
  const labelsMon = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];

  function pad(n) {
    return String(n).padStart(2, "0");
  }
  function ymd(d) {
    return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate());
  }
  function parseDay(s) {
    const [y, m, d] = (s || "").split("-").map(Number);
    return new Date(y, (m || 1) - 1, d || 1);
  }
  function contrast(hex) {
    const h = (hex || "#2f5d50").replace("#", "");
    const full = h.length === 3 ? h.split("").map((c) => c + c).join("") : h;
    const n = parseInt(full, 16);
    const r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255;
    const L = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    return L > 0.55 ? "#1c1915" : "#fbf8f2";
  }

  roots.forEach((root) => {
    let view = root.getAttribute("data-view") === "list" ? "list" : "month";
    let cursor = new Date();
    cursor.setDate(1);
    let cat = "all";

    function events() {
      return (data.events || []).filter((e) => {
        if (cat !== "all" && !(e.categories || []).some((c) => String(c.id) === String(cat))) {
          return false;
        }
        if (!settings.show_past_events) {
          const end = parseDay(e.endDate || e.startDate);
          const today = new Date();
          today.setHours(0, 0, 0, 0);
          if (end < today) return false;
        }
        return true;
      });
    }

    function render() {
      const monthLabel = cursor.toLocaleString(undefined, { month: "long", year: "numeric" });
      const cats = data.categories || [];
      root.innerHTML =
        '<div class="nc-toolbar">' +
        '<div><button class="nc-btn" data-act="prev" aria-label="Previous">‹</button>' +
        '<span class="nc-month-label">' + monthLabel + "</span>" +
        '<button class="nc-btn" data-act="next" aria-label="Next">›</button></div>' +
        '<div><button class="nc-btn' + (view === "month" ? " is-on" : "") + '" data-act="month">' +
        (data.i18n.month || "Month") +
        '</button><button class="nc-btn' + (view === "list" ? " is-on" : "") + '" data-act="list">' +
        (data.i18n.list || "List") +
        "</button></div>" +
        '<a class="nc-btn" href="' + data.ical + '">' + (data.i18n.sub || "Subscribe .ics") + "</a>" +
        "</div>" +
        '<div class="nc-filters">' +
        '<button class="nc-pill' + (cat === "all" ? " is-on" : "") + '" data-cat="all">' +
        (data.i18n.all || "All") +
        "</button>" +
        cats
          .map(
            (c) =>
              '<button class="nc-pill' +
              (String(cat) === String(c.id) ? " is-on" : "") +
              '" data-cat="' +
              c.id +
              '">' +
              c.name +
              "</button>"
          )
          .join("") +
        "</div>" +
        (view === "month" ? monthHtml() : listHtml());

      root.querySelectorAll("[data-act]").forEach((btn) => {
        btn.addEventListener("click", () => {
          const act = btn.getAttribute("data-act");
          if (act === "prev") cursor.setMonth(cursor.getMonth() - 1);
          if (act === "next") cursor.setMonth(cursor.getMonth() + 1);
          if (act === "month") view = "month";
          if (act === "list") view = "list";
          render();
        });
      });
      root.querySelectorAll("[data-cat]").forEach((btn) => {
        btn.addEventListener("click", () => {
          cat = btn.getAttribute("data-cat");
          render();
        });
      });
    }

    function monthHtml() {
      const start = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
      const startPad = (start.getDay() - firstDay + 7) % 7;
      const gridStart = new Date(start);
      gridStart.setDate(1 - startPad);
      const labels = firstDay === 1 ? labelsMon : labelsSun;
      let html = '<div class="nc-grid"><div class="nc-weekdays">';
      labels.forEach((d) => {
        html += "<div>" + d + "</div>";
      });
      html += "</div>";
      for (let w = 0; w < 6; w++) {
        html += '<div class="nc-week">';
        for (let i = 0; i < 7; i++) {
          const day = new Date(gridStart);
          day.setDate(gridStart.getDate() + w * 7 + i);
          const key = ymd(day);
          const out = day.getMonth() !== cursor.getMonth();
          const dayEvents = events().filter((e) => {
            return key >= e.startDate && key <= (e.endDate || e.startDate);
          });
          html +=
            '<div class="nc-day' + (out ? " is-out" : "") + '"><strong>' + day.getDate() + "</strong>";
          dayEvents.slice(0, 3).forEach((e) => {
            html +=
              '<a class="nc-event" href="' +
              (e.url || "#") +
              '" style="background:' +
              e.color +
              ";color:" +
              contrast(e.color) +
              '">' +
              e.title +
              "</a>";
          });
          html += "</div>";
        }
        html += "</div>";
      }
      html += "</div>";
      return html;
    }

    function listHtml() {
      const y = cursor.getFullYear();
      const m = cursor.getMonth();
      const items = events().filter((e) => {
        const s = parseDay(e.startDate);
        const en = parseDay(e.endDate || e.startDate);
        const from = new Date(y, m, 1);
        const to = new Date(y, m + 1, 0);
        return s <= to && en >= from;
      });
      if (!items.length) return '<p class="nc-meta">No events this month.</p>';
      return (
        '<div class="nc-list">' +
        items
          .map((e) => {
            const when = e.allDay
              ? e.startDate + (e.endDate && e.endDate !== e.startDate ? " – " + e.endDate : "") + " · All day"
              : e.startDate + (e.startTime ? " · " + e.startTime : "");
            return (
              '<a class="nc-card" href="' +
              (e.url || "#") +
              '"><h3>' +
              e.title +
              '</h3><div class="nc-meta">' +
              when +
              (e.location ? " · " + e.location : "") +
              "</div></a>"
            );
          })
          .join("") +
        "</div>"
      );
    }

    render();
  });
})();
