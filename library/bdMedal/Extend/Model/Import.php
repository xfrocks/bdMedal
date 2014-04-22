<?php

class bdMedal_Extend_Model_Import extends XFCP_bdMedal_Extend_Model_Import
{
	public function bdMedal_importCategory($oldId, array $info)
	{
		$categoryId = $this->_importData($oldId, 'bdMedal_DataWriter_Category', 'medalCategories', 'category_id', $info);

		return $categoryId;
	}

	public function bdMedal_importMedal($oldId, array $info, $imageUrl)
	{
		$errorHandler = XenForo_DataWriter::ERROR_ARRAY;
		$dwName = 'bdMedal_DataWriter_Medal';
		$contentKey = 'medals';
		$idKey = 'medal_id';

		XenForo_Db::beginTransaction();

		$dw = XenForo_DataWriter::create($dwName, $errorHandler);
		$dw->bulkSet($info);

		$imageFileName = basename($imageUrl);
		$contents = @file_get_contents($imageUrl);
		if (empty($contents))
		{
			$imageFileName = XenForo_Application::$time . '.png';
			$contents = file_get_contents(dirname(__FILE__) . '/medal_image_default.png');
		}
		$imageTempPath = XenForo_Helper_File::getInternalDataPath() . '/' . $imageFileName;
		file_put_contents($imageTempPath, $contents);

		$image = new XenForo_Upload($imageFileName, $imageTempPath);
		$dw->setImage($image);

		if ($dw->save())
		{
			$newId = $dw->get($idKey);
			if ($oldId !== 0 && $oldId !== '')
			{
				$this->logImportData($contentKey, $oldId, $newId);
			}
		}
		else
		{
			$newId = false;
		}

		XenForo_Db::commit();

		return $newId;
	}

	public function bdMedal_importAwarded($medalId, $userId, $username, $awardDate, $awardReason)
	{
		// check for duplicated awarded record
		// added since 1.3
		$awardedModel = $this->getModelFromCache('bdMedal_Model_Awarded');

		$errorHandler = XenForo_DataWriter::ERROR_ARRAY;
		$dwName = 'bdMedal_DataWriter_Awarded';
		// $contentKey = 'medals';
		$idKey = 'medal_id';

		$dw = XenForo_DataWriter::create($dwName, $errorHandler);
		$dw->set('medal_id', $medalId);
		$dw->set('user_id', $userId);
		$dw->set('username', $username);
		$dw->set('award_date', $awardDate);
		$dw->set('award_reason', $awardReason);

		if ($dw->save())
		{
			$newId = $dw->get($idKey);
		}
		else
		{
			$newId = false;
		}

		return $newId;
	}

}
