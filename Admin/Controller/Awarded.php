<?php

namespace Xfrocks\Medal\Admin\Controller;

use XF\Entity\User;
use \Xfrocks\Medal\Entity\Awarded as EntityAwarded;
use \Xfrocks\Medal\Entity\Medal;

class Awarded extends Entity
{
    public function actionAdd()
    {
        $input = $this->filter([
            'medal_id' => 'uint',
            'usernames' => 'str',
            'award_reason' => 'str',
            'avoid_duplicated' => 'bool',
        ]);

        if ($this->isPost()) {
            /** @var Medal $medal */
            $medal = $this->assertRecordExists('Xfrocks\Medal:Medal', $input['medal_id']);

            $users = [];
            $userNames = preg_split('#\s*,\s*#', $input['usernames'], -1, PREG_SPLIT_NO_EMPTY);
            if ($userNames !== false) {
                /** @var \XF\Repository\User $userRepo */
                $userRepo = $this->repository('XF:User');
                $users = $userRepo->getUsersByNames($userNames, $notFound);
            }
            if (count($users) === 0 || !empty($notFound)) {
                return $this->error(\XF::phrase('requested_user_not_found'));
            }

            /** @var \Xfrocks\Medal\Repository\Medal $medalRepo */
            $medalRepo = $this->repository('Xfrocks\Medal:Medal');

            /** @var User $user */
            foreach ($users as $user) {
                if ($input['avoid_duplicated'] &&
                    $medalRepo->hasExistingAwarded($medal, $user)) {
                    continue;
                }

                $medalRepo->award($medal, $user, ['award_reason' => $input['award_reason']]);
            }

            return $this->redirect($this->buildLink('awarded-medals'));
        }

        /** @var \Xfrocks\Medal\Repository\Medal $medalRepo */
        $medalRepo = $this->repository('Xfrocks\Medal:Medal');

        $medalTree = $medalRepo->getMedalTreeForSelectRow();

        $viewParams = [
            'medalTree' => $medalTree,
            'input' => $input,
        ];

        return $this->view('Xfrocks\Medal:Awarded\Add', 'bdmedal_awarded_add', $viewParams);
    }

    public function getEntityExplain($entity)
    {
        /** @var EntityAwarded $awarded */
        $awarded = $entity;

        $date = \XF::language()->date($awarded->award_date);
        if (empty($awarded->award_reason)) {
            return $date;
        }

        return sprintf('%s, %s', $date, strip_tags($awarded->award_reason));
    }

    public function getEntityHint($entity)
    {
        /** @var EntityAwarded $awarded */
        $awarded = $entity;
        return $awarded->username;
    }

    public function getEntityLabel($entity)
    {
        /** @var EntityAwarded $awarded */
        $awarded = $entity;
        /** @var Medal|null $medal */
        $medal = $awarded->getExistingRelation('Medal');
        if ($medal === null) {
            return $awarded->awarded_id;
        }

        return $medal->name;
    }

    protected function entityAddEdit($entity)
    {
        $view = parent::entityAddEdit($entity);

        if (!$entity->exists()) {
            $columns = $view->getParam('columns');
            $columns['medal_id']['value'] = $this->filter('medal_id', 'uint');
            $view->setParam('columns', $columns);
        }

        return $view;
    }

    protected function getPrefixForPhrases()
    {
        return 'bdmedal_awarded';
    }

    protected function getRoutePrefix()
    {
        return 'awarded-medals';
    }

    protected function getShortName()
    {
        return 'Xfrocks\Medal:Awarded';
    }

    protected function getViewReply($action, array $viewParams)
    {
        $viewParams['macroTemplateEntityEdit'] = 'bdmedal_awarded';
        $viewParams['macroTemplateEntityListFilters'] = 'bdmedal_awarded';
        $viewParams['macroTemplateEntityListItemPopup'] = 'bdmedal_awarded';

        return parent::getViewReply($action, $viewParams);
    }
}
