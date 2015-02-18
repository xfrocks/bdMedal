<?php

class bdMedal_Extend_ControllerPublic_Thread extends XFCP_bdMedal_Extend_ControllerPublic_Thread
{
    protected function _getDefaultViewParams(array $forum, array $thread, array $posts, $page = 1, array $viewParams = array())
    {
        /** @var bdMedal_Model_Awarded $awardedModel */
        $awardedModel = $this->getModelFromCache('bdMedal_Model_Awarded');

        $dataByUser = array();
        foreach ($posts as &$post) {
            if (isset($dataByUser[$post['user_id']])) {
                $post['xf_bdmedal_awarded_cached'] = $dataByUser[$post['user_id']];
            } else {
                $dataByUser[$post['user_id']] = $awardedModel->prepareCachedData($post['xf_bdmedal_awarded_cached']);
            }
        }

        return parent::_getDefaultViewParams($forum, $thread, $posts, $page, $viewParams);
    }

}
