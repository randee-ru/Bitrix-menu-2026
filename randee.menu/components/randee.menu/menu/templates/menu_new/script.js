(function () {
  "use strict";

  var roots = [];
  var resizeTimer;

  function outerWidth(el) {
    if (!el) {
      return 0;
    }
    var r = el.getBoundingClientRect();
    var s = window.getComputedStyle(el);
    return (
      r.width +
      (parseFloat(s.marginLeft) || 0) +
      (parseFloat(s.marginRight) || 0)
    );
  }

  function boundaryEl(root) {
    return (
      root.closest(".header-menu") ||
      root.closest("nav.mega-menu.sliced") ||
      root.closest("nav.mega-menu") ||
      root
    );
  }

  function skipReflow(root) {
    var p = root.parentElement;
    while (p) {
      if (p.classList && p.classList.contains("collapse")) {
        if (window.getComputedStyle(p).display === "none") {
          return true;
        }
      }
      p = p.parentElement;
    }
    var hf = root.closest("#headerfixed");
    if (hf && !hf.classList.contains("fixed")) {
      return true;
    }
    return false;
  }

  function getMainItems(wrap) {
    return Array.prototype.filter.call(wrap.children, function (c) {
      return (
        c.classList &&
        c.classList.contains("randee-menu__top-item") &&
        !c.classList.contains("randee-menu__top-item--more")
      );
    });
  }

  function sumWidth(nodes) {
    var s = 0;
    for (var i = 0; i < nodes.length; i++) {
      s += outerWidth(nodes[i]);
    }
    return s;
  }

  function barTotalWidth(wrap, moreItem, moreList) {
    var main = getMainItems(wrap);
    var w = sumWidth(main);
    if (moreList && moreList.children.length) {
      moreItem.classList.add("is-visible");
      w += outerWidth(moreItem);
    }
    return w;
  }

  function closeMoreOpen(root) {
    var m = root.querySelector(".randee-menu__top-item--more");
    if (m) {
      m.classList.remove("randee-menu__top-item--open");
      var b = m.querySelector(".randee-menu__top-more-btn");
      if (b) {
        b.setAttribute("aria-expanded", "false");
      }
    }
  }

  function closeAll(root) {
    root.querySelectorAll(".randee-menu__top-item--open").forEach(function (el) {
      if (!el.classList.contains("randee-menu__top-item--more")) {
        el.classList.remove("randee-menu__top-item--open");
      }
    });
    root.querySelectorAll(".randee-menu__top-subitem--open").forEach(function (el) {
      el.classList.remove("randee-menu__top-subitem--open");
    });
    closeMoreOpen(root);
  }

  function closeSiblingFlyouts(subitem) {
    var list = subitem.parentElement;
    if (!list) {
      return;
    }
    list.querySelectorAll(".randee-menu__top-subitem--open").forEach(function (el) {
      if (el !== subitem) {
        el.classList.remove("randee-menu__top-subitem--open");
      }
    });
  }

  function reflow(root) {
    var wrap = root.querySelector(".randee-menu__top-wrap");
    var moreItem = root.querySelector(".randee-menu__top-item--more");
    if (!wrap || !moreItem) {
      return;
    }
    var moreList = moreItem.querySelector(".randee-menu__top-more-list");
    if (!moreList) {
      return;
    }

    if (window.matchMedia("(max-width: 991px)").matches) {
      while (moreList.firstElementChild) {
        wrap.insertBefore(moreList.firstElementChild, moreItem);
      }
      moreItem.classList.remove("is-visible", "randee-menu__top-item--open");
      moreItem.setAttribute("aria-hidden", "true");
      closeMoreOpen(root);
      return;
    }

    if (skipReflow(root)) {
      return;
    }

    var boundary = boundaryEl(root);

    while (moreList.firstElementChild) {
      wrap.insertBefore(moreList.firstElementChild, moreItem);
    }
    moreItem.classList.remove("is-visible", "randee-menu__top-item--open");
    moreItem.setAttribute("aria-hidden", "true");
    closeMoreOpen(root);

    var blockW = boundary.clientWidth;

    function shrink() {
      var guard = 0;
      while (guard++ < 500) {
        var items = getMainItems(wrap);
        if (!items.length) {
          break;
        }
        var total = barTotalWidth(wrap, moreItem, moreList);
        if (total <= blockW - 1) {
          break;
        }
        moreList.insertBefore(items[items.length - 1], moreList.firstElementChild);
        moreItem.classList.add("is-visible");
        moreItem.setAttribute("aria-hidden", "false");
        blockW = boundary.clientWidth;
      }
    }

    function expand() {
      var guard = 0;
      while (guard++ < 500 && moreList.firstElementChild) {
        var first = moreList.firstElementChild;
        wrap.insertBefore(first, moreItem);
        var items = getMainItems(wrap);
        if (!items.length) {
          break;
        }
        var hasExtra = moreList.children.length > 0;
        if (!hasExtra) {
          moreItem.classList.remove("is-visible");
          moreItem.setAttribute("aria-hidden", "true");
        } else {
          moreItem.classList.add("is-visible");
          moreItem.setAttribute("aria-hidden", "false");
        }
        var totalMain = sumWidth(items);
        var moreW = hasExtra ? outerWidth(moreItem) : 0;
        var limit = blockW - 1;
        if (totalMain + moreW <= limit) {
          if (!hasExtra) {
            moreItem.classList.remove("is-visible");
            moreItem.setAttribute("aria-hidden", "true");
          }
          blockW = boundary.clientWidth;
          continue;
        }
        moreList.insertBefore(first, moreList.firstElementChild);
        moreItem.classList.add("is-visible");
        moreItem.setAttribute("aria-hidden", "false");
        break;
      }
    }

    shrink();
    expand();

    if (!moreList.firstElementChild) {
      moreItem.classList.remove("is-visible");
      moreItem.setAttribute("aria-hidden", "true");
    }

    root.classList.add("randee-menu--top-reflow-done");
  }

  function reflowAll() {
    document.querySelectorAll(".randee-menu--top").forEach(reflow);
  }

  window.randeeMenuTopReflow = reflowAll;

  if (!window.__randeeMenuTopDocBound) {
    window.__randeeMenuTopDocBound = true;
    document.addEventListener("click", function (e) {
      roots.forEach(function (root) {
        if (!root.contains(e.target)) {
          closeAll(root);
        }
      });
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        roots.forEach(closeAll);
      }
    });
    window.addEventListener("resize", function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(reflowAll, 120);
    });
    window.addEventListener("load", reflowAll);
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(reflowAll);
    }
  }

  function bind(root) {
    if (!root || root.dataset.randeeTopBound === "1") {
      return;
    }
    root.dataset.randeeTopBound = "1";
    roots.push(root);

    root.addEventListener("click", function (e) {
      var moreBtn = e.target.closest(".randee-menu__top-more-btn");
      if (moreBtn && root.contains(moreBtn)) {
        e.preventDefault();
        e.stopPropagation();
        var moreLi = moreBtn.closest(".randee-menu__top-item--more");
        if (!moreLi) {
          return;
        }
        var o = moreLi.classList.toggle("randee-menu__top-item--open");
        moreBtn.setAttribute("aria-expanded", o ? "true" : "false");
        return;
      }

      var subA = e.target.closest(".randee-menu__top-subitem--with-child > .randee-menu__top-suba");
      if (subA && root.contains(subA)) {
        var subLi = subA.closest(".randee-menu__top-subitem--with-child");
        if (!subLi) {
          return;
        }
        var open = subLi.classList.contains("randee-menu__top-subitem--open");
        if (!open) {
          e.preventDefault();
          closeSiblingFlyouts(subLi);
          subLi.classList.add("randee-menu__top-subitem--open");
        } else {
          subLi.classList.remove("randee-menu__top-subitem--open");
        }
        return;
      }

      var topA = e.target.closest(".randee-menu__top-item--dropdown > .randee-menu__top-link");
      if (!topA || !root.contains(topA)) {
        return;
      }
      if (topA.closest(".randee-menu__top-item--more")) {
        return;
      }
      var item = topA.closest(".randee-menu__top-item--dropdown");
      if (!item) {
        return;
      }
      var isOpen = item.classList.contains("randee-menu__top-item--open");
      if (!isOpen) {
        e.preventDefault();
        closeAll(root);
        item.classList.add("randee-menu__top-item--open");
      } else {
        item.classList.remove("randee-menu__top-item--open");
      }
    });

    reflow(root);
  }

  function scan() {
    document.querySelectorAll(".randee-menu--top").forEach(bind);
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
