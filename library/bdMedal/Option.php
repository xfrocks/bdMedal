<?php

class bdMedal_Option
{
    public static function get($key)
    {
        switch ($key) {
            case 'navtabId':
                return 'bdMedal';
        }

        return XenForo_Application::get('options')->get('bdMedal_' . $key);
    }
}
