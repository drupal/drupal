<?php

namespace Drupal\Core\Render;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Render\Element\FormElementInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

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
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The cache tag invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagInvalidator;

  /**
   * Constructs an ElementInfoManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tag_invalidator
   *   The cache tag invalidator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, CacheTagsInvalidatorInterface $cache_tag_invalidator, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager) {
    $this->setCacheBackend($cache_backend, 'element_info');
    $this->themeManager = $theme_manager;
    $this->cacheTagInvalidator = $cache_tag_invalidator;

    parent::__construct('Element', $namespaces, $module_handler, 'Drupal\Core\Render\Element\ElementInterface', 'Drupal\Core\Render\Annotation\RenderElement');
    $this->alterInfo('element_plugin');
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo($type) {
    $theme_name = $this->themeManager->getActiveTheme()->getName();
    if (!isset($this->elementInfo[$theme_name])) {
      $this->elementInfo[$theme_name] = $this->buildInfo($theme_name);
    }
    $info = isset($this->elementInfo[$theme_name][$type]) ? $this->elementInfo[$theme_name][$type] : [];
    $info['#defaults_loaded'] = TRUE;
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfoProperty($type, $property_name, $default = NULL) {
    $info = $this->getInfo($type);

    return isset($info[$property_name]) ? $info[$property_name] : $default;
  }

  /**
   * Builds up all element information.
   *
   * @param string $theme_name
   *   The theme name.
   *
   * @return array
   */
  protected function buildInfo($theme_name) {
    // Get cached definitions.
    $cid = $this->getCid($theme_name);
    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    // Otherwise, rebuild and cache.
    $info = [];
    foreach ($this->getDefinitions() as $element_type => $definition) {
      $element = $this->createInstance($element_type);
      $element_info = $element->getInfo();

      // If this is element is to be used exclusively in a form, denote that it
      // will receive input, and assign the value callback.
      if ($element instanceof FormElementInterface) {
        $element_info['#input'] = TRUE;
        $element_info['#value_callback'] = [$definition['class'], 'valueCallback'];
      }
      $info[$element_type] = $element_info;
    }

    foreach ($info as $element_type => $element) {
      $info[$element_type]['#type'] = $element_type;
    }
    // Allow modules to alter the element type defaults.
    $this->moduleHandler->alter('element_info', $info);
    $this->themeManager->alter('element_info', $info);

    $this->cacheBackend->set($cid, $info, Cache::PERMANENT, ['element_info_build']);

    return $info;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Render\Element\ElementInterface
   */
  public function createInstance($plugin_id, array $configuration = []) {
    return parent::createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    $this->elementInfo = NULL;
    $this->cacheTagInvalidator->invalidateTags(['element_info_build']);

    parent::clearCachedDefinitions();
  }

  /**
   * Returns the CID used to cache the element info.
   *
   * @param string $theme_name
   *   The theme name.
   *
   * @return string
   */
  protected function getCid($theme_name) {
    return 'element_info_build:' . $theme_name;
  }

}
