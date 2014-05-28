<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\DestinationBase.
 */


namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\RequirementsInterface;

abstract class DestinationBase extends PluginBase implements MigrateDestinationInterface, RequirementsInterface {

  /**
   * The migration.
   *
   * @var \Drupal\migrate\Entity\MigrationInterface
   */
  protected $migration;

  /**
   * Constructs an entity destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param MigrationInterface $migration
   *   The migration.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    return $this->pluginDefinition['requirements_met'];
  }

  /**
   * Modify the Row before it is imported.
   */
  public function preImport() {
    // By default we do nothing.
  }

  /**
   * Modify the Row before it is rolled back.
   */
  public function preRollback() {
    // By default we do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function postImport() {
    // By default we do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function postRollback() {
    // By default we do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function rollbackMultiple(array $destination_identifiers) {
    // By default we do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated() {
    // TODO: Implement getCreated() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdated() {
    // TODO: Implement getUpdated() method.
  }

  /**
   * {@inheritdoc}
   */
  public function resetStats() {
    // TODO: Implement resetStats() method.
  }

}
