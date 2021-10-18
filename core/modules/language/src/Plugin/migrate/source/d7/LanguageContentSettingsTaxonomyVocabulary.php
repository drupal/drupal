<?php

namespace Drupal\language\Plugin\migrate\source\d7;

use Drupal\taxonomy\Plugin\migrate\source\d7\Vocabulary;

/**
 * Drupal 7 i18n vocabularies source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBas
 *
 * @MigrateSource(
 *   id = "d7_language_content_settings_taxonomy_vocabulary",
 *   source_module = "i18n_taxonomy"
 * )
 */
class LanguageContentSettingsTaxonomyVocabulary extends Vocabulary {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    if ($this->getDatabase()
      ->schema()
      ->fieldExists('taxonomy_vocabulary', 'i18n_mode')) {
      $query->addField('v', 'language');
      $query->addField('v', 'i18n_mode');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['language'] = $this->t('i18n language');
    $fields['i18n_mode'] = $this->t('i18n mode');
    return $fields;
  }

}
