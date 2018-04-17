<?php

namespace Xfrocks\Medal\Service\Medal;

use XF\Service\AbstractService;
use XF\Util\File;
use Xfrocks\Medal\Entity\Medal;

class Image extends AbstractService
{
    protected $error = null;

    protected $fileName;

    protected $isSvg = false;

    /** @var Medal */
    protected $medal;

    public function __construct(\XF\App $app, Medal $medal)
    {
        parent::__construct($app);

        $this->medal = $medal;
    }

    public function getError()
    {
        return $this->error;
    }

    public function setImage($fileName)
    {
        if (!$this->validateImage($fileName, $isSvg, $error)) {
            $this->error = $error;
            $this->fileName = null;
            return false;
        }

        $this->fileName = $fileName;
        $this->isSvg = $isSvg;
        return true;
    }

    public function setImageFromUpload(\XF\Http\Upload $upload)
    {
        $upload->setAllowedExtensions(['gif', 'jpe', 'jpeg', 'jpg', 'png', 'svg']);

        if (!$upload->isValid($errors)) {
            $this->error = reset($errors);
            return false;
        }

        return $this->setImage($upload->getTempFile());
    }

    public function updateImage()
    {
        if (!$this->fileName) {
            throw new \LogicException('No source file');
        }
        if (!$this->medal->exists()) {
            throw new \LogicException('Medal does not exist');
        }

        $medal = $this->medal;
        $medal->image_date = \XF::$time;
        $medal->is_svg = $this->isSvg;
        $sizeMap = $medal->getImageSizeMap();

        $imageManager = $this->app->imageManager();
        $outputFiles = ['l' => $this->fileName];

        foreach ($sizeMap as $code => $size) {
            if (isset($outputFiles[$code])) {
                continue;
            }

            $image = $imageManager->imageFromFile($this->fileName);
            if (!$image) {
                continue;
            }

            $image->resizeShortEdge($size);

            $newTempFile = File::getTempFile();
            if ($newTempFile && $image->save($newTempFile)) {
                $outputFiles[$code] = $newTempFile;
            }
            unset($image);
        }

        if (count($outputFiles) != count($sizeMap)) {
            throw new \RuntimeException('Failed to prepare output files');
        }

        foreach ($outputFiles as $code => $file) {
            $dataFile = $medal->getImageAbstractedPath($code);
            File::copyFileToAbstractedPath($file, $dataFile);
        }

        $medal->save();

        return true;
    }

    public function validateImage($fileName, &$isSvg, &$error = null)
    {
        $error = null;

        if (!file_exists($fileName)) {
            throw new \InvalidArgumentException('File does not exist');
        }
        if (!is_readable($fileName)) {
            throw new \InvalidArgumentException('File is not readable');
        }

        $imageInfo = filesize($fileName) ? @getimagesize($fileName) : false;
        if (!$imageInfo) {
            $fh = fopen($fileName, 'r');
            $firstFive = fread($fh, 5);
            fclose($fh);
            if ($firstFive === '<?xml' || $firstFive === '<svg ') {
                $isSvg = true;
                return true;
            }

            $error = \XF::phrase('provided_file_is_not_valid_image');
            return false;
        }

        $type = $imageInfo[2];
        if (!in_array($type, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            $error = \XF::phrase('provided_file_is_not_valid_image');
            return false;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        if (!$this->app->imageManager()->canResize($width, $height)) {
            $error = \XF::phrase('uploaded_image_is_too_big');
            return false;
        }

        return true;
    }
}
