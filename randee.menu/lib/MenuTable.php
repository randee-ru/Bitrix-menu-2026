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

class MenuTable extends Entity\DataManager
{
    public static function getTableName(): string
    {
        return 'b_randee_menu';
    }

    public static function getMap(): array
    {
        return [
            new Entity\IntegerField('ID', [
                'primary'      => true,
                'autocomplete' => true,
            ]),
            new Entity\StringField('CODE', [
                'required'   => true,
                'validation' => [__CLASS__, 'validateCode'],
            ]),
            new Entity\StringField('NAME', [
                'required'   => true,
                'validation' => [__CLASS__, 'validateName'],
            ]),
            new Entity\TextField('DESCRIPTION'),
            new Entity\IntegerField('SORT', [
                'default' => 100,
            ]),
            new Entity\BooleanField('ACTIVE', [
                'values' => ['N', 'Y'],
                'default' => 'Y',
            ]),
            new Entity\DatetimeField('DATE_CREATE'),
            new Entity\DatetimeField('DATE_MODIFY'),
        ];
    }

    public static function validateCode(): array
    {
        return [
            new Entity\Validator\Length(null, 50),
        ];
    }

    public static function validateName(): array
    {
        return [
            new Entity\Validator\Length(null, 255),
        ];
    }

    public static function onBeforeAdd(Entity\Event $event): Entity\EventResult
    {
        $result = new Entity\EventResult();
        $data   = $event->getParameter('fields');
        $result->modifyFields([
            'DATE_CREATE' => new DateTime(),
            'DATE_MODIFY' => new DateTime(),
        ]);
        return $result;
    }

    public static function onBeforeUpdate(Entity\Event $event): Entity\EventResult
    {
        $result = new Entity\EventResult();
        $result->modifyFields([
            'DATE_MODIFY' => new DateTime(),
        ]);
        return $result;
    }
}
