<?php

namespace Avantis;

use \Bitrix\Main\Entity\DataManager;
use \Bitrix\Main\Entity\IntegerField;
use \Bitrix\Main\Entity\TextField;
use \Bitrix\Main\Entity\BooleanField;


class ContragentDataTmpTable extends DataManager
{

    public static function getTableName()
    {
        return 'av_contragent_tmp';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
            (new IntegerField('XML_ID'))->configureRequired(),
            (new BooleanField('NEED_DELETE')),
            (new TextField('TITLE')),
            (new TextField('FULL_NAME')),
            (new TextField('TYPE')),
            (new TextField('INN')),
            (new TextField('KPP')),
            (new TextField('OKPO')),
            (new TextField('BANK_ACCOUNT')),
            (new TextField('YR_ADDRESS')),
            (new TextField('ADDRESS')),
        ];
    }

}

