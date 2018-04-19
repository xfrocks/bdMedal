<?php

namespace Xfrocks\Medal\XF\Entity;

use XF\Mvc\Entity\Structure;

/**
 * @property array xf_bdmedal_awarded_cached
 */
class User extends XFCP_User
{
    /**
     * @param \XF\Entity\User $user
     * @param null $error
     * @return bool
     */
    public function canAward($user, &$error = null)
    {
        if (!$this->hasPermission('general', 'bdMedal_award')) {
            return false;
        }

        if ($user !== null && $user->user_id === $this->user_id) {
            $error = \XF::phraseDeferred('bdmedal_you_cannot_award_medal_to_yourself');
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getMedalCount()
    {
        return count($this->getMedals());
    }

    /**
     * @return array
     */
    public function getMedals()
    {
        return $this->xf_bdmedal_awarded_cached ?: [];
    }

    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->columns['xf_bdmedal_awarded_cached'] = [
            'type' => self::SERIALIZED_ARRAY
        ];

        $structure->getters['medals'] = true;
        $structure->getters['medal_count'] = true;

        return $structure;
    }
}
