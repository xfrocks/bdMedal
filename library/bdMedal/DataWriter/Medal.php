<?php

class bdMedal_DataWriter_Medal extends XenForo_DataWriter
{
    const IMAGE_PREPARED = 'imagePrepared';
    const SIZE_ORIGINAL = -1;

    public static $imageQuality = 85;

    public function setImage(XenForo_Upload $upload, $uploadSizeCode = 'l')
    {
        if (!$upload->isValid()) {
            throw new XenForo_Exception($upload->getErrors(), true);
        }

        if ($upload->isImage()) {
            $imageType = $upload->getImageInfoField('type');

            if (!in_array($imageType, array(
                IMAGETYPE_GIF,
                IMAGETYPE_JPEG,
                IMAGETYPE_PNG,
            ))
            ) {
                throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
            }

            $this->set('is_svg', 0);
            $this->setExtraData(self::IMAGE_PREPARED, $this->_prepareImage($upload, $uploadSizeCode));
        } elseif (bdMedal_Helper_Svg::isSvg($upload)) {
            $this->set('is_svg', 1);
            $this->setExtraData(self::IMAGE_PREPARED, $this->_prepareSvg($upload));
        } else {
            throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
        }

        $this->set('image_date', XenForo_Application::$time);
    }

    protected function _prepareImage(XenForo_Upload $upload, $uploadSizeCode)
    {
        $outputFiles = array();
        $prepared = $this->getExtraData(self::IMAGE_PREPARED);
        if (is_array($prepared)) {
            $outputFiles = $prepared;
        }

        $error = false;

        $fileName = $upload->getTempFile();
        $imageType = $upload->getImageInfoField('type');
        $outputType = $imageType;
        $imageSizes = self::getImageSizes();
        reset($imageSizes);

        if ($uploadSizeCode == 'l') {
            while (list($sizeCode, $maxDimensions) = each($imageSizes)) {
                if (isset($outputFiles[$sizeCode])) {
                    continue;
                }

                $newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'bdMedal');

                if ($maxDimensions == self::SIZE_ORIGINAL) {
                    copy($fileName, $newTempFile);
                } else {
                    $image = XenForo_Image_Abstract::createFromFile($fileName, $imageType);
                    if (!$image) {
                        $error = true;
                    } else {
                        $image->thumbnail($maxDimensions, $maxDimensions);

                        $image->output($outputType, $newTempFile, self::$imageQuality);
                        unset($image);
                    }
                }

                $outputFiles[$sizeCode] = $newTempFile;
            }
        } else {
            $newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'bdMedal');
            copy($fileName, $newTempFile);
            $outputFiles[$uploadSizeCode] = $newTempFile;
        }

        if ($error) {
            foreach ($outputFiles AS $tempFile) {
                if ($tempFile != $fileName) {
                    @unlink($tempFile);
                }
            }

            throw new XenForo_Exception('Non-image passed in to _prepareImage');
        }

        return $outputFiles;
    }

    protected function _prepareSvg(XenForo_Upload $upload)
    {
        $outputFiles = array();

        $fileName = $upload->getTempFile();
        foreach (array_keys(self::getImageSizes()) as $sizeCode) {
            $newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'bdMedal');
            copy($fileName, $newTempFile);
            $outputFiles[$sizeCode] = $newTempFile;
        }

        return $outputFiles;
    }

    protected function _moveImages($uploaded)
    {
        if (is_array($uploaded)) {
            $data = $this->getMergedData();
            foreach ($uploaded as $sizeCode => $tempFile) {
                $filePath = bdMedal_Model_Medal::getImageFilePath($data, $sizeCode);
                $directory = dirname($filePath);

                if (XenForo_Helper_File::createDirectory($directory, true) && is_writable($directory)) {
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    $success = XenForo_Helper_File::safeRename($tempFile, $filePath);
                    if ($success) {
                        XenForo_Helper_File::makeWritableByFtpUser($filePath);
                    }
                }
            }
        }
    }

    protected function _preSave()
    {
        $imageDate = $this->get('image_date');
        if (empty($imageDate)) {
            $this->error(new XenForo_Phrase('bdmedal_medal_must_have_an_image'));
        }

        $uploaded = $this->getExtraData(self::IMAGE_PREPARED);
        if (!empty($uploaded)) {
            $existingData = $this->getMergedExistingData();
            foreach (array_keys(self::getImageSizes()) as $sizeCode) {
                if (isset($uploaded[$sizeCode])) {
                    // this size has uploaded data
                    continue;
                }

                $filePath = bdMedal_Model_Medal::getImageFilePath($existingData, $sizeCode);
                if (!empty($filePath) && file_exists($filePath)) {
                    $newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'bdMedal');
                    copy($filePath, $newTempFile);
                    $uploaded[$sizeCode] = $newTempFile;
                    $this->setExtraData(self::IMAGE_PREPARED, $uploaded);
                }
            }
        }
    }

    protected function _postSave()
    {
        $uploaded = $this->getExtraData(self::IMAGE_PREPARED);
        if ($uploaded) {
            $this->_moveImages($uploaded);

            if ($this->isUpdate()) {
                /* remove old image */
                $existingData = $this->getMergedExistingData();
                foreach (array_keys(self::getImageSizes()) as $sizeCode) {
                    $filePath = bdMedal_Model_Medal::getImageFilePath($existingData, $sizeCode);
                    if (!empty($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
        }

        $this->_rebuildUsers();
    }

    protected function _postDelete()
    {
        $existingData = $this->getMergedExistingData();
        foreach (array_keys(self::getImageSizes()) as $sizeCode) {
            $filePath = bdMedal_Model_Medal::getImageFilePath($existingData, $sizeCode);
            if (!empty($filePath)) {
                @unlink($filePath);
            }
        }

        $this->_rebuildUsers(true);
    }

    protected function _rebuildUsers($delete = false)
    {
        $awardedModel = $this->_getAwardedModel();

        $users = $awardedModel->getAllAwarded(array('medal_id' => $this->get('medal_id')));

        if (empty($delete)) {
            foreach ($users as $user) {
                $awardedModel->rebuildUser($user['user_id']);
            }
        } else {
            foreach ($users as $user) {
                $dw = XenForo_DataWriter::create('bdMedal_DataWriter_Awarded');
                $dw->setExistingData($user, true);
                $dw->setOption(bdMedal_DataWriter_Awarded::OPTION_DISABLE_REBUILD_MEDAL, true);
                $dw->delete();
            }
        }

    }

    protected function _getFields()
    {
        return array(
            'xf_bdmedal_medal' => array(
                'medal_id' => array(
                    'type' => XenForo_DataWriter::TYPE_UINT,
                    'autoIncrement' => true
                ),
                'name' => array(
                    'type' => XenForo_DataWriter::TYPE_STRING,
                    'length' => 255,
                    'required' => true
                ),
                'category_id' => array(
                    'type' => XenForo_DataWriter::TYPE_UINT,
                    'required' => true,
                    'verification' => array(
                        '$this',
                        '_verifyCategoryId'
                    )
                ),
                'description' => array('type' => XenForo_DataWriter::TYPE_STRING),
                'display_order' => array(
                    'type' => XenForo_DataWriter::TYPE_UINT,
                    'default' => 0
                ),
                'user_count' => array(
                    'type' => XenForo_DataWriter::TYPE_UINT,
                    'default' => 0
                ),
                'last_award_date' => array(
                    'type' => XenForo_DataWriter::TYPE_UINT,
                    'default' => 0
                ),
                'last_award_user_id' => array(
                    'type' => XenForo_DataWriter::TYPE_UINT,
                    'default' => 0
                ),
                'last_award_username' => array(
                    'type' => XenForo_DataWriter::TYPE_STRING,
                    'length' => 50,
                    'default' => ''
                ),
                'image_date' => array('type' => XenForo_DataWriter::TYPE_UINT),
                'is_svg' => array('type' => XenForo_DataWriter::TYPE_BOOLEAN),
            )
        );
    }

    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data, 'medal_id')) {
            return false;
        }

        return array('xf_bdmedal_medal' => $this->_getMedalModel()->getMedalById($id));
    }

    protected function _getUpdateCondition($tableName)
    {
        $conditions = array();

        foreach (array('0' => 'medal_id') as $field) {
            $conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
        }

        return implode(' AND ', $conditions);
    }

    /**
     * @return bdMedal_Model_Medal
     */
    protected function _getMedalModel()
    {
        return $this->getModelFromCache('bdMedal_Model_Medal');
    }

    /**
     * @return bdMedal_Model_Awarded
     */
    protected function _getAwardedModel()
    {
        return $this->getModelFromCache('bdMedal_Model_Awarded');
    }

    protected function _verifyCategoryId($categoryId)
    {
        if ($categoryId > 0) {
            // good
        } else {
            $this->error(new XenForo_Phrase('bdmedal_medal_must_belong_to_a_category'), 'category_id');
            return false;
        }

        return true;
    }

    public static function getImageSizes()
    {
        static $imageSizes = false;

        if ($imageSizes === false) {
            $imageSizes = array(
                'l' => self::SIZE_ORIGINAL,
                't' => 12,
            );

            $mSize = bdMedal_Option::get('imageSizeM');
            $sSize = bdMedal_Option::get('imageSizeS');

            if ($mSize > 0) {
                $imageSizes['m'] = $mSize;
            }

            if ($sSize > 0) {
                $imageSizes['s'] = $sSize;
            }
        }

        return $imageSizes;
    }

}
