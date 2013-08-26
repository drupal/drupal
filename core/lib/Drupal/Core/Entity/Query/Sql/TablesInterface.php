<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\Sql\TablesInterface.
 */

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
   * @param $langcode
   *   The language code the field values are to be shown in.
   *
   * @throws \Drupal\Core\Entity\Query\QueryException
   *   If $field specifies an invalid relationship.
   *
   * @return string
   *   The return value is a string containing the alias of the table, a dot
   *   and the appropriate SQL column as passed in. This allows the direct use
   *   of this in a query for a condition or sort.
   */
  public function addField($field, $type, $langcode);

}
