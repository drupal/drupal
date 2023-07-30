<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d7;

// cspell:ignore objectid objectindex

/**
 * Drupal 7 i18n vocabulary translations source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d7_taxonomy_vocabulary_translation",
 *   source_module = "i18n_taxonomy"
 * )
 */
class VocabularyTranslation extends Vocabulary {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->leftJoin('i18n_string', 'i18n', 'CAST ([v].[vid] AS CHAR(222)) = [i18n].[objectid]');
    $query->innerJoin('locales_target', 'lt', '[lt].[lid] = [i18n].[lid]');
    $query
      ->condition('type', 'vocabulary')
      ->fields('lt')
      ->fields('i18n');
    $query->addField('lt', 'lid', 'lt_lid');

    if ($this->getDatabase()
      ->schema()
      ->fieldExists('taxonomy_vocabulary', 'language')) {
      $query->addField('v', 'language', 'v_language');
    }
    if ($this->getDatabase()
      ->schema()
      ->fieldExists('taxonomy_vocabulary', 'i18n_mode')) {
      $query->addField('v', 'i18n_mode');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'i18n_mode' => $this->t('Internationalization mode.'),
      'v_language' => $this->t('Language from the taxonomy_vocabulary table.'),
      'property' => $this->t('Name of property being translated.'),
      'type' => $this->t('Name of property being translated.'),
      'objectid' => $this->t('Name of property being translated.'),
      'lt_lid' => $this->t('Name of property being translated.'),
      'translation' => $this->t('Translation of either the name or the description.'),
      'lid' => $this->t('Language string ID'),
      'textgroup' => $this->t('A module defined group of translations'),
      'context' => $this->t('Full string ID for quick search: type:objectid:property.'),
      'objectindex' => $this->t('Integer value of Object ID'),
      'format' => $this->t('The {filter_format}.format of the string'),
      'language' => $this->t('Language code from locales_target table'),
      'plid' => $this->t('Parent lid'),
      'plural' => $this->t('Plural index number'),
      'i18n_status' => $this->t('Translation needs update'),
    ];
    return parent::fields() + $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [];
    $ids['language']['type'] = 'string';
    $ids['language']['alias'] = 'lt';
    $ids['property']['type'] = 'string';
    return parent::getIds() + $ids;
  }

}
