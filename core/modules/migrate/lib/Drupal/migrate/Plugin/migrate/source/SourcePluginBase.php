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
  function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
  }

   /**
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
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
    $this->getModuleHandler()->invokeAll('migrate_prepare_row', array($row, $this, $this->migration));
    $this->getModuleHandler()->invokeAll('migrate_ '. $this->migration->id() . '_prepare_row', array($row, $this, $this->migration));
    return TRUE;
  }

}
