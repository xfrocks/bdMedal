<?php

class bdMedal_DataWriter_Awarded extends XenForo_DataWriter
{
	const OPTION_DISABLE_REBUILD_MEDAL = 'disableRebuildMedal';

	protected function _postSave()
	{
		if ($this->isInsert())
		{
			$this->_alertUser();
			$this->_publishToNewsFeed();
		}

		$this->_rebuildMedal();
		$this->_rebuildUser();
	}

	protected function _postDelete()
	{
		$this->_rebuildMedal();
		$this->_rebuildUser();
	}

	protected function _alertUser()
	{
		$visitor = XenForo_Visitor::getInstance();

		if ($visitor->get('user_id') != $this->get('user_id'))
		{
			$userId = $visitor->get('user_id');
			$username = $visitor->get('username');

			if ($userId == 0)
			{
				$userId = $this->get('user_id');
				$username = $this->get('username');
			}

			XenForo_Model_Alert::alert($this->get('user_id'), $userId, $username, 'medal', $this->get('medal_id'), 'award');
		}
	}

	protected function _publishToNewsFeed()
	{
		$this->getModelFromCache('XenForo_Model_NewsFeed')->publish($this->get('user_id'), $this->get('username'), 'medal', $this->get('medal_id'), 'awarded');
	}

	protected function _rebuildMedal($lastAwarded = null)
	{
		$disableRebuildMedal = $this->getExtraData(self::OPTION_DISABLE_REBUILD_MEDAL);

		if (empty($disableRebuildMedal))
		{
			$awardedModel = $this->_getAwardedModel();
			if (empty($lastAwarded))
			{
				$awardeds = $awardedModel->getAllAwarded(array('medal_id' => $this->get('medal_id')), array(
					'order' => 'award_date',
					'direction' => 'desc',
					'limit' => 1
				));
				$lastAwarded = reset($awardeds);
			}
			$count = $awardedModel->countAllAwarded(array('medal_id' => $this->get('medal_id')));

			$dw = XenForo_DataWriter::create('bdMedal_DataWriter_Medal');
			$dw->setExistingData($this->get('medal_id'));
			$dw->set('user_count', $count);
			$dw->set('last_award_date', $lastAwarded['award_date']);
			$dw->set('last_award_user_id', $lastAwarded['user_id']);
			$dw->set('last_award_username', $lastAwarded['username']);
			$dw->save();
		}
	}

	protected function _rebuildUser()
	{
		$this->_getAwardedModel()->rebuildUser($this->get('user_id'));
	}

	protected function _getFields()
	{
		return array('xf_bdmedal_awarded' => array(
				'awarded_id' => array(
					'type' => XenForo_DataWriter::TYPE_UINT,
					'autoIncrement' => true
				),
				'medal_id' => array(
					'type' => XenForo_DataWriter::TYPE_UINT,
					'required' => true
				),
				'user_id' => array(
					'type' => XenForo_DataWriter::TYPE_UINT,
					'required' => true
				),
				'username' => array(
					'type' => XenForo_DataWriter::TYPE_STRING,
					'length' => 50,
					'required' => true
				),
				'award_date' => array(
					'type' => XenForo_DataWriter::TYPE_UINT,
					'default' => XenForo_Application::$time
				),
				'award_reason' => array('type' => XenForo_DataWriter::TYPE_STRING),
				'adjusted_display_order' => array(
					'type' => XenForo_DataWriter::TYPE_UINT,
					'default' => 0
				),
			));
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'awarded_id'))
		{
			return false;
		}

		return array('xf_bdmedal_awarded' => $this->_getAwardedModel()->getAwardedById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		$conditions = array();

		foreach (array('0' => 'awarded_id') as $field)
		{
			$conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
		}

		return implode(' AND ', $conditions);
	}

	protected function _getAwardedModel()
	{
		return $this->getModelFromCache('bdMedal_Model_Awarded');
	}

}
