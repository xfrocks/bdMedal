<?php

class bdMedal_Extend_ControllerPublic_Help extends XFCP_bdMedal_Extend_ControllerPublic_Help
{
    public function actionMedals()
    {
        /** @var bdMedal_Model_Medal $medalModel */
        $medalModel = $this->getModelFromCache('bdMedal_Model_Medal');
        /** @var bdMedal_Model_Awarded $awardedModel */
        $awardedModel = $this->getModelFromCache('bdMedal_Model_Awarded');

        $medals = $medalModel->getAllMedal(array(), array(
            'join' => bdMedal_Model_Medal::FETCH_CATEGORY,
            'order' => 'category',
        ));

        $awardeds = array();
        $awardedConditions = array();
        $awardedFetchOptions = array('join' => bdMedal_Model_Awarded::FETCH_USER);
        $awardedCount = $awardedModel->countAllAwarded($awardedConditions, $awardedFetchOptions);
        $awardedUsersMax = max(50, bdMedal_Option::get('awardedUsersMax'));
        if ($awardedCount < $awardedUsersMax * count($medals)) {
            $awardeds = $awardedModel->getAllAwarded($awardedConditions, $awardedFetchOptions);
        } else {
            $noAwardeds = true;
        }

        $viewParams = array(
            'medals' => $medals,
            'awardeds' => $awardeds,
            'noAwardeds' => !empty($noAwardeds),

            'canViewAwardedUsers' => $awardedModel->canViewAwardedUsers(),
            'showAll' => $this->_input->filterSingle('show', XenForo_Input::STRING) == 'all',
        );

        if (bdMedal_Option::get('listPage') != 'help') {
            // we have to update the route section to highlight the medals navtab correctly
            $this->_routeMatch->setSections(bdMedal_Option::get('navtabId'), '');
        }

        return $this->_getWrapper('bdmedal_medals',
            $this->responseView('bdMedal_ViewPublic_Help_Medals', 'bdmedal_help_medals', $viewParams));
    }

}
