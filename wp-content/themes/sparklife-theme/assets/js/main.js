/* ============================================================
   Spark Life Electrical — front-end behaviour
   Sticky header · mobile nav · FAQ accordion · scroll reveal ·
   animated stat counters · AJAX enquiry forms (CC Fields).
   ============================================================ */
(function () {
  "use strict";

  var reduceMotion = window.matchMedia &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---- footer year (harmless if the server already printed it) ---- */
  var yr = document.getElementById("year");
  if (yr) yr.textContent = new Date().getFullYear();

  /* ---- sticky header shadow ---- */
  var header = document.getElementById("header");
  if (header) {
    var onScroll = function () {
      header.classList.toggle("is-stuck", window.scrollY > 8);
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  /* ---- mobile nav ---- */
  var burger = document.getElementById("burger");
  var nav = document.getElementById("nav");
  if (burger && nav) {
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
  }

  /* ---- FAQ accordion ---- */
  var questions = [].slice.call(document.querySelectorAll(".acc__q"));
  questions.forEach(function (btn) {
    btn.addEventListener("click", function () {
      var panel = btn.nextElementSibling;
      var open = btn.getAttribute("aria-expanded") === "true";

      // Only one open at a time, per accordion.
      var group = btn.closest(".acc");
      if (group) {
        group.querySelectorAll(".acc__q").forEach(function (other) {
          if (other !== btn) {
            other.setAttribute("aria-expanded", "false");
            if (other.nextElementSibling) other.nextElementSibling.style.maxHeight = null;
          }
        });
      }

      btn.setAttribute("aria-expanded", String(!open));
      if (panel) panel.style.maxHeight = open ? null : panel.scrollHeight + "px";
    });
  });

  /* ---- scroll reveal ---- */
  var revealEls = [].slice.call(
    document.querySelectorAll(
      ".section__head, .svc, .svc-card, .step, .review, .stat, .why__copy, " +
      ".about__media, .about__copy, .svcrow__media, .svcrow__copy, .qform, " +
      ".quote-sec__copy, .area__list li, .gallery__item, .ticks li, .hero__copy, .hero__card"
    )
  );
  if (!reduceMotion && "IntersectionObserver" in window) {
    revealEls.forEach(function (el, i) {
      el.setAttribute("data-reveal", "");
      el.style.transitionDelay = (i % 4) * 80 + "ms";
    });
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
    revealEls.forEach(function (el) { io.observe(el); });
  }

  /* ---- animated stat counters ---- */
  var animateStats = function (wrap) {
    wrap.querySelectorAll(".stat__num").forEach(function (el) {
      var target = parseFloat(el.getAttribute("data-count"));
      if (isNaN(target)) return;
      var decimals = parseInt(el.getAttribute("data-decimals") || "0", 10);
      var suffix = el.getAttribute("data-suffix") || "";

      if (reduceMotion) {
        el.textContent = target.toFixed(decimals) + suffix;
        return;
      }
      var start = performance.now();
      var dur = 1400;
      var tick = function (now) {
        var p = Math.min((now - start) / dur, 1);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = (target * eased).toFixed(decimals) + suffix;
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    });
  };

  document.querySelectorAll(".stats").forEach(function (wrap) {
    if (!("IntersectionObserver" in window)) { animateStats(wrap); return; }
    var sio = new IntersectionObserver(function (entries) {
      if (entries[0].isIntersecting) {
        animateStats(wrap);
        sio.disconnect();
      }
    }, { threshold: 0.4 });
    sio.observe(wrap);
  });

  /* ---- enquiry forms → CC Fields (admin-ajax) ---- */
  var ajaxUrl = (window.SPARKLIFE && window.SPARKLIFE.ajax_url) || "";

  document.querySelectorAll("form.js-form").forEach(function (form) {
    var button = form.querySelector('button[type="submit"]');
    var success = form.querySelector(".cform__success");

    var showError = function (message) {
      var box = form.querySelector(".cform__error");
      if (!box) {
        box = document.createElement("p");
        box.className = "cform__error";
        form.appendChild(box);
      }
      box.textContent = message;
      box.hidden = false;
    };

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      // Native validation first — required name/phone.
      var invalid = null;
      form.querySelectorAll("[required]").forEach(function (input) {
        var ok = input.value.trim() !== "";
        input.setAttribute("aria-invalid", ok ? "false" : "true");
        if (!ok && !invalid) invalid = input;
      });
      if (invalid) { invalid.focus(); return; }

      if (!ajaxUrl) { form.submit(); return; }

      form.classList.add("is-sending");
      if (button) {
        button.dataset.label = button.textContent;
        button.textContent = "Sending…";
        button.disabled = true;
      }

      fetch(ajaxUrl, {
        method: "POST",
        credentials: "same-origin",
        body: new FormData(form)
      })
        .then(function (r) { return r.json().catch(function () { return null; }); })
        .then(function (res) {
          if (res && res.success) {
            if (success) {
              success.hidden = false;
              success.scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth", block: "center" });
            }
            form.querySelectorAll("input:not([type=hidden]), textarea, select").forEach(function (f) { f.value = ""; });
            if (button) button.textContent = "Sent ✓";
            return;
          }
          var msg = (res && res.data && res.data.message) ||
            "Sorry, we couldn't send that. Please try again, or give us a call.";
          showError(msg);
          if (button) {
            button.textContent = button.dataset.label || "Send";
            button.disabled = false;
          }
        })
        .catch(function () {
          showError("Something went wrong sending your message. Please give us a call instead.");
          if (button) {
            button.textContent = button.dataset.label || "Send";
            button.disabled = false;
          }
        })
        .finally(function () {
          form.classList.remove("is-sending");
        });
    });
  });
})();
