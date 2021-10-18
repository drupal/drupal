<?php

namespace Drupal\language\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 i18n vocabularies source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBas
 *
 * @MigrateSource(
 *   id = "d6_language_content_settings_taxonomy_vocabulary",
 *   source_module = "taxonomy"
 * )
 */
class LanguageContentSettingsTaxonomyVocabulary extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('vocabulary', 'v')
      ->fields('v', ['vid']);
    if ($this->getDatabase()
      ->schema()
      ->fieldExists('vocabulary', 'language')) {
      $query->addField('v', 'language');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'vid' => $this->t('The vocabulary ID.'),
      'language' => $this->t('The default language for new terms.'),
      'state' => $this->t('The i18n taxonomy translation setting.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get the i18n taxonomy translation setting for this vocabulary.
    // 0 - No multilingual options
    // 1 - Localizable terms. Run through the localization system.
    // 2 - Predefined language for a vocabulary and its terms.
    // 3 - Per-language terms, translatable (referencing terms with different
    // languages) but not localizable.
    $i18ntaxonomy_vocabulary = $this->variableGet('i18ntaxonomy_vocabulary', []);
    $vid = $row->getSourceProperty('vid');
    $state = 0;
    if (array_key_exists($vid, $i18ntaxonomy_vocabulary)) {
      $state = $i18ntaxonomy_vocabulary[$vid];
    }
    $row->setSourceProperty('state', $state);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['vid']['type'] = 'integer';
    return $ids;
  }

}
