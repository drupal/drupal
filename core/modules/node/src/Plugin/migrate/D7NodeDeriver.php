<?php

namespace Drupal\node\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for Drupal 7 node and node revision migrations based on node types.
 */
class D7NodeDeriver extends DeriverBase implements ContainerDeriverInterface {
  use MigrationDeriverTrait;

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * Already-instantiated cckfield plugins, keyed by ID.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface[]
   */
  protected $cckPluginCache;

  /**
   * The CCK plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface
   */
  protected $cckPluginManager;

  /**
   * D7NodeDeriver constructor.
   *
   * @param string $base_plugin_id
   *   The base plugin ID for the plugin ID.
   * @param \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface $cck_manager
   *   The CCK plugin manager.
   */
  public function __construct($base_plugin_id, MigrateCckFieldPluginManagerInterface $cck_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->cckPluginManager = $cck_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('plugin.manager.migrate.cckfield')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $fields = [];
    try {
      $source_plugin = static::getSourcePlugin('d7_field_instance');
      $source_plugin->checkRequirements();

      // Read all field instance definitions in the source database.
      foreach ($source_plugin as $row) {
        if ($row->getSourceProperty('entity_type') == 'node') {
          $fields[$row->getSourceProperty('bundle')][$row->getSourceProperty('field_name')] = $row->getSource();
        }
      }
    }
    catch (RequirementsException $e) {
      // If checkRequirements() failed then the field module did not exist and
      // we do not have any fields. Therefore, $fields will be empty and below
      // we'll create a migration just for the node properties.
    }

    try {
      foreach (static::getSourcePlugin('d7_node_type') as $row) {
        $node_type = $row->getSourceProperty('type');
        $values = $base_plugin_definition;

        $values['label'] = t('@label (@type)', [
          '@label' => $values['label'],
          '@type' => $row->getSourceProperty('name'),
        ]);
        $values['source']['node_type'] = $node_type;
        $values['destination']['default_bundle'] = $node_type;

        $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($values);
        if (isset($fields[$node_type])) {
          foreach ($fields[$node_type] as $field_name => $info) {
            $field_type = $info['type'];
            try {
              $plugin_id = $this->cckPluginManager->getPluginIdFromFieldType($field_type, ['core' => 7], $migration);
              if (!isset($this->cckPluginCache[$field_type])) {
                $this->cckPluginCache[$field_type] = $this->cckPluginManager->createInstance($plugin_id, ['core' => 7], $migration);
              }
              $this->cckPluginCache[$field_type]
                ->processCckFieldValues($migration, $field_name, $info);
            }
            catch (PluginNotFoundException $ex) {
              $migration->setProcessOfProperty($field_name, $field_name);
            }
          }
        }
        $this->derivatives[$node_type] = $migration->getPluginDefinition();
      }
    }
    catch (DatabaseExceptionWrapper $e) {
      // Once we begin iterating the source plugin it is possible that the
      // source tables will not exist. This can happen when the
      // MigrationPluginManager gathers up the migration definitions but we do
      // not actually have a Drupal 7 source database.
    }

    return $this->derivatives;
  }

}
