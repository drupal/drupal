<?php

namespace Drupal\node\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for Drupal 6 node and node revision migrations based on node types.
 */
class D6NodeDeriver extends DeriverBase implements ContainerDeriverInterface {
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
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $cckPluginManager;

  /**
   * D6NodeDeriver constructor.
   *
   * @param string $base_plugin_id
   *   The base plugin ID for the plugin ID.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $cck_manager
   *   The CCK plugin manager.
   */
  public function __construct($base_plugin_id, PluginManagerInterface $cck_manager) {
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
   * Gets the definition of all derivatives of a base plugin.
   *
   * @param array $base_plugin_definition
   *   The definition array of the base plugin.
   *
   * @return array
   *   An array of full derivative definitions keyed on derivative id.
   *
   * @see \Drupal\Component\Plugin\Derivative\DeriverBase::getDerivativeDefinition()
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Read all CCK field instance definitions in the source database.
    $fields = array();
    try {
      $source_plugin = static::getSourcePlugin('d6_field_instance');
      $source_plugin->checkRequirements();

      foreach ($source_plugin as $row) {
        $fields[$row->getSourceProperty('type_name')][$row->getSourceProperty('field_name')] = $row->getSource();
      }
    }
    catch (RequirementsException $e) {
      // If checkRequirements() failed then the content module did not exist and
      // we do not have any CCK fields. Therefore, $fields will be empty and
      // below we'll create a migration just for the node properties.
    }

    try {
      foreach (static::getSourcePlugin('d6_node_type') as $row) {
        $node_type = $row->getSourceProperty('type');
        $values = $base_plugin_definition;

        $values['label'] = t("@label (@type)", [
          '@label' => $values['label'],
          '@type' => $node_type,
        ]);
        $values['source']['node_type'] = $node_type;
        $values['destination']['default_bundle'] = $node_type;

        // If this migration is based on the d6_node_revision migration, it
        // should explicitly depend on the corresponding d6_node variant.
        if ($base_plugin_definition['id'] == 'd6_node_revision') {
          $values['migration_dependencies']['required'][] = 'd6_node:' . $node_type;
        }

        $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($values);
        if (isset($fields[$node_type])) {
          foreach ($fields[$node_type] as $field_name => $info) {
            $field_type = $info['type'];
            if ($this->cckPluginManager->hasDefinition($info['type'])) {
              if (!isset($this->cckPluginCache[$field_type])) {
                $this->cckPluginCache[$field_type] = $this->cckPluginManager->createInstance($field_type, ['core' => 6], $migration);
              }
              $this->cckPluginCache[$field_type]
                ->processCckFieldValues($migration, $field_name, $info);
            }
            else {
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
      // not actually have a Drupal 6 source database.
    }

    return $this->derivatives;
  }

}
