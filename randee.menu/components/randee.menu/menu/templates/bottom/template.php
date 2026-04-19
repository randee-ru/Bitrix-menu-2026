<?php

declare(strict_types=1);

/**
 * Футерное меню (колонки / аккордеон на моб.) — Randee BEM.
 *
 * @var array $arResult
 * @var array $arParams
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);

$docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
$relCss = '/local/components/randee.menu/menu/templates/bottom/style.css';
if ($docRoot !== '' && is_file($docRoot . $relCss) && method_exists($this, 'addExternalCss')) {
    $this->addExternalCss($relCss);
}

$items = $arResult['ITEMS'] ?? [];
if ($items === [] || !is_array($items)) {
    return;
}

global $APPLICATION;
$curUrl = (is_object($APPLICATION) && method_exists($APPLICATION, 'GetCurPage')) ? (string)$APPLICATION->GetCurPage(true) : '';

$bMenuToRow = false;
if (isset($arParams['ROW_ITEMS'])) {
    $bMenuToRow = ($arParams['ROW_ITEMS'] === true) || ($arParams['ROW_ITEMS'] === 'Y');
}

$bBoldItems = false;
if (isset($arParams['BOLD_ITEMS'])) {
    $bBoldItems = ($arParams['BOLD_ITEMS'] === true) || ($arParams['BOLD_ITEMS'] === 'Y');
}

$rootMenuType = (string)($arParams['ROOT_MENU_TYPE'] ?? ($arParams['MENU_CODE'] ?? 'bottom_menu'));

$isSelected = static function (string $link) use ($curUrl): bool {
    $link = trim($link);
    if ($link === '' || $link === '#') {
        return false;
    }
    return $link === $curUrl;
};

$renderSubItems = function (array $children) use ($bBoldItems, $isSelected): string {
    if ($children === []) {
        return '';
    }
    $lastIndex = count($children) - 1;
    $html = '';
    foreach ($children as $i => $child) {
        if (!is_array($child)) {
            continue;
        }
        $link = (string)($child['LINK'] ?? '');
        $name = (string)($child['NAME'] ?? '');
        $bLink = trim($link) !== '' && $link !== '#';
        $active = $isSelected($link) ? ' randee-menu__bot-subitem--active' : '';
        $titleClass = $bBoldItems ? ' randee-menu__bot-subtitle--emph' : '';
        $itemClasses = 'randee-menu__bot-subitem'
            . ($i === 0 ? ' randee-menu__bot-subitem--first' : '')
            . ($i === $lastIndex ? ' randee-menu__bot-subitem--last' : '');
        $html .= '<div class="' . $itemClasses . '">';
        $html .= '<div class="randee-menu__bot-sublink' . $active . '">';
        $html .= '<div class="randee-menu__bot-subtitle' . $titleClass . '">';
        if ($bLink) {
            $html .= '<a class="randee-menu__bot-suba" href="' . htmlspecialcharsbx($link) . '">' . htmlspecialcharsbx($name) . '</a>';
        } else {
            $html .= '<span>' . htmlspecialcharsbx($name) . '</span>';
        }
        $html .= '</div></div></div>';
    }
    return $html;
};

$mod = $bBoldItems ? 'randee-menu__bot--bold' : 'randee-menu__bot--normal';
?>
<div class="randee-menu randee-menu--bottom <?= htmlspecialcharsbx($mod) ?>">
    <div class="randee-menu__bot-items">
        <?php if ($bMenuToRow): ?>
            <div class="randee-menu__bot-row">
        <?php endif; ?>

        <?php foreach ($items as $i => $item): ?>
            <?php
            $childNodes = is_array($item['CHILDREN'] ?? null) ? ($item['CHILDREN'] ?? []) : [];
            $hasChildren = $childNodes !== [];
            $name = (string)($item['NAME'] ?? '');
            $link = (string)($item['LINK'] ?? '');
            $bLink = trim($link) !== '' && $link !== '#';
            $collapseId = 'randee-bot-' . preg_replace('/\W+/', '-', $rootMenuType) . '-' . (string)$i;
            $selected = $isSelected($link);
            ?>

            <div class="randee-menu__bot-col">
                <?php
                $headClass = 'randee-menu__bot-head';
                if ($selected) {
                    $headClass .= ' randee-menu__bot-head--active';
                }
                if (!$bBoldItems && $hasChildren) {
                    $headClass .= ' randee-menu__bot-head--collapsible';
                }
                ?>
                <div class="<?= $headClass ?>"
                    <?php if (!$bBoldItems && $hasChildren): ?>
                        data-randee-bot-toggle="#<?= htmlspecialcharsbx($collapseId) ?>"
                    <?php endif; ?>
                >
                    <div class="randee-menu__bot-title <?= $bBoldItems ? 'randee-menu__bot-title--bold' : '' ?>">
                        <?php if ($bLink): ?>
                            <a class="randee-menu__bot-link" href="<?= htmlspecialcharsbx($link) ?>"><?= htmlspecialcharsbx($name) ?></a>
                        <?php else: ?>
                            <span><?= htmlspecialcharsbx($name) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!$bBoldItems && $hasChildren): ?>
                        <button type="button" class="randee-menu__bot-expand" aria-expanded="false" aria-label="Раздел"></button>
                    <?php endif; ?>
                </div>

                <?php if ($hasChildren): ?>
                    <div id="<?= htmlspecialcharsbx($collapseId) ?>" class="randee-menu__bot-panel<?= $bBoldItems ? '' : ' randee-menu__bot-panel--collapse' ?>">
                        <?= $renderSubItems($childNodes) ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php endforeach; ?>

        <?php if ($bMenuToRow): ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$bBoldItems): ?>
<script data-skip-moving="true">
(function () {
  function bindBottom(root) {
    if (!root || root.dataset.randeeBotBound) return;
    root.dataset.randeeBotBound = '1';
    root.querySelectorAll('[data-randee-bot-toggle]').forEach(function (head) {
      head.addEventListener('click', function () {
        if (window.matchMedia('(min-width: 768px)').matches) return;
        var id = head.getAttribute('data-randee-bot-toggle');
        var panel = id ? root.querySelector(id) : null;
        var btn = head.querySelector('.randee-menu__bot-expand');
        var open = panel && !panel.classList.contains('is-open');
        root.querySelectorAll('.randee-menu__bot-panel.is-open').forEach(function (p) { p.classList.remove('is-open'); });
        root.querySelectorAll('.randee-menu__bot-expand').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
        if (open && panel) {
          panel.classList.add('is-open');
          if (btn) btn.setAttribute('aria-expanded', 'true');
        }
      });
    });
  }
  document.querySelectorAll('.randee-menu--bottom').forEach(bindBottom);
})();
</script>
<?php endif; ?>
