<?php

declare(strict_types=1);

/**
 * Горизонтальное меню с выпадающим списком — Randee BEM.
 *
 * @var array $arResult
 * @var array $arParams
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);

$docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
$relCss = '/local/components/randee.menu/menu/templates/menu_new/style.css';
$relJs = '/local/components/randee.menu/menu/templates/menu_new/script.js';
if ($docRoot !== '' && is_file($docRoot . $relCss) && method_exists($this, 'addExternalCss')) {
    $this->addExternalCss($relCss);
}
if ($docRoot !== '' && is_file($docRoot . $relJs) && method_exists($this, 'addExternalJs')) {
    $this->addExternalJs($relJs);
}

$items = $arResult['ITEMS'] ?? [];
if ($items === [] || !is_array($items)) {
    return;
}

$renderAttrs = static function (array $params): string {
    $attrs = '';
    foreach ($params as $k => $v) {
        if (is_scalar($v)) {
            $attrs .= ' ' . htmlspecialcharsbx((string)$k) . '="' . htmlspecialcharsbx((string)$v) . '"';
        }
    }
    return $attrs;
};

$normalizeLink = static function (array $item): string {
    $linkRaw = (string)($item['LINK'] ?? '');
    $linkType = (string)($item['LINK_TYPE'] ?? 'inner');
    if ($linkRaw === '') {
        return '#';
    }
    if ($linkType === 'inner' && $linkRaw !== '#' && strpos($linkRaw, 'http') !== 0) {
        return $linkRaw[0] === '/' ? $linkRaw : '/' . $linkRaw;
    }
    return $linkRaw;
};

$svgArrow = '<span class="randee-menu__top-chevron" aria-hidden="true"><svg width="10" height="10" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M2.5 1 7 5l-4.5 4V1z"/></svg></span>';
$svgMore = '<span class="randee-menu__top-dots" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="17" height="3" viewBox="0 0 17 3">'
    . '<path class="cls-1" d="M923.5,178a1.5,1.5,0,1,1-1.5,1.5A1.5,1.5,0,0,1,923.5,178Zm7,0a1.5,1.5,0,1,1-1.5,1.5A1.5,1.5,0,0,1,930.5,178Zm7,0a1.5,1.5,0,1,1-1.5,1.5A1.5,1.5,0,0,1,937.5,178Z" transform="translate(-922 -178)"></path>'
    . '</svg></span>';

$maxLevel = (int)($arParams['MAX_LEVEL'] ?? 4);
if ($maxLevel < 1) {
    $maxLevel = 4;
}

$renderLi = function (array $node, int $depth) use (&$renderLi, $renderAttrs, $normalizeLink, $svgArrow, $maxLevel): string {
    $link = $normalizeLink($node);
    $params = is_array($node['PARAMS'] ?? null) ? ($node['PARAMS'] ?? []) : [];
    $attrs = $renderAttrs($params);
    $hasChildren = !empty($node['CHILDREN']);
    $active = (($node['ACTIVE'] ?? 'Y') === 'Y');

    $classes = 'randee-menu__top-subitem';
    if ($hasChildren) {
        $classes .= ' randee-menu__top-subitem--with-child';
    }
    if ($active) {
        $classes .= ' randee-menu__top-subitem--active';
    }

    $html = '<li class="' . $classes . '">';
    $html .= '<a class="randee-menu__top-suba" href="' . htmlspecialcharsbx($link) . '"'
        . (!empty($node['TARGET']) ? ' target="' . htmlspecialcharsbx((string)$node['TARGET']) . '"' : '') . $attrs . '>';
    $html .= htmlspecialcharsbx((string)($node['NAME'] ?? ''));
    if ($hasChildren) {
        $html .= $svgArrow;
    }
    $html .= '</a>';

    if ($hasChildren && $depth < $maxLevel) {
        $html .= '<div class="randee-menu__top-fly"><ul class="randee-menu__top-flylist">';
        foreach ($node['CHILDREN'] as $grand) {
            $html .= $renderLi($grand, $depth + 1);
        }
        $html .= '</ul></div>';
    }

    $html .= '</li>';
    return $html;
};

$counter = 1;
$total = count($items);
?>
<div class="randee-menu randee-menu--top">
    <div class="randee-menu__top-wrap">
        <?php foreach ($items as $item): ?>
            <?php
            $hasChildren = !empty($item['CHILDREN']);
            $isFirst = $counter === 1;
            $isLast = $counter === $total;
            $active = (($item['ACTIVE'] ?? 'Y') === 'Y');
            $classes = 'randee-menu__top-item';
            if ($isFirst) {
                $classes .= ' randee-menu__top-item--first';
            }
            if ($isLast) {
                $classes .= ' randee-menu__top-item--last';
            }
            if ($hasChildren) {
                $classes .= ' randee-menu__top-item--dropdown';
            }
            if ($active) {
                $classes .= ' randee-menu__top-item--active';
            }
            $link = $normalizeLink($item);
            $params = is_array($item['PARAMS'] ?? null) ? ($item['PARAMS'] ?? []) : [];
            $attrs = $renderAttrs($params);
            ?>
            <div class="<?= $classes ?>">
                <a class="randee-menu__top-link"
                    href="<?= htmlspecialcharsbx($link) ?>"
                    <?= !empty($item['TARGET']) ? ' target="' . htmlspecialcharsbx((string)$item['TARGET']) . '"' : '' ?>
                    <?= $attrs ?>
                >
                    <span class="randee-menu__top-text"><?= htmlspecialcharsbx((string)($item['NAME'] ?? '')) ?></span>
                    <?php if ($hasChildren): ?>
                        <?= $svgArrow ?>
                    <?php endif; ?>
                </a>

                <?php if ($hasChildren && $maxLevel >= 2): ?>
                    <div class="randee-menu__top-panel">
                        <ul class="randee-menu__top-list">
                            <?php foreach ($item['CHILDREN'] as $child): ?>
                                <?= $renderLi($child, 2) ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            <?php $counter++; ?>
        <?php endforeach; ?>

        <div class="randee-menu__top-item randee-menu__top-item--more" aria-hidden="true">
            <button type="button" class="randee-menu__top-more-btn" aria-expanded="false"
                    aria-label="<?= htmlspecialcharsbx('Ещё') ?>">
                <?= $svgMore ?>
            </button>
            <div class="randee-menu__top-more-panel">
                <div class="randee-menu__top-more-list"></div>
            </div>
        </div>
    </div>
</div>
<script data-skip-moving="true">
window.addEventListener("load", function () {
    if (window.__randeeTopMenuChained) {
        return;
    }
    window.__randeeTopMenuChained = true;
    var prev = typeof window.topMenuAction === "function" ? window.topMenuAction : null;
    window.topMenuAction = function () {
        if (prev) {
            prev();
        }
        if (window.randeeMenuTopReflow) {
            window.randeeMenuTopReflow();
        }
    };
});
</script>
