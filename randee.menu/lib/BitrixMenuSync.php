<?php
/**
 * Sync randee.menu tree into Bitrix "file menu" format used by `bitrix:menu`.
 *
 * Aspro:menu_new uses standard `bitrix:menu` with ROOT_MENU_TYPE="top" and CHILD_MENU_TYPE="left"
 * (and USE_EXT="Y"). Therefore we generate:
 * - /.top.menu.php
 * - /.left.menu_ext.php in every directory that participates as a parent in our tree
 *
 * NOTE: For recursive dropdowns Bitrix treats a menu item as "directory" only if:
 * - LINK is internal (no http(s)://, mailto:, javascript:, #)
 * - and LINK ends with "/"
 */
namespace Randee\Menu;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class BitrixMenuSync
{
    public const DEFAULT_ROOT_MENU_TYPE = 'top';
    public const DEFAULT_CHILD_MENU_TYPE = 'left';
    /**
     * Menu CODE from randee.menu that should be synced into Aspro standard header menu (top/left).
     */
    public const ASpro_SYNC_MENU_CODE = 'heads';

    /**
     * @param string $menuCode Menu CODE from randee.menu
     */
    public static function syncToBitrixMenu(string $menuCode, string $rootMenuType = self::DEFAULT_ROOT_MENU_TYPE, string $childMenuType = self::DEFAULT_CHILD_MENU_TYPE): void
    {
        if (!Loader::includeModule('randee.menu')) {
            return;
        }

        if ($menuCode !== self::ASpro_SYNC_MENU_CODE) {
            // We only sync the menu intended to replace Aspro top/left.
            return;
        }

        $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        if ($docRoot === '') {
            return;
        }

        $siteDir = defined('SITE_DIR') ? (string)SITE_DIR : '';
        $siteDir = self::normalizeSiteDir($siteDir);

        $tree = MenuManager::getMenuTree($menuCode, true);
        if (!is_array($tree)) {
            $tree = [];
        }

        $topFilePath = $docRoot . $siteDir . '/.' . $rootMenuType . '.menu.php';
        self::writeMenuFile($topFilePath, self::buildMenuLinksFromNodes($tree));

        // For every node that is a "directory link", write left menu ext for its children (can be empty).
        foreach ($tree as $node) {
            self::syncLeftFilesForNode($node, $docRoot, $siteDir, $childMenuType);
        }

        self::clearBitrixMenuCache();
    }

    /**
     * @param array<string,mixed> $node
     */
    private static function syncLeftFilesForNode(array $node, string $docRoot, string $siteDir, string $childMenuType): void
    {
        $link = (string)($node['LINK'] ?? '');
        $children = is_array($node['CHILDREN'] ?? null) ? ($node['CHILDREN'] ?? []) : [];

        // Only directory-like internal links participate in dropdown recursion.
        if (!self::isDirectoryLink($link, $children)) {
            return;
        }

        $dirAbs = self::linkToAbsDir($link, $docRoot, $siteDir);
        if ($dirAbs === '') {
            return;
        }

        // Prepare children menu items inside this directory.
        $leftLinks = self::buildMenuLinksFromNodes($children);

        $leftMenuExt = $dirAbs . '/.' . $childMenuType . '.menu_ext.php';
        $leftMenuPhp = $dirAbs . '/.' . $childMenuType . '.menu.php';

        self::writeMenuFile($leftMenuExt, $leftLinks);
        self::writeMenuFile($leftMenuPhp, $leftLinks);

        // Recurse into child directory nodes.
        foreach ($children as $child) {
            if (is_array($child)) {
                self::syncLeftFilesForNode($child, $docRoot, $siteDir, $childMenuType);
            }
        }
    }

    /**
     * Build the $aMenuLinks structure required by CMenu:
     * [TEXT, LINK, ADDITIONAL_LINKS_ARRAY, PARAMS_ARRAY, CONDITION_STRING]
     *
     * @param array<int,array<string,mixed>> $nodes
     * @return array<int,array<int,mixed>>
     */
    private static function buildMenuLinksFromNodes(array $nodes): array
    {
        $result = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            $title = (string)($node['NAME'] ?? '');
            if ($title === '') {
                continue;
            }

            $link = (string)($node['LINK'] ?? '');
            $children = is_array($node['CHILDREN'] ?? null) ? ($node['CHILDREN'] ?? []) : [];
            if (!self::isProtocolLike($link) && !str_starts_with($link, '/')) {
                $link = '/' . $link;
            }

            // If this item has children, it must be a directory link for recursion.
            if (!empty($children) && !self::hasTrailingSlash($link)) {
                // If link has query - we can't safely turn it into a directory; keep as-is.
                if (strpos($link, '?') === false && strpos($link, '#') === false) {
                    $link = rtrim($link, '/') . '/';
                }
            }

            $params = self::normalizeParams($node['PARAMS'] ?? []);
            if (!empty($node['TARGET']) && in_array($node['TARGET'], ['_blank', '_parent', '_top'], true)) {
                // Aspro builds ATTRIBUTE string from PARAMS keys prefixed with "attr_".
                $params['attr_target'] = $node['TARGET'];
            }

            $result[] = [
                $title,
                $link,
                [],
                $params,
                '',
            ];
        }

        return $result;
    }

    /**
     * @param mixed $params
     * @return array<string,mixed>
     */
    private static function normalizeParams($params): array
    {
        if (is_array($params)) {
            return $params;
        }
        if (is_string($params) && $params !== '') {
            $decoded = json_decode($params, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    private static function writeMenuFile(string $filePath, array $aMenuLinks): void
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $php = "<?php\n";
        $php .= '$aMenuLinks = ' . var_export($aMenuLinks, true) . ";\n";
        $php .= "?>\n";

        @file_put_contents($filePath, $php, LOCK_EX);
    }

    private static function isProtocolLike(string $link): bool
    {
        return preg_match("'^(([A-Za-z]+://)|mailto:|javascript:|#)'i", $link) === 1;
    }

    private static function hasTrailingSlash(string $link): bool
    {
        return $link !== '' && str_ends_with($link, '/');
    }

    private static function isDirectoryLink(string $link, array $children): bool
    {
        if ($link === '') {
            return false;
        }
        // For recursion we need an internal directory-like link.
        if (self::isProtocolLike($link)) {
            return false;
        }
        $linkNoQuery = explode('?', $link, 2)[0];
        $linkNoQuery = explode('#', $linkNoQuery, 2)[0];
        $linkNoQuery = $linkNoQuery !== '' ? $linkNoQuery : $link;

        return self::hasTrailingSlash($linkNoQuery);
    }

    private static function linkToAbsDir(string $link, string $docRoot, string $siteDir): string
    {
        // Ensure it starts with "/"
        $link = $link !== '' ? $link : '/';
        if (!str_starts_with($link, '/')) {
            $link = '/' . $link;
        }

        $rel = ltrim($link, '/');
        $abs = $docRoot . $siteDir . '/' . $rel;
        $abs = rtrim($abs, '/\\') . '/';
        return $abs;
    }

    private static function normalizeSiteDir(string $siteDir): string
    {
        $siteDir = trim($siteDir);
        if ($siteDir === '' || $siteDir === '/') {
            return '';
        }
        if (!str_starts_with($siteDir, '/')) {
            $siteDir = '/' . $siteDir;
        }
        if (!str_ends_with($siteDir, '/')) {
            $siteDir .= '/';
        }
        return $siteDir;
    }

    private static function clearBitrixMenuCache(): void
    {
        if (class_exists('\\CBitrixComponent') && method_exists('\\CBitrixComponent', 'clearComponentCache')) {
            @\CBitrixComponent::clearComponentCache('bitrix:menu');
        }

        // Managed cache: try to clear if available. (Not all versions support the same directories.)
        try {
            $cache = Application::getInstance()->getManagedCache();
            $cache->cleanDir('menu');
        } catch (\Throwable $e) {
            // ignore
        }
    }
}

