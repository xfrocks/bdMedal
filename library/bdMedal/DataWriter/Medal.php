<?php

class bdMedal_DataWriter_Medal extends XenForo_DataWriter
{
	const IMAGE_PREPARED = 'imagePrepared';
	const SIZE_ORIGINAL = -1;

	public static $imageQuality = 85;

	public function setImage(XenForo_Upload $upload)
	{
		if (!$upload->isValid())
		{
			throw new XenForo_Exception($upload->getErrors(), true);
		}

		if (!$upload->isImage())
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		};

		$imageType = $upload->getImageInfoField('type');
		if (!in_array($imageType, array(
			IMAGETYPE_GIF,
			IMAGETYPE_JPEG,
			IMAGETYPE_PNG
		)))
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		}

		$this->setExtraData(self::IMAGE_PREPARED, $this->_prepareImage($upload));
		$this->set('image_date', XenForo_Application::$time);
	}

	protected function _prepareImage(XenForo_Upload $upload)
	{
		$outputFiles = array();
		$fileName = $upload->getTempFile();
		$imageType = $upload->getImageInfoField('type');
		$outputType = $imageType;
		$width = $upload->getImageInfoField('width');
		$height = $upload->getImageInfoField('height');

		$imageSizes = self::getImageSizes();
		reset($imageSizes);

		/*
		 list($sizeCode, $maxDimensions) = each(self::$_imageSizes);

		 $shortSide = ($width > $height ? $height : $width);

		 if ($shortSide > $maxDimensions) {
		 $newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xfa');
		 $image = XenForo_Image_Abstract::createFromFile($fileName, $imageType);
		 $image->thumbnailFixedShorterSide($maxDimensions);

		 $image->output($outputType, $newTempFile, self::$imageQuality);

		 $width = $image->getWidth();
		 $height = $image->getHeight();

		 $outputFiles[$sizeCode] = $newTempFile;
		 }
		 else
		 {
		 $outputFiles[$sizeCode] = $fileName;
		 }
		 */

		while (list($sizeCode, $maxDimensions) = each($imageSizes))
		{
			$newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xfa');

			if ($maxDimensions == self::SIZE_ORIGINAL)
			{
				copy($fileName, $newTempFile);
			}
			else
			{
				$image = XenForo_Image_Abstract::createFromFile($fileName, $imageType);
				if (!$image)
				{
					continue;
				}

				$image->thumbnail($maxDimensions, $maxDimensions);

				$image->output($outputType, $newTempFile, self::$imageQuality);
				unset($image);
			}

			$outputFiles[$sizeCode] = $newTempFile;
		}

		if (count($outputFiles) != count($imageSizes))
		{
			foreach ($outputFiles AS $tempFile)
			{
				if ($tempFile != $fileName)
				{
					@unlink($tempFile);
				}
			}

			throw new XenForo_Exception('Non-image passed in to _prepareImage');
		}

		return $outputFiles;
	}

	protected function _moveImages($uploaded)
	{
		if (is_array($uploaded))
		{
			$data = $this->getMergedData();
			foreach ($uploaded as $sizeCode => $tempFile)
			{
				$filePath = bdMedal_Model_Medal::getImageFilePath($data, $sizeCode);
				$directory = dirname($filePath);

				if (XenForo_Helper_File::createDirectory($directory, true) && is_writable($directory))
				{
					if (file_exists($filePath))
					{
						unlink($filePath);
					}

					$success = @rename($tempFile, $filePath);
					if ($success)
					{
						XenForo_Helper_File::makeWritableByFtpUser($filePath);
					}
				}
			}
		}
	}

	protected function _preSave()
	{
		$imageDate = $this->get('image_date');
		if (empty($imageDate))
		{
			$this->error(new XenForo_Phrase('bdmedal_medal_must_have_an_image'));
		}
	}

	protected function _postSave()
	{
		$uploaded = $this->getExtraData(self::IMAGE_PREPARED);
		if ($uploaded)
		{
			$this->_moveImages($uploaded);

			if ($this->isUpdate())
			{
				/* remove old image */
				$existingData = $this->getMergedExistingData();
				foreach (array_keys(self::getImageSizes()) as $sizeCode)
				{
					$filePath = bdMedal_Model_Medal::getImageFilePath($existingData, $sizeCode);
					if (!empty($filePath))
						@unlink($filePath);
				}
			}
		}

		$this->_rebuildUsers();
	}

	protected function _postDelete()
	{
		$existingData = $this->getMergedExistingData();
		foreach (array_keys(self::getImageSizes()) as $sizeCode)
		{
			$filePath = bdMedal_Model_Medal::getImageFilePath($existingData, $sizeCode);
			if (!empty($filePath))
				@unlink($filePath);
		}

		$this->_rebuildUsers(true);
	}

	protected function _rebuildUsers($delete = false)
	{
		$awardedModel = $this->_getAwardedModel();

		$users = $awardedModel->getAllAwarded(array('medal_id' => $this->get('medal_id')));

		if (empty($delete))
		{
			foreach ($users as $user)
			{
				$awardedModel->rebuildUser($user['user_id']);
			}
		}
		else
		{
			foreach ($users as $user)
			{
				$dw = XenForo_DataWriter::create('bdMedal_DataWriter_Awarded');
				$dw->setExistingData($user, true);
				$dw->setExtraData(bdMedal_DataWriter_Awarded::OPTION_DISABLE_REBUILD_MEDAL, 1);
				$dw->delete();
			}
		}

	}

	protected function _getFields()
	{
		return array('xf_bdmedal_medal' => array(
				'medal_id' => array(
					'type' => 'uint',
					'autoIncrement' => true
				),
				'name' => array(
					'type' => 'string',
					'length' => 255,
					'required' => true
				),
				'category_id' => array(
					'type' => 'uint',
					'required' => true,
					'verification' => array(
						'$this',
						'_verifyCategoryId'
					)
				),
				'description' => array('type' => 'string'),
				'display_order' => array(
					'type' => 'uint',
					'default' => 0
				),
				'user_count' => array(
					'type' => 'uint',
					'default' => 0
				),
				'last_award_date' => array(
					'type' => 'uint',
					'default' => 0
				),
				'last_award_user_id' => array(
					'type' => 'uint',
					'default' => 0
				),
				'last_award_username' => array(
					'type' => 'string',
					'length' => 50,
					'default' => ''
				),
				'image_date' => array('type' => 'uint'),
			));
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'medal_id'))
		{
			return false;
		}

		return array('xf_bdmedal_medal' => $this->_getMedalModel()->getMedalById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		$conditions = array();

		foreach (array('0' => 'medal_id') as $field)
		{
			$conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
		}

		return implode(' AND ', $conditions);
	}

	protected function _getMedalModel()
	{
		return $this->getModelFromCache('bdMedal_Model_Medal');
	}

	protected function _getAwardedModel()
	{
		return $this->getModelFromCache('bdMedal_Model_Awarded');
	}

	protected function _verifyCategoryId($categoryId)
	{
		if ($categoryId > 0)
		{
			// good
		}
		else
		{
			$this->error(new XenForo_Phrase('bdmedal_medal_must_belong_to_a_category'), 'category_id');
			return false;
		}

		return true;
	}

	public static function getImageSizes()
	{
		static $imageSizes = false;

		if ($imageSizes === false)
		{
			$imageSizes = array(
				'l' => self::SIZE_ORIGINAL,
				't' => 12,
			);

			$mSize = bdMedal_Option::get('imageSizeM');
			$sSize = bdMedal_Option::get('imageSizeS');

			if ($mSize > 0)
			{
				$imageSizes['m'] = $mSize;
			}

			if ($sSize > 0)
			{
				$imageSizes['s'] = $sSize;
			}
		}

		return $imageSizes;
	}

}
