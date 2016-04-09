<?php

namespace Drupal\Core\Entity\Query\Sql;

/**
 * Adds tables and fields to the SQL entity query.
 */
interface TablesInterface {

  /**
   * Adds a field to a database query.
   *
   * @param string $field
   *   If it contains a dot, then field name dot field column. If it doesn't
   *   then entity property name.
   * @param string $type
   *   Join type, can either be INNER or LEFT.
   * @param string $langcode
   *   The language code the field values are to be queried in.
   *
   * @return string
   *   The return value is a string containing the alias of the table, a dot
   *   and the appropriate SQL column as passed in. This allows the direct use
   *   of this in a query for a condition or sort.
   *
   * @throws \Drupal\Core\Entity\Query\QueryException
   *   If $field specifies an invalid relationship.
   */
  public function addField($field, $type, $langcode);

  /**
   * Determines whether the given field is case sensitive.
   *
   * This information can only be provided after it was added with addField().
   *
   * @param string $field_name
   *   The name of the field.
   *
   * @return bool|null
   *   TRUE if the field is case sensitive, FALSE if not. Returns NULL when the
   *   field did not define if it is case sensitive or not.
   */
  public function isFieldCaseSensitive($field_name);

}
