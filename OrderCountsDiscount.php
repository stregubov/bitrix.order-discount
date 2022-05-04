<?php

use Bitrix\Main\Context;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\OrderPropsValueTable;
use Bitrix\Sale\Internals\OrderTable;

class OrderCountsDiscount extends \CSaleActionCtrlAction
{
    /**
     * Получение имени класса
     * @return string
     */
    public static function GetClassName()
    {
        return __CLASS__;
    }

    /**
     * Получение ID условия
     * @return array|string
     */
    public static function GetControlID()
    {
        return "DiscountPriceType";
    }

    /**
     * @param $arParams
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function GetControlShow($arParams)
    {
        $arControls = static::GetAtomsEx();
        $arResult = array(
            'controlgroup' => true,
            'group' => false,
            'label' => 'Кастомные правила',
            'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
            'children' => [
                array(
                    'controlId' => static::GetControlID(),
                    'group' => false,
                    'label' => "Количество заказов меньше, чем ",
                    'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
                    'control' => array(
                        "Количество заказов меньше, чем",
                        $arControls["PT"]
                    )
                )
            ]
        );

        return $arResult;
    }

    /**
     * @param bool $strControlID
     * @param bool $boolEx
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function GetAtomsEx($strControlID = false, $boolEx = false)
    {
        $boolEx = (true === $boolEx ? true : false);

        $arAtomList = [
            "PT" => [
                "JS" => [
                    "id" => "PT",
                    "name" => "extra",
                    "type" => "select",
                    "values" => [
                        "1" => "1",
                        "2" => "2",
                        "3" => "3"
                    ],
                    "defaultText" => "...",
                    "defaultValue" => "",
                    "first_option" => "..."
                ],
                "ATOM" => [
                    "ID" => "PT",
                    "FIELD_TYPE" => "string",
                    "FIELD_LENGTH" => 255,
                    "MULTIPLE" => "N",
                    "VALIDATE" => "list"
                ]
            ],
        ];

        if (!$boolEx) {
            foreach ($arAtomList as &$arOneAtom) {
                $arOneAtom = $arOneAtom["JS"];
            }
            if (isset($arOneAtom)) {
                unset($arOneAtom);
            }
        }

        return $arAtomList;
    }

    /**
     * @param $arOneCondition
     * @param $arParams
     * @param $arControl
     * @param bool $arSubs
     * @return string
     */
    public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
    {
        return __CLASS__ . '::applyProductDiscount($row,' . $arOneCondition["PT"] . ')';
    }

    /**
     * @param $row
     * @param $count
     * @return bool
     */
    public static function applyProductDiscount($row, $count)
    {
        $request = Context::getCurrent()->getRequest();
        global $USER;
        if ($USER->IsAdmin()) {

            // Если пользователь авторизован, то ищем его заказы по ID
            if ($USER->IsAuthorized()) {
                $userId = (int)$USER->getId();

                if ($userId) {
                    $orders = OrderTable::getList(['filter' => ['USER_ID' => $userId], 'select' => ['ID']]);

                    $ordersCount = $orders->getSelectedRowsCount();

                    if ($ordersCount > $count) {
                        return false;
                    }
                }
            }

            // Ищем заказы по номеру телефона
            $prop = OrderPropsTable::getList([
                'filter' => ['IS_PHONE' => 'Y', '=PERSON_TYPE_ID' => 3],
                'cache' => ['ttl' => 3600]
            ])->fetch();

            if (!$prop) {
                return false;
            }

            $phonePropertyId = $prop['ID'];

            $phone = $request->getPost('ORDER_PROP_' . $phonePropertyId);

            if (empty($phone)) {
                $props = $request->getPost('order');
                $phone = $props['ORDER_PROP_' . $phonePropertyId];
            }

            if (empty($phone)) {
                return false;
            }

            $phone = str_replace(['+', '-', '(', ')'], '', $phone);

            $propValues = OrderPropsValueTable::getList([
                'filter' => [
                    'ORDER_PROPS_ID' => $phonePropertyId,
                    'VALUE' => $phone
                ],
                'select' => ['ORDER_ID']
            ]);

            $ordersCount = $propValues->getSelectedRowsCount();

            return $ordersCount < $count;
        }

        return false;
    }
}
