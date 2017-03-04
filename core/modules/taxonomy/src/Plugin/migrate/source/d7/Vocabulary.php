<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 vocabularies source from database.
 *
 * @MigrateSource(
 *   id = "d7_taxonomy_vocabulary",
 *   source_provider = "taxonomy"
 * )
 */
class Vocabulary extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('taxonomy_vocabulary', 'v')
      ->fields('v', [
        'vid',
        'name',
        'description',
        'hierarchy',
        'module',
        'weight',
        'machine_name',
      ]);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'vid' => $this->t('The vocabulary ID.'),
      'name' => $this->t('The name of the vocabulary.'),
      'description' => $this->t('The description of the vocabulary.'),
      'hierarchy' => $this->t('The type of hierarchy allowed within the vocabulary. (0 = disabled, 1 = single, 2 = multiple)'),
      'module' => $this->t('Module responsible for the vocabulary.'),
      'weight' => $this->t('The weight of the vocabulary in relation to other vocabularies.'),
      'machine_name' => $this->t('Unique machine name of the vocabulary.')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['vid']['type'] = 'integer';
    return $ids;
  }

}
