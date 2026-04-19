<?php
/**
 * @author Randee
 * @copyright 2025
 */

namespace Randee\Menu;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class MenuItemTable extends Entity\DataManager
{
    public static function getTableName(): string
    {
        return 'b_randee_menu_item';
    }

    public static function getMap(): array
    {
        return [
            new Entity\IntegerField('ID', [
                'primary'      => true,
                'autocomplete' => true,
            ]),
            new Entity\IntegerField('MENU_ID', [
                'required' => true,
            ]),
            new Entity\IntegerField('PARENT_ID', [
                'default' => 0,
            ]),
            new Entity\StringField('NAME', [
                'required'   => true,
                'validation' => [__CLASS__, 'validateName'],
            ]),
            new Entity\StringField('LINK', [
                'validation' => [__CLASS__, 'validateLink'],
            ]),
            new Entity\StringField('LINK_TYPE', [
                'values'  => ['inner', 'outer'],
                'default' => 'inner',
            ]),
            new Entity\IntegerField('SORT', [
                'default' => 100,
            ]),
            new Entity\BooleanField('ACTIVE', [
                'values'  => ['N', 'Y'],
                'default' => 'Y',
            ]),
            new Entity\TextField('PARAMS'),
            new Entity\StringField('TARGET', [
                'values'  => ['_self', '_blank', '_parent', '_top'],
                'default' => '_self',
            ]),
            new Entity\IntegerField('DEPTH_LEVEL', [
                'default' => 1,
            ]),
            new Entity\DatetimeField('DATE_CREATE'),
            new Entity\DatetimeField('DATE_MODIFY'),
            new Entity\ReferenceField(
                'MENU',
                MenuTable::class,
                ['=this.MENU_ID' => 'ref.ID']
            ),
            new Entity\ReferenceField(
                'PARENT',
                self::class,
                ['=this.PARENT_ID' => 'ref.ID']
            ),
        ];
    }

    public static function validateName(): array
    {
        return [
            new Entity\Validator\Length(null, 255),
        ];
    }

    public static function validateLink(): array
    {
        return [
            new Entity\Validator\Length(null, 500),
        ];
    }

    public static function onBeforeAdd(Entity\Event $event): Entity\EventResult
    {
        $result = new Entity\EventResult();
        $data   = $event->getParameter('fields');
        $depth  = 1;
        if (!empty($data['PARENT_ID'])) {
            $parent = self::getById($data['PARENT_ID'])->fetch();
            $depth  = ($parent['DEPTH_LEVEL'] ?? 1) + 1;
        }
        $result->modifyFields([
            'DEPTH_LEVEL' => $depth,
            'DATE_CREATE' => new DateTime(),
            'DATE_MODIFY' => new DateTime(),
        ]);
        return $result;
    }

    public static function onBeforeUpdate(Entity\Event $event): Entity\EventResult
    {
        $result = new Entity\EventResult();
        $id     = $event->getParameter('id');
        $data   = $event->getParameter('fields');

        $modify = ['DATE_MODIFY' => new DateTime()];

        if (isset($data['PARENT_ID'])) {
            $depth = 1;
            if ($data['PARENT_ID'] > 0) {
                $parent = self::getById($data['PARENT_ID'])->fetch();
                $depth  = ($parent['DEPTH_LEVEL'] ?? 1) + 1;
            }
            $modify['DEPTH_LEVEL'] = $depth;
        }

        $result->modifyFields($modify);
        return $result;
    }
}
