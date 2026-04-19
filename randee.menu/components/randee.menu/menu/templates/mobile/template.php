<?php

declare(strict_types=1);

/**
 * Mobile accordion menu — Randee BEM, vanilla JS.
 *
 * @var array $arResult
 * @var array $arParams
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);

$docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
$relCss = '/local/components/randee.menu/menu/templates/mobile/style.css';
$relJs = '/local/components/randee.menu/menu/templates/mobile/script.js';
if ($docRoot !== '' && is_file($docRoot . $relCss) && method_exists($this, 'addExternalCss')) {
    $this->addExternalCss($relCss);
}
if ($docRoot !== '' && is_file($docRoot . $relJs) && method_exists($this, 'addExternalJs')) {
    $this->addExternalJs($relJs);
}

$items = $arResult['ITEMS'] ?? [];
if (!is_array($items) || $items === []) {
    return;
}

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

$renderAttrs = static function (array $params): string {
    $attrs = '';
    foreach ($params as $k => $v) {
        if (is_scalar($v)) {
            $attrs .= ' ' . htmlspecialcharsbx((string)$k) . '="' . htmlspecialcharsbx((string)$v) . '"';
        }
    }
    return $attrs;
};

$svgDown = '<svg class="randee-menu__mob-svg" width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M6 8 1 3h10z"/></svg>';
$svgBack = '<svg class="randee-menu__mob-svg" width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M12.5 15 6 10l6.5-5 1.5 1.8L9.8 10l4.2 3.2z"/></svg>';

global $APPLICATION;
$curUrl = (is_object($APPLICATION) && method_exists($APPLICATION, 'GetCurPage'))
    ? (string)$APPLICATION->GetCurPage(true)
    : '';

$itemSelected = static function (array $node) use ($normalizeLink, $curUrl): bool {
    $link = $normalizeLink($node);
    if ($link === '' || $link === '#' || $curUrl === '') {
        return false;
    }
    if ($link === $curUrl) {
        return true;
    }
    $linkDir = rtrim($link, '/') . '/';
    $curDir = rtrim($curUrl, '/') . '/';
    return str_starts_with($curDir, $linkDir);
};

$maxLevel = (int)($arParams['MAX_LEVEL'] ?? 4);
if ($maxLevel < 1) {
    $maxLevel = 4;
}

$dropdownFor = null;
$dropdownFor = function (array $parent, int $parentDepth) use (&$dropdownFor, $maxLevel, $normalizeLink, $renderAttrs, $svgDown, $svgBack, $itemSelected): string {
    $children = is_array($parent['CHILDREN'] ?? null) ? ($parent['CHILDREN'] ?? []) : [];
    if ($children === [] || $parentDepth >= $maxLevel) {
        return '';
    }
    $pLink = $normalizeLink($parent);
    $pName = (string)($parent['NAME'] ?? '');
    $pTarget = (string)($parent['TARGET'] ?? '');
    $pAttrs = $renderAttrs(is_array($parent['PARAMS'] ?? null) ? ($parent['PARAMS'] ?? []) : []);

    $html = '<ul class="randee-menu__mob-dropdown">';
    $html .= '<li class="randee-menu__mob-item randee-menu__mob-item--back">';
    $html .= '<div class="randee-menu__mob-back">';
    $html .= '<button type="button" class="randee-menu__mob-arrow-btn" aria-label="Назад">' . $svgBack . '</button>';
    $html .= '</div></li>';
    $html .= '<li class="randee-menu__mob-item randee-menu__mob-item--title">';
    $html .= '<div class="randee-menu__mob-row">';
    $html .= '<a class="randee-menu__mob-link" href="' . htmlspecialcharsbx($pLink) . '"';
    if ($pTarget !== '' && $pTarget !== '_self') {
        $html .= ' target="' . htmlspecialcharsbx($pTarget) . '"';
    }
    $html .= $pAttrs . '><span>' . htmlspecialcharsbx($pName) . '</span></a></div></li>';

    foreach ($children as $child) {
        if (!is_array($child)) {
            continue;
        }
        $chDepth = $parentDepth + 1;
        $sub = is_array($child['CHILDREN'] ?? null) ? ($child['CHILDREN'] ?? []) : [];
        $hasNested = $sub !== [] && $chDepth < $maxLevel;

        $cLink = $normalizeLink($child);
        $cName = (string)($child['NAME'] ?? '');
        $cAttrs = $renderAttrs(is_array($child['PARAMS'] ?? null) ? ($child['PARAMS'] ?? []) : []);
        $cTarget = (string)($child['TARGET'] ?? '_self');
        $sel = $itemSelected($child);

        $liClass = 'randee-menu__mob-item randee-menu__mob-item--nested';
        if ($sel) {
            $liClass .= ' randee-menu__mob-item--selected';
        }
        if ($hasNested) {
            $liClass .= ' randee-menu__mob-item--parent';
        }

        $html .= '<li class="' . $liClass . '">';
        $html .= '<div class="randee-menu__mob-row">';
        $html .= '<a class="randee-menu__mob-link" href="' . htmlspecialcharsbx($cLink) . '" title="' . htmlspecialcharsbx($cName) . '"';
        if ($cTarget !== '' && $cTarget !== '_self') {
            $html .= ' target="' . htmlspecialcharsbx($cTarget) . '"';
        }
        $html .= $cAttrs . '>';
        $html .= '<span>' . htmlspecialcharsbx($cName) . '</span>';
        if ($hasNested) {
            $html .= $svgDown;
        }
        $html .= '</a>';
        if ($hasNested) {
            $html .= '<button type="button" class="randee-menu__mob-toggle" aria-label="Открыть" aria-expanded="false"></button>';
        }
        $html .= '</div>';
        if ($hasNested) {
            $html .= $dropdownFor($child, $chDepth);
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
};

?>
<div class="randee-menu randee-menu--mobile">
    <ul class="randee-menu__mob-root">
        <?php foreach ($items as $arItem):
            if (!is_array($arItem)) {
                continue;
            }
            $bShowChilds = $maxLevel > 1;
            $children = is_array($arItem['CHILDREN'] ?? null) ? ($arItem['CHILDREN'] ?? []) : [];
            $bParent = $children !== [] && $bShowChilds;

            $link = $normalizeLink($arItem);
            $name = (string)($arItem['NAME'] ?? '');
            $attrs = $renderAttrs(is_array($arItem['PARAMS'] ?? null) ? ($arItem['PARAMS'] ?? []) : []);
            $target = (string)($arItem['TARGET'] ?? '_self');
            $sel = $itemSelected($arItem);
            $lic = 'randee-menu__mob-item randee-menu__mob-item--root';
            if ($sel) {
                $lic .= ' randee-menu__mob-item--selected';
            }
            if ($bParent) {
                $lic .= ' randee-menu__mob-item--parent';
            }
            ?>
            <li class="<?= $lic ?>">
                <div class="randee-menu__mob-row">
                    <a class="randee-menu__mob-link" href="<?= htmlspecialcharsbx($link) ?>"
                        title="<?= htmlspecialcharsbx($name) ?>"
                        <?= $target !== '' && $target !== '_self' ? 'target="' . htmlspecialcharsbx($target) . '"' : '' ?>
                        <?= $attrs ?>
                    >
                        <span><?= htmlspecialcharsbx($name) ?></span>
                        <?php if ($bParent): ?>
                            <?= $svgDown ?>
                        <?php endif; ?>
                    </a>
                    <?php if ($bParent): ?>
                        <button type="button" class="randee-menu__mob-toggle" aria-label="Открыть" aria-expanded="false"></button>
                    <?php endif; ?>
                </div>
                <?php if ($bParent): ?>
                    <?= $dropdownFor($arItem, 1) ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
</think>

I made a typo in the template - fix it.

<｜tool▁calls▁begin｜><｜tool▁call▁begin｜>
Read