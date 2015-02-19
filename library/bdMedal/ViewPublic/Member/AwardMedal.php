<?php

class bdMedal_ViewPublic_Member_AwardMedal extends XenForo_ViewPublic_Base
{
    public function prepareParams()
    {
        $medals = $this->_params['medals'];
        $awardedMedals = $this->_params['awardedMedals'];

        $options = array();
        foreach ($medals as $medal) {
            $isAwarded = false;
            foreach ($awardedMedals as $awardedMedal) {
                if ($awardedMedal['medal_id'] == $medal['medal_id']) {
                    $isAwarded = true;
                }
            }

            $option = array(
                'value' => $medal['medal_id'],
                'label' => $medal['name'],
                'disabled' => $isAwarded,
            );

            $options[$medal['category_name']][$medal['medal_id']] = $option;
        }
        $this->_params['medalsGrouped'] = $options;
    }

}