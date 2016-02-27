<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\migrate\D6NodeDeriver.
 */

namespace Drupal\node\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for Drupal 6 node and node revision migrations based on node types.
 */
class D6NodeDeriver extends DeriverBase implements ContainerDeriverInterface {
  use MigrationDeriverTrait;

  /**
   * @var bool
   */
  protected $init = FALSE;

  /**
   * Already-instantiated cckfield plugins, keyed by ID.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface[]
   */
  protected $cckPluginCache;

  /**
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $cckPluginManager;

  /**
   * D6NodeDeriver constructor.
   *
   * @param $base_plugin_id
   * @param \Drupal\Component\Plugin\PluginManagerInterface $cck_manager
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
   * @return array
   *   An array of full derivative definitions keyed on derivative id.
   *
   * @see getDerivativeDefinition()
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Read all CCK field instance definitions in the source database.
    $fields = array();
    $source_plugin = static::getSourcePlugin('d6_field_instance');
    try {
      $source_plugin->checkRequirements();

      foreach ($source_plugin as $row) {
        $fields[$row->getSourceProperty('type_name')][$row->getSourceProperty('field_name')] = $row->getSource();
      }
    }
    catch (RequirementsException $e) {
      // Don't do anything; $fields will be empty.
    }

    try {
      foreach (static::getSourcePlugin('d6_node_type') as $row) {
        $node_type = $row->getSourceProperty('type');
        $values = $base_plugin_definition;
        $derivative_id = $node_type;

        $label = $base_plugin_definition['label'];
        $values['label'] = t("@label (@type)", [
          '@label' => $label,
          '@type' => $node_type
        ]);
        $values['source']['node_type'] = $node_type;

        // If this migration is based on the d6_node_revision template, it should
        // explicitly depend on the corresponding d6_node variant.
        if ($base_plugin_definition['id'] == 'd6_node_revision') {
          $values['migration_dependencies']['required'][] = 'd6_node:' . $node_type;
        }

        $migration = new Migration([], uniqid(), $values);
        if (isset($fields[$node_type])) {
          foreach ($fields[$node_type] as $field_name => $info) {
            $field_type = $info['type'];
            if ($this->cckPluginManager->hasDefinition($info['type'])) {
              if (!isset($this->cckPluginCache[$field_type])) {
                $this->cckPluginCache[$field_type] = $this->cckPluginManager->createInstance($field_type, [], $migration);
              }
              $this->cckPluginCache[$field_type]
                ->processCckFieldValues($migration, $field_name, $info);
            }
            else {
              $migration->setProcessOfProperty($field_name, $field_name);
            }
          }
        }
        $this->derivatives[$derivative_id] = $migration->getPluginDefinition();
      }
    }
    catch (\Exception $e) {
      // @TODO https://www.drupal.org/node/2666640
    }
    return $this->derivatives;
  }

}
