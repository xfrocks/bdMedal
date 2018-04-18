<?php

namespace Xfrocks\Medal\Admin\Controller;

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

    protected function finderForList()
    {
        $finder = parent::finderForList();

        $medalId = $this->filter('medal_id', 'uint');
        if ($medalId > 0) {
            /** @var Medal $medal */
            $medal = $this->assertRecordExists('Xfrocks\Medal:Medal', $medalId);
            $finder->where('medal_id', $medal->medal_id);
        }

        return $finder;
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
}
