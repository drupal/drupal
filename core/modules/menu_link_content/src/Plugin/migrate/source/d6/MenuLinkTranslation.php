<?php

namespace Drupal\menu_link_content\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\menu_link_content\Plugin\migrate\source\MenuLink;

/**
 * Gets Menu link translations from source database.
 *
 * @MigrateSource(
 *   id = "d6_menu_link_translation",
 *   source_module = "i18nmenu"
 * )
 */
class MenuLinkTranslation extends MenuLink {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Ideally, the query would return rows for each language for each menu link
    // with the translations for both the title and description or just the
    // title translation or just the description translation. That query quickly
    // became complex and would be difficult to maintain.
    // Therefore, build a query based on i18nstrings table where each row has
    // the translation for only one property, either title or description. The
    // method prepareRow() is then used to obtain the translation for the other
    // property.
    // The query starts with the same query as menu_link.
    $query = parent::query();

    // Add in the property, which is either title or description. Cast the mlid
    // to text so PostgreSQL can make the join.
    $query->leftJoin('i18n_strings', 'i18n', 'CAST(ml.mlid as CHAR(255)) = i18n.objectid');
    $query->isNotNull('i18n.lid');
    $query->addField('i18n', 'lid');
    $query->addField('i18n', 'property');

    // Add in the translation for the property.
    $query->innerJoin('locales_target', 'lt', 'i18n.lid = lt.lid');
    $query->addField('lt', 'language');
    $query->addField('lt', 'translation');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $language = $row->getSourceProperty('language');
    $mlid = $row->getSourceProperty('mlid');

    // If this row has been migrated it is a duplicate then skip it.
    if ($this->idMap->lookupDestinationIds(['mlid' => $mlid, 'language' => $language])) {
      return FALSE;
    }

    // Save the translation for this property.
    $property = $row->getSourceProperty('property');
    $row->setSourceProperty($property . '_translated', $row->getSourceProperty('translation'));

    // Get the translation, if one exists, for the property not already in the
    // row.
    $other_property = ($property == 'title') ? 'description' : 'title';
    $query = $this->select('i18n_strings', 'i18n')
      ->fields('i18n', ['lid'])
      ->condition('i18n.property', $other_property)
      ->condition('i18n.objectid', $mlid);
    $query->leftJoin('locales_target', 'lt', 'i18n.lid = lt.lid');
    $query->condition('lt.language', $language);
    $query->addField('lt', 'translation');
    $results = $query->execute()->fetchAssoc();
    $row->setSourceProperty($other_property . '_translated', $results['translation']);
    parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'language' => $this->t('Language for this menu.'),
      'title_translated' => $this->t('Menu link title translation.'),
      'description_translated' => $this->t('Menu link description translation.'),
    ];
    return parent::fields() + $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['language']['type'] = 'string';
    return parent::getIds() + $ids;
  }

}
