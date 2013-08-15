<?php

/**
 * @file
 * Definition of Drupal\Core\Database\Query\ExtendableInterface
 */

namespace Drupal\Core\Database\Query;

/**
 * Interface for extendable query objects.
 *
 * "Extenders" follow the "Decorator" OOP design pattern.  That is, they wrap
 * and "decorate" another object.  In our case, they implement the same interface
 * as select queries and wrap a select query, to which they delegate almost all
 * operations.  Subclasses of this class may implement additional methods or
 * override existing methods as appropriate.  Extenders may also wrap other
 * extender objects, allowing for arbitrarily complex "enhanced" queries.
 */
interface ExtendableInterface {

  /**
   * Enhance this object by wrapping it in an extender object.
   *
   * @param $extender_name
   *   The base name of the extending class.  The base name will be checked
   *   against the current database connection to allow driver-specific subclasses
   *   as well, using the same logic as the query objects themselves.
   * @return \Drupal\Core\Database\Query\ExtendableInterface
   *   The extender object, which now contains a reference to this object.
   */
  public function extend($extender_name);
}
