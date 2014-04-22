<?php

class bdMedal_ViewPublic_Help_Medals extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$this->_params['medalsGrouped'] = array();
		foreach ($this->_params['medals'] as $medalId => $medal)
		{
			if (!isset($this->_params['medalsGrouped'][$medal['category_id']]))
			{
				$this->_params['medalsGrouped'][$medal['category_id']] = array(
					'category_id' => $medal['category_id'],
					'name' => $medal['category_name'],
					'description' => $medal['category_description'],
					'medals' => array(),
				);
			}

			$this->_params['medalsGrouped'][$medal['category_id']]['medals'][$medalId] = $medal;
		}

		$awardedUsersMax = bdMedal_Option::get('awardedUsersMax');
		if ($this->_params['showAll'])
			$awardedUsersMax = 0;
		// show all, ignore the options

		$this->_params['awardedsGrouped'] = array();
		foreach ($this->_params['awardeds'] as $awardedId => $awarded)
		{
			if (!isset($this->_params['awardedsGrouped'][$awarded['medal_id']]))
			{
				$this->_params['awardedsGrouped'][$awarded['medal_id']] = array(
					'awardeds' => array(),
					'hidden' => array(),
				);
			}

			if ($awardedUsersMax > 0 AND count($this->_params['awardedsGrouped'][$awarded['medal_id']]['awardeds']) >= $awardedUsersMax)
			{
				// the maximum limit was reached
				$this->_params['awardedsGrouped'][$awarded['medal_id']]['hidden'][$awardedId] = $awarded;
			}
			else
			{
				$this->_params['awardedsGrouped'][$awarded['medal_id']]['awardeds'][$awardedId] = $awarded;
			}
		}

		foreach ($this->_params['awardedsGrouped'] as &$grouped)
		{
			if (count($grouped['hidden']) == 1)
			{
				// only 1 left, merge it back
				$awarded = array_shift($grouped['hidden']);
				$grouped['awardeds'][$awarded['awarded_id']] = $awarded;
			}
		}
	}

}
