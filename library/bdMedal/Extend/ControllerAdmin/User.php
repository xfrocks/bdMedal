<?php

class bdMedal_Extend_ControllerAdmin_User extends XFCP_bdMedal_Extend_ControllerAdmin_User
{
    public function actionAwardedMedals()
    {
        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
        $user = $this->_getUserOrError($userId);

        /** @var bdMedal_Model_Awarded $awardedModel */
        $awardedModel = $this->getModelFromCache('bdMedal_Model_Awarded');
        $medals = $awardedModel->getAwardedMedals($user['user_id']);

        $viewParams = array(
            'user' => $user,
            'medals' => $medals,
        );

        return $this->responseView('bdMedal_ViewAdmin_User_AwardedMedals', 'bdmedal_user_awarded_medals', $viewParams);
    }

}
