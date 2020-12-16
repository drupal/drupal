<?php

namespace Drupal\menu_link_content\Plugin\migrate\source\d7;

use Drupal\content_translation\Plugin\migrate\source\I18nQueryTrait;
use Drupal\migrate\Row;
use Drupal\menu_link_content\Plugin\migrate\source\MenuLink;

/**
 * Gets Menu link translations from source database.
 *
 * @MigrateSource(
 *   id = "d7_menu_link_translation",
 *   source_module = "i18n_menu"
 * )
 */
class MenuLinkTranslation extends MenuLink {

  use I18nQueryTrait;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    // Add in the property, which is either title or description. Cast the mlid
    // to text so PostgreSQL can make the join.
    $query->leftJoin('i18n_string', 'i18n', 'CAST(ml.mlid AS CHAR(255)) = i18n.objectid');
    $query->fields('i18n', ['lid', 'objectid', 'property', 'textgroup'])
      ->condition('i18n.textgroup', 'menu')
      ->condition('i18n.type', 'item');

    // Add in the translation for the property.
    $query->innerJoin('locales_target', 'lt', 'i18n.lid = lt.lid');
    $query->addField('lt', 'language', 'lt_language');
    $query->fields('lt', ['translation']);
    $query->isNotNull('lt.language');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!parent::prepareRow($row)) {
      return FALSE;
    }

    // Put the language on the row as 'language'.
    $row->setSourceProperty('language', $row->getSourceProperty('lt_language'));

    // Save the translation for this property.
    $property_in_row = $row->getSourceProperty('property');

    // Set the i18n string table for use in I18nQueryTrait.
    $this->i18nStringTable = 'i18n_string';
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
    $ids['language']['alias'] = 'lt';
    return parent::getIds() + $ids;
  }

}
