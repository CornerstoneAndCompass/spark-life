/* ============================================================
   Spark Life - homepage interactions
   ============================================================ */
(function () {
  "use strict";

  /* ---- year ---- */
  var yr = document.getElementById("year");
  if (yr) yr.textContent = new Date().getFullYear();

  /* ---- sticky header shadow ---- */
  var header = document.getElementById("header");
  var onScroll = function () {
    header.classList.toggle("is-stuck", window.scrollY > 8);
  };
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  /* ---- mobile nav ---- */
  var burger = document.getElementById("burger");
  var nav = document.getElementById("nav");
  var closeNav = function () {
    nav.classList.remove("is-open");
    burger.classList.remove("is-open");
    burger.setAttribute("aria-expanded", "false");
    document.body.style.overflow = "";
  };
  burger.addEventListener("click", function () {
    var open = nav.classList.toggle("is-open");
    burger.classList.toggle("is-open", open);
    burger.setAttribute("aria-expanded", String(open));
    document.body.style.overflow = open ? "hidden" : "";
  });
  nav.querySelectorAll("a").forEach(function (a) {
    a.addEventListener("click", closeNav);
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") closeNav();
  });

  /* ---- non-clickable demo links ---- */
  document.querySelectorAll("[data-soon]").forEach(function (el) {
    el.addEventListener("click", function (e) {
      e.preventDefault();
    });
  });

  /* ---- FAQ accordion ---- */
  document.querySelectorAll(".acc__q").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var panel = btn.nextElementSibling;
      var open = btn.getAttribute("aria-expanded") === "true";

      document.querySelectorAll(".acc__q").forEach(function (other) {
        if (other !== btn) {
          other.setAttribute("aria-expanded", "false");
          other.nextElementSibling.style.maxHeight = null;
        }
      });

      btn.setAttribute("aria-expanded", String(!open));
      panel.style.maxHeight = open ? null : panel.scrollHeight + "px";
    });
  });

  /* ---- scroll reveal ---- */
  var revealEls = [].slice.call(
    document.querySelectorAll(
      ".section__head, .svc, .step, .review, .stat, .why__copy, " +
      ".about__media, .about__copy, .qform, .quote-sec__copy, .area__list li"
    )
  );
  revealEls.forEach(function (el, i) {
    el.setAttribute("data-reveal", "");
    el.style.transitionDelay = (i % 4) * 80 + "ms";
  });

  if ("IntersectionObserver" in window) {
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting) {
            en.target.classList.add("in");
            io.unobserve(en.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
    );
    revealEls.forEach(function (el) {
      io.observe(el);
    });
  } else {
    revealEls.forEach(function (el) {
      el.classList.add("in");
    });
  }

  /* ---- animated stat counters ---- */
  var counted = false;
  var animateCounts = function () {
    if (counted) return;
    counted = true;
    document.querySelectorAll(".stat__num").forEach(function (el) {
      var target = parseFloat(el.getAttribute("data-count"));
      var decimals = parseInt(el.getAttribute("data-decimals") || "0", 10);
      var suffix = el.getAttribute("data-suffix") || "";
      var start = performance.now();
      var dur = 1400;
      var tick = function (now) {
        var p = Math.min((now - start) / dur, 1);
        var eased = 1 - Math.pow(1 - p, 3);
        var val = (target * eased).toFixed(decimals);
        el.textContent = val + suffix;
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    });
  };
  var statsWrap = document.querySelector(".stats");
  if (statsWrap && "IntersectionObserver" in window) {
    var sio = new IntersectionObserver(
      function (entries) {
        if (entries[0].isIntersecting) {
          animateCounts();
          sio.disconnect();
        }
      },
      { threshold: 0.4 }
    );
    sio.observe(statsWrap);
  } else {
    animateCounts();
  }

  /* ---- demo quote form ---- */
  var form = document.getElementById("qform");
  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var ok = document.getElementById("qformOk");
      if (ok) ok.hidden = false;
      form
        .querySelector('button[type="submit"]')
        .setAttribute("disabled", "true");
      ok.scrollIntoView({ behavior: "smooth", block: "center" });
    });
  }
})();
