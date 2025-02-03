<?php

declare(strict_types=1);

namespace Drupal\migrate_drupal\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;

// cspell:ignore objectid

/**
 * Gets an i18n translation from the source database.
 */
trait I18nQueryTrait {

  /**
   * The i18n string table name.
   *
   * @var string
   */
  protected string $i18nStringTable;

  /**
   * Gets the translation for the property not already in the row.
   *
   * For some i18n migrations there are two translation values, such as a
   * translated title and a translated description, that need to be retrieved.
   * Since these values are stored in separate rows of the i18nStringTable
   * table we get them individually, one in the source plugin query() and the
   * other in prepareRow(). The names of the properties varies, for example,
   * in BoxTranslation they are 'body' and 'title' whereas in
   * MenuLinkTranslation they are 'title' and 'description'. This will save both
   * translations to the row.
   *
   * @param \Drupal\migrate\Row $row
   *   The current migration row which must include both a 'language' property
   *   and an 'objectid' property. The 'objectid' is the value for the
   *   'objectid' field in the i18n_string table.
   * @param string $property_not_in_row
   *   The name of the property to get the translation for.
   * @param string $object_id_name
   *   The value of the objectid in the i18n table.
   * @param \Drupal\migrate\Plugin\MigrateIdMapInterface $id_map
   *   The ID map.
   *
   * @return bool
   *   FALSE if the property has already been migrated.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function getPropertyNotInRowTranslation(Row $row, string $property_not_in_row, string $object_id_name, MigrateIdMapInterface $id_map): bool {
    $language = $row->getSourceProperty('language');
    if (!$language) {
      throw new MigrateException('No language found.');
    }
    $object_id = $row->getSourceProperty($object_id_name);
    if (!$object_id) {
      throw new MigrateException('No objectid found.');
    }

    // If this row has been migrated it is a duplicate so skip it.
    if ($id_map->lookupDestinationIds([$object_id_name => $object_id, 'language' => $language])) {
      return FALSE;
    }

    // Save the translation for the property already in the row.
    $property_in_row = $row->getSourceProperty('property');
    $row->setSourceProperty($property_in_row . '_translated', $row->getSourceProperty('translation'));

    // Get the translation, if one exists, for the property not already in the
    // row.
    $query = $this->select($this->i18nStringTable, 'i18n')
      ->fields('i18n', ['lid'])
      ->condition('i18n.property', $property_not_in_row)
      ->condition('i18n.objectid', $object_id);
    $query->leftJoin('locales_target', 'lt', '[i18n].[lid] = [lt].[lid]');
    $query->condition('lt.language', $language);
    $query->addField('lt', 'translation');
    $results = $query->execute()->fetchAssoc();
    $row->setSourceProperty($property_not_in_row . '_translated', $results['translation'] ?? NULL);
    return TRUE;
  }

}
