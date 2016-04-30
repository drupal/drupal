<?php

namespace Drupal\Component\Bridge;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
  protected $canonicalNamesReplacements = array('-' => '', '_' => '', ' ' => '', '\\' => '', '/' => '');

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
   * Constructs a ZfExtensionManagerSfContainer object.
   *
   * @param string $prefix
   *   The prefix to be used when retrieving plugins from the container.
   */
  public function __construct($prefix = '') {
    return $this->prefix = $prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function get($extension) {
    return $this->container->get($this->prefix . $this->canonicalizeName($extension));
  }

  /**
   * {@inheritdoc}
   */
  public function has($extension) {
    return $this->container->has($this->prefix . $this->canonicalizeName($extension));
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

}
