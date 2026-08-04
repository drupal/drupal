<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

/**
 * Enumeration of cases for column size.
 *
 * It represents a hint about the largest value a column will store and
 * determines which of the database engine specific data types will be used
 * (e.g. on MySQL, TINYINT vs. INT vs. BIGINT). 'normal', the default, selects
 * the base type (e.g. on MySQL, INT, VARCHAR, BLOB, etc.). Not all sizes are
 * available for all data types. See DatabaseSchema::getFieldTypeMap() for
 * possible combinations.
 */
enum ColumnSize: string {

  case Normal = 'normal';

  case Tiny = 'tiny';
  case Small = 'small';
  case Medium = 'medium';
  case Big = 'big';

}
