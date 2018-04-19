<?php

namespace Xfrocks\Medal\Pub\Controller;

use XF\Mvc\Reply\Exception;
use XF\Pub\Controller\AbstractController;
use XF\Repository\User;
use Xfrocks\Medal\Entity\Medal as EntityMedal;

class Medal extends AbstractController
{
    public function actionAward()
    {
        /** @var \Xfrocks\Medal\XF\Entity\User $visitor */
        $visitor = \XF::visitor();
        if (!$visitor->canAwardMedal(null, $error)) {
            return $this->noPermission($error);
        }

        $input = $this->filter([
            'medal_id' => 'uint',
            'username' => 'str',
            'award_reason' => 'str',
        ]);

        if ($this->isPost()) {
            $medal = $this->assertMedalExists($input['medal_id']);

            /** @var User $userRepo */
            $userRepo = $this->repository('XF:User');
            $user = $userRepo->getUserByNameOrEmail($input['username']);
            if (!$user) {
                return $this->error(\XF::phrase('requested_user_not_found'));
            }
            if (!$visitor->canAwardMedal($user, $error)) {
                return $this->noPermission($error);
            }

            $medalRepo = $this->getMedalRepo();

            if ($this->options()->bdMedal_defaultAvoidDuplicated &&
                $medalRepo->hasExistingAwarded($medal, $user)) {
                $phraseParams = [
                    'name' => $user->username,
                    'medal' => $medal->name,
                ];
                return $this->error(\XF::phrase('bdmedal_x_have_been_awarded_medal_y', $phraseParams));
            }

            $safeAwardReason = htmlentities($input['award_reason']);
            $medalRepo->award($medal, $user, ['award_reason' => $safeAwardReason]);

            return $this->redirect($this->buildLink('members', $user));
        }

        $medalTree = $this->getMedalRepo()->getMedalTreeForSelectRow();

        $viewParams = [
            'medalTree' => $medalTree,
            'input' => $input,
        ];

        return $this->view('Xfrocks\Medal:Medal\Award', 'bdmedal_medal_award', $viewParams);
    }

    public function actionUsers()
    {
        $medal = $this->assertMedalExists($this->filter('medal_id', 'uint'));
        $finder = $this->finder('Xfrocks\Medal:Awarded')
            ->with('User', true)
            ->where('medal_id', $medal->medal_id)
            ->order('award_date', 'DESC');
        $total = $finder->total();

        $page = $this->filterPage();
        $perPage = $this->options()->bdMedal_medalUsersPerPage;
        $awardeds = $finder->limitByPage($page, $perPage)->fetch();

        $viewParams = [
            'medal' => $medal,
            'total' => $total,
            'awardeds' => $awardeds,

            'page' => $page,
            'perPage' => $perPage,
            'pageNavParams' => ['medal_id' => $medal->medal_id],
        ];

        return $this->view('Xfrocks\Medal:Medal\Users', 'bdmedal_medal_users', $viewParams);
    }

    /**
     * @param int $medalId
     * @return EntityMedal
     * @throws Exception
     */
    protected function assertMedalExists($medalId)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->assertRecordExists('Xfrocks\Medal:Medal', $medalId);
    }

    /**
     * @return \Xfrocks\Medal\Repository\Medal
     */
    protected function getMedalRepo()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository('Xfrocks\Medal:Medal');
    }
}
