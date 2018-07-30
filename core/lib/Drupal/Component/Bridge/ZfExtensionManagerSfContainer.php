<?php

namespace Drupal\Component\Bridge;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Zend\Feed\Reader\ExtensionManagerInterface as ReaderManagerInterface;
use Zend\Feed\Writer\ExtensionManagerInterface as WriterManagerInterface;

/**
 * Defines a bridge between the ZF2 service manager to Symfony container.
 */
class ZfExtensionManagerSfContainer implements ReaderManagerInterface, WriterManagerInterface, ContainerAwareInterface {

  /**
   * This property was based from Zend Framework (http://framework.zend.com/)
   *
   * @link http://github.com/zendframework/zf2 for the canonical source repository
   * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
   * @license http://framework.zend.com/license/new-bsd New BSD License
   *
   * A map of characters to be replaced through strtr.
   *
   * @var array
   *
   * @see \Drupal\Component\Bridge\ZfExtensionManagerSfContainer::canonicalizeName().
   */
  protected $canonicalNamesReplacements = ['-' => '', '_' => '', ' ' => '', '\\' => '', '/' => ''];

  /**
   * The prefix to be used when retrieving plugins from the container.
   *
   * @var string
   */
  protected $prefix = '';

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * A local cache of computed canonical names.
   *
   * @var string[]
   */
  protected $canonicalNames;

  /**
   * @var \Zend\Feed\Reader\ExtensionManagerInterface|\Zend\Feed\Writer\ExtensionManagerInterface
   */
  protected $standalone;

  /**
   * Constructs a ZfExtensionManagerSfContainer object.
   *
   * @param string $prefix
   *   The prefix to be used when retrieving plugins from the container.
   */
  public function __construct($prefix = '') {
    $this->prefix = $prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function get($extension) {
    try {
      return $this->container->get($this->prefix . $this->canonicalizeName($extension));
    }
    catch (ServiceNotFoundException $e) {
      if ($this->standalone && $this->standalone->has($extension)) {
        return $this->standalone->get($extension);
      }
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function has($extension) {
    if ($this->container->has($this->prefix . $this->canonicalizeName($extension))) {
      return TRUE;
    }
    return $this->standalone && $this->standalone->has($extension);
  }

  /**
   * This method was based from Zend Framework (http://framework.zend.com/)
   *
   * @link http://github.com/zendframework/zf2 for the canonical source repository
   * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
   * @license http://framework.zend.com/license/new-bsd New BSD License
   *
   * Canonicalize the extension name to a service name.
   *
   * @param string $name
   *   The extension name.
   *
   * @return string
   *   The service name, without the prefix.
   */
  protected function canonicalizeName($name) {
    if (isset($this->canonicalNames[$name])) {
      return $this->canonicalNames[$name];
    }
    // This is just for performance instead of using str_replace().
    return $this->canonicalNames[$name] = strtolower(strtr($name, $this->canonicalNamesReplacements));
  }

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container = NULL) {
    $this->container = $container;
  }

  /**
   * @param $class
   */
  public function setStandalone($class) {
    if (!is_subclass_of($class, ReaderManagerInterface::class) && !is_subclass_of($class, WriterManagerInterface::class)) {
      throw new \RuntimeException("$class must implement Zend\Feed\Reader\ExtensionManagerInterface or Zend\Feed\Writer\ExtensionManagerInterface");
    }
    $this->standalone = new $class();
  }

}
