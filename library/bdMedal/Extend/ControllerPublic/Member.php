<?php

class bdMedal_Extend_ControllerPublic_Member extends XFCP_bdMedal_Extend_ControllerPublic_Member
{
    public function actionMember()
    {
        $response = parent::actionMember();

        if ($response instanceof XenForo_ControllerResponse_View
            && !empty($response->params['user'])
        ) {
            /** @var bdMedal_Model_Awarded $awardedModel */
            $awardedModel = $this->getModelFromCache('bdMedal_Model_Awarded');
            $response->params['bdMedal_canAwardUser'] = $awardedModel->canAwardUser($response->params['user']);
        }

        return $response;
    }

    public function actionAwardMedal()
    {
        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        /** @var XenForo_ControllerHelper_UserProfile $userHelper */
        $userHelper = $this->getHelper('UserProfile');
        /** @var bdMedal_Model_Medal $medalModel */
        $medalModel = $this->getModelFromCache('bdMedal_Model_Medal');
        /** @var bdMedal_Model_Awarded $awardedModel */
        $awardedModel = $this->getModelFromCache('bdMedal_Model_Awarded');

        $user = $userHelper->getUserOrError($userId);

        if (!$awardedModel->canAwardUser($user)) {
            return $this->responseNoPermission();
        }

        $medals = $medalModel->getAllMedal(array(), array(
            'join' => bdMedal_Model_Medal::FETCH_CATEGORY,
            'order' => 'category',
        ));

        $awardedMedals = $awardedModel->getAwardedMedals($user['user_id']);

        if ($this->isConfirmedPost()) {
            $medalId = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);

            // escape reason to avoid html injection
            $reason = $this->_input->filterSingle('reason', XenForo_Input::STRING);
            $reason = htmlentities($reason);

            if (!isset($medals[$medalId])) {
                return $this->responseError(new XenForo_Phrase('bdmedal_medal_not_found'), 404);
            }
            $medal = $medals[$medalId];

            foreach ($awardedMedals as $awardedMedal) {
                if ($awardedMedal['medal_id'] == $medal['medal_id']) {
                    return $this->responseError(new XenForo_Phrase('bdmedal_x_have_been_awarded_medal_y_already', array(
                        'name' => $user['username'],
                        'medal' => $medal['name'],
                    )), 400);
                }
            }

            $awardedModel->award($medal, array($user), array('award_reason' => $reason));

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
                XenForo_Link::buildPublicLink('members/medals', $user)
            );
        }

        $viewParams = array(
            'user' => $user,
            'medals' => $medals,
            'awardedMedals' => $awardedMedals,
        );

        return $this->responseView('bdMedal_ViewPublic_Member_AwardMedal', 'bdmedal_member_award_medal', $viewParams);
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
            'canAwardUser' => $awardedModel->canAwardUser($user),
        );

        return $this->responseView('bdMedal_ViewPublic_Member_Medals', 'bdmedal_member_medals', $viewParams);
    }

    public function actionMedalsAwarded()
    {
        $medalId = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);

        /** @var bdMedal_Model_Medal $medalModel */
        $medalModel = $this->getModelFromCache('bdMedal_Model_Medal');
        /** @var bdMedal_Model_Awarded $awardedModel */
        $awardedModel = $this->getModelFromCache('bdMedal_Model_Awarded');

        if (!$awardedModel->canViewAwardedUsers()) {
            return $this->responseNoPermission();
        }

        $medal = $medalModel->getMedalById($medalId);
        if (empty($medal)) {
            return $this->responseError(new XenForo_Phrase('bdmedal_medal_not_found'), 404);
        }

        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);
        $usersPerPage = XenForo_Application::get('options')->membersPerPage;

        $awardedConditions = array(
            'medal_id' => $medal['medal_id'],
        );
        $awardedFetchOptions = array(
            'join' => bdMedal_Model_Awarded::FETCH_USER,
            'perPage' => $usersPerPage,
            'page' => $page,
        );

        $totalUsers = $awardedModel->countAllAwarded($awardedConditions, $awardedFetchOptions);
        $this->canonicalizePageNumber($page, $usersPerPage, $totalUsers, 'members/medals/awarded');

        $users = $awardedModel->getAllAwarded($awardedConditions, $awardedFetchOptions);

        $viewParams = array(
            'medal' => $medal,
            'users' => $users,

            'totalUsers' => $totalUsers,
            'page' => $page,
            'usersPerPage' => $usersPerPage,
        );

        return $this->responseView('bdMedal_ViewPublic_Member_Medals_Awarded', 'bdmedal_member_medals_awarded', $viewParams);
    }

}
