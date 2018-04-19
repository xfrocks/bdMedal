<?php

namespace Xfrocks\Medal\Entity;

use XF\Entity\User;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Repository\UserAlert;

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
    public function __set($key, $value)
    {
        if ($key === 'parent_id') {
            // Do no op for parent_id to support medal sorting
            // TODO: find a better way to do this
            return;
        }

        parent::__set($key, $value);
    }

    public function canView()
    {
        return true;
    }

    public function getEntityColumnLabel($columnName)
    {
        switch ($columnName) {
            case 'adjusted_display_order':
                return \XF::phrase('display_order');
            case 'award_reason':
                return \XF::phrase('bdmedal_' . $columnName);
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

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user_id = $user->user_id;
        $this->username = $user->username;
    }

    protected function _postSave()
    {
        parent::_postSave();

        $this->autoRebuild();

        $this->sendAlert();
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        $this->autoRebuild();
    }

    protected function _preSave()
    {
        parent::_preSave();

        if ($this->isUpdate()) {
            if ($this->isChanged('medal_id') ||
                $this->isChanged('user_id') ||
                $this->isChanged('username')) {
                throw new \LogicException('Awarded medal cannot change its medal_id, user_id and username values.');
            }
        }
    }

    protected function autoRebuild()
    {
        if ($this->getOption('rebuild_medal')) {
            $this->rebuildMedal();
        }
        if ($this->getOption('rebuild_user')) {
            $this->rebuildUser();
        }
    }

    protected function sendAlert()
    {
        if (!$this->isInsert()) {
            return;
        }

        /** @var UserAlert $alertRepo */
        $alertRepo = $this->repository('XF:UserAlert');
        $visitor = \XF::visitor();
        $userId = intval($visitor->user_id);
        $username = strval($visitor->username);
        $alertRepo->alert($this->User, $userId, $username, 'medal', $this->awarded_id, 'insert');
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_bdmedal_awarded';
        $structure->shortName = 'Xfrocks\Medal:Awarded';
        $structure->primaryKey = 'awarded_id';
        $structure->columns = [
            'awarded_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'medal_id' => ['type' => self::UINT, 'required' => true, 'writeOnce' => true],
            'user_id' => ['type' => self::UINT, 'required' => true, 'writeOnce' => true],
            'username' => ['type' => self::STR, 'maxLength' => 50, 'required' => true, 'writeOnce' => true],
            'award_date' => ['type' => self::UINT, 'default' => \XF::$time],
            'award_reason' => ['type' => self::STR, 'html' => true],
            'adjusted_display_order' => ['type' => self::UINT, 'default' => 0],
        ];

        $structure->contentType = 'medal';
        $structure->behaviors = [
            'XF:NewsFeedPublishable' => [
                'usernameField' => 'username',
                'dateField' => 'award_date'
            ]
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
