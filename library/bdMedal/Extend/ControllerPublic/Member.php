<?php
class bdMedal_Extend_ControllerPublic_Member extends XFCP_bdMedal_Extend_ControllerPublic_Member {
	public function actionMember() {
		$response = parent::actionMember();
		
		if ($response instanceof XenForo_ControllerResponse_View) {
			$awardedModel = $this->getModelFromCache('bdMedal_Model_Awarded');
			$awardedModel->prepareCachedData($response->params['user']['xf_bdmedal_awarded_cached']);
		}
		
		return $response;
	}
	
	public function actionMedals() {
		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		$user = $this->getHelper('UserProfile')->getUserOrError($userId);
		$medals = $this->getModelFromCache('bdMedal_Model_Awarded')->getAwardedMedals($user['user_id']);
		
		$viewParams = array(
			'user' => $user,
			'medals' => $medals,
		);
		
		return $this->responseView('bdMedal_ViewPublic_Member_Medals', 'bdmedal_member_medals', $viewParams);
	}
} 