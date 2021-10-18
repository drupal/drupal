<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d7;

use Drupal\migrate\Row;

/**
 * Drupal 7 i18n taxonomy terms source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\taxonomy\Plugin\migrate\source\d7\Term
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d7_taxonomy_term_translation",
 *   source_module = "i18n_taxonomy"
 * )
 */
class TermTranslation extends Term {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    if ($this->database->schema()->fieldExists('taxonomy_term_data', 'language')) {
      $query->addField('td', 'language', 'td_language');
    }
    // Get data when the i18n_mode column exists and it is not the Drupal 7
    // value I18N_MODE_NONE or I18N_MODE_LOCALIZE. Otherwise, return no data.
    // @see https://git.drupalcode.org/project/i18n/-/blob/7.x-1.x/i18n.module#L26
    if ($this->database->schema()->fieldExists('taxonomy_vocabulary', 'i18n_mode')) {
      $query->addField('tv', 'i18n_mode');
      $query->condition('tv.i18n_mode', ['0', '1'], 'NOT IN');
    }
    else {
      $query->alwaysFalse();
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
