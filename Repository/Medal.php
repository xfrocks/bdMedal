<?php

namespace Xfrocks\Medal\Repository;

use XF\Mvc\Entity\Repository;
use Xfrocks\Medal\Entity\Medal as EntityMedal;

class Medal extends Repository
{
    public function canAward()
    {
        return \XF::visitor()->hasPermission('general', 'bdMedal_award');
    }

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
}
