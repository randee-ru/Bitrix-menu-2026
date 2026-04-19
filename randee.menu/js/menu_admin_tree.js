/**
 * Дерево пунктов меню в админке: вложенный drag-and-drop (SortableJS)
 */
(function () {
	'use strict';

	/** Прямой дочерний ol.nested-sortable у li (не первый ol во всём поддереве) */
	function getChildNestedList(li) {
		var ch = li.children;
		for (var k = 0; k < ch.length; k++) {
			var c = ch[k];
			if (c.tagName === 'OL' && c.classList.contains('nested-sortable')) {
				return c;
			}
		}
		return null;
	}

	function serializeList(ol) {
		var nodes = [];
		if (!ol) {
			return nodes;
		}
		var lis = ol.children;
		for (var i = 0; i < lis.length; i++) {
			var li = lis[i];
			if (!li.classList.contains('randee-menu-tree__item')) {
				continue;
			}
			var id = parseInt(li.getAttribute('data-id'), 10);
			if (!id) {
				continue;
			}
			var sub = getChildNestedList(li);
			var children = sub ? serializeList(sub) : [];
			nodes.push({ id: id, children: children });
		}
		return nodes;
	}

	function bindSave(rootDiv, topLevelOl, cfg) {
		var btn = document.getElementById('randee-menu-tree-save');
		var hint = document.getElementById('randee-menu-tree-dirty');
		if (!btn || !topLevelOl) {
			return;
		}
		function markDirty() {
			if (hint) {
				hint.style.display = '';
			}
		}
		if (rootDiv) {
			rootDiv.addEventListener('sortme', markDirty);
		}

		btn.addEventListener('click', function () {
			var tree = serializeList(topLevelOl);
			btn.disabled = true;
			BX.ajax({
				url: cfg.ajaxUrl,
				method: 'POST',
				dataType: 'json',
				data: {
					sessid: BX.bitrix_sessid(),
					action: 'reorder_tree',
					MENU_ID: cfg.menuId,
					lang: cfg.lang,
					tree: JSON.stringify(tree),
				},
				onsuccess: function (data) {
					btn.disabled = false;
					if (typeof data === 'string') {
						try {
							data = JSON.parse(data);
						} catch (e) {
							data = null;
						}
					}
					if (data && data.success) {
						if (hint) {
							hint.style.display = 'none';
						}
						alert('Порядок сохранён');
					} else {
						alert((data && data.message) ? data.message : 'Ошибка сохранения');
					}
				},
				onfailure: function () {
					btn.disabled = false;
					alert('Ошибка сети');
				},
			});
		});
	}

	function initSortables(rootOl) {
		if (typeof Sortable === 'undefined' || !rootOl) {
			return;
		}
		var lists = rootOl.querySelectorAll('ol.nested-sortable');
		for (var i = 0; i < lists.length; i++) {
			if (lists[i].dataset.randeeSortableBound === 'Y') {
				continue;
			}
			lists[i].dataset.randeeSortableBound = 'Y';
			Sortable.create(lists[i], {
				group: {
					name: 'randee-menu-nested',
					pull: true,
					put: true,
				},
				handle: '.randee-menu-tree__handle',
				animation: 150,
				fallbackOnBody: true,
				swapThreshold: 0.65,
				onEnd: function () {
					var wrap = document.getElementById('randee-menu-tree-root');
					if (wrap) {
						wrap.dispatchEvent(new CustomEvent('sortme'));
					}
				},
			});
		}
	}

	window.RandeeMenuAdminTree = {
		init: function (cfg) {
			var root = document.getElementById('randee-menu-tree-root');
			if (!root) {
				return;
			}
			var topOl = root.querySelector('ol.nested-sortable');
			if (!topOl) {
				return;
			}
			initSortables(root);
			bindSave(root, topOl, cfg);
		},
	};
})();
