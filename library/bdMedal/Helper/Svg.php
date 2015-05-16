<?php

class bdMedal_Helper_Svg
{
    public static function isSvg(XenForo_Upload $upload)
    {
        return XenForo_Helper_File::getFileExtension($upload->getFileName()) === 'svg';
    }
}