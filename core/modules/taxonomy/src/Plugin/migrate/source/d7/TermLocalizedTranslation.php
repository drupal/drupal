<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d7;

use Drupal\content_translation\Plugin\migrate\source\I18nQueryTrait;
use Drupal\migrate\Row;

/**
 * Drupal 7 i18n taxonomy terms from source database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\taxonomy\Plugin\migrate\source\d7\Term
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d7_term_localized_translation",
 *   source_module = "i18n_taxonomy"
 * )
 */
class TermLocalizedTranslation extends Term {

  use I18nQueryTrait;

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Ideally, the query would return rows for each language for each taxonomy
    // term with the translations for both the name and description or just the
    // name translation or just the description translation. That query quickly
    // became complex and would be difficult to maintain.
    // Therefore, build a query based on i18nstrings table where each row has
    // the translation for only one property, either name or description. The
    // method prepareRow() is then used to obtain the translation for the other
    // property.
    $query = parent::query();
    $query->addField('td', 'language', 'td.language');

    // Add in the property, which is either name or description.
    // Cast td.tid as char for PostgreSQL compatibility.
    $query->leftJoin('i18n_string', 'i18n', 'CAST([td].[tid] AS CHAR(255)) = [i18n].[objectid]');
    $query->condition('i18n.type', 'term');
    $query->addField('i18n', 'lid');
    $query->addField('i18n', 'property');

    // Add in the translation for the property.
    $query->innerJoin('locales_target', 'lt', '[i18n].[lid] = [lt].[lid]');
    $query->addField('lt', 'language', 'lt.language');
    $query->addField('lt', 'translation');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!parent::prepareRow($row)) {
      return FALSE;
    }

    // Override language with ltlanguage.
    $language = $row->getSourceProperty('ltlanguage');
    $row->setSourceProperty('language', $language);

    // Set the i18n string table for use in I18nQueryTrait.
    $this->i18nStringTable = 'i18n_string';

    // Save the translation for the property already in the row.
    $property_in_row = $row->getSourceProperty('property');

    // Get the translation for the property not already in the row and save it
    // in the row.
    $property_not_in_row = ($property_in_row == 'name') ? 'description' : 'name';
    return $this->getPropertyNotInRowTranslation($row, $property_not_in_row, 'tid', $this->idMap);
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
    $ids['language']['alias'] = 'lt';
    return parent::getIds() + $ids;
  }

}
