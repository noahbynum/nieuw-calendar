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

  function attachPopupScrollbar(dialogEl) {
    var scroller = dialogEl.querySelector(".nc-dialog-scroll");
    var track = dialogEl.querySelector(".nc-scrollbar");
    var thumb = dialogEl.querySelector(".nc-scrollbar-thumb");
    if (!scroller || !track || !thumb) return;
    var hideTimer = null;
    function updateThumb() {
      var view = scroller.clientHeight;
      var full = scroller.scrollHeight;
      if (full <= view + 1) {
        thumb.style.height = "0px";
        dialogEl.classList.remove("is-scrolling");
        return false;
      }
      var trackH = track.clientHeight || view;
      var thumbH = Math.max(18, Math.round((view / full) * trackH));
      var maxTop = Math.max(0, trackH - thumbH);
      var maxScroll = full - view;
      var top = maxScroll > 0 ? (scroller.scrollTop / maxScroll) * maxTop : 0;
      thumb.style.height = thumbH + "px";
      thumb.style.transform = "translateY(" + top + "px)";
      return true;
    }
    function flash() {
      if (!updateThumb()) return;
      dialogEl.classList.add("is-scrolling");
      if (hideTimer) window.clearTimeout(hideTimer);
      hideTimer = window.setTimeout(function () {
        if (dialogEl) dialogEl.classList.remove("is-scrolling");
      }, 750);
    }
    scroller.addEventListener("scroll", flash, { passive: true });
    updateThumb();
  }
}
