<?php
class bdMedal_Model_Medal extends XenForo_Model {
	const FETCH_CATEGORY = 0x01;
	const FETCH_IMAGES = 0x02;
	
	public function getList(array $conditions = array(), array $fetchOptions = array()) {
		$data = $this->getAllMedal($conditions, $fetchOptions);
		$list = array();
		
		foreach ($data as $id => $row) {
			$list[$id] = $row['name'];
		}
		
		return $list;
	}

	public function getMedalById($id, array $fetchOptions = array()) {
		$data = $this->getAllMedal(array ('medal_id' => $id), $fetchOptions);
		
		return reset($data);
	}
	
	public function getAllMedal(array $conditions = array(), array $fetchOptions = array()) {
		$whereConditions = $this->prepareMedalConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareMedalOrderOptions($fetchOptions);
		$joinOptions = $this->prepareMedalFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$medals = $this->fetchAllKeyed($this->limitQueryResults("
				SELECT medal.*
					$joinOptions[selectFields]
				FROM `xf_bdmedal_medal` AS medal
					$joinOptions[joinTables]
				WHERE $whereConditions
					$orderClause
			", $limitOptions['limit'], $limitOptions['offset']
		), 'medal_id');
		
		foreach ($medals as &$medal) {
			if (!empty($fetchOptions['join'])) {
				if ($fetchOptions['join'] & self::FETCH_IMAGES) {
					$medal['images'] = array();
					if (!empty($medal['image_date'])) {
						foreach (array_keys(bdMedal_DataWriter_Medal::getImageSizes()) as $size) {
							$medal['images'][$size] = self::getImageUrl($medal, $size);
						}
					}
				}
			}
		}
		
		return $medals;
	}
		
	public function countAllMedal(array $conditions = array(), array $fetchOptions = array()) {
		$whereConditions = $this->prepareMedalConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareMedalOrderOptions($fetchOptions);
		$joinOptions = $this->prepareMedalFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM `xf_bdmedal_medal` AS medal
				$joinOptions[joinTables]
			WHERE $whereConditions
		");
	}
	
	public function prepareMedalConditions(array $conditions, array &$fetchOptions) {
		$sqlConditions = array();
		$db = $this->_getDb();
		
		foreach (array('0' => 'medal_id', '1' => 'category_id', '2' => 'display_order', '3' => 'user_count', '4' => 'last_award_date', '5' => 'last_award_user_id') as $intField) {
			if (!isset($conditions[$intField])) continue;
			
			if (is_array($conditions[$intField])) {
				$sqlConditions[] = "medal.$intField IN (" . $db->quote($conditions[$intField]) . ")";
			} else {
				$sqlConditions[] = "medal.$intField = " . $db->quote($conditions[$intField]);
			}
		}
		
		return $this->getConditionsForClause($sqlConditions);
	}
	
	public function prepareMedalFetchOptions(array $fetchOptions) {
		$selectFields = '';
		$joinTables = '';

		if (!empty($fetchOptions['join'])) {
			if ($fetchOptions['join'] & self::FETCH_CATEGORY) {
				$selectFields .= '
					, category.name AS category_name, category.description AS category_description
				';
				$joinTables .= '
					INNER JOIN `xf_bdmedal_category` AS category
						ON (category.category_id = medal.category_id)
				';
			}
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}
	
	public function prepareMedalOrderOptions(array &$fetchOptions) {
		$choices = array(
			'category' => 'category.display_order',
		);
		$orderSql = $this->getOrderByClause($choices, $fetchOptions);
		
		if (!empty($fetchOptions['order']) AND $fetchOptions['order'] == 'category') {
			if (empty($fetchOptions['join'])) $fetchOptions['join'] = 0;
			
			$fetchOptions['join'] |= self::FETCH_CATEGORY;
		}
		
		return (empty($orderSql) ? 'ORDER BY ' : $orderSql . ',') . 'medal.display_order ASC';
	}
	
	public static function getImageFilePath(array $medal, $size = 'l') {
		$internal = self::_getImageInternal($medal, $size);
		
		if (!empty($internal)) {
			return XenForo_Helper_File::getExternalDataPath() . $internal;
		} else {
			return '';
		}
	}
	
	public static function getImageUrl(array $medal, $size = 'l') {
		$internal = self::_getImageInternal($medal, $size);
		
		if (!empty($internal)) {
			return XenForo_Application::$externalDataPath . $internal;
		} else {
			return '';
		}
	}
	
	public static function helperMedalImage($medal, $size = 's') {
		$url = self::getImageUrl($medal, $size);
		
		if (!empty($url)) {
			return "<img src=\"$url\" />";
		} else {
			return '';
		}
	}
	
	public static function helperMedalImageSize($size) {
		$imageSizes = bdMedal_DataWriter_Medal::getImageSizes();
		$size = strtolower($size);

		if (isset($imageSizes[$size])) {
			return $imageSizes[$size];
		} else {
			return 0;
		}
	}
	
	protected static function _getImageInternal(array $medal, $size) {
		if (empty($medal['medal_id']) OR empty($medal['image_date'])) return '';
		$size = strtolower($size);
		// we should check size with bdMedal_DataWriter_Medal::getImageSizes() but that's
		// too strictly I guess...

		return "/medal/{$medal['medal_id']}_{$medal['image_date']}{$size}.jpg";
	}
}