<?php

class bdMedal_ViewAdmin_Medal_List extends XenForo_ViewAdmin_Base
{
    public function renderHtml()
    {
        $this->_params['medalsGrouped'] = array();
        foreach ($this->_params['allMedal'] as $medalId => $medal) {
            $this->_params['medalsGrouped'][$medal['category_name']][$medalId] = $medal;
        }
    }
}
