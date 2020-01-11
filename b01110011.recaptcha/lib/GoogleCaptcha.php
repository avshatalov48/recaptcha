<?php

use Bitrix\Main\Page\Asset;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;

require_once __DIR__ .'/../helper.php';

class GoogleCaptcha
{
    // ����������� ��������� �� ������
    public const ERROR_MESSAGE = '���� �������� ��� ������� ���������������. ���������� ������������� �������� � �������� ��������� �����.';

    /**
     * ���������� JS ������� ��� reCaptcha v3
     */
    public function initJS()
    {
        $Asset = Asset::getInstance();
        $siteKey = Option::get(bx_module_id(), 'site_key');
        $hideBadge = Option::get(bx_module_id(), 'hide_badge', 'Y');

        $Asset->addString('<script src="https://www.google.com/recaptcha/api.js?render='. $siteKey .'"></script>');
        $Asset->addString('<script>window.recaptcha = { siteKey: "'. $siteKey .'", tokenLifeTime: 100 };</script>'); // ����� ����� ������ � �������� (2 ������ ������������ ����� ����� ������)
        $Asset->addString('<script src="/bitrix/js/b01110011.recaptcha/script.js"></script>');
        
        if ($hideBadge == 'Y')
            $Asset->addString('<style>.grecaptcha-badge {display: none;}</style>');
    }

    /**
     * ���������� �������� �� ����
     */
    public function initCheckSpam()
    {
        $EventManager = EventManager::getInstance();

        $EventManager->addEventHandler('form', 'onBeforeResultAdd', ['GoogleCaptcha', 'checkWebForm']);
        $EventManager->addEventHandler('main', 'OnBeforeUserRegister', ['GoogleCaptcha', 'checkRegistration']);
        $EventManager->addEventHandler('iblock', 'OnBeforeIBlockElementAdd', ['GoogleCaptcha', 'checkIBlock']);
    }

    /**
     * �������� ���� �� ������ ��� ����
     */
    public function checkWebForm($WEB_FORM_ID, &$arFields, &$arValues)
    {
        $webformIDs = Option::get(bx_module_id(), 'webform_ids');
        if (empty($webformIDs)) return true;

        // ���� �� �� ������ ����������� ���� ������ ������, �� �� ��������� �����
        $webformIDs = explode(',', $webformIDs);
        if (!in_array($WEB_FORM_ID, $webformIDs, true)) return true;
        
        return self::checkSpam();
    }

    /**
     * �������� ��� ����������� ������������
     */
    public function checkRegistration(&$arArgs)
    {
        $registrationEnable = Option::get(bx_module_id(), 'registrationEnable', 'N');
        if ($registrationEnable == 'N') return true;

        return self::checkSpam();
    }

    /**
     * �������� ��� ���������� � ��������
     */
    public function checkIBlock(&$arParams)
    {
        $iblockIDs = Option::get(bx_module_id(), 'iblock_ids');
        if (empty($iblockIDs)) return true;

        // ���� �� �� ������ ����������� ���������� ������ ������, �� �� ��������� �����
        $iblockIDs = explode(',', $iblockIDs);
        if (!in_array((string) $arParams['IBLOCK_ID'], $iblockIDs, true)) return true;
        
        return self::checkSpam();
    }

    /**
     * �������� ����� �������� �����
     */
    public function checkSpam()
    {
        global $APPLICATION;

        // ���� �� ��������� ������ �� �������, �� �� ���������
        if (preg_match('/^\/bitrix\/.*$/i', $APPLICATION->GetCurPage())) return true;

        $isError = false;
        $recaptcha_token = $_REQUEST['recaptcha_token'];

        if (isset($recaptcha_token) && !empty($recaptcha_token))
        {
            $secretKey = Option::get(bx_module_id(), 'secret_key');
            $permissibleScore = (float) Option::get(bx_module_id(), 'permissible_score', 0.5);

            $recaptcha = new \ReCaptcha\ReCaptcha($secretKey, $permissibleScore);
            $response = $recaptcha->verify($recaptcha_token);

            if (!$response->isSuccess()) $isError = true;
        }
        else
        {
            $isError = true;
        }

        if ($isError)
        {
            $errorMessage = Option::get(bx_module_id(), 'error_message');
            if (empty($errorMessage)) $errorMessage = self::ERROR_MESSAGE;

            $APPLICATION->ThrowException($errorMessage);
            return false;
        }
    }
}