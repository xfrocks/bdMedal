<?php

class bdMedal_ControllerAdmin_Medal extends XenForo_ControllerAdmin_Abstract
{
    protected function _preDispatch($action)
    {
        $this->assertAdminPermission('bdMedal');
    }

    public function actionIndex()
    {
        $model = $this->_getMedalModel();
        $allMedal = $model->getAllMedal(array(), array(
            'join' => bdMedal_Model_Medal::FETCH_CATEGORY,
            'order' => 'category',
        ));

        $viewParams = array('allMedal' => $allMedal);

        return $this->responseView('bdMedal_ViewAdmin_Medal_List', 'bdmedal_medal_list', $viewParams);
    }

    public function actionAdd()
    {
        $viewParams = array(
            'medal' => array(),
            'allCategory' => $this->_getCategoryModel()->getList(),
        );

        return $this->responseView('bdMedal_ViewAdmin_Medal_Edit', 'bdmedal_medal_edit', $viewParams);
    }

    public function actionEdit()
    {
        $medalId = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);
        $medal = $this->_getMedalHelper()->getMedalOrError($medalId,
            array('join' => bdMedal_Model_Medal::FETCH_IMAGES));

        $viewParams = array(
            'medal' => $medal,
            'allCategory' => $this->_getCategoryModel()->getList(),
        );

        return $this->responseView('bdMedal_ViewAdmin_Medal_Edit', 'bdmedal_medal_edit', $viewParams);
    }

    public function actionSave()
    {
        $this->_assertPostOnly();

        $id = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);

        $dwInput = $this->_input->filter(array(
            'name' => 'string',
            'category_id' => 'uint',
            'description' => 'string',
            'display_order' => 'uint',
        ));

        /** @var bdMedal_DataWriter_Medal $dw */
        $dw = XenForo_DataWriter::create('bdMedal_DataWriter_Medal');
        if ($id) {
            $dw->setExistingData($id);
        }
        $dw->bulkSet($dwInput);

        $imageM = XenForo_Upload::getUploadedFile('image_m');
        if (!empty($imageM)) {
            $dw->setImage($imageM, 'm');
        }

        $imageS = XenForo_Upload::getUploadedFile('image_s');
        if (!empty($imageS)) {
            $dw->setImage($imageS, 's');
        }

        $image = XenForo_Upload::getUploadedFile('image');
        if (!empty($image)) {
            $dw->setImage($image);
        }

        $dw->save();

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('medal-medals'));
    }

    public function actionDelete()
    {
        $medalId = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);
        $medal = $this->_getMedalHelper()->getMedalOrError($medalId);

        if ($this->isConfirmedPost()) {
            $dw = XenForo_DataWriter::create('bdMedal_DataWriter_Medal');
            $dw->setExistingData($medal, true);
            $dw->delete();

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('medal-medals'));
        } else {
            $viewParams = array('medal' => $medal);

            return $this->responseView('bdMedal_ViewAdmin_Medal_Delete', 'bdmedal_medal_delete', $viewParams);
        }
    }

    public function actionAward()
    {
        $medalId = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);
        $target = $this->_input->filterSingle('target', XenForo_Input::STRING);
        $reason = $this->_input->filterSingle('reason', XenForo_Input::STRING);

        if ($medalId OR $this->isConfirmedPost()) {
            $medal = $this->_getMedalHelper()->getMedalOrError($medalId);
        } else {
            $medal = array();
        }

        if ($this->isConfirmedPost()) {
            /** @var XenForo_Model_User $userModel */
            $userModel = $this->getModelFromCache('XenForo_Model_User');
            $awardedModel = $this->_getAwardedModel();

            $users = $userModel->getUsersByNames(explode(',', $target));
            if (empty($users)) {
                return $this->responseError(new XenForo_Phrase('bdmedal_no_users_to_award'));
            }

            $avoidDuplicated = $this->_input->filterSingle('avoid_duplicated', XenForo_Input::UINT);
            if ($avoidDuplicated) {
                $allAwarded = $awardedModel->getAllAwarded(array(
                    'medal_id' => $medal['medal_id'],
                    'user_id' => array_keys($users)
                ));

                foreach ($allAwarded as $awarded) {
                    unset($users[$awarded['user_id']]);
                }
            }

            if (!empty($users)) {
                $awardedModel->award($medal, $users, array('award_reason' => $reason));
            }

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('medal-medals/awarded-users', $medal));
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
                'reason' => $reason,
            );

            return $this->responseView('bdMedal_ViewAdmin_Medal_Award', 'bdmedal_medal_award', $viewParams);
        }
    }

    public function actionEditAward()
    {
        $medalId = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);
        $awardedId = $this->_input->filterSingle('awarded_id', XenForo_Input::UINT);
        $awardReason = $this->_input->filterSingle('award_reason', XenForo_Input::STRING);
        list($medal, $awarded) = $this->_getMedalHelper()->getAwardedMedalOrError($medalId, $awardedId);

        if ($this->isConfirmedPost()) {
            $dw = XenForo_DataWriter::create('bdMedal_DataWriter_Awarded');
            $dw->setExistingData($awarded, true);
            $dw->set('award_reason', $awardReason);
            $dw->save();

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('medal-medals/awarded-users', $awarded));
        } else {
            $viewParams = array(
                'medal' => $medal,
                'user' => $awarded,
                'awarded' => $awarded,
            );

            return $this->responseView('bdMedal_ViewAdmin_Medal_EditAward', 'bdmedal_medal_edit_award', $viewParams);
        }
    }

    public function actionReverseAward()
    {
        $medalId = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);
        $awardedId = $this->_input->filterSingle('awarded_id', XenForo_Input::UINT);
        list($medal, $awarded) = $this->_getMedalHelper()->getAwardedMedalOrError($medalId, $awardedId);

        if ($this->isConfirmedPost()) {
            $this->_getAwardedModel()->reverseAward($awarded);

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('medal-medals/awarded-users', $awarded));
        } else {
            $viewParams = array(
                'medal' => $medal,
                'user' => $awarded,
                'awarded' => $awarded,
            );

            return $this->responseView(
                'bdMedal_ViewAdmin_Medal_ReverseAward',
                'bdmedal_medal_reverse_award',
                $viewParams
            );
        }
    }

    public function actionAwardedUsers()
    {
        $medalId = $this->_input->filterSingle('medal_id', XenForo_Input::UINT);
        $medal = $this->_getMedalHelper()->getMedalOrError($medalId);
        $users = $this->_getAwardedModel()->getAwardedUsers($medal['medal_id']);

        $viewParams = array(
            'medal' => $medal,
            'users' => $users,
        );

        return $this->responseView('bdMedal_ViewAdmin_Medal_AwardedUsers', 'bdmedal_medal_awarded_users', $viewParams);
    }

    /**
     * @return bdMedal_ControllerHelper_Medal
     */
    protected function _getMedalHelper()
    {
        return $this->getHelper('bdMedal_ControllerHelper_Medal');
    }

    /**
     * @return bdMedal_Model_Category
     */
    protected function _getCategoryModel()
    {
        return $this->getModelFromCache('bdMedal_Model_Category');
    }

    /**
     * @return bdMedal_Model_Medal
     */
    protected function _getMedalModel()
    {
        return $this->getModelFromCache('bdMedal_Model_Medal');
    }

    /**
     * @return bdMedal_Model_Awarded
     */
    protected function _getAwardedModel()
    {
        return $this->getModelFromCache('bdMedal_Model_Awarded');
    }

}
