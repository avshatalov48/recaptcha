<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

require_once __DIR__ .'/helper.php';

$module_id = bx_module_id();
$LOC = bx_loc_prefix();

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/options.php');
Loc::loadMessages(__FILE__);

// �������� ���� �� ��������� ������
if ($APPLICATION->GetGroupRight($module_id) < 'S')
{
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

Loader::includeModule($module_id);

$request = HttpApplication::getInstance()->getContext()->getRequest();

// ��������� ������� � ���� ����
$aTabs =
[
    [
        'DIV' => 'settings',
        'TAB' => Loc::getMessage($LOC .'TAB_SETTINGS'),
        'OPTIONS' =>
        [
            [
                'site_key',
                Loc::getMessage($LOC .'FIELD_SITE_KEY'),
                '',
                ['text', 50]
            ],
            [
                'secret_key',
                Loc::getMessage($LOC .'FIELD_SECRET_KEY'),
                '',
                ['text', 50]
            ],
            [
                'permissible_score',
                Loc::getMessage($LOC .'FIELD_PERMISSIBLE_SCORE'),
                '0.5',
                ['text', 5]
            ],
            [
                'hide_badge',
                Loc::getMessage($LOC .'FIELD_HIDE_BADGE'),
                'Y',
                ['checkbox']
            ],
            [
                'error_message',
                Loc::getMessage($LOC .'FIELD_ERROR_MESSAGE'),
                '',
                ['text', 50]
            ]
        ]
    ]
];


/**
 * ����������� �������������
 */
$aTabs[] =
[
    'DIV' => 'registration',
    'TAB' => Loc::getMessage($LOC .'TAB_REGISTRATION'),
    'OPTIONS' =>
    [
        [
            'registrationEnable',
            Loc::getMessage($LOC .'FIELD_REGISTRATION'),
            'N',
            ['checkbox']
        ],
        ['note' => Loc::getMessage($LOC .'NOTE_REGISTRATION')]
    ]
];


/**
 * ���������� ������ "��� �����"
 */
if (Loader::includeModule('form'))
{
    // �������� ������ ����
    $arWebForm = [];
    $rsForms = CForm::GetList($by = "s_sort", $order = "asc", [], $filtered);
    while ($arForm = $rsForms->Fetch())
    {
        $arWebForm[$arForm['ID']] = '[' . $arForm['ID'] . '] ' . $arForm['NAME'];
    }

    if (!empty($arWebForm))
    {
        $aTabs[] =
        [
            'DIV' => 'webform',
            'TAB' => Loc::getMessage($LOC .'TAB_WEBFORM'),
            'OPTIONS' =>
            [
                [
                    'webform_ids',
                    Loc::getMessage($LOC .'FIELD_WEBFORM_IDS'),
                    '',
                    ['multiselectbox', $arWebForm]
                ]
            ]
        ];
    }
}


/**
 * ���������� ������ "���������"
 */
if (Loader::includeModule('iblock'))
{
    // �������� ������ ����
    $arBlocks = [];
    $rsBlocks = CIBlock::GetList();
    while ($arBlock = $rsBlocks->Fetch())
    {
        $arBlocks[$arBlock['ID']] = '[' . $arBlock['ID'] . '] ' . $arBlock['NAME'];
    }

    if (!empty($arBlocks))
    {
        $aTabs[] =
        [
            'DIV' => 'iblock',
            'TAB' => Loc::getMessage($LOC .'TAB_IBLOCK'),
            'OPTIONS' =>
            [
                [
                    'iblock_ids',
                    Loc::getMessage($LOC .'FIELD_IBLOCK_IDS'),
                    '',
                    ['multiselectbox', $arBlocks]
                ]
            ]
        ];
    }
}


// ���������� ��������
if ($request->isPost() && $request['Update'] && check_bitrix_sessid())
{
    foreach ($aTabs as $aTab)
    {
        foreach ($aTab['OPTIONS'] as $arOption)
        {
            if (!is_array($arOption)) continue; // ������ � ����������, ������������ ��� ���������� �������� � ����� �������
            if ($arOption['note']) continue; // ����������� � ����������

            __AdmSettingsSaveOption($module_id, $arOption);
        }
    }
}

// ����� �����
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>

<? $tabControl->Begin(); ?>
<form method="POST"
    action="<?=$APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($request['mid'])?>&lang=<?=$request['lang']?>"
    name="<?=bx_module_id_prefix() . '_settings'?>">

    <?
    foreach ($aTabs as $aTab)
    {
        if ($aTab['OPTIONS'])
        {
            $tabControl->BeginNextTab();
            __AdmSettingsDrawList($module_id, $aTab['OPTIONS']);
        }
    }
    ?>

    <? $tabControl->Buttons(); ?>
    <input type="submit" name="Update" value="<?=Loc::getMessage('MAIN_SAVE')?>">
    <input type="reset" name="reset" value="<?=Loc::getMessage('MAIN_RESET')?>">

    <?=bitrix_sessid_post()?>
</form>
<? $tabControl->End(); ?>