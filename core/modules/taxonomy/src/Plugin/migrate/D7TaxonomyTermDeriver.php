<?php

namespace Drupal\taxonomy\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\FieldDiscoveryInterface;
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
   * The migration field discovery service.
   *
   * @var \Drupal\migrate_drupal\FieldDiscoveryInterface
   */
  protected $fieldDiscovery;

  /**
   * D7TaxonomyTermDeriver constructor.
   *
   * @param string $base_plugin_id
   *   The base plugin ID for the plugin ID.
   * @param \Drupal\migrate_drupal\FieldDiscoveryInterface $field_discovery
   *   The migration field discovery service.
   */
  public function __construct($base_plugin_id, FieldDiscoveryInterface $field_discovery) {
    $this->basePluginId = $base_plugin_id;
    $this->fieldDiscovery = $field_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('migrate_drupal.field_discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

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

        /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
        $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($values);
        $this->fieldDiscovery->addBundleFieldProcesses($migration, 'taxonomy_term', $bundle);
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
