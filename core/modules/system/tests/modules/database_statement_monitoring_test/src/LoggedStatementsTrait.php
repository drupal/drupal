<?php

namespace Drupal\database_statement_monitoring_test;

@trigger_error('\Drupal\database_statement_monitoring_test\LoggedStatementsTrait is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3318162', E_USER_DEPRECATED);

/**
 * Trait for Connection classes that can store logged statements.
 *
 * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3318162
 */
trait LoggedStatementsTrait {

  /**
   * Logged statements.
   *
   * @var string[]
   */
  protected $loggedStatements;

  /**
   * {@inheritdoc}
   */
  public function query($query, array $args = [], $options = []) {
    // Log the query if it is a string, can receive statement objects e.g
    // in the pgsql driver. These are hard to log as the table name has already
    // been replaced.
    if (is_string($query)) {
      $stringified_args = array_map(function ($v) {
        return is_array($v) ? implode(',', $v) : $v;
      }, $args);
      $this->loggedStatements[] = str_replace(array_keys($stringified_args), array_values($stringified_args), $query);
    }
    return parent::query($query, $args, $options);
  }

  /**
   * Resets logged statements.
   *
   * @return $this
   */
  public function resetLoggedStatements() {
    $this->loggedStatements = [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDriverClass($class) {
    static $fixed_namespace;
    if (!$fixed_namespace) {
      // Override because we've altered the namespace in
      // \Drupal\KernelTests\Core\Cache\EndOfTransactionQueriesTest::getDatabaseConnectionInfo()
      // to use the logging Connection classes. Set to a proper database driver
      // namespace.
      $this->connectionOptions['namespace'] = (new \ReflectionClass(get_parent_class($this)))->getNamespaceName();
      $fixed_namespace = TRUE;
    }
    return parent::getDriverClass($class);
  }

  /**
   * Returns the executed queries.
   *
   * @return string[]
   */
  public function getLoggedStatements() {
    return $this->loggedStatements;
  }

}
