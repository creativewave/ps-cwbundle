<?php

define('_PS_ROOT_DIR_', dirname(__DIR__));

require_once 'cwbundle.php';

class Module
{
    public function __construct()
    {
    }

    public function l($text)
    {
        return $text;
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    public function display($template_path, $template_name, $id_cache)
    {
        return '';
    }

    public function getCacheId($name = null)
    {
        return $name ?? $this->name;
    }
}

class ObjectModel
{
    const HAS_ONE = 1;
    const HAS_MANY = 1;
    const TYPE_INT = 1;
    const TYPE_STRING = 1;

    public function save()
    {
    }

    public function delete()
    {
    }

    public function duplicateObject()
    {
    }

    public function setFieldsToUpdate($fields)
    {
    }
}

class Tools
{
    public function getValue($value, $default)
    {
        return $_POST[$value] ?? $default;
    }
}
