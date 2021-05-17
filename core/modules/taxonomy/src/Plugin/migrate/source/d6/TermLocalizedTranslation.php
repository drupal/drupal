<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d6;

use Drupal\migrate\Row;

/**
 * Gets i18n taxonomy terms from source database.
 *
 * For available configuration keys, refer to the parent classes:
 * @see \Drupal\taxonomy\Plugin\migrate\source\d6\Term
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d6_term_localized_translation",
 *   source_module = "i18ntaxonomy"
 * )
 */
class TermLocalizedTranslation extends Term {

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
    $query->leftJoin('i18n_strings', 'i18n', 'CAST([td].[tid] AS CHAR(255)) = [i18n].[objectid]');
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
    $language = $row->getSourceProperty('ltlanguage');
    $row->setSourceProperty('language', $language);
    $tid = $row->getSourceProperty('tid');

    // If this row has been migrated it is a duplicate then skip it.
    if ($this->idMap->lookupDestinationIds(['tid' => $tid, 'language' => $language])) {
      return FALSE;
    }

    // Save the translation for this property.
    $property = $row->getSourceProperty('property');
    $row->setSourceProperty($property . '_translated', $row->getSourceProperty('translation'));

    // Get the translation, if one exists, for the property not already in the
    // row.
    $other_property = ($property == 'name') ? 'description' : 'name';
    $query = $this->select('i18n_strings', 'i18n')
      ->fields('i18n', ['lid'])
      ->condition('i18n.type', 'term')
      ->condition('i18n.property', $other_property)
      ->condition('i18n.objectid', $tid);
    $query->leftJoin('locales_target', 'lt', '[i18n].[lid] = [lt].[lid]');
    $query->condition('lt.language', $language);
    $query->addField('lt', 'translation');
    $results = $query->execute()->fetchAssoc();
    if ($results) {
      $row->setSourceProperty($other_property . '_translated', $results['translation']);
    }
    else {
      // The translation does not exist.
      $row->setSourceProperty($other_property . '_translated', NULL);
    }

    return parent::prepareRow($row);
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
