<?php

namespace Drupal\node\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\FieldDiscoveryInterface;
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
   * Whether or not to include translations.
   *
   * @var bool
   */
  protected $includeTranslations;

  /**
   * The migration field discovery service.
   *
   * @var \Drupal\migrate_drupal\FieldDiscoveryInterface
   */
  protected $fieldDiscovery;

  /**
   * D6NodeDeriver constructor.
   *
   * @param string $base_plugin_id
   *   The base plugin ID for the plugin ID.
   * @param bool $translations
   *   Whether or not to include translations.
   * @param \Drupal\migrate_drupal\FieldDiscoveryInterface $field_discovery
   *   The migration field discovery service.
   */
  public function __construct($base_plugin_id, $translations, FieldDiscoveryInterface $field_discovery) {
    $this->basePluginId = $base_plugin_id;
    $this->includeTranslations = $translations;
    $this->fieldDiscovery = $field_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    // Translations don't make sense unless we have content_translation.
    return new static(
      $base_plugin_id,
      $container->get('module_handler')->moduleExists('content_translation'),
      $container->get('migrate_drupal.field_discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if ($base_plugin_definition['id'] == 'd6_node_translation' && !$this->includeTranslations) {
      // Refuse to generate anything.
      return $this->derivatives;
    }

    $node_types = static::getSourcePlugin('d6_node_type');
    try {
      $node_types->checkRequirements();
    }
    catch (RequirementsException $e) {
      // If the d6_node_type requirements failed, that means we do not have a
      // Drupal source database configured - there is nothing to generate.
      return $this->derivatives;
    }

    try {
      foreach ($node_types as $row) {
        $node_type = $row->getSourceProperty('type');
        $values = $base_plugin_definition;

        $values['label'] = t("@label (@type)", [
          '@label' => $values['label'],
          '@type' => $node_type,
        ]);
        $values['source']['node_type'] = $node_type;
        $values['destination']['default_bundle'] = $node_type;

        // If this migration is based on the d6_node_revision migration or
        // is for translations of nodes, it should explicitly depend on the
        // corresponding d6_node variant.
        if (in_array($base_plugin_definition['id'], ['d6_node_revision', 'd6_node_translation'])) {
          $values['migration_dependencies']['required'][] = 'd6_node:' . $node_type;
        }

        /** @var \Drupal\migrate\Plugin\Migration $migration */
        $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($values);
        $this->fieldDiscovery->addBundleFieldProcesses($migration, 'node', $node_type);
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
