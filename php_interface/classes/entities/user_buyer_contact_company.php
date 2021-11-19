<?php

namespace Avantis;

use \Bitrix\Main\Entity\DataManager;
use \Bitrix\Main\Entity\IntegerField;
use \Bitrix\Main\Entity\BooleanField;
use \Bitrix\Main\Entity\ReferenceField;
use \Bitrix\Main\Entity\TextField;
use \Bitrix\Main\UserTable;
use \Bitrix\Sale\Internals\UserPropsTable;
use \Bitrix\Crm\ContactTable;
use \Bitrix\Crm\CompanyTable;


class DealerBindsTable extends DataManager
{

    public static function getTableName()
    {
        return 'av_user_buyer_contact_company';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
            (new IntegerField('USER_ID'))->configureRequired(),
            (new IntegerField('BUYER_PROFILE_ID'))->configureRequired(),
            (new IntegerField('CONTACT_ID'))->configureRequired(),
            (new IntegerField('COMPANY_ID'))->configureRequired(),
            (new BooleanField('IS_MAIN_CONTRAGENT'))->configureRequired(),
            (new TextField('COMPANY_XML')),

            new ReferenceField(
                'USER',
                UserTable::getEntity(),
                ['=this.USER_ID' => 'ref.ID']
            ),
            new ReferenceField(
                'BUYER_PROFILE',
                UserPropsTable::getEntity(),
                ['=this.BUYER_PROFILE_ID' => 'ref.ID']
            ),
            new ReferenceField(
                'CONTACT',
                ContactTable::getEntity(),
                ['=this.CONTACT_ID' => 'ref.ID']
            ),
            new ReferenceField(
                'COMPANY',
                CompanyTable::getEntity(),
                ['=this.COMPANY_ID' => 'ref.ID']
            ),
        ];
    }

}

