<?php

namespace Drupal\block\Plugin\migrate\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
   * The migration process plugin, configured for lookups in d6_custom_block
   * and d7_custom_block.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $migrationPlugin;

  /**
   * The block_content entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityStorageInterface $storage, MigrateProcessInterface $migration_plugin) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockContentStorage = $storage;
    $this->migrationPlugin = $migration_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_manager = $container->get('entity.manager');
    $migration_configuration = [
      'migration' => [
        'd6_custom_block',
        'd7_custom_block',
      ],
    ];
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_manager->getDefinition('block_content') ? $entity_manager->getStorage('block_content') : NULL,
      $container->get('plugin.manager.migrate.process')->createInstance('migration', $migration_configuration, $migration)
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
            $block_id = $this->migrationPlugin
              ->transform($delta, $migrate_executable, $row, $destination_property);
            if ($block_id) {
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
