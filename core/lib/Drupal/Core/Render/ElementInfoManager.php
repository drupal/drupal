<?php

/**
 * @file
 * Contains \Drupal\Core\Render\ElementInfoManager.
 */

namespace Drupal\Core\Render;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Render\Element\FormElementInterface;

/**
 * Provides a plugin manager for element plugins.
 *
 * @see \Drupal\Core\Render\Annotation\RenderElement
 * @see \Drupal\Core\Render\Annotation\FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see \Drupal\Core\Render\Element\FormElement
 * @see \Drupal\Core\Render\Element\ElementInterface
 * @see \Drupal\Core\Render\Element\FormElementInterface
 * @see plugin_api
 */
class ElementInfoManager extends DefaultPluginManager implements ElementInfoManagerInterface {

  /**
   * Stores the available element information.
   *
   * @var array
   */
  protected $elementInfo;

  /**
   * Constructs a ElementInfoManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->setCacheBackend($cache_backend, 'element_info');

    parent::__construct('Element', $namespaces, $module_handler, 'Drupal\Core\Render\Element\ElementInterface', 'Drupal\Core\Render\Annotation\RenderElement');
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo($type) {
    if (!isset($this->elementInfo)) {
      $this->elementInfo = $this->buildInfo();
    }
    $info = isset($this->elementInfo[$type]) ? $this->elementInfo[$type] : array();
    $info['#defaults_loaded'] = TRUE;
    return $info;
  }

  /**
   * Builds up all element information.
   */
  protected function buildInfo() {
    // @todo Remove this hook once all elements are converted to plugins in
    //   https://www.drupal.org/node/2311393.
    $info = $this->moduleHandler->invokeAll('element_info');

    foreach ($this->getDefinitions() as $element_type => $definition) {
      $element = $this->createInstance($element_type);
      $element_info = $element->getInfo();

      // If this is element is to be used exclusively in a form, denote that it
      // will receive input, and assign the value callback.
      if ($element instanceof FormElementInterface) {
        $element_info['#input'] = TRUE;
        $element_info['#value_callback'] = array($definition['class'], 'valueCallback');
      }
      $info[$element_type] = $element_info;
    }
    foreach ($info as $element_type => $element) {
      $info[$element_type]['#type'] = $element_type;
    }
    // Allow modules to alter the element type defaults.
    $this->moduleHandler->alter('element_info', $info);

    return $info;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Render\Element\ElementInterface
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    return parent::createInstance($plugin_id, $configuration);
  }

}
