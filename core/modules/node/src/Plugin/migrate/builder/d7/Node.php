<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\migrate\builder\d7\Node.
 */

namespace Drupal\node\Plugin\migrate\builder\d7;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Plugin\migrate\builder\BuilderBase;

/**
 * @PluginID("d7_node")
 */
class Node extends BuilderBase {

  /**
   * {@inheritdoc}
   */
  public function buildMigrations(array $template) {
    $migrations = [];

    $fields = [];
    foreach ($this->getSourcePlugin('d7_field_instance', $template['source']) as $field) {
      $entity_type = $field->getSourceProperty('entity_type');
      $bundle = $field->getSourceProperty('bundle');
      $field_name = $field->getSourceProperty('field_name');
      $fields[$entity_type][$bundle][$field_name] = $field->getSource();
    }

    foreach ($this->getSourcePlugin('d7_node_type', $template['source']) as $node_type) {
      $bundle = $node_type->getSourceProperty('type');
      $values = $template;
      $values['id'] .= '__' . $bundle;
      $values['label'] = $this->t('@label (@type)', ['@label' => $values['label'], '@type' => $node_type->getSourceProperty('name')]);
      $values['source']['node_type'] = $bundle;
      $migration = Migration::create($values);

      if (isset($fields['node'][$bundle])) {
        foreach (array_keys($fields['node'][$bundle]) as $field) {
          $migration->setProcessOfProperty($field, $field);
        }
      }

      $migrations[] = $migration;
    }

    return $migrations;
  }

}
