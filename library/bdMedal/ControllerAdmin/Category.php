<?php

class bdMedal_ControllerAdmin_Category extends XenForo_ControllerAdmin_Abstract
{
    protected function _preDispatch($action)
    {
        $this->assertAdminPermission('bdMedal');
    }

    public function actionIndex()
    {
        $model = $this->_getCategoryModel();
        $allCategory = $model->getAllCategory();

        $viewParams = array('allCategory' => $allCategory);

        return $this->responseView('bdMedal_ViewAdmin_Category_List', 'bdmedal_category_list', $viewParams);
    }

    public function actionAdd()
    {
        $viewParams = array('category' => array(),);

        return $this->responseView('bdMedal_ViewAdmin_Category_Edit', 'bdmedal_category_edit', $viewParams);
    }

    public function actionEdit()
    {
        $id = $this->_input->filterSingle('category_id', XenForo_Input::UINT);
        $category = $this->_getCategoryOrError($id);

        $viewParams = array('category' => $category,);

        return $this->responseView('bdMedal_ViewAdmin_Category_Edit', 'bdmedal_category_edit', $viewParams);
    }

    public function actionSave()
    {
        $this->_assertPostOnly();

        $id = $this->_input->filterSingle('category_id', XenForo_Input::UINT);

        $dwInput = $this->_input->filter(array(
            'name' => 'string',
            'description' => 'string',
            'display_order' => 'uint'
        ));

        $dw = XenForo_DataWriter::create('bdMedal_DataWriter_Category');
        if ($id) {
            $dw->setExistingData($id);
        }
        $dw->bulkSet($dwInput);
        $dw->save();

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('medal-categories'));
    }

    public function actionDelete()
    {
        $id = $this->_input->filterSingle('category_id', XenForo_Input::UINT);
        $category = $this->_getCategoryOrError($id);

        if ($this->isConfirmedPost()) {
            $dw = XenForo_DataWriter::create('bdMedal_DataWriter_Category');
            $dw->setExistingData($id);
            $dw->delete();

            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('medal-categories'));
        } else {
            $viewParams = array('category' => $category);

            return $this->responseView('bdMedal_ViewAdmin_Category_Delete', 'bdmedal_category_delete', $viewParams);
        }
    }

    protected function _getCategoryOrError($id, array $fetchOptions = array())
    {
        $info = $this->_getCategoryModel()->getCategoryById($id, $fetchOptions);

        if (empty($info)) {
            throw $this->responseException($this->responseError(new XenForo_Phrase('bdmedal_category_not_found'), 404));
        }

        return $info;
    }

    /**
     * @return bdMedal_Model_Category
     */
    protected function _getCategoryModel()
    {
        return $this->getModelFromCache('bdMedal_Model_Category');
    }

}
