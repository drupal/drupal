<?php

namespace Drupal\taxonomy\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for Drupal 7 taxonomy term migrations based on vocabularies.
 */
class D7TaxonomyTermDeriver extends DeriverBase implements ContainerDeriverInterface {
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
   * D7TaxonomyTermDeriver constructor.
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
        if ($row->getSourceProperty('entity_type') == 'taxonomy_term') {
          $fields[$row->getSourceProperty('bundle')][$row->getSourceProperty('field_name')] = $row->getSource();
        }
      }
    }
    catch (RequirementsException $e) {
      // If checkRequirements() failed then the field module did not exist and
      // we do not have any fields. Therefore, $fields will be empty and below
      // we'll create a migration just for the node properties.
    }

    $vocabulary_source_plugin = static::getSourcePlugin('d7_taxonomy_vocabulary');
    try {
      $vocabulary_source_plugin->checkRequirements();
    }
    catch (RequirementsException $e) {
      // If the d7_taxonomy_vocabulary requirements failed, that means we do not
      // have a Drupal source database configured - there is nothing to
      // generate.
      return $this->derivatives;
    }

    try {
      foreach ($vocabulary_source_plugin as $row) {
        $bundle = $row->getSourceProperty('machine_name');
        $values = $base_plugin_definition;

        $values['label'] = t('@label (@type)', [
          '@label' => $values['label'],
          '@type' => $row->getSourceProperty('name'),
        ]);
        $values['source']['bundle'] = $bundle;
        $values['destination']['default_bundle'] = $bundle;

        /** @var Migration $migration */
        $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($values);
        if (isset($fields[$bundle])) {
          foreach ($fields[$bundle] as $field_name => $info) {
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
        $this->derivatives[$bundle] = $migration->getPluginDefinition();
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
