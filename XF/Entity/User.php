<?php

namespace Xfrocks\Medal\XF\Entity;

use XF\Mvc\Entity\Structure;
use Xfrocks\Medal\Entity\Awarded;
use Xfrocks\Medal\Entity\Category;
use Xfrocks\Medal\Entity\Medal;

/**
 * @property array medal_awardeds
 * @property int medal_count
 * @property array xf_bdmedal_awarded_cached
 */
class User extends XFCP_User
{
    /**
     * @param \XF\Entity\User $user
     * @param null $error
     * @return bool
     */
    public function canAwardMedal($user, &$error = null)
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
     * @param \XF\Entity\User $user
     * @param null $error
     * @return bool
     */
    public function canSortMedals($user, &$error = null)
    {
        if ($this->is_admin && $this->hasAdminPermission('bdMedal')) {
            return true;
        }

        if ($user->user_id !== $this->user_id) {
            $error = \XF::phraseDeferred('bdmedal_you_cannot_sort_others');
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getMedalAwardeds()
    {
        $awardeds = [];

        if (is_array($this->xf_bdmedal_awarded_cached)) {
            $em = $this->em();
            $shortNameCategory = 'Xfrocks\Medal:Category';
            $shortNameMedal = 'Xfrocks\Medal:Medal';

            foreach ($this->xf_bdmedal_awarded_cached as $array) {
                /** @var Category $category */
                $category = $em->findCached($shortNameCategory, $array['category_id']);
                if (empty($category)) {
                    $category = $em->instantiateEntity($shortNameCategory, [
                        'category_id' => $array['category_id'],
                        'name' => $array['category_name'],
                        'description' => $array['category_description'],
                    ]);
                }

                /** @var Medal $medal */
                $medal = $em->findCached($shortNameMedal, $array['medal_id']);
                if (empty($medal)) {
                    $medal = $em->instantiateEntity($shortNameMedal, $array, ['Category' => $category]);
                }

                /** @var Awarded $awarded */
                $awarded = $em->instantiateEntity('Xfrocks\Medal:Awarded', $array, ['Medal' => $medal]);

                $awardeds[$awarded->awarded_id] = $awarded;
            }
        }

        return $awardeds;
    }

    /**
     * @return int
     */
    public function getMedalCount()
    {
        if (!is_array($this->xf_bdmedal_awarded_cached)) {
            return 0;
        }

        return count($this->xf_bdmedal_awarded_cached);
    }

    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->columns['xf_bdmedal_awarded_cached'] = [
            'type' => self::SERIALIZED_ARRAY
        ];

        $structure->getters['medal_awardeds'] = true;
        $structure->getters['medal_count'] = true;

        return $structure;
    }
}
