<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d7;

use Drupal\content_translation\Plugin\migrate\source\I18nQueryTrait;
use Drupal\migrate\Row;

/**
 * Gets i18n taxonomy terms from source database.
 *
 * @MigrateSource(
 *   id = "d7_taxonomy_term_translation",
 *   source_module = "i18n_taxonomy"
 * )
 */
class TermTranslation extends Term {

  use I18nQueryTrait;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    if ($this->database->schema()->fieldExists('taxonomy_term_data', 'language')) {
      $query->addField('td', 'language', 'td_language');
    }
    if ($this->database->schema()->fieldExists('taxonomy_vocabulary', 'i18n_mode')) {
      $query->addField('tv', 'i18n_mode');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!parent::prepareRow($row)) {
      return FALSE;
    }
    $row->setSourceProperty('language', $row->getSourceProperty('td_language'));
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'language' => $this->t('Language for this term.'),
      'name_translated' => $this->t('Term name translation.'),
      'description_translated' => $this->t('Term description translation.'),
    ];
    return parent::fields() + $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['language']['type'] = 'string';
    $ids['language']['alias'] = 'td';
    return parent::getIds() + $ids;
  }

}
