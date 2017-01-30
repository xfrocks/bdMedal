<?php

class bdMedal_Helper_Template
{
    public static function renderCachedMedals($template, array $params)
    {
        if (!isset($params['user'])) {
            return '';
        }
        $user = $params['user'];
        if (empty($user['user_id'])
            || !isset($user['xf_bdmedal_awarded_cached'])
        ) {
            return '';
        }

        if (!is_array($user['xf_bdmedal_awarded_cached'])) {
            $user['xf_bdmedal_awarded_cached'] = @unserialize($user['xf_bdmedal_awarded_cached']);
        }
        if (empty($user['xf_bdmedal_awarded_cached'])) {
            return '';
        }

        $awardedModel = self::_getAwardedModel();
        $awardedMedals = $awardedModel->applyOrganizedOrder($user['xf_bdmedal_awarded_cached']);

        $output = array();
        foreach ($awardedMedals as $awardedMedal) {
            if (!empty($params['max']) && count($output) >= $params['max']) {
                continue;
            }

            $prepared = $awardedModel->prepareAwardedMedalForOutput($user, $awardedMedal, $params);
            $search = array_keys($prepared);
            $replace = array_map('htmlentities', array_values($prepared));

            if (!empty($params['imageSize'])) {
                $search[] = '{medalImage}';
                $replace[] = bdMedal_Model_Medal::helperMedalImage($awardedMedal, $params['imageSize']);
            }

            $output[] = str_replace($search, $replace, $template);
        }

        return implode('', $output);
    }

    /**
     * @return bdMedal_Model_Awarded
     */
    protected static function _getAwardedModel()
    {
        $model = null;

        if ($model === null) {
            $model = XenForo_Model::create('bdMedal_Model_Awarded');
        }

        return $model;
    }
}