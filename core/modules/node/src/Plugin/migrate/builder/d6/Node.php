<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\migrate\builder\d6\Node.
 */

namespace Drupal\node\Plugin\migrate\builder\d6;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate_drupal\Plugin\migrate\builder\CckBuilder;

/**
 * @PluginID("d6_node")
 */
class Node extends CckBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildMigrations(array $template) {
    $migrations = [];

    // Read all CCK field instance definitions in the source database.
    $fields = array();
    $source_plugin = $this->getSourcePlugin('d6_field_instance', $template['source']);
    try {
      $source_plugin->checkRequirements();

      foreach ($source_plugin as $field) {
        $info = $field->getSource();
        $fields[$info['type_name']][$info['field_name']] = $info;
      }
    }
    catch (RequirementsException $e) {
      // Don't do anything; $fields will be empty.
    }

    foreach ($this->getSourcePlugin('d6_node_type', $template['source']) as $row) {
      $node_type = $row->getSourceProperty('type');
      $values = $template;
      $values['id'] = $template['id'] . '__' . $node_type;

      $label = $template['label'];
      $values['label'] = $this->t("@label (@type)", ['@label' => $label, '@type' => $node_type]);
      $values['source']['node_type'] = $node_type;

      // If this migration is based on the d6_node_revision template, it should
      // explicitly depend on the corresponding d6_node variant.
      if ($template['id'] == 'd6_node_revision') {
        $values['migration_dependencies']['required'][] = 'd6_node__' . $node_type;
      }

      $migration = Migration::create($values);

      if (isset($fields[$node_type])) {
        foreach ($fields[$node_type] as $field => $info) {
          if ($this->cckPluginManager->hasDefinition($info['type'])) {
            $this->getCckPlugin($info['type'])
              ->processCckFieldValues($migration, $field, $info);
          }
          else {
            $migration->setProcessOfProperty($field, $field);
          }
        }
      }

      $migrations[] = $migration;
    }

    return $migrations;
  }

}
