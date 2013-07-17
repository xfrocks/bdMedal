<?php
class bdMedal_Listener {
	public static function load_class($class, array &$extend) {
		static $classes = array(
			'XenForo_ControllerAdmin_User',
		
			'XenForo_ControllerPublic_Member',
			'XenForo_ControllerPublic_Help',
			'XenForo_ControllerPublic_Thread',
		
			'XenForo_Model_Import',
		);
		
		if (in_array($class, $classes)) {
			$extend[] = str_replace('XenForo_', 'bdMedal_Extend_', $class);
		}
	}
	
	public static function load_class_importer($class, array &$extend) {
		if (strpos(strtolower($class), 'vbulletin') != false
			AND !defined('bdMedal_Extend_Importer_vBulletin_LOADED')) {
			$extend[] = 'bdMedal_Extend_Importer_vBulletin';
		}		
	}
	
	public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data) {
		XenForo_Template_Helper_Core::$helperCallbacks['bdmedal_image'] = array('bdMedal_Model_Medal', 'helperMedalImage');
		XenForo_Template_Helper_Core::$helperCallbacks['bdmedal_imagesize'] = array('bdMedal_Model_Medal', 'helperMedalImageSize');
		XenForo_Template_Helper_Core::$helperCallbacks['bdmedal_getoption'] = array('bdMedal_Option', 'get');
		
		// sondh@2012-10-18
		// these two helper is kept for legacy reason only
		XenForo_Template_Helper_Core::$helperCallbacks['medalimage'] = XenForo_Template_Helper_Core::$helperCallbacks['bdmedal_image'];
		XenForo_Template_Helper_Core::$helperCallbacks['medalimagesize'] = XenForo_Template_Helper_Core::$helperCallbacks['bdmedal_imagesize'];
		
		// sondh@2012-11-04
		// add rebuilder
		if ($dependencies instanceof XenForo_Dependencies_Admin) {
			XenForo_CacheRebuilder_Abstract::$builders['bdMedal_User'] = 'bdMedal_CacheRebuilder_User';
		}
	}
	
	public static function navigation_tabs(array &$extraTabs, $selectedTabId) {
		$listPage = bdMedal_Option::get('listPage');
		
		if ($listPage == 'help') {
			// no need to add navtab
		} else {
			$position = false;
			$tabId = bdMedal_Option::get('navtabId');
			
			switch ($listPage) {
				case 'navtab_home':		$position = 'home'; 	break;
				case 'navtab_middle':	$position = 'middle'; 	break;
				case 'navtab_end':		$position = 'end'; 		break;
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
	
	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template) {
		static $first = true;
		if ($first === true) {
			$template->preloadTemplate('bdmedal_message_medals');
			$first = false;
		}

		if ($templateName == 'member_view') {
			$template->preloadTemplate('bdmedal_member_view_sidebar_middle1');
			$template->preloadTemplate('bdmedal_member_view_tabs_heading');
			$template->preloadTemplate('bdmedal_member_view_tabs_content');
		}
		elseif ($templateName == 'help_wrapper') {
			$template->preloadTemplate('bdmedal_help_sidebar_links');
		}
		elseif ($templateName == 'PAGE_CONTAINER') {
			$template->preloadTemplate('bdmedal_navigation_tabs_help');
		}
		elseif ($templateName == 'tools_rebuild') {
			$template->preloadTemplate('bdmedal_tools_rebuild');
		}
	}
	
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template) {
		$positionInPost = XenForo_Application::get('options')->get('bdMedal_positionInPost');
		
		if ($positionInPost != 'manual') {
			if (strpos($positionInPost, $hookName) === 0) {
				// found the position
				$ourTemplate = $template->create('bdmedal_message_medals', $hookParams);
				$rendered = $ourTemplate->render();
				
				// output
				$positionInternal = trim(str_replace($hookName, '', $positionInPost), '_');
				switch ($positionInternal) {
					case 'top':
						$contents = $rendered . $contents;
						break;
					case 'bottom':
						$contents .= $rendered;
						break;
				}
			}
		} else {
			if ($hookName == 'bdmedal_message_medals_manual') {
				$ourTemplate = $template->create('bdmedal_message_medals', $hookParams);
				$contents .= $ourTemplate->render();
			}
		}
		
		if ($hookName == 'member_view_sidebar_middle1') {
			$ourTemplate = $template->create('bdmedal_member_view_sidebar_middle1', $template->getParams());
			$rendered = $ourTemplate->render();
			$contents .= $rendered;
		}
		elseif ($hookName == 'member_view_tabs_heading') {
			$ourTemplate = $template->create('bdmedal_member_view_tabs_heading', $template->getParams());
			$rendered = $ourTemplate->render();
			$contents .= $rendered;
		}
		elseif ($hookName == 'member_view_tabs_content') {
			$ourTemplate = $template->create('bdmedal_member_view_tabs_content', $template->getParams());
			$rendered = $ourTemplate->render();
			$contents .= $rendered;
		}
		elseif ($hookName == 'help_sidebar_links') {
			$ourTemplate = $template->create('bdmedal_help_sidebar_links', $template->getParams());
			$rendered = $ourTemplate->render();
			$search = '<!-- slot: pre_tos_url -->';
			$contents = str_replace($search, $rendered . $search, $contents);
		}
		elseif ($hookName == 'navigation_tabs_help') {
			$ourTemplate = $template->create('bdmedal_navigation_tabs_help');
			$rendered = $ourTemplate->render();
			$contents .= $rendered;
		}
	}
	
	public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template) {
		if ($templateName == 'tools_rebuild') {
			$ourTemplate = $template->create('bdmedal_tools_rebuild', $template->getParams());
			$html = $ourTemplate->render();
			
			$content .= $html;
		}
	}
	
	public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes) {
		$hashes += bdMedal_FileSums::getHashes();
	}
}