<?php

namespace Xfrocks\Medal\Entity;

use XF\Entity\User;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int awarded_id
 * @property int medal_id
 * @property int user_id
 * @property string username
 * @property int award_date
 * @property string award_reason
 * @property int adjusted_display_order
 *
 * RELATIONS
 * @property Medal Medal
 * @property User User
 */
class Awarded extends Entity
{
    public function getEntityColumnLabel($columnName)
    {
        switch ($columnName) {
            case 'award_reason':
                return \XF::phrase('bdmedal_' . $columnName);
            case 'medal_id':
                return \XF::phrase('bdmedal_medal_entity');
        }

        return null;
    }

    public function rebuildMedal()
    {
        $app = $this->app();
        \Xfrocks\Medal\Util\Medal::rebuild($app, $this->medal_id);

        if ($this->isChanged('medal_id')) {
            $existingMedalId = $this->getExistingValue('medal_id');
            if (!empty($existingMedalId)) {
                \Xfrocks\Medal\Util\Medal::rebuild($app, $existingMedalId);
            }
        }
    }

    public function rebuildUser()
    {
        $app = $this->app();
        \Xfrocks\Medal\Util\User::rebuild($app, $this->user_id);

        if ($this->isChanged('user_id')) {
            $existingUserId = $this->getExistingValue('user_id');
            if (!empty($existingUserId)) {
                \Xfrocks\Medal\Util\User::rebuild($this->app(), $existingUserId);
            }
        }
    }

    protected function _autoRebuild()
    {
        if ($this->getOption('rebuild_medal')) {
            $this->rebuildMedal();
        }
        if ($this->getOption('rebuild_user')) {
            $this->rebuildUser();
        }
    }

    protected function _postSave()
    {
        parent::_postSave();

        $this->_autoRebuild();
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        $this->_autoRebuild();
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_bdmedal_awarded';
        $structure->shortName = 'Xfrocks\Medal:Awarded';
        $structure->primaryKey = 'awarded_id';
        $structure->columns = [
            'awarded_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'medal_id' => ['type' => self::UINT, 'required' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'username' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'award_date' => ['type' => self::UINT, 'default' => \XF::$time],
            'award_reason' => ['type' => self::STR],
            'adjusted_display_order' => ['type' => self::UINT, 'default' => 0],
        ];

        $structure->relations = [
            'Medal' => [
                'entity' => 'Xfrocks\Medal:Medal',
                'type' => self::TO_ONE,
                'conditions' => 'medal_id',
                'primary' => true,
            ],
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true,
            ],
        ];

        $structure->options = [
            'rebuild_medal' => true,
            'rebuild_user' => true,
        ];

        $structure->defaultWith = ['Medal'];

        return $structure;
    }
}
