<?php

namespace Xfrocks\Medal\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int category_id
 * @property string name
 * @property string description
 * @property int display_order
 */
class Category extends Entity
{
    public function getEntityColumnLabel($columnName)
    {
        switch ($columnName) {
            case 'name':
            case 'description':
                return \XF::phrase('bdmedal_' . $columnName);
            case 'display_order':
                return \XF::phrase('display_order');
        }

        return null;
    }

    public function getEntityLabel()
    {
        return $this->name;
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        if ($this->getOption('delete_medals')) {
            $this->app()->jobManager()->enqueueUnique(
                'xfrocksMedalCategoryDelete' . $this->category_id,
                'Xfrocks\Medal:CategoryDelete',
                [
                    'category_id' => $this->category_id
                ]
            );
        }
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_bdmedal_category';
        $structure->shortName = 'Xfrocks\Medal:Category';
        $structure->primaryKey = 'category_id';
        $structure->columns = [
            'category_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'name' => ['type' => self::STR, 'maxLength' => 255, 'required' => true],
            'description' => ['type' => self::STR, 'html' => true],
            'display_order' => ['type' => self::UINT, 'default' => 10],
        ];

        $structure->options = [
            'delete_medals' => true,
        ];

        return $structure;
    }
}
