<?php

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines a field type category info plugin manager.
 *
 * A module can define field type categories in a
 * MODULE_NAME.field_type_categories.yml file contained in the module's
 * base directory. Each plugin has the following structure:
 * @code
 *   CATEGORY_NAME:
 *     label: STRING
 *     description: STRING
 *     weight: INTEGER
 *     libraries:
 *       - STRING
 * @endcode
 * For example:
 * @code
 * text:
 *   label: Text
 *   description: Text fields.
 *   weight: 2
 *   libraries:
 *     - module_name/library_name
 * @endcode
 *
 * @see \Drupal\Core\Field\FieldTypeCategoryInterface
 * @see \Drupal\Core\Field\FieldTypeCategory
 * @see \hook_field_type_category_info_alter
 */
class FieldTypeCategoryManager extends DefaultPluginManager implements FieldTypeCategoryManagerInterface, FallbackPluginManagerInterface {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'label' => '',
    'description' => '',
    'weight' => 0,
    'class' => FieldTypeCategory::class,
  ];

  /**
   * Constructs a new FieldTypeCategoryManager.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(protected readonly string $root, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->moduleHandler = $module_handler;
    $this->alterInfo('field_type_category_info');
    $this->setCacheBackend($cache_backend, 'field_type_category_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery(): YamlDiscovery {
    if (!isset($this->discovery)) {
      $directories = ['core' => $this->root . '/core'];
      $directories += $this->moduleHandler->getModuleDirectories();
      $this->discovery = new YamlDiscovery('field_type_categories', $directories);
      $this->discovery
        ->addTranslatableProperty('label')
        ->addTranslatableProperty('description');
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions): void {
    parent::alterDefinitions($definitions);

    if (!isset($definitions[FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY])) {
      throw new \LogicException('Missing fallback category.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []): string {
    return FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY;
  }

}
