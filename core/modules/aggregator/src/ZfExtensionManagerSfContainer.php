<?php

namespace Drupal\aggregator;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Laminas\Feed\Reader\ExtensionManagerInterface as ReaderManagerInterface;
use Laminas\Feed\Writer\ExtensionManagerInterface as WriterManagerInterface;

/**
 * Defines a bridge between the Laminas service manager to Symfony container.
 */
class ZfExtensionManagerSfContainer implements ReaderManagerInterface, WriterManagerInterface, ContainerAwareInterface {

  /**
   * A map of characters to be replaced through strtr.
   *
   * This property is based on Laminas service manager.
   *
   * @link https://github.com/laminas/laminas-servicemanager for the canonical source repository
   * @copyright Copyright (c) 2019, Laminas Foundation. (https://getlaminas.org/)
   * @license https://github.com/laminas/laminas-servicemanager/blob/master/LICENSE.md
   *
   * @var array
   *
   * @see \Drupal\aggregator\ZfExtensionManagerSfContainer::canonicalizeName().
   * @see https://github.com/laminas/laminas-servicemanager/blob/2.7.11/src/ServiceManager.php#L114
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
   * @var \Laminas\Feed\Reader\ExtensionManagerInterface|\Laminas\Feed\Writer\ExtensionManagerInterface
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
    if ($this->standalone && $this->standalone->has($extension)) {
      return $this->standalone->get($extension);
    }
    return $this->container->get($this->prefix . $this->canonicalizeName($extension));
  }

  /**
   * {@inheritdoc}
   */
  public function has($extension) {
    if ($this->standalone && $this->standalone->has($extension)) {
      return TRUE;
    }
    return $this->container->has($this->prefix . $this->canonicalizeName($extension));
  }

  /**
   * Canonicalize the extension name to a service name.
   *
   * This method is based on Laminas service manager.
   *
   * @link https://github.com/laminas/laminas-servicemanager for the canonical source repository
   * @copyright Copyright (c) 2019, Laminas Foundation. (https://getlaminas.org/)
   * @license https://github.com/laminas/laminas-servicemanager/blob/master/LICENSE.md
   *
   * @param string $name
   *   The extension name.
   *
   * @return string
   *   The service name, without the prefix.
   *
   * @see https://github.com/laminas/laminas-servicemanager/blob/2.7.11/src/ServiceManager.php#L900
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
   *   The class to set as standalone.
   */
  public function setStandalone($class) {
    if (!is_subclass_of($class, ReaderManagerInterface::class) && !is_subclass_of($class, WriterManagerInterface::class)) {
      throw new \RuntimeException("$class must implement Laminas\Feed\Reader\ExtensionManagerInterface or Laminas\Feed\Writer\ExtensionManagerInterface");
    }
    $this->standalone = new $class();
  }

}
