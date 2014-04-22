<?php

class bdMedal_NewsFeedHandler_Medal extends XenForo_NewsFeedHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, array $viewingUser)
	{
		$medalModel = $model->getModelFromCache('bdMedal_Model_Medal');

		return $medalModel->getAllMedal(array('medal_id' => $contentIds));
	}

	protected function _getDefaultTemplateTitle($contentType, $action)
	{
		return 'bdmedal_' . parent::_getDefaultTemplateTitle($contentType, $action);
	}

	protected function _prepareNewsFeedItemAfterAction(array $item, $content, array $viewingUser)
	{
		$item['content']['user_id'] = $item['user_id'];
		// TODO: double check this

		return $item;
	}

}
