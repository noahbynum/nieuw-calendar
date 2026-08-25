(function () {
  const allDay = document.getElementById("nieuw_event_all_day");
  if (!allDay) return;

  const fields = document.querySelectorAll(".nieuw-time-field");

  function sync() {
    fields.forEach((el) => {
      el.style.opacity = allDay.checked ? "0.4" : "1";
      el.querySelectorAll("input").forEach((input) => {
        input.disabled = allDay.checked;
      });
    });
  }

  allDay.addEventListener("change", sync);
  sync();
})();
