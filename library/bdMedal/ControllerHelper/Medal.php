<?php

class bdMedal_ControllerHelper_Medal extends XenForo_ControllerHelper_Abstract
{
    public function getMedalOrError($medalId, array $fetchOptions = array())
    {
        $medal = $this->_getMedalModel()->getMedalById($medalId, $fetchOptions);

        if (empty($medal)) {
            throw $this->_controller->responseException(
                $this->_controller->responseError(new XenForo_Phrase('bdmedal_medal_not_found'), 404)
            );
        }

        return $medal;
    }

    public function getAwardedMedalOrError(
        $medalId,
        $awardedId,
        array $medalFetchOptions = array(),
        array $awardedFetchOptions = array()
    ) {
        $medal = $this->getMedalOrError($medalId, $medalFetchOptions);
        $awarded = $this->_getAwardedModel()->getAwardedById($awardedId, $awardedFetchOptions);

        if (empty($awarded)) {
            throw $this->_controller->responseException(
                $this->_controller->responseError(new XenForo_Phrase('bdmedal_requested_award_not_found'), 404)
            );
        }

        if ($awarded['medal_id'] !== $medal['medal_id']) {
            throw $this->_controller->getNoPermissionResponseException();
        }

        return array($medal, $awarded);
    }

    /**
     * @return bdMedal_Model_Medal
     */
    protected function _getMedalModel()
    {
        return $this->_controller->getModelFromCache('bdMedal_Model_Medal');
    }

    /**
     * @return bdMedal_Model_Awarded
     */
    protected function _getAwardedModel()
    {
        return $this->_controller->getModelFromCache('bdMedal_Model_Awarded');
    }
}