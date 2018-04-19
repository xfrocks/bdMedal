<?php

namespace Xfrocks\Medal\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Util\File;

/**
 * COLUMNS
 * @property int medal_id
 * @property string name
 * @property int category_id
 * @property string description
 * @property int display_order
 * @property int user_count
 * @property int last_award_date
 * @property int last_award_user_id
 * @property string last_award_username
 * @property int image_date
 * @property bool is_svg
 *
 * RELATIONS
 * @property Category Category
 */
class Medal extends Entity
{
    public function deleteExistingImages()
    {
        $sizeMap = $this->getImageSizeMap(true);
        foreach ($sizeMap as $code => $size) {
            $dataFile = $this->getImageAbstractedPath($code, true);
            if (!is_string($dataFile)) {
                continue;
            }
            File::deleteFromAbstractedPath($dataFile);
        }
    }

    public function getEntityColumnLabel($columnName)
    {
        switch ($columnName) {
            case 'name':
            case 'description':
                return \XF::phrase('bdmedal_' . $columnName);
            case 'category_id':
                return \XF::phrase('bdmedal_category_entity');
            case 'display_order':
                return \XF::phrase('display_order');
        }

        return null;
    }

    public function getEntityLabel()
    {
        return $this->name;
    }

    public function getImageAbstractedPath($code, $getExistingValue = false)
    {
        $path = $this->getImagePath($code, $getExistingValue);
        if (empty($path)) {
            return null;
        }

        return 'data://' . $path;
    }

    public function getImagePath($code, $getExistingValue = false)
    {
        $medalId = $this->medal_id;
        $imageDate = $getExistingValue ? $this->getExistingValue('image_date') : $this->image_date;
        if (!$imageDate) {
            return null;
        }

        $isSvg = $getExistingValue ? $this->getExistingValue('is_svg') : $this->is_svg;
        if ($isSvg) {
            return sprintf('medal/%d_%d.svg', $medalId, $imageDate);
        }

        $code = strtolower($code);
        $sizeMap = $this->getImageSizeMap($getExistingValue);
        if (!isset($sizeMap[$code])) {
            return null;
        }

        return sprintf('medal/%d_%d%s.jpg', $medalId, $imageDate, $code);
    }

    public function getImageSizeMap($getExistingValue = false)
    {
        $sizeMap = ['l' => $this->getImageSizePixels('L')];
        $isSvg = $getExistingValue ? $this->getExistingValue('is_svg') : $this->is_svg;
        if ($isSvg) {
            return $sizeMap;
        } else {
            $sizeMap['t'] = $this->getImageSizePixels('T');
        }

        $sizeM = $this->getImageSizePixels('M');
        if ($sizeM > 0) {
            $sizeMap['m'] = $sizeM;
        }

        $sizeS = $this->getImageSizePixels('S');
        if ($sizeS > 0) {
            $sizeMap['s'] = $sizeS;
        }

        return $sizeMap;
    }

    public function getImageSizePixels($code)
    {
        $code = strtoupper($code);
        switch ($code) {
            case 'L':
                return -1;
            case 'T':
                return 22;
        }

        $optionId = 'bdMedal_imageSize' . $code;
        return intval($this->app()->options()->offsetGet($optionId));
    }

    public function getImageUrl($code, $canonical = false)
    {
        $app = $this->app();

        $path = $this->getImagePath($code);
        if (empty($path)) {
            return null;
        }

        return $app->applyExternalDataUrl($path, $canonical);
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        $this->deleteExistingImages();

        if ($this->getOption('delete_awardeds')) {
            $this->app()->jobManager()->enqueueUnique(
                'xfrocksMedalMedalDelete' . $this->medal_id,
                'Xfrocks\Medal:MedalDelete',
                [
                    'medal_id' => $this->medal_id
                ]
            );
        }
    }

    protected function _postSave()
    {
        parent::_postSave();

        if ($this->isChanged('image_date')) {
            $this->deleteExistingImages();
        }

        if ($this->isUpdate() && $this->getOption('rebuild_users')) {
            $this->app()->jobManager()->enqueueUnique(
                'xfrocksMedalMedalSave' . $this->medal_id,
                'Xfrocks\Medal:MedalSave',
                [
                    'medal_id' => $this->medal_id
                ]
            );
        }
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_bdmedal_medal';
        $structure->shortName = 'Xfrocks\Medal:Medal';
        $structure->primaryKey = 'medal_id';
        $structure->columns = [
            'medal_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'name' => ['type' => self::STR, 'maxLength' => 255, 'required' => true],
            'category_id' => ['type' => self::UINT, 'required' => true],
            'description' => ['type' => self::STR, 'html' => true],
            'display_order' => ['type' => self::UINT, 'default' => 10],
            'user_count' => ['type' => self::UINT, 'default' => 0],
            'last_award_date' => ['type' => self::UINT, 'default' => 0],
            'last_award_user_id' => ['type' => self::UINT, 'default' => 0],
            'last_award_username' => ['type' => self::STR, 'default' => ''],
            'image_date' => ['type' => self::UINT, 'default' => 0],
            'is_svg' => ['type' => self::BOOL, 'default' => false],
        ];

        $structure->relations = [
            'Category' => [
                'entity' => 'Xfrocks\Medal:Category',
                'type' => self::TO_ONE,
                'conditions' => 'category_id',
                'primary' => true,
            ],
        ];

        $structure->options = [
            'delete_awardeds' => true,
            'rebuild_users' => true,
        ];

        return $structure;
    }
}
