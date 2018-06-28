<?php

namespace Drupal\block_content\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Gets Drupal 6 i18n custom block translations from database.
 *
 * @MigrateSource(
 *   id = "d6_box_translation",
 *   source_module = "i18nblocks"
 * )
 */
class BoxTranslation extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Build a query based on i18n_strings table where each row has the
    // translation for only one property, either title or description. The
    // method prepareRow() is then used to obtain the translation for the
    // other property.
    $query = $this->select('boxes', 'b')
      ->fields('b', ['bid', 'format', 'body'])
      ->fields('i18n', ['property'])
      ->fields('lt', ['lid', 'translation', 'language'])
      ->orderBy('b.bid')
      ->isNotNull('lt.lid');

    // Use 'title' for the info field to match the property name in the
    // i18n_strings table.
    $query->addField('b', 'info', 'title');

    // Add in the property, which is either title or body. Cast the bid to text
    // so PostgreSQL can make the join.
    $query->leftJoin('i18n_strings', 'i18n', 'i18n.objectid = CAST(b.bid as CHAR(255))');
    $query->condition('i18n.type', 'block');

    // Add in the translation for the property.
    $query->leftJoin('locales_target', 'lt', 'lt.lid = i18n.lid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $language = $row->getSourceProperty('language');
    $bid = $row->getSourceProperty('bid');

    // If this row has been migrated it is a duplicate then skip it.
    if ($this->idMap->lookupDestinationIds(['bid' => $bid, 'language' => $language])) {
      return FALSE;
    }

    // Save the translation for this property.
    $property = $row->getSourceProperty('property');
    $row->setSourceProperty($property . '_translated', $row->getSourceProperty('translation'));

    // Get the translation for the property not already in the row.
    $translation = ($property === 'title') ? 'body' : 'title';
    $query = $this->select('i18n_strings', 'i18n')
      ->fields('i18n', ['lid'])
      ->condition('i18n.property', $translation)
      ->condition('i18n.objectid', $bid);
    $query->leftJoin('locales_target', 'lt', 'i18n.lid = lt.lid');
    $query->condition('lt.language', $language)
      ->addField('lt', 'translation');
    $results = $query->execute()->fetchAssoc();
    if (!$results) {
      $row->setSourceProperty($translation . '_translated', NULL);
    }
    else {
      $row->setSourceProperty($translation . '_translated', $results['translation']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'bid' => $this->t('The block numeric identifier.'),
      'format' => $this->t('Input format of the custom block/box content.'),
      'lid' => $this->t('i18n_string table id'),
      'language' => $this->t('Language for this field.'),
      'property' => $this->t('Block property'),
      'translation' => $this->t('The translation of the value of "property".'),
      'title' => $this->t('Block title.'),
      'title_translated' => $this->t('Block title translation.'),
      'body' => $this->t('Block body.'),
      'body_translated' => $this->t('Block body translation.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['bid']['type'] = 'integer';
    $ids['bid']['alias'] = 'b';
    $ids['language']['type'] = 'string';
    return $ids;
  }

}
