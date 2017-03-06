<?php

class bdMedal_Listener
{
    public static function load_class_importer($class, array &$extend)
    {
        if (strpos(strtolower($class), 'vbulletin') != false AND !defined('bdMedal_Extend_Importer_vBulletin_LOADED')) {
            $extend[] = 'bdMedal_Extend_Importer_vBulletin';
        }
    }

    public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
    {
        XenForo_Template_Helper_Core::$helperCallbacks[strtolower('bdMedal_image')] = array(
            'bdMedal_Model_Medal',
            'helperMedalImage'
        );
        XenForo_Template_Helper_Core::$helperCallbacks[strtolower('bdMedal_imageSize')] = array(
            'bdMedal_Model_Medal',
            'helperMedalImageSize'
        );
        XenForo_Template_Helper_Core::$helperCallbacks[strtolower('bdMedal_getOption')] = array(
            'bdMedal_Option',
            'get'
        );

        // sondh@2012-10-18
        // these two helper is kept for legacy reason only
        XenForo_Template_Helper_Core::$helperCallbacks[strtolower('medalImage')] = XenForo_Template_Helper_Core::$helperCallbacks['bdmedal_image'];
        XenForo_Template_Helper_Core::$helperCallbacks[strtolower('medalImageSize')] = XenForo_Template_Helper_Core::$helperCallbacks['bdmedal_imagesize'];

        if (isset($data['routesAdmin'])) {
            XenForo_CacheRebuilder_Abstract::$builders['bdMedal_User'] = 'bdMedal_CacheRebuilder_User';

            bdMedal_ShippableHelper_Updater::onInitDependencies($dependencies);
        }
    }

    public static function navigation_tabs(array &$extraTabs, $selectedTabId)
    {
        $listPage = bdMedal_Option::get('listPage');

        if ($listPage == 'help') {
            // no need to add navtab
        } else {
            $position = false;
            $tabId = bdMedal_Option::get('navtabId');

            switch ($listPage) {
                case 'navtab_home':
                    $position = 'home';
                    break;
                case 'navtab_middle':
                    $position = 'middle';
                    break;
                case 'navtab_end':
                    $position = 'end';
                    break;
            }

            if ($position !== false) {
                $extraTabs[$tabId] = array(
                    'title' => new XenForo_Phrase('bdmedal_medals'),
                    'href' => XenForo_Link::buildPublicLink('help/medals'),
                    'position' => $position,
                    'selected' => ($selectedTabId == $tabId),
                );
            }
        }
    }

    public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
    {
        $hashes += bdMedal_FileSums::getHashes();
    }

    public static function load_class_XenForo_ControllerAdmin_User($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerAdmin_User') {
            $extend[] = 'bdMedal_Extend_ControllerAdmin_User';
        }
    }

    public static function load_class_XenForo_ControllerPublic_Account($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerPublic_Account') {
            $extend[] = 'bdMedal_Extend_ControllerPublic_Account';
        }
    }

    public static function load_class_XenForo_ControllerPublic_Member($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerPublic_Member') {
            $extend[] = 'bdMedal_Extend_ControllerPublic_Member';
        }
    }

    public static function load_class_XenForo_ControllerPublic_Help($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerPublic_Help') {
            $extend[] = 'bdMedal_Extend_ControllerPublic_Help';
        }
    }

    public static function load_class_XenForo_Model_Import($class, array &$extend)
    {
        if ($class === 'XenForo_Model_Import') {
            $extend[] = 'bdMedal_Extend_Model_Import';
        }
    }
}
