<?php
class bdMedal_Installer {
	/* Start auto-generated lines of code. Change made will be overwriten... */

	protected static $_tables = array(
		'category' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_bdmedal_category` (
				`category_id` INT(10) UNSIGNED AUTO_INCREMENT
				,`name` VARCHAR(255) NOT NULL
				,`description` TEXT
				,`display_order` INT(10) UNSIGNED DEFAULT \'0\'
				, PRIMARY KEY (`category_id`)
				
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdmedal_category`'
		),
		'medal' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_bdmedal_medal` (
				`medal_id` INT(10) UNSIGNED AUTO_INCREMENT
				,`name` VARCHAR(255) NOT NULL
				,`category_id` INT(10) UNSIGNED NOT NULL
				,`description` TEXT
				,`display_order` INT(10) UNSIGNED DEFAULT \'0\'
				,`user_count` INT(10) UNSIGNED DEFAULT \'0\'
				,`last_award_date` INT(10) UNSIGNED DEFAULT \'0\'
				,`last_award_user_id` INT(10) UNSIGNED DEFAULT \'0\'
				,`last_award_username` VARCHAR(50) DEFAULT \'\'
				,`image_date` INT(10) UNSIGNED DEFAULT \'0\'
				, PRIMARY KEY (`medal_id`)
				, INDEX `category_id` (`category_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdmedal_medal`'
		),
		'awarded' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_bdmedal_awarded` (
				`awarded_id` INT(10) UNSIGNED AUTO_INCREMENT
				,`medal_id` INT(10) UNSIGNED NOT NULL
				,`user_id` INT(10) UNSIGNED NOT NULL
				,`username` VARCHAR(50) NOT NULL
				,`award_date` INT(10) UNSIGNED NOT NULL
				, PRIMARY KEY (`awarded_id`)
				,UNIQUE INDEX `medal_id_user_id` (`medal_id`,`user_id`)
				, INDEX `user_id` (`user_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdmedal_awarded`'
		)
	);
	protected static $_patches = array(
		array(
			'table' => 'xf_user',
			'field' => 'xf_bdmedal_awarded_cached',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_user` LIKE \'xf_bdmedal_awarded_cached\'',
			'alterTableAddColumnQuery' => 'ALTER TABLE `xf_user` ADD COLUMN `xf_bdmedal_awarded_cached` MEDIUMBLOB',
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_user` DROP COLUMN `xf_bdmedal_awarded_cached`'
		)
	);

	public static function install() {
		$db = XenForo_Application::get('db');

		foreach (self::$_tables as $table) {
			$db->query($table['createQuery']);
		}
		
		foreach (self::$_patches as $patch) {
			$existed = $db->fetchOne($patch['showColumnsQuery']);
			if (empty($existed)) {
				$db->query($patch['alterTableAddColumnQuery']);
			}
		}
		
		self::installCustomized();
	}
	
	public static function uninstall() {
		$db = XenForo_Application::get('db');
		
		foreach (self::$_patches as $patch) {
			$existed = $db->fetchOne($patch['showColumnsQuery']);
			if (!empty($existed)) {
				$db->query($patch['alterTableDropColumnQuery']);
			}
		}
		
		foreach (self::$_tables as $table) {
			$db->query($table['dropQuery']);
		}
		
		self::uninstallCustomized();
	}

	/* End auto-generated lines of code. Feel free to make changes below */
	
	private static function installCustomized() {
		$db = XenForo_Application::getDb();
		
		self::_installDemoData($db);
		
		// since 1.3
		$db->query("REPLACE INTO `xf_content_type` (content_type, addon_id, fields) VALUES ('medal', 'bdMedal', '')");
		$db->query("REPLACE INTO `xf_content_type_field` (content_type, field_name, field_value) VALUES ('medal', 'alert_handler_class', 'bdMedal_AlertHandler_Medal')");
		$db->query("REPLACE INTO `xf_content_type_field` (content_type, field_name, field_value) VALUES ('medal', 'news_feed_handler_class', 'bdMedal_NewsFeedHandler_Medal')");
		XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
	}
	
	private static function uninstallCustomized() {
		$db = XenForo_Application::getDb();
		
		$db->query("DELETE FROM `xf_content_type` WHERE addon_id = ?", array('bdMedal'));
		$db->query("DELETE FROM `xf_content_type_field` WHERE content_type = ?", array('medal'));
		XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
	}
	
	protected static function _installDemoData($db) {
		$existed = $db->fetchOne('SELECT COUNT(*) FROM `xf_bdmedal_category`');
		$existed2 = $db->fetchOne('SELECT COUNT(*) FROM `xf_bdmedal_medal`');
		
		if (empty($existed) AND empty($existed2)) {
			$categories = array(
				'only' => array(
					'name' => 'Category 1',
					'description' => 'HTML description goes here!'
				),
			);
			$medals = array(
				array(
					'name' => 'Medal 1',
					'category_id' => 1,
					'demo_image' => 'demo1.png',
				),
				array(
					'name' => 'Medal 2 (but have high display order)',
					'category_id' => 1,
					'demo_image' => 'demo2.png',
					'display_order' => '10',
				),
				array(
					'name' => 'Medal 3',
					'category_id' => 1,
					'demo_image' => 'demo3.png',
				),
			);
			
			foreach ($categories as &$category) {
				$categoryDw = XenForo_DataWriter::create('bdMedal_DataWriter_Category');
				$categoryDw->bulkSet($category);
				$categoryDw->save();
				
				$category = $categoryDw->getMergedData();
			}
			
			foreach ($medals as $medal) {
				$srcImagePath = dirname(__FILE__) . '/_demo/' . $medal['demo_image'];
				$imagePath = XenForo_Helper_File::getInternalDataPath() . '/' . XenForo_Application::$time . $medal['demo_image'];
				copy($srcImagePath, $imagePath);
				$image =  new XenForo_Upload($medal['demo_image'], $imagePath);
				
				$medalDw = XenForo_DataWriter::create('bdMedal_DataWriter_Medal');
				$medalDw->bulkSet($medal, array('ignoreInvalidFields' => true));
				$medalDw->set('category_id', $categories['only']['category_id']);
				$medalDw->setImage($image);
				$medalDw->save();
			}
		}
	}
	
}