<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\EntityTaxonomyTerm.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\migrate\Row;

/**
 * @MigrateDestination(
 *   id = "entity:taxonomy_term"
 * )
 */
class EntityTaxonomyTerm extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntity(Row $row, array $old_destination_id_values) {
    if ($row->stub()) {
      $row->setDestinationProperty('name', $this->t('Stub name for source tid:') . $row->getSourceProperty('tid'));
    }
    return parent::getEntity($row, $old_destination_id_values);
  }

}
