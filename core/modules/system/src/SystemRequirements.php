<?php

namespace Drupal\system;

/**
 * Class for helper methods used for the system requirements.
 */
class SystemRequirements {

  /**
   * Determines whether the passed in PHP version disallows multiple statements.
   *
   * @param string $phpversion
   *
   * @return bool
   */
  public static function phpVersionWithPdoDisallowMultipleStatements($phpversion) {
    // PDO::MYSQL_ATTR_MULTI_STATEMENTS was introduced in PHP versions 5.5.21
    // and 5.6.5.
    return (version_compare($phpversion, '5.5.21', '>=') && version_compare($phpversion, '5.6.0', '<'))
      || version_compare($phpversion, '5.6.5', '>=');
  }

}
