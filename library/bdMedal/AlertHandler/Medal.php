<?php
class bdMedal_AlertHandler_Medal extends XenForo_AlertHandler_Abstract {
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser) {
		$medalModel = $model->getModelFromCache('bdMedal_Model_Medal');
		
		return $medalModel->getAllMedal(array('medal_id' => $contentIds));
	}
	
	protected function _getDefaultTemplateTitle($contentType, $action) {
		return 'bdmedal_' . parent::_getDefaultTemplateTitle($contentType, $action);
	}
}