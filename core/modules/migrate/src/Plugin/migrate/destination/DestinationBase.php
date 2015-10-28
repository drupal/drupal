<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\DestinationBase.
 */


namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\RequirementsInterface;

/**
 * Base class for migrate destination classes.
 *
 * @see \Drupal\migrate\Plugin\MigrateDestinationInterface
 * @see \Drupal\migrate\Plugin\MigrateDestinationPluginManager
 * @see \Drupal\migrate\Annotation\MigrateDestination
 * @see plugin_api
 *
 * @ingroup migration
 */
abstract class DestinationBase extends PluginBase implements MigrateDestinationInterface, RequirementsInterface {

  /**
   * Indicates whether the destination can be rolled back.
   *
   * @var bool
   */
  protected $supportsRollback = FALSE;

  /**
   * The rollback action to be saved for the last imported item.
   *
   * @var int
   */
  protected $rollbackAction = MigrateIdMapInterface::ROLLBACK_DELETE;

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
  public function rollbackAction() {
    return $this->rollbackAction;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    if (empty($this->pluginDefinition['requirements_met'])) {
      throw new RequirementsException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    // By default we do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function supportsRollback() {
    return $this->supportsRollback;
  }

  /**
   * For a destination item being updated, set the appropriate rollback action.
   *
   * @param array $id_map
   *   The map row data for the item.
   */
  protected function setRollbackAction(array $id_map) {
    // If the entity we're updating was previously migrated by us, preserve the
    // existing rollback action.
    if (isset($id_map['sourceid1'])) {
      $this->rollbackAction = $id_map['rollback_action'];
    }
    // Otherwise, we're updating an entity which already existed on the
    // destination and want to make sure we do not delete it on rollback.
    else {
      $this->rollbackAction = MigrateIdMapInterface::ROLLBACK_PRESERVE;
    }
  }
}
