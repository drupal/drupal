<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Hydrator;

use Zend\ServiceManager\AbstractPluginManager;

/**
 * Plugin manager implementation for hydrators.
 *
 * Enforces that adapters retrieved are instances of HydratorInterface
 */
class HydratorPluginManager extends AbstractPluginManager
{
    /**
     * Whether or not to share by default
     *
     * @var bool
     */
    protected $shareByDefault = false;

    /**
     * Default aliases
     *
     * @var array
     */
    protected $aliases = [
        'delegatinghydrator' => 'Zend\Hydrator\DelegatingHydrator',
    ];

    /**
     * Default set of adapters
     *
     * @var array
     */
    protected $invokableClasses = [
        'arrayserializable' => 'Zend\Hydrator\ArraySerializable',
        'classmethods'      => 'Zend\Hydrator\ClassMethods',
        'objectproperty'    => 'Zend\Hydrator\ObjectProperty',
        'reflection'        => 'Zend\Hydrator\Reflection'
    ];

    /**
     * Default factory-based adapters
     *
     * @var array
     */
    protected $factories = [
        'Zend\Hydrator\DelegatingHydrator' => 'Zend\Hydrator\DelegatingHydratorFactory',
    ];

    /**
     * {@inheritDoc}
     */
    public function validatePlugin($plugin)
    {
        if ($plugin instanceof HydratorInterface) {
            // we're okay
            return;
        }

        throw new Exception\RuntimeException(sprintf(
            'Plugin of type %s is invalid; must implement Zend\Hydrator\HydratorInterface',
            (is_object($plugin) ? get_class($plugin) : gettype($plugin))
        ));
    }
}
