<?php

declare(strict_types=1);

/**
 * Mega menu — как в оригинале Aspro: слева весь L1, справа одно подменю активной ветки + линия от пункта.
 *
 * @var array $arResult
 * @var array $arParams
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);

$docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
$relCss = '/local/components/randee.menu/menu/templates/mega_menu/style.css';
$relJs = '/local/components/randee.menu/menu/templates/mega_menu/script.js';
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

global $APPLICATION;

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

$getCurUrl = static function () use ($APPLICATION): string {
    if (is_object($APPLICATION) && method_exists($APPLICATION, 'GetCurPage')) {
        return (string)$APPLICATION->GetCurPage(true);
    }
    return '';
};

$isSelected = static function (string $link, string $curUrl): bool {
    $link = trim($link);
    $curUrl = trim($curUrl);
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

$branchMatchesPage = static function (array $item, string $curUrl) use (&$branchMatchesPage, $normalizeLink, $isSelected): bool {
    if ($isSelected($normalizeLink($item), $curUrl)) {
        return true;
    }
    $nested = $item['CHILDREN'] ?? [];
    if (!is_array($nested)) {
        return false;
    }
    foreach ($nested as $child) {
        if (is_array($child) && $branchMatchesPage($child, $curUrl)) {
            return true;
        }
    }
    return false;
};

$svgChevron = '<svg class="randee-menu__mega-svg" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10" aria-hidden="true"><path fill="currentColor" d="M2.5 1 7 5l-4.5 4V1z"/></svg>';

$maxLevel = (int)($arParams['MAX_LEVEL'] ?? 3);
if ($maxLevel < 1) {
    $maxLevel = 3;
}

$useLight = ($arParams['MEGA_FORCE_LIGHT'] ?? 'N') === 'Y';
$megaMod = $useLight ? 'randee-menu--mega-light' : 'randee-menu--mega-dark';
$useNativeScroll = ($arParams['ADD_MCUSTOM_SCROLLBAR_CLASS'] ?? 'N') === 'Y';
$scrollMod = $useNativeScroll ? ' randee-menu--mega-native-scroll' : '';

$curUrl = $getCurUrl();
$rootItems = $items;

$openedRootIndex = 1;
$scanCounter = 0;
foreach ($rootItems as $scanItem) {
    if (!is_array($scanItem)) {
        continue;
    }
    $scanCounter++;
    if ($branchMatchesPage($scanItem, $curUrl)) {
        $openedRootIndex = $scanCounter;
        break;
    }
}

$megaRows = [];
$rowIndex = 0;
foreach ($rootItems as $arItem) {
    if (!is_array($arItem)) {
        continue;
    }
    $rowIndex++;
    $children = is_array($arItem['CHILDREN'] ?? null) ? ($arItem['CHILDREN'] ?? []) : [];
    $hasChildren = $children !== [];
    $bShowChilds = $hasChildren && $maxLevel > 1;
    $link = $normalizeLink($arItem);
    $params = is_array($arItem['PARAMS'] ?? null) ? ($arItem['PARAMS'] ?? []) : [];
    $target = (string)($arItem['TARGET'] ?? '_self');
    $selected = $isSelected($link, $curUrl);
    $branchActive = $branchMatchesPage($arItem, $curUrl);
    $currentRoot = $rowIndex === $openedRootIndex;

    $megaRows[] = [
        'i' => $rowIndex,
        'item' => $arItem,
        'children' => $children,
        'bShowChilds' => $bShowChilds,
        'link' => $link,
        'attrs' => $renderAttrs($params),
        'target' => $target,
        'selected' => $selected,
        'branchActive' => $branchActive,
        'currentRoot' => $currentRoot,
    ];
}

$totalRows = count($megaRows);
?>
<div class="randee-menu randee-menu--mega <?= htmlspecialcharsbx($megaMod) ?><?= htmlspecialcharsbx($scrollMod) ?>">
    <div class="randee-menu__mega-layout">
        <nav class="randee-menu__mega-aside" aria-label="Разделы">
            <?php foreach ($megaRows as $row): ?>
                <?php
                $i = (int)$row['i'];
                $arItem = $row['item'];
                $bShowChilds = $row['bShowChilds'];
                $link = $row['link'];
                $attrs = $row['attrs'];
                $target = $row['target'];
                $selected = $row['selected'];
                $branchActive = $row['branchActive'];
                $currentRoot = $row['currentRoot'];
                $isFirst = $i === 1;
                $isLast = $i === $totalRows;

                $asideClasses = 'randee-menu__mega-aside-row';
                if ($isFirst) {
                    $asideClasses .= ' randee-menu__mega-aside-row--first';
                }
                if ($isLast) {
                    $asideClasses .= ' randee-menu__mega-aside-row--last';
                }
                if ($bShowChilds) {
                    $asideClasses .= ' randee-menu__mega-aside-row--dropdown';
                }
                if ($currentRoot) {
                    $asideClasses .= ' randee-menu__mega-aside-row--current';
                }
                if ($selected) {
                    $asideClasses .= ' randee-menu__mega-aside-row--active';
                }
                if ($branchActive && !$selected) {
                    $asideClasses .= ' randee-menu__mega-aside-row--branch-active';
                }
                ?>
                <div class="<?= htmlspecialcharsbx($asideClasses) ?>" data-randee-mega-index="<?= $i ?>">
                    <a class="randee-menu__mega-link randee-menu__mega-link--l1"
                       href="<?= htmlspecialcharsbx($link) ?>"
                        <?= $target !== '' && $target !== '_self' ? 'target="' . htmlspecialcharsbx($target) . '"' : '' ?>
                        <?= $attrs ?>
                    ><?= htmlspecialcharsbx((string)($arItem['NAME'] ?? '')) ?></a>
                    <?php if ($bShowChilds): ?>
                        <span class="randee-menu__mega-line" aria-hidden="true"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </nav>
        <div class="randee-menu__mega-stage">
            <?php foreach ($megaRows as $row): ?>
                <?php
                if (!$row['bShowChilds']) {
                    continue;
                }
                $i = (int)$row['i'];
                $children = $row['children'];
                $currentRoot = $row['currentRoot'];
                $wrapClasses = 'randee-menu__mega-panel-wrap';
                if ($currentRoot) {
                    $wrapClasses .= ' randee-menu__mega-panel-wrap--current';
                }
                ?>
                <div class="<?= htmlspecialcharsbx($wrapClasses) ?>" data-randee-mega-panel="<?= $i ?>">
                    <div class="randee-menu__mega-panel">
                        <ul class="randee-menu__mega-l2">
                            <?php foreach ($children as $arSubItem): ?>
                                <?php
                                if (!is_array($arSubItem)) {
                                    continue;
                                }
                                $subChildren = is_array($arSubItem['CHILDREN'] ?? null) ? ($arSubItem['CHILDREN'] ?? []) : [];
                                $hasSubChildren = $subChildren !== [];
                                $bShowSub = $hasSubChildren && $maxLevel > 2;
                                $subLink = $normalizeLink($arSubItem);
                                $subName = (string)($arSubItem['NAME'] ?? '');
                                $subParams = is_array($arSubItem['PARAMS'] ?? null) ? ($arSubItem['PARAMS'] ?? []) : [];
                                $subAttrs = $renderAttrs($subParams);
                                $subTarget = (string)($arSubItem['TARGET'] ?? '_self');
                                $subSelected = $isSelected($subLink, $curUrl);
                                $li2Class = 'randee-menu__mega-l2-item' . ($bShowSub ? ' randee-menu__mega-l2-item--with-children' : '')
                                    . ($subSelected ? ' randee-menu__mega-l2-item--active' : '');
                                ?>
                                <li class="<?= $li2Class ?>">
                                    <div class="randee-menu__mega-l2-wrap">
                                        <a class="randee-menu__mega-link randee-menu__mega-link--l2"
                                           href="<?= htmlspecialcharsbx($subLink) ?>"
                                           title="<?= htmlspecialcharsbx($subName) ?>"
                                            <?= $subTarget !== '' && $subTarget !== '_self' ? 'target="' . htmlspecialcharsbx($subTarget) . '"' : '' ?>
                                            <?= $subAttrs ?>
                                        ><?= htmlspecialcharsbx($subName) ?></a>
                                        <?php if ($bShowSub): ?>
                                            <button type="button" class="randee-menu__mega-arrow" aria-expanded="false"
                                                    aria-label="Подменю">
                                                <?= $svgChevron ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($bShowSub): ?>
                                        <ul class="randee-menu__mega-l3">
                                            <?php foreach ($subChildren as $arSubSubItem): ?>
                                                <?php
                                                if (!is_array($arSubSubItem)) {
                                                    continue;
                                                }
                                                $subSubLink = $normalizeLink($arSubSubItem);
                                                $subSubName = (string)($arSubSubItem['NAME'] ?? '');
                                                $subSubParams = is_array($arSubSubItem['PARAMS'] ?? null) ? ($arSubSubItem['PARAMS'] ?? []) : [];
                                                $subSubAttrs = $renderAttrs($subSubParams);
                                                $subSubTarget = (string)($arSubSubItem['TARGET'] ?? '_self');
                                                $subSubSelected = $isSelected($subSubLink, $curUrl);
                                                ?>
                                                <li class="randee-menu__mega-l3-item<?= $subSubSelected ? ' randee-menu__mega-l3-item--active' : '' ?>">
                                                    <a class="randee-menu__mega-link randee-menu__mega-link--l3"
                                                       href="<?= htmlspecialcharsbx($subSubLink) ?>"
                                                       title="<?= htmlspecialcharsbx($subSubName) ?>"
                                                        <?= $subSubTarget !== '' && $subSubTarget !== '_self' ? 'target="' . htmlspecialcharsbx($subSubTarget) . '"' : '' ?>
                                                        <?= $subSubAttrs ?>
                                                    ><?= htmlspecialcharsbx($subSubName) ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
