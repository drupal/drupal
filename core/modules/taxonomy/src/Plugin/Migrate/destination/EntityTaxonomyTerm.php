<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\migrate\destination\EntityTaxonomyTerm.
 */

namespace Drupal\taxonomy\Plugin\migrate\destination;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;

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
    if ($row->isStub()) {
      $row->setDestinationProperty('name', $this->t('Stub name for source tid:') . $row->getSourceProperty('tid'));
    }
    return parent::getEntity($row, $old_destination_id_values);
  }

}
