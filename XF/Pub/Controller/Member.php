<?php

namespace Xfrocks\Medal\XF\Pub\Controller;

use XF\ControllerPlugin\Sort;
use XF\Mvc\ParameterBag;

class Member extends XFCP_Member
{
    public function actionMedals(ParameterBag $params)
    {
        $user = $this->assertViewableUser($params->user_id);
        $this->assertCanonicalUrl($this->buildLink('members/medals', $user));

        /** @var \Xfrocks\Medal\Repository\Medal $medalRepo */
        $medalRepo = $this->repository('Xfrocks\Medal:Medal');
        $awardeds = $medalRepo->findAwardedsForUser($user->user_id)
            ->with('Medal', true)
            ->fetch();

        $viewParams = [
            'user' => $user,
            'awardeds' => $awardeds,
        ];

        return $this->view('Xfrocks\Medal:User\Medals', 'bdmedal_user_medals', $viewParams);
    }

    public function actionSortMedals(ParameterBag $params)
    {
        $user = $this->assertViewableUser($params->user_id);

        /** @var \Xfrocks\Medal\Repository\Medal $medalRepo */
        $medalRepo = $this->repository('Xfrocks\Medal:Medal');
        $awardeds = $medalRepo->findAwardedsForUser($user->user_id)
            ->with('Medal', true)
            ->fetch();

        if ($this->isPost()) {
            /** @var Sort $sorter */
            $sorter = $this->plugin('XF:Sort');
            $sortTree = $sorter->buildSortTree($this->filter('sort_data', 'json-array'));

            $sortOptions = [
                'jump' => 1,
                'orderColumn' => 'adjusted_display_order',
            ];
            $sorter->sortTree($sortTree, $awardeds, 'parent_id', $sortOptions);

            return $this->redirect($this->buildLink('members/medals', $user));
        }

        $viewParams = [
            'user' => $user,
            'awardeds' => $awardeds,
        ];

        return $this->view('Xfrocks\Medal:User\SortMedals', 'bdmedal_user_sort_medals', $viewParams);
    }
}
