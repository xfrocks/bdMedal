<?php

class bdMedal_Model_Awarded extends XenForo_Model
{
	const FETCH_USER = 0x01;
	const FETCH_MEDAL = 0x02;
	const FETCH_CATEGORY = 0x04;

	public function canViewAwardedUsers($viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'bdMedal_viewAwardedUsers');
	}

	public function award(array $medal, array $users, array $bulkSet = array())
	{
		foreach ($users as $user)
		{
			$dw = XenForo_DataWriter::create('bdMedal_DataWriter_Awarded');
			$dw->set('medal_id', $medal['medal_id']);
			$dw->set('user_id', $user['user_id']);
			$dw->set('username', $user['username']);
			$dw->bulkSet($bulkSet);
			$dw->save();
		}
	}

	public function reverseAward(array $awarded)
	{
		$dw = XenForo_DataWriter::create('bdMedal_DataWriter_Awarded');
		$dw->setExistingData($awarded);
		$dw->delete();
	}

	public function rebuildUser($userId)
	{
		$conditions = array('user_id' => $userId);
		$fetchOptions = array(
			'join' => bdMedal_Model_Awarded::FETCH_MEDAL | bdMedal_Model_Awarded::FETCH_CATEGORY,
			'order' => 'display_order',
		);

		$awardeds = $this->getAllAwarded($conditions, $fetchOptions);

		$this->_db->update('xf_user', array('xf_bdmedal_awarded_cached' => serialize($awardeds)), array('user_id = ?' => $userId));
	}

	public function prepareCachedData(&$cached)
	{
		if (!is_array($cached))
		{
			$cached = @unserialize($cached);
			if (empty($cached))
			{
				$cached = array();
			}
		}

		$this->applyOrganizedOrder($cached);

		return $cached;
	}

	public function applyOrganizedOrder(array &$awardeds)
	{
		switch (bdMedal_Option::get('listOrder'))
		{
			case 'award_date':
				uasort($awardeds, create_function('$a, $b', 'return $b["award_date"] - $a["award_date"];'));
				break;
			case 'random':
				uasort($awardeds, create_function('$a, $b', 'return $b["award_date"] * $a["medal_id"] - $a["award_date"] * $b["medal_id"];'));
				break;
			case 'display_order':
			default:
				// no need to sort
				break;
		}

		$organized = array();
		$notOrganized = array();

		foreach ($awardeds as $key => $awarded)
		{
			if (!isset($awarded['adjusted_display_order']))
			{
				// old version
				$notOrganized[$key] = $awarded;
			}
			elseif ($awarded['adjusted_display_order'] < 0)
			{
				// hidden
			}
			elseif ($awarded['adjusted_display_order'] > 0)
			{
				$organized[$key] = $awarded;
			}
			else
			{
				$notOrganized[$key] = $awarded;
			}
		}

		uasort($organized, create_function('$a, $b', 'return $a["adjusted_display_order"] - $b["adjusted_display_order"];'));

		$awardeds = $organized;
		$awardeds += $notOrganized;

		return $awardeds;
	}

	public function getAwardedUsers($medalId, array $fetchOptions = array())
	{
		if (emptY($fetchOptions['join']))
		{
			$fetchOptions['join'] = 0;
		}
		$fetchOptions['join'] |= self::FETCH_USER;
		$fetchOptions['order'] = 'award_date';
		$fetchOptions['direction'] = 'desc';

		return $this->getAllAwarded(array('medal_id' => $medalId), $fetchOptions);
	}

	public function getAwardedMedals($userId, array $fetchOptions = array())
	{
		if (empty($fetchOptions['join']))
		{
			$fetchOptions['join'] = 0;
		}
		$fetchOptions['join'] |= self::FETCH_MEDAL;
		$fetchOptions['order'] = 'award_date';
		$fetchOptions['direction'] = 'desc';

		return $this->getAllAwarded(array('user_id' => $userId), $fetchOptions);
	}

	public function getAwardedUser($medalId, $userId, array $fetchOptions = array())
	{
		$awardeds = $this->getAlLAwarded(array(
			'medal_id' => $medalId,
			'user_id' => $userId
		));

		return reset($awardeds);
	}

	public function getList(array $conditions = array(), array $fetchOptions = array())
	{
		$data = $this->getAllAwarded($conditions, $fetchOptions);
		$list = array();

		foreach ($data as $id => $row)
		{
			$list[$id] = $row['username'];
		}

		return $list;
	}

	public function getAwardedById($id, array $fetchOptions = array())
	{
		$data = $this->getAllAwarded(array('awarded_id' => $id), $fetchOptions);

		return reset($data);
	}

	public function getAllAwarded(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareAwardedConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareAwardedOrderOptions($fetchOptions);
		$joinOptions = $this->prepareAwardedFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults("
				SELECT awarded.*
					$joinOptions[selectFields]
				FROM `xf_bdmedal_awarded` AS awarded
					$joinOptions[joinTables]
				WHERE $whereConditions
					$orderClause
			", $limitOptions['limit'], $limitOptions['offset']), 'awarded_id');
	}

	public function countAllAwarded(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareAwardedConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareAwardedOrderOptions($fetchOptions);
		$joinOptions = $this->prepareAwardedFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM `xf_bdmedal_awarded` AS awarded
				$joinOptions[joinTables]
			WHERE $whereConditions
		");
	}

	public function prepareAwardedConditions(array $conditions, array &$fetchOptions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		foreach (array('0' => 'awarded_id', '1' => 'medal_id', '2' => 'user_id', '3' => 'award_date') as $intField)
		{
			if (!isset($conditions[$intField]))
				continue;

			if (is_array($conditions[$intField]))
			{
				$sqlConditions[] = "awarded.$intField IN (" . $db->quote($conditions[$intField]) . ")";
			}
			else
			{
				$sqlConditions[] = "awarded.$intField = " . $db->quote($conditions[$intField]);
			}
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareAwardedFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_USER)
			{
				$selectFields .= ',
					user.*';
				$joinTables .= '
					INNER JOIN `xf_user` AS user
						ON (user.user_id = awarded.user_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_MEDAL)
			{
				$selectFields .= ',
					medal.*';
				$joinTables .= '
					INNER JOIN `xf_bdmedal_medal` AS medal
						ON (medal.medal_id = awarded.medal_id)';

				if ($fetchOptions['join'] & self::FETCH_CATEGORY)
				{
					$selectFields .= ',
						category.name AS category_name, category.description AS category_description';
					$joinTables .= '
						INNER JOIN `xf_bdmedal_category` AS category
							ON (category.category_id = medal.category_id)';
				}
			}
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables' => $joinTables
		);
	}

	public function prepareAwardedOrderOptions(array &$fetchOptions, $defaultOrderSql = 'awarded.award_date')
	{
		$choices = array(
			'award_date' => 'awarded.award_date',
			'display_order' => 'category.display_order, medal.display_order',
		);
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

}
