(function () {
  "use strict";

  function destroyMalihu(node) {
    if (!node || !node.classList || !node.classList.contains("mCustomScrollbar")) {
      return;
    }
    if (typeof jQuery !== "undefined" && jQuery.fn && jQuery.fn.mCustomScrollbar) {
      try {
        var $n = jQuery(node);
        if ($n.data("mCS")) {
          $n.mCustomScrollbar("destroy");
        }
      } catch (e) {
        /* ignore */
      }
    }
    node.classList.remove("mCustomScrollbar");
  }

  function releaseParentMalihu(root) {
    var el = root.parentElement;
    for (var i = 0; i < 10 && el; i += 1, el = el.parentElement) {
      if (el.classList && el.classList.contains("mCustomScrollbar")) {
        destroyMalihu(el);
        break;
      }
    }
  }

  function releaseNestedScrollbars(root) {
    root.querySelectorAll("ul.randee-menu__mega-l2").forEach(destroyMalihu);
  }

  function scheduleRelease(root) {
    releaseParentMalihu(root);
    releaseNestedScrollbars(root);
    window.setTimeout(function () {
      releaseParentMalihu(root);
      releaseNestedScrollbars(root);
    }, 50);
    window.setTimeout(function () {
      releaseParentMalihu(root);
      releaseNestedScrollbars(root);
    }, 320);
  }

  /** Правая колонка по высоте как левая (абсолютные панели не растягивают grid сами) */
  function syncStageHeight(root) {
    var aside = root.querySelector(".randee-menu__mega-aside");
    var stage = root.querySelector(".randee-menu__mega-stage");
    if (!aside || !stage) {
      return;
    }
    var h = aside.offsetHeight;
    if (h > 0) {
      stage.style.minHeight = h + "px";
    }
  }

  function applyAsideCurrent(root, row) {
    var idx = row.getAttribute("data-randee-mega-index");
    var isDropdown = row.classList.contains("randee-menu__mega-aside-row--dropdown");
    root.querySelectorAll(".randee-menu__mega-aside-row--current").forEach(function (r) {
      r.classList.remove("randee-menu__mega-aside-row--current");
    });
    root.querySelectorAll(".randee-menu__mega-panel-wrap--current").forEach(function (p) {
      p.classList.remove("randee-menu__mega-panel-wrap--current");
      p.setAttribute("aria-hidden", "true");
    });
    row.classList.add("randee-menu__mega-aside-row--current");
    if (isDropdown && idx) {
      var panel = root.querySelector('.randee-menu__mega-panel-wrap[data-randee-mega-panel="' + idx + '"]');
      if (panel) {
        panel.classList.add("randee-menu__mega-panel-wrap--current");
        panel.setAttribute("aria-hidden", "false");
      }
    }
  }

  function closeSiblingL3(item) {
    var l2 = item.closest(".randee-menu__mega-l2");
    if (!l2) {
      return;
    }
    l2.querySelectorAll(".randee-menu__mega-l2-item--with-children").forEach(function (sib) {
      if (sib === item) {
        return;
      }
      var btn = sib.querySelector(".randee-menu__mega-arrow");
      var panel = sib.querySelector(".randee-menu__mega-l3");
      if (btn) {
        btn.classList.remove("randee-menu__mega-arrow--open");
        btn.setAttribute("aria-expanded", "false");
      }
      if (panel) {
        panel.classList.remove("randee-menu__mega-l3--open");
      }
    });
  }

  function bindMega(root) {
    if (!root || root.dataset.randeeMegaBound === "1") {
      return;
    }
    root.dataset.randeeMegaBound = "1";

    scheduleRelease(root);

    syncStageHeight(root);
    window.requestAnimationFrame(function () {
      syncStageHeight(root);
    });
    window.setTimeout(function () {
      syncStageHeight(root);
    }, 400);

    if (!window.__randeeMegaResize) {
      window.__randeeMegaResize = true;
      window.addEventListener(
        "resize",
        function () {
          document.querySelectorAll(".randee-menu--mega").forEach(syncStageHeight);
        },
        { passive: true }
      );
    }

    root.querySelectorAll(".randee-menu__mega-panel-wrap").forEach(function (p) {
      if (!p.classList.contains("randee-menu__mega-panel-wrap--current")) {
        p.setAttribute("aria-hidden", "true");
      } else {
        p.setAttribute("aria-hidden", "false");
      }
    });

    root.querySelectorAll(".randee-menu__mega-aside-row").forEach(function (row) {
      row.addEventListener("mouseenter", function () {
        if (row.classList.contains("randee-menu__mega-aside-row--current")) {
          return;
        }
        var timer = window.setTimeout(function () {
          applyAsideCurrent(root, row);
          scheduleRelease(root);
          syncStageHeight(root);
        }, 200);
        row.addEventListener(
          "mouseleave",
          function onLeave() {
            window.clearTimeout(timer);
            row.removeEventListener("mouseleave", onLeave);
          },
          { once: true }
        );
      });
    });

    root.querySelectorAll(".randee-menu__mega-arrow").forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        var li = btn.closest(".randee-menu__mega-l2-item--with-children");
        if (!li) {
          return;
        }
        var panel = li.querySelector(".randee-menu__mega-l3");
        if (!panel) {
          return;
        }
        var open = !btn.classList.contains("randee-menu__mega-arrow--open");
        closeSiblingL3(li);
        if (open) {
          btn.classList.add("randee-menu__mega-arrow--open");
          btn.setAttribute("aria-expanded", "true");
          panel.classList.add("randee-menu__mega-l3--open");
        } else {
          btn.classList.remove("randee-menu__mega-arrow--open");
          btn.setAttribute("aria-expanded", "false");
          panel.classList.remove("randee-menu__mega-l3--open");
        }
      });
    });
  }

  function scan() {
    document.querySelectorAll(".randee-menu--mega").forEach(bindMega);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", scan);
  } else {
    scan();
  }

  if (typeof BX !== "undefined" && BX.addCustomEvent) {
    BX.addCustomEvent("onAjaxSuccess", scan);
  }
})();
