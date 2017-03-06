<?php

class bdMedal_Helper_Template
{
    public static function getCachedMedalsCount($default, array $params)
    {
        try {
            list(, $medals) = self::_getCachedMedals($params);
        } catch (XenForo_Exception $e) {
            if (XenForo_Application::debugMode()) {
                XenForo_Error::logException($e, false);
            }

            return $default;
        }

        return count($medals);
    }

    public static function renderCachedMedals($template, array $params)
    {
        try {
            list($user, $medals) = self::_getCachedMedals($params);
        } catch (XenForo_Exception $e) {
            if (XenForo_Application::debugMode()) {
                XenForo_Error::logException($e, false);
            }

            return '';
        }

        $awardedModel = self::_getAwardedModel();
        $awardedMedals = $awardedModel->applyOrganizedOrder($medals);

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

    /**
     * @param array $params
     * @return array
     * @throws XenForo_Exception
     */
    protected static function _getCachedMedals(array $params)
    {
        if (!isset($params['user'])) {
            throw new XenForo_Exception('No user');
        }
        $user = $params['user'];
        if (empty($user['user_id'])
            || !isset($user['xf_bdmedal_awarded_cached'])
        ) {
            throw new XenForo_Exception('No data');
        }

        $medals = $user['xf_bdmedal_awarded_cached'];
        if (!is_array($medals)) {
            $medals = @unserialize($medals);
        }
        if (!is_array($medals)) {
            throw new XenForo_Exception('Invalid data');
        }

        return array($user, $medals);
    }
}