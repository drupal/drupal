<?php

/**
 * @file
 * Definition of Drupal\taxonomy\VocabularyStorageController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Config\Entity\ConfigStorageController;

/**
 * Defines a controller class for taxonomy vocabularies.
 */
class VocabularyStorageController extends ConfigStorageController implements VocabularyStorageControllerInterface {

  /**
   * Overrides Drupal\Core\Config\Entity\ConfigStorageController::resetCache().
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('taxonomy_vocabulary_get_names');
    parent::resetCache($ids);
    cache_invalidate_tags(array('content' => TRUE));
    entity_info_cache_clear();
  }

  /**
   * {@inheritdoc}
   */
  public function getToplevelTids($vids) {
    return db_query('SELECT t.tid FROM {taxonomy_term_data} t INNER JOIN {taxonomy_term_hierarchy} th ON th.tid = t.tid WHERE t.vid IN (:vids) AND th.parent = 0', array(':vids' => $vids))->fetchCol();
  }

}
