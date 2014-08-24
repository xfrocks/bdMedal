<?php

class bdMedal_Extend_ControllerPublic_Help extends XFCP_bdMedal_Extend_ControllerPublic_Help
{
	public function actionMedals()
	{
		$medalModel = $this->getModelFromCache('bdMedal_Model_Medal');
		$awardedModel = $this->getModelFromCache('bdMedal_Model_Awarded');

		$medals = $medalModel->getAllMedal(array(), array(
			'join' => bdMedal_Model_Medal::FETCH_CATEGORY,
			'order' => 'category',
		));

		$awardedConditions = array();
		$awardedFetchOptions = array('join' => bdMedal_Model_Awarded::FETCH_USER);
		$awardedCount = $awardedModel->countAllAwarded($awardedConditions, $awardedFetchOptions);
		$awardedUsersMax = max(50, bdMedal_Option::get('awardedUsersMax'));
		if ($awardedCount < $awardedUsersMax * count($medals))
		{
			$awardeds = $awardedModel->getAllAwarded($awardedConditions, $awardedFetchOptions);
		}
		else
		{
			$awardeds = array();

			foreach ($medals as $medal)
			{
				$awardeds = array_merge($awardeds, $awardedModel->getAllAwarded(array_merge($awardedConditions, array('medal_id' => $medal['medal_id'])), $awardedFetchOptions));
			}
		}

		$viewParams = array(
			'medals' => $medals,
			'awardeds' => $awardeds,

			'canViewAwardedUsers' => $awardedModel->canViewAwardedUsers(),
			'showAll' => $this->_input->filterSingle('show', XenForo_Input::STRING) == 'all',
		);

		if (bdMedal_Option::get('listPage') != 'help')
		{
			// we have to update the route section to highlight the medals navtab correctly
			$this->_routeMatch->setSections(bdMedal_Option::get('navtabId'), '');
		}

		return $this->_getWrapper('bdmedal_medals', $this->responseView('bdMedal_ViewPublic_Help_Medals', 'bdmedal_help_medals', $viewParams));
	}

}
