<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\migrate\D7NodeDeriver.
 */

namespace Drupal\node\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for Drupal 7 node and node revision migrations based on node types.
 */
class D7NodeDeriver extends DeriverBase implements ContainerDeriverInterface {
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
   * D7NodeDeriver constructor.
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
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    try {
      // Read all field instance definitions in the source database.
      $fields = array();
      foreach (static::getSourcePlugin('d7_field_instance') as $row) {
        if ($row->getSourceProperty('entity_type') == 'node') {
          $fields[$row->getSourceProperty('bundle')][$row->getSourceProperty('field_name')] = $row->getSource();
        }
      }

      foreach (static::getSourcePlugin('d7_node_type') as $row) {
        $node_type = $row->getSourceProperty('type');
        $values = $base_plugin_definition;
        $derivative_id = $node_type;

        $values['label'] = t('@label (@type)', [
          '@label' => $values['label'],
          '@type' => $row->getSourceProperty('name')
        ]);
        $values['source']['node_type'] = $node_type;

        $migration = new Migration([], uniqid(), $values);
        if (isset($fields[$node_type])) {
          foreach ($fields[$node_type] as $field_name => $info) {
            $field_type = $info['type'];
            if ($this->cckPluginManager->hasDefinition($field_type)) {
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
      // It is possible no D7 tables are loaded so just eat exceptions.
    }

    return $this->derivatives;
  }


}
