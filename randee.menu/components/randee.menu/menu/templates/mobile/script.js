(function () {
  "use strict";

  function collapseItem(item) {
    item.classList.remove("randee-menu__mob-item--expanded");
    item.querySelectorAll(".randee-menu__mob-item--expanded").forEach(function (c) {
      c.classList.remove("randee-menu__mob-item--expanded");
    });
  }

  function bind(root) {
    if (!root || root.dataset.randeeMobBound === "1") {
      return;
    }
    root.dataset.randeeMobBound = "1";

    root.addEventListener("click", function (e) {
      var toggle = e.target.closest(".randee-menu__mob-toggle");
      if (toggle && root.contains(toggle)) {
        e.preventDefault();
        var item = toggle.closest(".randee-menu__mob-item");
        if (!item || !item.classList.contains("randee-menu__mob-item--parent")) {
          return;
        }
        item.classList.add("randee-menu__mob-item--expanded");
        return;
      }

      var backBtn = e.target.closest(".randee-menu__mob-arrow-btn");
      if (backBtn && root.contains(backBtn) && backBtn.closest(".randee-menu__mob-item--back")) {
        e.preventDefault();
        var backLi = backBtn.closest(".randee-menu__mob-item--back");
        var ul = backLi ? backLi.parentElement : null;
        var parentLi = ul ? ul.parentElement : null;
        if (parentLi && parentLi.classList.contains("randee-menu__mob-item--parent")) {
          collapseItem(parentLi);
        }
        return;
      }

      var anchor = e.target.closest("a.randee-menu__mob-link");
      if (!anchor || !root.contains(anchor)) {
        return;
      }
      var itemLi = anchor.closest(".randee-menu__mob-item");
      if (!itemLi) {
        return;
      }
      if (itemLi.classList.contains("randee-menu__mob-item--back")) {
        e.preventDefault();
        var drop = itemLi.parentElement;
        var par = drop ? drop.parentElement : null;
        if (par && par.classList.contains("randee-menu__mob-item--parent")) {
          collapseItem(par);
        }
        return;
      }
      if (itemLi.classList.contains("randee-menu__mob-item--title")) {
        e.preventDefault();
        return;
      }
      if (itemLi.classList.contains("randee-menu__mob-item--parent")) {
        var href = anchor.getAttribute("href") || "";
        if (href === "" || href === "#") {
          e.preventDefault();
          itemLi.classList.add("randee-menu__mob-item--expanded");
        }
      }
    });
  }

  function scan() {
    document.querySelectorAll(".randee-menu--mobile").forEach(bind);
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
