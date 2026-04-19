<?php
/**
 * @author Randee
 * @copyright 2025
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class randee_menu extends CModule
{
    public $MODULE_ID = 'randee.menu';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->MODULE_NAME        = Loc::getMessage('RANDEE_MENU_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('RANDEE_MENU_MODULE_DESC');
        $this->PARTNER_NAME       = Loc::getMessage('RANDEE_MENU_PARTNER_NAME');
        $this->PARTNER_URI        = Loc::getMessage('RANDEE_MENU_PARTNER_URI');
    }

    public function DoInstall(): bool
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();
        return true;
    }

    public function DoUninstall(): bool
    {
        $this->UnInstallEvents();
        $this->UnInstallDB();
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
        return true;
    }

    public function InstallDB(): bool
    {
        $connection = Application::getConnection();
        $sqlPath    = __DIR__ . '/db/mysql/install.sql';

        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            if ($sql) {
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $query) {
                    if (!empty($query)) {
                        $connection->query($query);
                    }
                }
            }
        }
        return true;
    }

    public function UnInstallDB(): bool
    {
        $connection = Application::getConnection();
        $sqlPath    = __DIR__ . '/db/mysql/uninstall.sql';

        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            if ($sql) {
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $query) {
                    if (!empty($query)) {
                        $connection->query($query);
                    }
                }
            }
        }
        return true;
    }

    public function InstallEvents(): bool
    {
        return true;
    }

    public function UnInstallEvents(): bool
    {
        return true;
    }

    public function InstallFiles(): bool
    {
        CopyDirFiles(
            __DIR__ . '/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin',
            true,
            true
        );
        $localComponents = $_SERVER['DOCUMENT_ROOT'] . '/local/components';
        if (!is_dir($localComponents)) {
            @mkdir($localComponents, 0755, true);
        }
        CopyDirFiles(
            dirname(__DIR__) . '/components',
            $localComponents,
            true,
            true
        );
        // JS (Sortable и админ-дерево) лежит в каталоге модуля: /local|bitrix/modules/randee.menu/js/
        // Дополнительное копирование не требуется — поставляется вместе с модулем.
        return true;
    }

    public function UnInstallFiles(): bool
    {
        $docRoot = $_SERVER['DOCUMENT_ROOT'];
        $base = $docRoot . '/bitrix/admin/';
        @unlink($base . 'randee_menu_menus.php');
        @unlink($base . 'randee_menu_edit.php');
        @unlink($base . 'randee_menu_items.php');
        @unlink($base . 'randee_menu_item_edit.php');
        $compDir = new \Bitrix\Main\IO\Directory($docRoot . '/local/components/randee.menu');
        if ($compDir->isExists()) {
            $compDir->delete();
        }
        return true;
    }

    public function GetModuleRightList(): array
    {
        return [
            'reference_id' => ['D', 'R', 'W'],
            'reference'    => [
                '[D] ' . Loc::getMessage('RANDEE_MENU_RIGHT_DENIED'),
                '[R] ' . Loc::getMessage('RANDEE_MENU_RIGHT_READ'),
                '[W] ' . Loc::getMessage('RANDEE_MENU_RIGHT_WRITE'),
            ],
        ];
    }
}
