<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\Process\d6\BlockAggregator.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\Process\d6;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_block_plugin_id"
 * )
 */
class BlockPluginId extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $processPluginManager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $customBlockStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, MigratePluginManager $process_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->customBlockStorage = $storage;
    $this->migration = $migration;
    $this->processPluginManager = $process_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $entity_manager->getDefinition('custom_block') ? $entity_manager->getStorage('custom_block') : NULL,
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Set the block plugin id.
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    if (is_array($value)) {
      list($module, $delta) = $value;
      switch ($module) {
        case 'aggregator':
          list($type, $id) = explode('-', $delta);
          if ($type == 'category') {
            // @TODO skip row.
            // throw new MigrateSkipRowException();
          }
          $value = 'aggregator_feed_block';
          break;
        case 'menu':
          $value = "system_menu_block:$delta";
          break;
        case 'block':
          if ($this->customBlockStorage) {
            $block_ids = $this->processPluginManager
              ->createInstance('migration', array('migration' => 'd6_custom_block'), $this->migration)
              ->transform($delta, $migrate_executable, $row, $destination_property);
            $value = 'custom_block:' . $this->customBlockStorage->load($block_ids[0])->uuid();
          }
          else {
            throw new MigrateSkipRowException();
          }
          break;
        default:
          throw new MigrateSkipRowException();
      }
    }
    return $value;
  }

}
