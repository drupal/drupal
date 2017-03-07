<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Profile field source from database.
 *
 * @MigrateSource(
 *   id = "d6_i18n_taxonomy_vocabulary",
 *   source_provider = "taxonomy"
 * )
 */
class I18nTaxonomyVocabulary extends DrupalSqlBase {

  /**
   * The source table containing profile field info.
   *
   * @var string
   */
  protected $fieldTable;

  /**
   * The source table containing the profile values.
   *
   * @var string
   */
  protected $valueTable;

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
    $query->join('i18n_strings', 'i18n', 'i18n.objectid = v.vid');
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
      'translation' => $this->t('Translation of either the title or explanation.')
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
