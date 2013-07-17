<?php
class bdMedal_ControllerAdmin_Medal extends XenForo_ControllerAdmin_Abstract {
	protected function _preDispatch($action) {
		$this->assertAdminPermission('bdMedal');
	}

	public function actionIndex() {
		$model = $this->_getMedalModel();
		$allMedal = $model->getAllMedal(array(), array(
			'join' => bdMedal_Model_Medal::FETCH_CATEGORY,
			'order' => 'category',
		));
		
		$viewParams = array(
			'allMedal' => $allMedal
		);
		
		return $this->responseView('bdMedal_ViewAdmin_Medal_List', 'bdmedal_medal_list', $viewParams);
	}
	
	public function actionAdd() {
		$viewParams = array(
			'medal' => array(),
			'allCategory' => $this->getModelFromCache('bdMedal_Model_Category')->getList(),
		);
		
		return $this->responseView('bdMedal_ViewAdmin_Medal_Edit', 'bdmedal_medal_edit', $viewParams);
	}
	
	public function actionEdit() {
		$id = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);
		$medal = $this->_getMedalOrError($id, array('join' => bdMedal_Model_Medal::FETCH_IMAGES));
		
		$viewParams = array(
			'medal' => $medal,
			'allCategory' => $this->getModelFromCache('bdMedal_Model_Category')->getList(),
		);
		
		return $this->responseView('bdMedal_ViewAdmin_Medal_Edit', 'bdmedal_medal_edit', $viewParams);
	}
	
	public function actionSave() {
		$this->_assertPostOnly();
		
		$id = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);

		$dwInput = $this->_input->filter(array('name' => 'string', 'category_id' => 'uint', 'description' => 'string', 'display_order' => 'uint', 'user_count' => 'uint', 'last_award_date' => 'uint', 'last_award_user_id' => 'uint', 'last_award_username' => 'string'));
		
		$dw = XenForo_DataWriter::create('bdMedal_DataWriter_Medal');
		if ($id) {
			$dw->setExistingData($id);
		}
		$dw->bulkSet($dwInput);
		
		$image = XenForo_Upload::getUploadedFile('image');
		if (!empty($image)) {
			$dw->setImage($image);
		}
		
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('medal-medals')
		);
	}
	
	public function actionDelete() {
		$id = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);
		$medal = $this->_getMedalOrError($id);
		
		if ($this->isConfirmedPost()) {
			$dw = XenForo_DataWriter::create('bdMedal_DataWriter_Medal');
			$dw->setExistingData($id);
			$dw->delete();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('medal-medals')
			);
		} else {
			$viewParams = array(
				'medal' => $medal
			);

			return $this->responseView('bdMedal_ViewAdmin_Medal_Delete', 'bdmedal_medal_delete', $viewParams);
		}
	}
	
	public function actionAward() {
		$medalId = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);
		$target = $this->_input->filterSingle('target', XenForo_Input::STRING);
		
		if ($medalId OR $this->isConfirmedPost()) {
			$medal = $this->_getMedalOrError($medalId);
		} else {
			$medal = array();
		}
		
		if ($this->isConfirmedPost()) {
			$userModel = $this->getModelFromCache('XenForo_Model_User');
			$awardedModel = $this->_getAwardedModel();
			
			$users = $userModel->getUsersByNames(explode(',', $target));
			if (empty($users)) {
				return $this->responseError(new XenForo_Phrase('bdmedal_no_users_to_award'));
			}
			
			$awardedModel->award($medal, $users);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('medal-medals/awarded-users', $medal)
			);
		} else {
			$allMedal = $this->_getMedalModel()->getList(array(), array(
				'join' => bdMedal_Model_Medal::FETCH_CATEGORY,
				'order' => 'category',
			));
			
			$viewParams = array(
				'medal' => $medal,
				'allMedal' => $allMedal,
				'medalId' => $medalId,
				'target' => $target,
			);

			return $this->responseView('bdMedal_ViewAdmin_Medal_Award', 'bdmedal_medal_award', $viewParams);
		}
	}
	
	public function actionReverseAward() {
		$id = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);
		$medal = $this->_getMedalOrError($id);
		
		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		$userModel = $this->getModelFromCache('XenForo_Model_User');
		$user = $userModel->getUserById($userId);
		if (empty($user)) {
			return $this->responseError(new XenForo_Phrase('requested_user_not_found'));
		}
		
		$awardedModel = $this->_getAwardedModel();
		$awarded = $this->_getAwardedModel()->getAwardedUser($medal['medal_id'], $user['user_id']);
		if (empty($awarded)) {
			return $this->responseError(new XenForo_Phrase('bdmedal_requested_award_not_found'));
		}
		
		if ($this->isConfirmedPost()) {
			$awardedModel->reverseAward($awarded);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('medal-medals/awarded-users', $medal)
			);
		} else {
			$viewParams = array(
				'medal' => $medal,
				'user' => $user,
				'awarded' => $awarded,
			);

			return $this->responseView('bdMedal_ViewAdmin_Medal_Award', 'bdmedal_medal_reverse_award', $viewParams);
		}
	}
	
	public function actionAwardedUsers() {
		$id = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);
		$medal = $this->_getMedalOrError($id);
		$users = $this->_getAwardedModel()->getAwardedUsers($medal['medal_id']);
		
		$viewParams = array(
			'medal' => $medal,
			'users' => $users,
		);
		
		return $this->responseView('bdMedal_ViewAdmin_Medal_AwardedUsers', 'bdmedal_medal_awarded_users', $viewParams);
	}
	
	protected function _getMedalOrError($id, array $fetchOptions = array()) {
		$info = $this->_getMedalModel()->getMedalById($id, $fetchOptions);
		
		if (empty($info)) {
			throw $this->responseException($this->responseError(new XenForo_Phrase('bdmedal_medal_not_found'), 404));
		}
		
		return $info;
	}
	
	protected function _getMedalModel() {
		return $this->getModelFromCache('bdMedal_Model_Medal');
	}
	
	protected function _getAwardedModel() {
		return $this->getModelFromCache('bdMedal_Model_Awarded');
	}
}