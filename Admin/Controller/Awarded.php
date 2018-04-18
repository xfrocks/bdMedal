<?php

namespace Xfrocks\Medal\Admin\Controller;

use XF\Entity\User;
use XF\Mvc\Entity\Finder;
use XF\Mvc\FormAction;
use \Xfrocks\Medal\Entity\Awarded as EntityAwarded;
use \Xfrocks\Medal\Entity\Medal;

class Awarded extends Entity
{
    public function getEntityExplain($entity)
    {
        /** @var EntityAwarded $awarded */
        $awarded = $entity;
        return $awarded->award_reason;
    }

    public function getEntityHint($entity)
    {
        /** @var EntityAwarded $awarded */
        $awarded = $entity;
        return $awarded->username;
    }

    public function getEntityLabel($entity)
    {
        if (!$entity instanceof \Xfrocks\Medal\Entity\Awarded) {
            return parent::getEntityLabel($entity);
        }

        /** @var EntityAwarded $awarded */
        $awarded = $entity;
        /** @var Medal $medal */
        $medal = $awarded->getExistingRelation('Medal');
        if (!$medal) {
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

    protected function entityListData()
    {
        list($finder, $filters) = parent::entityListData();

        /** @var Finder $finder */
        $finder = $finder->order('award_date', 'DESC');

        $medalId = $this->filter('medal_id', 'uint');
        if ($medalId > 0) {
            /** @var Medal $medal */
            $medal = $this->assertRecordExists('Xfrocks\Medal:Medal', $medalId);
            $finder->where('medal_id', $medal->medal_id);
            $filters['medal'] = $medal;
            $filters['pageNavParams']['medal_id'] = $medal->medal_id;
        }

        $userId = $this->filter('user_id', 'uint');
        if ($userId > 0) {
            /** @var User $user */
            $user = $this->assertRecordExists('XF:User', $userId);
            $finder->where('user_id', $user->user_id);
            $filters['user'] = $user;
            $filters['pageNavParams']['user_id'] = $user->user_id;
        }

        return [$finder, $filters];
    }

    protected function entitySaveProcess($entity)
    {
        if (!$entity->exists()) {
            $formMultiple = $this->formAction();
            $input = $this->filter([
                'values' => 'array',
                'usernames' => 'str',
                'avoid_duplicated' => 'bool',
            ]);

            $awardeds = [];

            $formMultiple->setup(function (FormAction $form) use (&$awardeds, $input) {
                $userNames = preg_split('#\s*,\s*#', $input['usernames'], -1, PREG_SPLIT_NO_EMPTY);
                /** @var \XF\Repository\User $userRepo */
                $userRepo = $this->repository('XF:User');
                $users = $userRepo->getUsersByNames($userNames, $notFound);
                if (count($users) === 0 || !empty($notFound)) {
                    $form->logError(\XF::phrase('requested_user_not_found'));
                    return;
                }

                /** @var User $user */
                foreach ($users as $user) {
                    /** @var EntityAwarded $awarded */
                    $awarded = $this->createEntity();
                    $awarded->bulkSet($input['values']);
                    $awarded->user_id = $user->user_id;
                    $awarded->username = $user->username;

                    if ($input['avoid_duplicated']) {
                        $existing = $this->finder('Xfrocks\Medal:Awarded')
                            ->where('medal_id', $awarded->medal_id)
                            ->where('user_id', $awarded->user_id)
                            ->fetchOne();
                        if (!empty($existing)) {
                            continue;
                        }
                    }


                    $awardeds[] = $awarded;
                }
            });

            $formMultiple->validate(function (FormAction $form) use (&$awardeds) {
                /** @var EntityAwarded $awarded */
                foreach ($awardeds as $awarded) {
                    $awarded->preSave();
                    $form->logErrors($awarded->getErrors());
                }
            });

            $formMultiple->apply(function (FormAction $form) use (&$awardeds) {
                /** @var EntityAwarded $awarded */
                foreach ($awardeds as $awarded) {
                    $awarded->save(true, $form->isUsingTransaction() ? false : true);
                }
            });

            return $formMultiple;
        }

        $formSingle = parent::entitySaveProcess($entity);

        $username = $this->filter('username', 'str');
        $formSingle->setup(function (FormAction $form) use ($entity, $username) {
            /** @var \XF\Repository\User $userRepo */
            $userRepo = $this->repository('XF:User');
            $user = $userRepo->getUserByNameOrEmail($username);
            if (empty($user)) {
                $form->logError(\XF::phrase('requested_user_not_found'));
                return;
            }

            $entity->user_id = $user->user_id;
            $entity->username = $user->username;
        });

        return $formSingle;
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
