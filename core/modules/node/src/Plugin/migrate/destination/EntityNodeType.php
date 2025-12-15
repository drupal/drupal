<?php

namespace Drupal\node\Plugin\migrate\destination;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Row;

/**
 * Migration destination for node type entity.
 */
#[MigrateDestination('entity:node_type')]
class EntityNodeType extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  public function getEntity(Row $row, array $old_destination_id_values) {
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = parent::getEntity($row, $old_destination_id_values);

    // Config schema does not allow description or help text to be empty.
    if ($node_type->getDescription() === '') {
      $node_type->set('description', NULL);
    }
    if ($node_type->getHelp() === '') {
      $node_type->set('help', NULL);
    }
    return $node_type;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3533565
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533565', E_USER_DEPRECATED);
    $entity_ids = parent::import($row, $old_destination_id_values);
    if ($row->getDestinationProperty('create_body')) {
      $node_type = $this->storage->load(reset($entity_ids));
      $field_storage = FieldStorageConfig::loadByName('node', 'body');
      if (!$field_storage) {
        FieldStorageConfig::create([
          'field_name' => 'body',
          'type' => 'text_with_summary',
          'entity_type' => 'node',
          'cardinality' => 1,
          'persist_with_no_fields' => FALSE,
        ])->save();
        $field_storage = FieldStorageConfig::loadByName('node', 'body');
      }
      $field = FieldConfig::loadByName('node', $node_type->id(), 'body');

      if (!$field) {
        $field = FieldConfig::create([
          'field_storage' => $field_storage,
          'bundle' => $node_type->id(),
          'label' => $row->getDestinationProperty('create_body_label'),
          'settings' => [
            'display_summary' => TRUE,
            'allowed_formats' => [],
          ],
        ]);
        $field->save();

        /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
        $display_repository = \Drupal::service('entity_display.repository');

        // Assign widget settings for the default form mode.
        $display_repository->getFormDisplay('node', $node_type->id())
          ->setComponent('body', [
            'type' => 'text_textarea_with_summary',
          ])
          ->save();

        // Assign display settings for the 'default' and 'teaser' view modes.
        $display_repository->getViewDisplay('node', $node_type->id())
          ->setComponent('body', [
            'label' => 'hidden',
            'type' => 'text_default',
          ])
          ->save();

        // The teaser view mode is created by the Standard profile and might not
        // exist.
        $view_modes = $display_repository->getViewModes('node');
        if (isset($view_modes['teaser'])) {
          $display_repository->getViewDisplay('node', $node_type->id(), 'teaser')
            ->setComponent('body', [
              'label' => 'hidden',
              'type' => 'text_summary_or_trimmed',
            ])
            ->save();
        }
      }
    }
    return $entity_ids;
  }

}
