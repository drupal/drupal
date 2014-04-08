<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\source\SourcePluginBase.
 */

namespace Drupal\migrate\Plugin\migrate\source;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Row;

/**
 * The base class for all source plugins.
 */
abstract class SourcePluginBase extends PluginBase implements MigrateSourceInterface  {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\migrate\Entity\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
  }

  /**
   * Get the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  protected function getModuleHandler() {
    if (!isset($this->moduleHandler)) {
      $this->moduleHandler = \Drupal::moduleHandler();
    }
    return $this->moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $result_hook = $this->getModuleHandler()->invokeAll('migrate_prepare_row', array($row, $this, $this->migration));
    $result_named_hook = $this->getModuleHandler()->invokeAll('migrate_' . $this->migration->id() . '_prepare_row', array($row, $this, $this->migration));
    // If any of the hooks returned false, we want to skip the row.
    if (($result_hook && in_array(FALSE, $result_hook)) || ($result_named_hook && in_array(FALSE, $result_named_hook))) {
      return FALSE;
    }
  }

}
