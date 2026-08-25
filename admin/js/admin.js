(function () {
  const allDay = document.getElementById("nieuw_event_all_day");
  if (allDay) {
    const fields = document.querySelectorAll(".nieuw-time-field");
    function syncTimes() {
      fields.forEach((el) => {
        el.style.opacity = allDay.checked ? "0.4" : "1";
        el.querySelectorAll("input").forEach((input) => {
          input.disabled = allDay.checked;
        });
      });
    }
    allDay.addEventListener("change", syncTimes);
    syncTimes();
  }

  document.querySelectorAll("[data-confirm]").forEach((el) => {
    el.addEventListener("click", (event) => {
      const msg = el.getAttribute("data-confirm");
      if (msg && !window.confirm(msg)) {
        event.preventDefault();
      }
    });
  });

  const mediaRoot = document.querySelector("[data-nieuw-media]");
  if (mediaRoot && typeof wp !== "undefined" && wp.media) {
    const input = mediaRoot.querySelector('input[name="thumbnail_id"]');
    const preview = mediaRoot.querySelector(".nieuw-media-preview");
    const choose = mediaRoot.querySelector("[data-nieuw-media-choose]");
    const remove = mediaRoot.querySelector("[data-nieuw-media-remove]");
    let frame;
    if (choose) {
      choose.addEventListener("click", function () {
        if (frame) {
          frame.open();
          return;
        }
        frame = wp.media({
          title: (window.NieuwCalendarAdmin && NieuwCalendarAdmin.chooseImage) || "Choose image",
          button: { text: (window.NieuwCalendarAdmin && NieuwCalendarAdmin.useImage) || "Use image" },
          multiple: false,
        });
        frame.on("select", function () {
          const att = frame.state().get("selection").first().toJSON();
          input.value = att.id;
          const src = (att.sizes && att.sizes.medium && att.sizes.medium.url) || att.url;
          preview.innerHTML = src ? '<img src="' + src + '" alt="" />' : "";
        });
        frame.open();
      });
    }
    if (remove) {
      remove.addEventListener("click", function () {
        input.value = "";
        preview.innerHTML = "";
      });
    }
  }
})();
