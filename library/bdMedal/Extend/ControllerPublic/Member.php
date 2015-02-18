<?php

class bdMedal_Extend_ControllerPublic_Member extends XFCP_bdMedal_Extend_ControllerPublic_Member
{
    public function actionMember()
    {
        $response = parent::actionMember();

        if ($response instanceof XenForo_ControllerResponse_View) {
            /** @var bdMedal_Model_Awarded $awardedModel */
            $awardedModel = $this->getModelFromCache('bdMedal_Model_Awarded');
            $awardedModel->prepareCachedData($response->params['user']['xf_bdmedal_awarded_cached']);
        }

        return $response;
    }

    public function actionMedals()
    {
        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        /** @var XenForo_ControllerHelper_UserProfile $userHelper */
        $userHelper = $this->getHelper('UserProfile');
        /** @var bdMedal_Model_Awarded $awardedModel */
        $awardedModel = $this->getModelFromCache('bdMedal_Model_Awarded');

        $user = $userHelper->getUserOrError($userId);

        $medals = $awardedModel->getAwardedMedals($user['user_id']);
        $awardedModel->applyOrganizedOrder($medals);

        $viewParams = array(
            'user' => $user,
            'medals' => $medals,

            'canOrganize' => XenForo_Visitor::getInstance()->hasPermission('general', 'bdMedal_organize'),
        );

        return $this->responseView('bdMedal_ViewPublic_Member_Medals', 'bdmedal_member_medals', $viewParams);
    }

}
