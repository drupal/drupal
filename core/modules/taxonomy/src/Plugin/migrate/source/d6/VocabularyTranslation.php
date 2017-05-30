<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 vocabulary translations from source database.
 *
 * @MigrateSource(
 *   id = "d6_taxonomy_vocabulary_translation",
 *   source_provider = "taxonomy"
 * )
 */
class VocabularyTranslation extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('vocabulary', 'v')
      ->fields('v', ['vid', 'name', 'description'])
      ->fields('i18n', ['lid', 'type', 'property', 'objectid'])
      ->fields('lt', ['lid', 'translation'])
      ->condition('i18n.type', 'vocabulary');
    $query->addField('lt', 'language', 'language');
    // The i18n_strings table has two columns containing the object ID, objectid
    // and objectindex. The objectid column is a text field. Therefore, for the
    // join to work in PostgreSQL, use the objectindex field as this is numeric
    // like the vid field.
    $query->join('i18n_strings', 'i18n', 'v.vid = i18n.objectindex');
    $query->leftJoin('locales_target', 'lt', 'lt.lid = i18n.lid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'vid' => $this->t('The vocabulary ID.'),
      'language' => $this->t('Language for this field.'),
      'property' => $this->t('Name of property being translated.'),
      'translation' => $this->t('Translation of either the title or explanation.'),
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
