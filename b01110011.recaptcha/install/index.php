<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\EventManager;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

require_once __DIR__ .'/../helper.php';

class b01110011_recaptcha extends CModule
{
    protected $LOC_PREFIX;
    protected $FILE_PREFIX;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ .'/version.php';

        $this->LOC_PREFIX = bx_loc_prefix();
        $this->FILE_PREFIX = bx_file_prefix();

        $this->MODULE_ID = bx_module_id();
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage($this->LOC_PREFIX .'MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage($this->LOC_PREFIX .'MODULE_DESC');

        $this->PARTNER_NAME = Loc::getMessage($this->LOC_PREFIX .'PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage($this->LOC_PREFIX .'PARTNER_URI');
    }

    public function DoInstall()
    {
        global $APPLICATION;

        if ($this->isVersionD7())
        {
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();

            ModuleManager::registerModule($this->MODULE_ID);
        }
        else
        {
            $APPLICATION->ThrowException(Loc::getMessage($this->LOC_PREFIX .'INSTALL_ERROR_VERSION'));
        }

        $APPLICATION->IncludeAdminFile(Loc::getMessage($this->LOC_PREFIX .'INSTALL_TITLE'), $this->GetPath() .'/install/step.php');
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $request = Application::getInstance()->getContext()->getRequest();

        switch ($request['step'])
        {
            case null:
            case 1:

                $APPLICATION->IncludeAdminFile(Loc::getMessage($this->LOC_PREFIX .'UNINSTALL_TITLE'), $this->GetPath() .'/install/unstep.php');
            
            break;
            case 2:

                $this->UnInstallFiles();
                $this->UnInstallEvents();
        
                if ($request['savedata'] != 'Y')
                    $this->UnInstallDB();
        
                ModuleManager::unRegisterModule($this->MODULE_ID);

                $APPLICATION->IncludeAdminFile(Loc::getMessage($this->LOC_PREFIX .'UNINSTALL_TITLE'), $this->GetPath() .'/install/unstep2.php');
            
            break;
        }
    }

    /**
     * ��������� ������ ����
     */
    public function isVersionD7()
    {
        return CheckVersion(ModuleManager::getVersion('main'), '14.00.00');
    }

    /**
     * �������� ���� �� ����� ������
     */
    public function GetPath($withoutDocumentRoot = false)
    {
        if ($withoutDocumentRoot)
        {
            return str_ireplace(Application::getDocumentRoot(), '', dirname(__DIR__));
        }
        else
        {
            return dirname(__DIR__);
        }
    }

    /**
     * ������������� ������� ���� ������
     */
    public function InstallDB()
    {

    }

    /**
     * ������� ������������� �������
     */
    public function UnInstallDB()
    {
        Option::delete($this->MODULE_ID); // ������� ��������� ������
    }

    /**
     * ��������� �������
     */
    public function InstallEvents()
    {
        $EventManager = EventManager::getInstance();

        // �������� �� ����
        $EventManager->registerEventHandler('main', 'OnBeforeProlog', $this->MODULE_ID, 'GoogleCaptcha', 'initCheckSpam');

        // ������������� js
        $EventManager->registerEventHandler('main', 'OnProlog', $this->MODULE_ID, 'GoogleCaptcha', 'initJS');
    }
    
    /**
     * ������� ����������� �������
     */
    public function UnInstallEvents()
    {
        $EventManager = EventManager::getInstance();

        // �������� �� ����
        $EventManager->unRegisterEventHandler('main', 'OnBeforeProlog', $this->MODULE_ID, 'GoogleCaptcha', 'initCheckSpam');

        // ������������� js
        $EventManager->unRegisterEventHandler('main', 'OnProlog', $this->MODULE_ID, 'GoogleCaptcha', 'initJS');
    }

    /**
     * �������� ������ ����� � �������
     */
    public function InstallFiles()
    {
        // �������� ����������
        if (Directory::isDirectoryExists($path = $this->GetPath() .'/install/components'))
        {
            CopyDirFiles($path, Application::getDocumentRoot() .'/bitrix/components', true, true);
        }

        // �������� �������
        if (Directory::isDirectoryExists($path = $this->GetPath() .'/install/assets/js'))
        {
            CopyDirFiles($path, Application::getDocumentRoot() .'/bitrix/js/'. $this->MODULE_ID, true, true);
        }

        // �������� �����
        if (Directory::isDirectoryExists($path = $this->GetPath() .'/install/assets/css'))
        {
            CopyDirFiles($path, Application::getDocumentRoot() .'/bitrix/css/'. $this->MODULE_ID, true, true);
        }

        // �������� ��������� �����
        if (Directory::isDirectoryExists($path = $this->GetPath() .'/install/admin'))
        {
            if ($dir = opendir($path))
            {
                $exclusionFiles = ['.', '..'];

                while ($item = readdir($dir) !== false)
                {
                    if (in_array($item, $exclusionFiles)) continue;

                    copy($path .'/'. $item, $dest = Application::getDocumentRoot() .'/bitrix/admin/'. $this->FILE_PREFIX . $item);

                    // ��� ������ ���� ������ � ������ install/admin
                    if (file_exists($dest))
                    {
                        $content = file_get_contents($dest);
                        $content = str_replace('%%MODULE_ID%%', $this->MODULE_ID, $content);
                        file_put_contents($dest, $content);
                    }
                }

                closedir($dir);
            }
        }
    }

    /**
     * ������� �����
     */
    public function UnInstallFiles()
    {
        // ������� ����������
        if (Directory::isDirectoryExists($path = $this->GetPath() .'/install/components'))
        {
            if ($dir = opendir($path))
            {
                $exclusionFiles = ['.', '..'];

                while ($item = readdir($dir) !== false)
                {
                    if (in_array($item, $exclusionFiles)) continue;
                    if (!is_dir($path .'/'. $item)) continue;

                    Directory::deleteDirectory(Application::getDocumentRoot() .'/bitrix/components/'. $item);
                }

                closedir($dir);
            }
        }

        // ������� �������
        if (Directory::isDirectoryExists($path = $this->GetPath() .'/install/assets/js'))
        {
            Directory::deleteDirectory(Application::getDocumentRoot() .'/bitrix/js/'. $this->MODULE_ID);
        }

        // ������� �����
        if (Directory::isDirectoryExists($path = $this->GetPath() .'/install/assets/css'))
        {
            Directory::deleteDirectory(Application::getDocumentRoot() .'/bitrix/css/'. $this->MODULE_ID);
        }

        // ������� ��������� �����
        if (Directory::isDirectoryExists($path = $this->GetPath() .'/install/admin'))
        {
            if ($dir = opendir($path))
            {
                $exclusionFiles = ['.', '..'];

                while ($item = readdir($dir) !== false)
                {
                    if (in_array($item, $exclusionFiles)) continue;

                    File::deleteFile(Application::getDocumentRoot() .'/bitrix/admin/'. $this->FILE_PREFIX . $item);
                }

                closedir($dir);
            }
        }
    }
}