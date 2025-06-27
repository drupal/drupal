<?php

namespace Drupal\Core\Render;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\PreWarmablePluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\PreWarm\PreWarmableInterface;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\ElementInterface;
use Drupal\Core\Render\Element\FormElementInterface;
use Drupal\Core\Render\Element\Generic;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Provides a plugin manager for element plugins.
 *
 * @see \Drupal\Core\Render\Attribute\RenderElement
 * @see \Drupal\Core\Render\Attribute\FormElement
 * @see \Drupal\Core\Render\Element\RenderElementBase
 * @see \Drupal\Core\Render\Element\FormElementBase
 * @see \Drupal\Core\Render\Element\ElementInterface
 * @see \Drupal\Core\Render\Element\FormElementInterface
 * @see plugin_api
 */
class ElementInfoManager extends DefaultPluginManager implements ElementInfoManagerInterface, PreWarmableInterface {

  use PreWarmablePluginManagerTrait;

  /**
   * Stores the available element information.
   *
   * @var array
   */
  protected $elementInfo;

  /**
   * Class => plugin id mapping.
   *
   * More performant than reflecting runtime.
   *
   * @var array
   * @internal
   */
  protected array $reverseMapping = [];

  /**
   * Constructs an ElementInfoManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    protected ThemeHandlerInterface $themeHandler,
    ModuleHandlerInterface $module_handler,
    protected ThemeManagerInterface $themeManager,
  ) {
    $this->setCacheBackend($cache_backend, 'element_info');
    parent::__construct('Element', $namespaces, $module_handler, ElementInterface::class, RenderElement::class, 'Drupal\Core\Render\Annotation\RenderElement');
    $this->alterInfo('element_plugin');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery(): DiscoveryInterface {
    $discovery = parent::getDiscovery();
    return new class ($discovery, $this->reverseMapping) implements DiscoveryInterface {
      use DiscoveryTrait;

      public function __construct(protected DiscoveryInterface $decorated, protected array &$reverseMapping) {}

      public function getDefinitions(): array {
        $definitions = $this->decorated->getDefinitions();
        foreach ($definitions as $element_type => $definition) {
          $this->reverseMapping[$definition['class']] = $element_type;
        }
        return $definitions;
      }

    };
  }

  /**
   * {@inheritdoc}
   */
  protected function getCachedDefinitions(): ?array {
    if (!isset($this->definitions) && $cache = $this->cacheGet($this->cacheKey)) {
      $this->definitions = $cache->data['definitions'];
      $this->reverseMapping = $cache->data['reverse_mapping'];
    }
    return $this->definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function setCachedDefinitions($definitions): void {
    $data = [
      'definitions' => $definitions,
      'reverse_mapping' => $this->reverseMapping,
    ];
    $this->cacheSet($this->cacheKey, $data, Cache::PERMANENT, $this->cacheTags);
    $this->definitions = $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions(): void {
    $this->elementInfo = NULL;

    $cids = [];
    foreach ($this->themeHandler->listInfo() as $theme_name => $info) {
      $cids[] = $this->getCid($theme_name);
    }

    $this->cacheBackend->deleteMultiple($cids);

    parent::clearCachedDefinitions();
  }

  /**
   * Returns the CID used to cache the element info.
   *
   * @param string $theme_name
   *   The theme name.
   *
   * @return string
   *   The cache ID.
   */
  protected function getCid($theme_name): string {
    return 'element_info_build:' . $theme_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo($type) {
    $theme_name = $this->themeManager->getActiveTheme()->getName();
    if (!isset($this->elementInfo[$theme_name])) {
      $this->elementInfo[$theme_name] = $this->buildInfo($theme_name);
    }
    $info = $this->elementInfo[$theme_name][$type] ?? [];
    $info['#defaults_loaded'] = TRUE;
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfoProperty($type, $property_name, $default = NULL) {
    $info = $this->getInfo($type);

    return $info[$property_name] ?? $default;
  }

  /**
   * Builds up all element information.
   *
   * @param string $theme_name
   *   The theme name.
   *
   * @return array
   *   An array containing all element information.
   */
  protected function buildInfo($theme_name) {
    // Get cached definitions.
    $cid = $this->getCid($theme_name);
    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    // Otherwise, rebuild and cache.
    $info = [];
    $previous_error_handler = set_error_handler(function ($severity, $message, $file, $line) use (&$previous_error_handler) {
      // Ignore deprecations while building element information.
      if ($severity === E_USER_DEPRECATED) {
        // Don't execute PHP internal error handler.
        return TRUE;
      }
      if ($previous_error_handler) {
        return $previous_error_handler($severity, $message, $file, $line);
      }
    });
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
    restore_error_handler();

    foreach ($info as $element_type => $element) {
      $info[$element_type]['#type'] = $element_type;
    }
    // Allow modules to alter the element type defaults.
    $this->moduleHandler->alter('element_info', $info);
    $this->themeManager->alter('element_info', $info);

    $this->cacheBackend->set($cid, $info);

    return $info;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Render\Element\ElementInterface
   *   The render element plugin instance.
   */
  public function createInstance($plugin_id, array $configuration = [], &$element = []): ElementInterface {
    $instance = parent::createInstance($plugin_id, $configuration);
    assert($instance instanceof ElementInterface);
    $instance->initializeInternalStorage($element);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function fromClass(string $class, array $configuration = []): ElementInterface {
    $this->getDefinitions();
    if ($id = $this->getIdFromClass($class)) {
      return $this->createInstance($id, $configuration);
    }
    throw new \LogicException("$class is not a valid element class.");
  }

  /**
   * {@inheritdoc}
   */
  public function getIdFromClass(string $class): ?string {
    $this->getDefinitions();
    return $this->reverseMapping[$class] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function fromRenderable(ElementInterface|array &$element, string $class = Generic::class): ElementInterface {
    if ($element instanceof ElementInterface) {
      return $element;
    }
    if (isset($element['##object']) && $element['##object'] instanceof ElementInterface) {
      return $element['##object']->initializeInternalStorage($element);
    }
    $type = $element['#type'] ?? $this->getIdFromClass($class);
    if (!$type) {
      throw new \LogicException('The element passed to ElementInfoManager::fromRenderable must have a #type or a valid class must be provided.');
    }
    return $this->createInstance($type, element: $element);
  }

}
