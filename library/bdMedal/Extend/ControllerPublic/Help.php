<?php
class bdMedal_Extend_ControllerPublic_Help extends XFCP_bdMedal_Extend_ControllerPublic_Help {
	public function actionMedals() {
		$medalModel = $this->getModelFromCache('bdMedal_Model_Medal');
		$awardedModel = $this->getModelFromCache('bdMedal_Model_Awarded');
		
		$medals = $medalModel->getAllMedal(array(), array(
			'join' => bdMedal_Model_Medal::FETCH_CATEGORY,
			'order' => 'category',
		));
		$awardeds = $awardedModel->getAllAwarded(array(), array(
			'join' => bdMedal_Model_Awarded::FETCH_USER,
		));
		
		$viewParams = array(
			'medals' => $medals,
			'awardeds' => $awardeds,
		
			'canViewAwardedUsers' => $awardedModel->canViewAwardedUsers(),
			'showAll' => $this->_input->filterSingle('show', XenForo_Input::STRING) == 'all',
		);
		
		if (bdMedal_Option::get('listPage') != 'help') {
			// we have to update the route section to highlight the medals navtab correctly
			$this->_routeMatch->setSections(bdMedal_Option::get('navtabId'), '');
		}

		return $this->_getWrapper('bdmedal_medals',
			$this->responseView('bdMedal_ViewPublic_Help_Medals', 'bdmedal_help_medals', $viewParams)
		);
	}
} 