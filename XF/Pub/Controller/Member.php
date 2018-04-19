<?php

namespace Xfrocks\Medal\XF\Pub\Controller;

use XF\Mvc\ParameterBag;

class Member extends XFCP_Member
{
    public function actionMedals(ParameterBag $params)
    {
        $user = $this->assertViewableUser($params->user_id);
        $this->assertCanonicalUrl($this->buildLink('members/medals', $user));

        $awardeds = $this->finder('Xfrocks\Medal:Awarded')
            ->with('Medal', true)
            ->where('user_id', $user->user_id)
            ->order('award_date', 'DESC')
            ->fetch();

        $viewParams = [
            'user' => $user,
            'awardeds' => $awardeds,
        ];

        return $this->view('Xfrocks\Medal:User\Medals', 'bdmedal_user_medals', $viewParams);
    }
}
