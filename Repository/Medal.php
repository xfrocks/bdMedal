<?php

namespace Xfrocks\Medal\Repository;

use XF\Entity\User;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;
use XF\PrintableException;
use Xfrocks\Medal\Entity\Awarded;
use Xfrocks\Medal\Entity\Medal as EntityMedal;

class Medal extends Repository
{
    /**
     * @param EntityMedal $medal
     * @param User $user
     * @param array $bulkSet
     * @return Awarded
     * @throws PrintableException
     */
    public function award($medal, $user, array $bulkSet = [])
    {
        /** @var Awarded $awarded */
        $awarded = $this->em->create('Xfrocks\Medal:Awarded');
        $awarded->medal_id = $medal->medal_id;
        $awarded->setUser($user);
        $awarded->bulkSet($bulkSet);

        $awarded->save();

        return $awarded;
    }

    /**
     * @param int $userId
     * @return Finder
     */
    public function findAwardedsForUser($userId)
    {
        return $this->finder('Xfrocks\Medal:Awarded')
            ->where('user_id', $userId)
            ->order('adjusted_display_order', 'ASC')
            ->order('award_date', 'DESC')
            ->keyedBy('awarded_id');
    }

    /**
     * @return array
     */
    public function getMedalTree()
    {
        $medals = $this->finder('Xfrocks\Medal:Medal')
            ->order('Category.display_order', 'ASC')
            ->order('display_order', 'ASC')
            ->with('Category', true)
            ->fetch();

        $tree = [];
        /** @var EntityMedal $medal */
        foreach ($medals as $medal) {
            $categoryName = $medal->Category->name;
            if (!isset($tree[$categoryName])) {
                $tree[$categoryName] = [];
            }
            $tree[$categoryName][$medal->medal_id] = $medal;
        }

        return $tree;
    }

    /**
     * @return array
     */
    public function getMedalTreeForSelectRow()
    {
        $tree = $this->getMedalTree();

        $choices = [];
        foreach ($tree as $categoryName => $medals) {
            $choices[$categoryName] = [];
            /** @var EntityMedal $medal */
            foreach ($medals as $medal) {
                $choices[$categoryName][$medal->medal_id] = $medal->name;
            }
        }

        return $choices;
    }

    /**
     * @param EntityMedal $medal
     * @param User $user
     * @return bool
     */
    public function hasExistingAwarded($medal, $user)
    {
        return $this->finder('Xfrocks\Medal:Awarded')
                ->where('medal_id', $medal->medal_id)
                ->where('user_id', $user->user_id)
                ->total() > 0;
    }
}
