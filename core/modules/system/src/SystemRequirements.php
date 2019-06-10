<?php

namespace Drupal\system;

@trigger_error(__NAMESPACE__ . '\SystemRequirements is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. All supported PHP versions support disabling multi-statement queries in MySQL. See https://www.drupal.org/node/3054692', E_USER_DEPRECATED);

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
   *
   * @deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. All
   *   supported PHP versions support disabling multi-statement queries in
   *   MySQL.
   *
   * @see https://www.drupal.org/node/3054692
   */
  public static function phpVersionWithPdoDisallowMultipleStatements($phpversion) {
    @trigger_error(__NAMESPACE__ . '\SystemRequirements::phpVersionWithPdoDisallowMultipleStatements() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. All supported PHP versions support disabling multi-statement queries in MySQL. See https://www.drupal.org/node/3054692', E_USER_DEPRECATED);
    // PDO::MYSQL_ATTR_MULTI_STATEMENTS was introduced in PHP versions 5.5.21
    // and 5.6.5.
    return (version_compare($phpversion, '5.5.21', '>=') && version_compare($phpversion, '5.6.0', '<'))
      || version_compare($phpversion, '5.6.5', '>=');
  }

}
