<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Statement;

/**
 * Enumeration of the fetch modes for result sets.
 */
enum FetchAs {

  // Returns an anonymous object with property names that correspond to the
  // column names returned in the result set. This is the default fetch mode
  // for Drupal.
  case Object;

  // Returns a new instance of a requested class, mapping the columns of the
  // result set to named properties in the class.
  case ClassObject;

  // Returns an array indexed by column name as returned in the result set.
  case Associative;

  // Returns an array indexed by column number as returned in the result set,
  // starting at column 0.
  case List;

  // Returns a single column from the next row of a result set.
  case Column;

}
