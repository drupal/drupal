<?php

declare(strict_types=1);

namespace Drupal\taxonomy\Plugin\migrate\destination;

use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Row;

/**
 * Migration destination for taxonomy vocabulary.
 */
#[MigrateDestination('entity:taxonomy_vocabulary')]
class EntityTaxonomyVocabulary extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  public function getEntity(Row $row, array $old_destination_id_values) {
    /** @var \Drupal\taxonomy\VocabularyInterface $vocabulary */
    $vocabulary = parent::getEntity($row, $old_destination_id_values);

    // Config schema does not allow description to be empty.
    if (trim($vocabulary->getDescription()) === '') {
      $vocabulary->set('description', NULL);
    }
    return $vocabulary;
  }

}
