<?php

namespace Drupal\menu_link_content\Plugin\migrate\source\d6;

use Drupal\content_translation\Plugin\migrate\source\I18nQueryTrait;
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

  use I18nQueryTrait;

  /**
   * Drupal 6 table names.
   */
  const I18N_STRING_TABLE = 'i18n_strings';

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
    $query->leftJoin(static::I18N_STRING_TABLE, 'i18n', 'CAST(ml.mlid as CHAR(255)) = i18n.objectid');
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
    parent::prepareRow($row);

    // Save the translation for this property.
    $property_in_row = $row->getSourceProperty('property');

    // Set the i18n string table for use in I18nQueryTrait.
    $this->i18nStringTable = static::I18N_STRING_TABLE;
    // Get the translation for the property not already in the row and save it
    // in the row.
    $property_not_in_row = ($property_in_row == 'title') ? 'description' : 'title';
    return $this->getPropertyNotInRowTranslation($row, $property_not_in_row, 'mlid', $this->idMap);
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
