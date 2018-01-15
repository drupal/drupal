<?php

namespace Drupal\taxonomy;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Defines a storage handler class for taxonomy vocabularies.
 */
class VocabularyStorage extends ConfigEntityStorage implements VocabularyStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('taxonomy_vocabulary_get_names');
    parent::resetCache($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getToplevelTids($vids) {
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vids, 'IN')
      ->condition('parent.target_id', 0)
      ->execute();

    return array_values($tids);
  }

}
