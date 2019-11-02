<?php

namespace Drupal\block\Plugin\migrate\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateProcessPlugin(
 *   id = "block_plugin_id"
 * )
 */
class BlockPluginId extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration process plugin.
   *
   * The plugin is configured for lookups in d6_custom_block and
   * d7_custom_block.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   *
   * @deprecated in drupal:8.8.x and is removed from drupal:9.0.0. Use
   *   the migrate.lookup service instead.
   *
   * @see https://www.drupal.org/node/3047268
   */
  protected $migrationPlugin;

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * The block_content entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentStorage;

  /**
   * Constructs a BlockPluginId object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The block content storage object.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   */
  // @codingStandardsIgnoreLine
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityStorageInterface $storage, $migrate_lookup) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if ($migrate_lookup instanceof MigrateProcessInterface) {
      @trigger_error('Passing a migration process plugin as the fifth argument to ' . __METHOD__ . ' is deprecated in drupal:8.8.0 and will throw an error in drupal:9.0.0. Pass the migrate.lookup service instead. See https://www.drupal.org/node/3047268', E_USER_DEPRECATED);
      $this->migrationPlugin = $migrate_lookup;
      $migrate_lookup = \Drupal::service('migrate.lookup');
    }
    elseif (!$migrate_lookup instanceof MigrateLookupInterface) {
      throw new \InvalidArgumentException("The fifth argument to " . __METHOD__ . " must be an instance of MigrateLookupInterface.");
    }
    $this->blockContentStorage = $storage;
    $this->migrateLookup = $migrate_lookup;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager->getDefinition('block_content') ? $entity_type_manager->getStorage('block_content') : NULL,
      $container->get('migrate.lookup')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Set the block plugin id.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value)) {
      list($module, $delta) = $value;
      switch ($module) {
        case 'aggregator':
          list($type, $id) = explode('-', $delta);
          if ($type == 'feed') {
            return 'aggregator_feed_block';
          }
          break;

        case 'menu':
          return "system_menu_block:$delta";

        case 'block':
          if ($this->blockContentStorage) {
            // This BC layer is included because if the plugin constructor was
            // called in the legacy way with a migration_lookup process plugin,
            // it  may have been preconfigured with a different migration to
            // look up against. While this is unlikely, for maximum BC we will
            // continue to use the plugin to do the lookup if it is provided,
            // and support for this will be removed in Drupal 9.
            if ($this->migrationPlugin) {
              $block_id = $this->migrationPlugin
                ->transform($delta, $migrate_executable, $row, $destination_property);
            }
            else {
              $lookup_result = $this->migrateLookup->lookup(['d6_custom_block', 'd7_custom_block'], [$delta]);
              if ($lookup_result) {
                $block_id = $lookup_result[0]['id'];
              }
            }

            if (!empty($block_id)) {
              return 'block_content:' . $this->blockContentStorage->load($block_id)->uuid();
            }
          }
          break;

        default:
          break;
      }
    }
    else {
      return $value;
    }
  }

}
