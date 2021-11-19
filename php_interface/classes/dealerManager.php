<?php

namespace Avantis;

use \Bitrix\Crm\Binding\ContactCompanyTable;
use \Bitrix\Crm\EntityAddress;
use \Bitrix\Crm\EntityRequisite;
use \Bitrix\Crm\EntityAddressType;
use \Bitrix\Crm\EntityBankDetail;
use \CCrmOwnerType;
use \COption;
use \CSaleOrderUserProps;
use intec\core\db\Exception;

class DealerManager
{
    const BIND_TABLE_CLASS = '\Avantis\DealerBindsTable';
    const DEFAULT_COMPANY_TYPE = 'CUSTOMER';
    const DEALER_GROUP = 16;
    const COMPANY_CONTACT_FIELDS = [
            "EMAIL",
            'PHONE',
        ];
    const USER_FIELDS = [
        'LOGIN',
        'EMAIL',
        'ACTIVE',
        'NAME',
        'SECOND_NAME',
        'LAST_NAME',
    ];
    const COMPANY_FIELDS = [
        'TITLE',
        'COMPANY_TYPE',
        'EMAIL',
        'PHONE',
    ];
    const REQUISITE_FIELDS = [
        'RQ_ADDR',
        'NAME',
        'ACTIVE',
        'RQ_INN',
        'RQ_KPP',
        'RQ_OKPO',
        'RQ_COMPANY_NAME',
        'RQ_COMPANY_FULL_NAME',
    ];
    const ERROR_CREATE_TEXT = "Не удалось создать ";

    static $errors = [];


    public static function add($data)
    {
        if ($companyId = self::companyExist($data['COMPANY_XML'])) {
            return self::update($companyId, $data);
        }
        $data['mainContragent'] = self::isMainContragent($data);

        if ($data['mainContragent']) {
            $data['USER_ID'] = self::addUser($data);
            if(count(self::$errors) === 0){
                $data['CONTACT_ID'] = self::addContact($data);
            }
        } else {
            $mainBind = self::getMainBind($data['MAIN_COMPANY_XML']);
            $data['USER_ID'] = $mainBind->getUser_id();
            $data['CONTACT_ID'] = $mainBind->getContact_id();
        }
        $data['BUYER_PROFILE_ID'] = self::addBuyerProfile($data);
        $data['COMPANY_ID'] = self::addCompany($data);

        $requisitesResult = self::addCompanyRequisites($data);

        $requisiteId = $requisitesResult->isSuccess() ? (int)$requisitesResult->getId() : 0;
        if($requisiteId > 0) {
            self::addBankRequisites($requisiteId, $data);
        }

        self::bindCompanyContact($data['COMPANY_ID'], $data['CONTACT_ID']);

        self::addContragentBind($data);


        if ($companyId = $data['COMPANY_ID']) {
            echo "<a href=\"http://avantis.docker/crm/company/details/$companyId/\" target=\"_blank\">Создана компания с ID:$companyId</a><br/>";
        }else{
            throw new Exception("Не удалось создать компанию " . $data['COMPANY_XML']);
        }
        if ($userId = $data['USER_ID']) {
            echo 'Создан пользователь c ID:' . $userId . '<br/>';
        } else {
            throw new Exception("При создании пользователя произошла ошибка <br/>");
        }
        if ($buyerProfileId = $data['BUYER_PROFILE_ID']) {
            echo 'Создан профиль покупателя c ID:' . $buyerProfileId . '<br/>';
        } else {
            throw new Exception("При создании профиля покупателя произошла ошибка <br/>");
        }
        return self::$errors === [];
    }

    public static function delete($field, $value)
    {
        $statuses = [];
        $obBinds = (self::BIND_TABLE_CLASS)::query()->setFilter([$field => $value])->addOrder('IS_MAIN_CONTRAGENT', 'ASC')->setSelect(['*'])->exec()->fetchCollection();

        foreach ($obBinds as $obBind) {
            if (!$obBind->getIs_main_contragent()) {
                $statuses = self::deleteContragent($obBind, $statuses);
            }
            else{
                $statuses = self::deleteMainContragent($obBind, $statuses);
            }
        }
        return $statuses;
    }

    public static function update($companyId, $data)
    {
        $statuses = [];
        $obBinds = (self::BIND_TABLE_CLASS)::query()->setFilter(['COMPANY_ID' => $companyId])->setSelect(['*'])->exec()->fetchObject();
        $data['mainContragent'] = $obBinds->getIs_main_contragent();

        if ($data['mainContragent']) {
            $statuses['updated']['user'][$obBinds->getUser_id()] = self::updateUser($obBinds->getUser_id(), $data);
            $statuses['updated']['contact'][$obBinds->getContact_id()] = self::updateContact($obBinds->getContact_id(), $data);
        }
        $statuses['updated']['buyer_profile'][$obBinds->getBuyer_profile_id()] = self::updateBuyerProfile($obBinds->getBuyer_profile_id(), $data);
        $statuses['updated']['company'][] = self::updateCompany($obBinds->getCompany_id(), $data);

        $requisiteId = self::getCompanyRequisiteID($obBinds->getCompany_id())[0];
        $statuses['updated']['requisites'][$obBinds->getCompany_id()] = self::updateCompanyRequisites($requisiteId, $data);

        if(count($requisiteId) > 0) {
            $statuses['updated']['bank_requisites'] = self::updateBankRequisites($requisiteId, $data);
        }

        if(
            isset($data["XML_ID"], $data["MAIN_COMPANY_XML"]) && $data["XML_ID"] !== "" && $data["MAIN_COMPANY_XML"] !== ""
            && $data["XML_ID"] !== $data["MAIN_COMPANY_XML"]) {
//            self::updatebindCompanyContact($data['COMPANY_ID'], $data['CONTACT_ID']);
//            self::updateDealerBind($data);
        }
        return $statuses;
    }

    public static function getByUserId($id)
    {
       return self::getList(["filter" => ["USER_ID" => $id]]);
    }

    public static function getByXmlId($companyXml)
    {
        return self::getList(["filter" => ["COMPANY_XML" => $companyXml]])[0];
    }

    /**
     * @param $params
     * [
     *  "filter" => [
     *      "USER_ID" | "BUYER_PROFILE_ID" | "CONTACT_ID" | "COMPANY_ID" | "IS_MAIN_CONTRAGENT" | "COMPANY_XML" => value
     *  ],
     * ]
     * @return array
     */
    public static function getList($params)
    {
        $result = [];
        $obBinds = \Manao\DealerBindsTable::query();
        if(isset($params["filter"]) && is_array($params["filter"])){
            $obBinds->setFilter($params["filter"]);
        }
        $obBinds->setSelect(['*'])->exec();
        $allDilers = $obBinds->fetchAll();
        $usersId = array_map(fn($dealer)=>$dealer['USER_ID'], $allDilers);

        $usersDataTmp = self::getUserData($usersId);
        $usersData = [];
        foreach ($usersDataTmp as $user){
            $usersData[$user["ID"]] = $user;
        }
        unset($usersDataTmp);

        $companiesId = array_map(fn($dealer)=>$dealer['COMPANY_ID'], $allDilers);
        $companiesData = self::getCompanyData($companiesId);
        array_map(
            function(&$company){
                unset($company["ID"]);
            },
            $companiesData
        );

        foreach ($allDilers as $dealer){
            $data = array_merge($usersData[$dealer["USER_ID"]], $companiesData[$dealer["COMPANY_ID"]]);
            $result[] = $data;
        }

        return $result;
    }

    protected static function getUserData($userIds)
    {
        return \Bitrix\Main\UserTable::query()->
        setSelect(self::USER_FIELDS)->
        addSelect("ID")->
        addFilter('ID', $userIds)->
        exec()->
        fetchAll();
    }

    protected static function getCompanyData($companyIds)
    {
        $companiesData = [];
        $select = array_merge(self::COMPANY_FIELDS, ["ID"]);
        $rsCompany = \CCrmCompany::GetList([], ["ID" => $companyIds], $select);
        while($companyData = $rsCompany->Fetch()){
            $companiesData[$companyData["ID"]] = $companyData;
        }

        $companiesRequisites = self::getCompanyRequisites($companyIds);
        $requisitesId = array_keys($companiesRequisites);
        $companiesBanksRequisites = self::getCompanyBankRequisites($requisitesId);
        foreach($companiesBanksRequisites as $bank){
            $companiesRequisites[$bank["ENTITY_ID"]]["BANK_REQUISITES"][] = $bank;
        }

        foreach($companiesRequisites as $requisite){
            $companiesData[$requisite["ENTITY_ID"]] = array_merge($companiesData[(int)$requisite["ENTITY_ID"]], $requisite);
        }
        
        $companiesContacts = self::getCompanyContacts($companyIds);

        foreach($companiesRequisites as $requisite){
            $companiesData[$requisite["ENTITY_ID"]] = array_merge($companiesData[$requisite["ENTITY_ID"]], $requisite);
        }
        foreach($companiesData as $companyId => &$companyData){
            $companyData = array_merge((array)$companyData, (array)$companiesRequisites[$companyId], (array)$companiesContacts[$companyId]);
        }
        return $companiesData;
    }

    protected static function getCompanyRequisites($companyIds)
    {
        $companiesRequisites = [];
        $req = new EntityRequisite();

        $selectRequisites = array_merge(self::REQUISITE_FIELDS, ["ID", "ENTITY_ID"]);
        $dbRes = $req->getList(["filter" => ["ENTITY_ID" => $companyIds, "ENTITY_TYPE_ID" => \CCrmOwnerType::Company, 'PRESET_ID' => 1], "select" => $selectRequisites]);
        while($companyRequisites = $dbRes->fetch()){
            $companiesRequisites[$companyRequisites["ID"]] = $companyRequisites;
        }
        return $companiesRequisites;
    }

    protected static function getCompanyBankRequisites($requisitesIds)
    {
        $companiesBankRequisites = [];

        $req = new EntityBankDetail();
        $rs = $req->getList([
            "filter" => [
                "ENTITY_ID" => $requisitesIds,
                "ENTITY_TYPE_ID" => \CCrmOwnerType::Requisite,
                "COUNTRY_ID" => 1, //RUSSIA
            ],
        ]);
        while($bankRequisite = $rs->fetch()){
            $companiesBankRequisites[$bankRequisite["ID"]] = $bankRequisite;
        }

        return $companiesBankRequisites;
    }

    protected static function getCompanyContacts($companyIds)
    {
        $contacts = [];
        $dbResMultiFields = \CCrmFieldMulti::GetList(array(),array('ENTITY_ID'=>'COMPANY','ELEMENT_ID'=>$companyIds));
        while($contact = $dbResMultiFields->Fetch()){
            $contacts[$contact["ELEMENT_ID"]][$contact["TYPE_ID"]] = $contact["VALUE"];
        }
        return $contacts;
    }

    protected static function deleteContragent($obBind, $statuses)
    {
        $statuses['deleted']['company'][$obBind->getCompany_id()] = self::deleteCompany($obBind->getCompany_id());
        $statuses['deleted']['buyer_profile'][$obBind->getBuyer_profile_id()] = self::deleteBuyerProfile($obBind->getBuyer_profile_id());
        $statuses['deleted']['dealer_bind'][$obBind->getCompany_xml()] = self::deleteContragentBindByXml($obBind->getCompany_xml());
        return $statuses;
    }

    protected static function deleteMainContragent($obBind, $statuses)
    {
        if (self::isLastDealerCompany($obBind->getUser_id())) {
            $statuses = self::deleteContragent($obBind, $statuses);
            $statuses['deleted']['user'][$obBind->getUser_id()] = self::deleteUser($obBind->getUser_id());
            $statuses['deleted']['contact'][$obBind->getContact_id()] = self::deleteContact($obBind->getContact_id());
            self::delete('USER_ID', $obBind->getUser_id());
        }
        else{
            new \Exception("Попытка удалить дилера id-{$obBind->getUser_id()}, у которого, есть подчиненные компании.");
        }
        return $statuses;
    }

    protected static function isLastDealerCompany($userId): bool
    {
        $obBind = (self::BIND_TABLE_CLASS)::query()->setFilter(["USER_ID" => $userId])->setSelect(['ID'])->exec();
        return $obBind->getSelectedRowsCount() === 1;
    }

    protected static function addUser($data)
    {
        $user = new \CUser;
        $data = self::prepareUserParams($data);
        $userId = $user->Add($data);
        if(!$userId){
            self::$errors[] = self::ERROR_CREATE_TEXT . "пользователя";
        }
        return $userId;
    }

    protected static function updateUser($id, $data)
    {
        $fields = self::prepareUserParams($data);
        $user = new \CUser;
        return $user->Update($id, $fields);
    }

    protected static function deleteUser($userId)
    {
        return \CUser::Delete($userId);
    }

    protected static function prepareUserParams($params)
    {
        $def_group = COption::GetOptionString("main", "new_user_registration_def_group", "");
        if($def_group !== "") {
            $def_groups = explode(",", $def_group);
        }
        $def_groups[] = self::DEALER_GROUP;
        $params['XML_ID'] = $params['COMPANY_XML'];
        $params["GROUP_ID"] = $def_groups;

        return $params;
    }

    protected static function addBuyerProfile($data)
    {
        $userId = $data['USER_ID'];
        $profileId = false;
        $profileName = $data['COMPANY_TITLE'];
        $personType = $GLOBALS['AV_CONSTANTS']['DEALER_PERSON_TYPE_ID'];
        $arOrderPropsValues = self::prepareBuyerProfileParams($data);
        $arErrors = [];

        $arOrderPropsValues = self::getOrderPropsIdByCode($arOrderPropsValues);

        $profileId = CSaleOrderUserProps::DoSaveUserProfile($userId, $profileId, $profileName, $personType, $arOrderPropsValues, $arErrors);

        return $profileId;
    }

    protected static function updateBuyerProfile($buyerProfileId, $data)
    {
        $arOrderPropsValues = self::prepareBuyerProfileParams($data);
        $arOrderPropsValues["NAME"] = $data['COMPANY_TITLE'];
        $arOrderPropsValues["PERSON_TYPE_ID"] = $GLOBALS['AV_CONSTANTS']['DEALER_PERSON_TYPE_ID'];

        $fields = self::getOrderPropsIdByCode($arOrderPropsValues);
        return \CSaleOrderUserProps::Update($buyerProfileId, $fields);
    }

    protected static function deleteBuyerProfile($id)
    {
        $deleteStatus = \CSaleOrderUserProps::Delete($id);
        return $deleteStatus;
    }

    protected static function prepareBuyerProfileParams($data)
    {
        $fields = [
            'COMPANY_TITLE' => $data['COMPANY_TITLE'],
            'COMPANY_EMAIL' => $data['EMAIL'],
            'COMPANY_PHONE' => $data['PHONE'],
            'COMPANY_ADDRESS' => $data['ADDRESSES']['DELIVERY']['ADDRESS_1'], // нужно собирать адрес из полей
            'COMPANY_RQ_INN_1' => $data['INN'],
        ];
        return $fields;
    }

    protected static function getOrderPropsIdByCode(array $properties)
    {
        $result = [];
        $filter = [
            "PERSON_TYPE_ID" => $GLOBALS['AV_CONSTANTS']['DEALER_PERSON_TYPE_ID'],
            "CODE" => array_keys($properties),
        ];
        $rs_props = \CSaleOrderProps::GetList([], $filter, false, false, ["CODE", "ID"]);
        while ($props = $rs_props->Fetch()) {
            $result[$props["ID"]] = $properties[$props["CODE"]];
        }
        return $result;
    }

    protected static function addContact($data)
    {
        $arFields = [
            'NAME' => $data["NAME"],
            'SECOND_NAME' => $data["SECOND_NAME"],
            'LAST_NAME' => $data["LAST_NAME"],
            'FULL_NAME' => $data["FULL_NAME"],
        ];
        $obContact = new \CCrmContact(false);
        $contactId = $obContact->Add($arFields, true);
        if(!$contactId){
            self::$errors[] = self::ERROR_CREATE_TEXT . "контакт";
        }
        return $contactId;
    }

    protected static function updateContact($contactId, $data)
    {
        $obContact = new \CCrmContact(false);
        $arFields = ['NAME', 'SECOND_NAME', 'LAST_NAME', 'FULL_NAME'];
        $fields = [];
        foreach ($arFields as $field){
            if(isset($data[$field]) && $data[$field] !== "" && is_string($data[$field])){
                $fields[$field] = $data[$field];
            }
        }
        return $obContact->Update($contactId, $fields);
    }

    protected static function deleteContact($contactId)
    {
        $obContact = new \CCrmContact(false);
        return $obContact->Delete($contactId);
    }

    protected static function addCompany($data)
    {
        $obCompany = new \CCrmCompany(false);
        $fields = self::prepareCompanyParams($data);
        return $obCompany->Add($fields, true);
    }

    protected static function updateCompany($companyId, $data)
    {
        $obCompany = new \CCrmCompany(false);
        $fields = self::prepareCompanyParams($data);
        return $obCompany->Update($companyId, $fields);
    }

    protected static function deleteCompany($companyId)
    {
        $obCompany = new \CCrmCompany(false);
        $deleteStatus = $obCompany->Delete($companyId);
        self::deleteCompanyRequisites($companyId);
        return $deleteStatus;
    }

    protected static function prepareCompanyParams($params)
    {
        $fields = [];
        if(isset($params["COMPANY_TITLE"])){
            $fields["TITLE"] = $fields["COMPANY_TYPE"] = $params["COMPANY_TITLE"];
        }
        foreach (self::COMPANY_CONTACT_FIELDS as $field) {
            if (array_key_exists($field, $params)) {
                $fields["FM"][$field] = [
                    'n0' => [
                        'VALUE_TYPE' => 'WORK',
                        'VALUE' => $params[$field],
                    ]
                ];
            }
        }
        return $fields;
    }

    protected static function getCompanyRequisiteID($companyId)
    {
        $requisite = new EntityRequisite();
        return $requisite->getEntityRequisiteIDs(CCrmOwnerType::Company, $companyId);
    }

    protected static function addCompanyRequisites($data)
    {
        $fields = self::prepareRequisiteParams($data);
        $requisite = new EntityRequisite();
        $result = $requisite->add($fields);
        return $result;
    }

    protected static function updateCompanyRequisites($requisiteId, $data)
    {
        $fields = self::prepareRequisiteParams($data);
        $requisite = new EntityRequisite();
        return $requisite->update($requisiteId, $fields);
    }

    protected static function deleteCompanyRequisites($companyId)
    {
        $requisite = new EntityRequisite();
        return $requisite->deleteByEntity(CCrmOwnerType::Company, $companyId);
    }

    protected static function addBankRequisites($requisiteId, $data)
    {
        $result = [];
        $obBankRequisite = new EntityBankDetail;
        foreach ($data['BANK_REQUISITES'] as $bankRequisite) {
            $bankRequisite = self::prepareBankRequisiteParams($requisiteId, $bankRequisite);
            $bankRequisite['ID'] = "";
            $result[] = $obBankRequisite->add($bankRequisite);
        }
        return $result;
    }

    protected static function updateBankRequisites($requisiteId, $data)
    {
        $result = [];
        $obBankRequisite = new \Bitrix\Crm\EntityBankDetail;
        foreach ($data['BANK_REQUISITES'] as $bankRequisite) {
            $filter = [
                "RQ_ACC_NUM" => $bankRequisite["RQ_ACC_NUM"]
            ];
            $bankRequisiteId = $obBankRequisite->getList([$filter, "select" =>["ID"]])->fetch()["ID"];
            if($bankRequisiteId){
                $bankRequisite = self::prepareBankRequisiteParams($bankRequisiteId, $bankRequisite);
                $result[] = $obBankRequisite->update($bankRequisiteId, $bankRequisite);
            }
            else{
                $result[] = self::addBankRequisites($requisiteId, ["BANK_REQUISITES" => $bankRequisite])[0];
            }
        }
        return $result;
    }

    protected static function prepareRequisiteParams($data)
    {
        $fields['RQ_ADDR'] = self::prepareAddress($data['ADDRESSES']);
        $fields['ENTITY_ID'] = $data['COMPANY_ID'];
        $fields['ENTITY_TYPE_ID'] = \CCrmOwnerType::Company;//todo add like var in $data
        $fields['PRESET_ID'] = 1; //todo разобраться с шаблонами реквизитов
        $fields['NAME'] = $data['COMPANY_TITLE'];
        $fields['SORT'] = 500;
        $fields['ACTIVE'] = 'Y';
        $fields['RQ_INN'] = $data['INN'];
        $fields['RQ_KPP'] = $data['KPP'];
        $fields['RQ_OKPO'] = $data['OKPO'];
        $fields['RQ_COMPANY_NAME'] = $data['COMPANY_NAME'];
        $fields['RQ_COMPANY_FULL_NAME'] = $data['COMPANY_FULL_NAME'];
        return $fields;
    }

    protected static function prepareBankRequisiteParams($requisiteId, $bankRequisite)
    {
        $bankRequisite['ENTITY_ID'] = $requisiteId;
        $bankRequisite['ENTITY_TYPE_ID'] = CCrmOwnerType::Requisite;
        $bankRequisite['MODIFY_BY_ID'] = $bankRequisite['DATE_MODIFY'] = '';
        return $bankRequisite;
    }

    protected static function prepareAddress($addresses)
    {
        $result = [];
        foreach ($addresses as $type => $fields) {
            $typeId = EntityAddressType::resolveID($type);
            $result[$typeId] = $fields;
        }
        return $result;
    }

    protected static function bindCompanyContact($companyId, $contactId)
    {
        $contactId = (int)$contactId;
        $companyId = (int)$companyId;
        $companyIDs = [(int)$companyId];
        ContactCompanyTable::bindCompanyIDs($contactId, $companyIDs);
        $result = in_array($companyId, ContactCompanyTable::getContactCompanyIDs($contactId), true);
        return $result;
    }

    protected static function isMainContragent($data)
    {
        return $data['XML_ID'] === $data['MAIN_COMPANY_XML'];
    }

    protected static function addContragentBind($data)
    {
        $obBinds = (self::BIND_TABLE_CLASS)::createObject();
        $obBinds->setUser_id($data['USER_ID']);
        $obBinds->setContact_id($data['CONTACT_ID']);
        $obBinds->setBuyer_profile_id($data['BUYER_PROFILE_ID']);
        $obBinds->setCompany_id($data['COMPANY_ID']);
        $obBinds->setCompany_xml($data['COMPANY_XML']);
        $obBinds->setIs_main_contragent($data['mainContragent']);
        return $obBinds->save();
    }

    protected static function deleteContragentBindByXml($companyXml)
    {
        $obBind = (self::BIND_TABLE_CLASS)::query()->setFilter(["COMPANY_XML" => $companyXml])->setSelect(['*'])->exec()->fetchObject();
        return $obBind->delete();
    }

    protected static function getMainBind($companyXml)
    {
        return (self::BIND_TABLE_CLASS)::query()->
        setFilter(['COMPANY_XML' => $companyXml, 'IS_MAIN_CONTRAGENT' => true])->
        setSelect(['CONTACT_ID', 'USER_ID'])->
        exec()->fetchObject();
    }

    protected static function companyExist($companyXml)
    {
        $dealer = (bool)(self::BIND_TABLE_CLASS)::query()->
        setFilter(['COMPANY_XML' => $companyXml])->
        setSelect(['ID'])->
        exec()->fetchObject();
        if($dealer){
            return $dealer->getCompany_id();
        }
        return $dealer;
    }

}
