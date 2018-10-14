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
 * @property int parent_id
 * @property int lft
 * @property int rgt
 * @property int depth
 * @property array breadcrumb_data
 *
 * RELATIONS
 * @property \Xfrocks\Medal\Entity\Category[] Children
 * @property \Xfrocks\Medal\Entity\Category Parent
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
            case 'parent_id':
                return \XF::phrase('bdmedal_parent_category');
        }

        return null;
    }

    public function getEntityLabel($withDepth = true)
    {
        return ($withDepth ? str_repeat('--', $this->depth) . ' ' : '') . $this->name;
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

        if ($this->getOption('update_children_parent_id')) {
            foreach ($this->Children as $child) {
                $child->parent_id = $this->parent_id;
                $child->save();
            }
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

            'parent_id' => ['type' => self::UINT, 'default' => 0],
            'lft' => ['type' => self::UINT],
            'rgt' => ['type' => self::UINT],
            'depth' => ['type' => self::UINT],
            'breadcrumb_data' => ['type' => self::SERIALIZED_ARRAY, 'default' => []],
        ];

        $structure->behaviors = [
            'XF:TreeStructured' => [
                'titleField' => 'name',
            ]
        ];

        $structure->options = [
            'delete_medals' => true,
            'update_children_parent_id' => true,
        ];

        $structure->relations = [
            'Children' => [
                'entity' => $structure->shortName,
                'type' => self::TO_MANY,
                'conditions' => [
                    ['parent_id', '=', '$category_id']
                ],
            ],
            'Parent' => [
                'entity' => $structure->shortName,
                'type' => self::TO_ONE,
                'conditions' => [
                    ['category_id', '=', '$parent_id']
                ],
                'primary' => true
            ],
        ];

        return $structure;
    }
}
