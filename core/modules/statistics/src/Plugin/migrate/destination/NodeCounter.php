<?php

namespace Drupal\statistics\Plugin\migrate\destination;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

// cspell:ignore daycount totalcount

/**
 * Destination for node counter.
 */
#[MigrateDestination(
  id: 'node_counter',
  destination_module: 'statistics'
)]
class NodeCounter extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a node counter plugin.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['nid' => ['type' => 'integer']];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('The ID of the node to which these statistics apply.'),
      'totalcount' => $this->t('The total number of times the node has been viewed.'),
      'daycount' => $this->t('The total number of times the node has been viewed today.'),
      'timestamp' => $this->t('The most recent time the node has been viewed.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $nid = $row->getDestinationProperty('nid');
    $daycount = $row->getDestinationProperty('daycount');
    $totalcount = $row->getDestinationProperty('totalcount');
    $timestamp = $row->getDestinationProperty('timestamp');

    $this->connection
      ->merge('node_counter')
      ->key('nid', $nid)
      ->fields([
        'daycount' => $daycount,
        'totalcount' => $totalcount,
        'timestamp' => $timestamp,
      ])
      ->expression('daycount', '[daycount] + :daycount', [':daycount' => $daycount])
      ->expression('totalcount', '[totalcount] + :totalcount', [':totalcount' => $totalcount])
      // Per Drupal policy: "A query may have any number of placeholders, but
      // all must have unique names even if they have the same value."
      // https://www.drupal.org/docs/drupal-apis/database-api/static-queries#placeholders
      ->expression('timestamp', 'CASE WHEN [timestamp] > :timestamp1 THEN [timestamp] ELSE :timestamp2 END', [':timestamp1' => $timestamp, ':timestamp2' => $timestamp])
      ->execute();

    return [$row->getDestinationProperty('nid')];
  }

}
