<?php

class bdMedal_CacheRebuilder_User extends XenForo_CacheRebuilder_Abstract
{
	public function getRebuildMessage() {
		return new XenForo_Phrase('users');
	}

	public function showExitLink() {
		return true;
	}

	public function rebuild($position = 0, array &$options = array(), &$detailedMessage = '') {
		$options['batch'] = isset($options['batch']) ? $options['batch'] : 75;
		$options['batch'] = max(1, $options['batch']);

		/* @var $userModel XenForo_Model_User */
		$userModel = XenForo_Model::create('XenForo_Model_User');
		
		/* @var $awardedModel bdMedal_Model_Awarded */
		$awardedModel = XenForo_Model::create('bdMedal_Model_Awarded');

		$userIds = $userModel->getUserIdsInRange($position, $options['batch']);
		if (sizeof($userIds) == 0) {
			return true;
		}

		XenForo_Db::beginTransaction();

		foreach ($userIds AS $userId) {
			$position = $userId;

			$awardedModel->rebuildUser($userId);
		}

		XenForo_Db::commit();

		$detailedMessage = XenForo_Locale::numberFormat($position);

		return $position;
	}
}