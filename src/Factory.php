<?php

namespace Cityware\Datagrid;

abstract class Factory
{
    /**
     * Adapter plugin manager
     * @var AdapterPluginManager
     */
    protected static $adapters;

    /**
     * @var array Known captcha types
     */
    protected static $classMap = array(
        'table' => 'Cityware\Datagrid\Adapter\Table',
    );

    /**
     * Create a captcha adapter instance
     *
     * @param  array|Traversable                  $options
     * @return AdapterInterface
     * @throws Exception\InvalidArgumentException for a non-array, non-Traversable $options
     * @throws Exception\DomainException          if class is missing or invalid
     */
    public static function factory($adapter = false)
    {
        if (!is_string($adapter)) {
            throw new \Cityware\Exception\InvalidArgumentException(
                    sprintf('%s expects an string or Traversable argument; received "%s"', __METHOD__, (is_object($adapter) ? get_class($adapter) : gettype($adapter)))
            );
        }

        if (isset(static::$classMap[strtolower($adapter)])) {
            $class = static::$classMap[strtolower($adapter)];
        }

        if (!class_exists($class)) {
            throw new \Cityware\Exception\DomainException(
                    sprintf('%s expects the "class" to resolve to an existing class; received "%s"', __METHOD__, $class)
            );
        }

        switch (strtolower($adapter)) {
            case 'table':
                return new Adapter\TableNew();
                break;
            default:
                throw new \Cityware\Exception\InvalidArgumentException('Adaptador não definico');
                break;
        }
    }

}
