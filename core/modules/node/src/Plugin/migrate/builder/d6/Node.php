<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\migrate\builder\d6\Node.
 */

namespace Drupal\node\Plugin\migrate\builder\d6;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\builder\d6\CckBuilder;

/**
 * @PluginID("d6_node")
 */
class Node extends CckBuilder {

  /**
   * Already-instantiated cckfield plugins, keyed by ID.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface[]
   */
  protected $cckPluginCache = [];

  /**
   * Gets a cckfield plugin instance.
   *
   * @param string $field_type
   *   The field type (plugin ID).
   * @param \Drupal\migrate\Entity\MigrationInterface|NULL $migration
   *   The migration, if any.
   *
   * @return \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface
   *   The cckfield plugin instance.
   */
  protected function getCckPlugin($field_type, MigrationInterface $migration = NULL) {
    if (empty($this->cckPluginCache[$field_type])) {
      $this->cckPluginCache[$field_type] = $this->cckPluginManager->createInstance($field_type, [], $migration);
    }
    return $this->cckPluginCache[$field_type];
  }

  /**
   * {@inheritdoc}
   */
  public function buildMigrations(array $template) {
    $migrations = [];

    foreach ($this->getSourcePlugin('d6_node_type', $template['source']) as $row) {
      $node_type = $row->getSourceProperty('type');
      $values = $template;
      $values['id'] = $template['id'] . '__' . $node_type;
      $label = $template['label'];
      $values['label'] = $this->t("@label (@type)", ['@label' => $label, '@type' => $node_type]);
      $values['source']['node_type'] = $node_type;
      $migration = Migration::create($values);

      $fields = $this->getSourcePlugin('d6_field_instance', ['node_type' => $node_type] + $template['source']);
      foreach ($fields as $field) {
        $data = $field->getSource();

        if ($this->cckPluginManager->hasDefinition($data['type'])) {
          $this->getCckPlugin($data['type'])
            ->processCckFieldValues($migration, $data['field_name'], $data);
        }
        else {
          $migration->setProcessOfProperty($data['field_name'], $data['field_name']);
        }
      }

      $migrations[] = $migration;
    }

    return $migrations;
  }

}
