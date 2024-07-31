<?php

namespace Drupal\sqlite\Driver\Database\sqlite;

use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Database\StatementPrefetchIterator;

/**
 * SQLite implementation of \Drupal\Core\Database\Statement.
 *
 * The PDO SQLite driver only closes SELECT statements when the PDOStatement
 * destructor is called and SQLite does not allow data change (INSERT,
 * UPDATE etc) on a table which has open SELECT statements. This is a
 * user-space mock of PDOStatement that buffers all the data and doesn't
 * have those limitations.
 */
class Statement extends StatementPrefetchIterator implements StatementInterface {

  /**
   * {@inheritdoc}
   *
   * The PDO SQLite layer doesn't replace numeric placeholders in queries
   * correctly, and this makes numeric expressions (such as COUNT(*) >= :count)
   * fail. We replace numeric placeholders in the query ourselves to work
   * around this bug.
   *
   * See http://bugs.php.net/bug.php?id=45259 for more details.
   */
  protected function getStatement(string $query, ?array &$args = []): object {
    if (is_array($args) && !empty($args)) {
      // Check if $args is a simple numeric array.
      if (range(0, count($args) - 1) === array_keys($args)) {
        // In that case, we have unnamed placeholders.
        $count = 0;
        $new_args = [];
        foreach ($args as $value) {
          if (is_float($value) || is_int($value)) {
            if (is_float($value)) {
              // Force the conversion to float so as not to loose precision
              // in the automatic cast.
              $value = sprintf('%F', $value);
            }
            $query = substr_replace($query, $value, strpos($query, '?'), 1);
          }
          else {
            $placeholder = ':db_statement_placeholder_' . $count++;
            $query = substr_replace($query, $placeholder, strpos($query, '?'), 1);
            $new_args[$placeholder] = $value;
          }
        }
        $args = $new_args;
      }
      else {
        // Else, this is using named placeholders.
        foreach ($args as $placeholder => $value) {
          if (is_float($value) || is_int($value)) {
            if (is_float($value)) {
              // Force the conversion to float so as not to loose precision
              // in the automatic cast.
              $value = sprintf('%F', $value);
            }

            // We will remove this placeholder from the query as PDO throws an
            // exception if the number of placeholders in the query and the
            // arguments does not match.
            unset($args[$placeholder]);
            // PDO allows placeholders to not be prefixed by a colon. See
            // http://marc.info/?l=php-internals&m=111234321827149&w=2 for
            // more.
            if ($placeholder[0] != ':') {
              $placeholder = ":$placeholder";
            }
            // When replacing the placeholders, make sure we search for the
            // exact placeholder. For example, if searching for
            // ':db_placeholder_1', do not replace ':db_placeholder_11'.
            $query = preg_replace('/' . preg_quote($placeholder, NULL) . '\b/', $value, $query);
          }
        }
      }
    }

    return $this->clientConnection->prepare($query);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($args = [], $options = []) {
    try {
      $return = parent::execute($args, $options);
    }
    catch (\PDOException $e) {
      // The database schema might be changed by another process in between the
      // time that the statement was prepared and the time the statement was run
      // (e.g. usually happens when running tests). In this case, we need to
      // re-run the query.
      // @see http://www.sqlite.org/faq.html#q15
      // @see http://www.sqlite.org/rescode.html#schema
      if (!empty($e->errorInfo[1]) && $e->errorInfo[1] === 17) {
        // The schema has changed. SQLite specifies that we must resend the query.
        $return = parent::execute($args, $options);
      }
      else {
        // Rethrow the exception.
        throw $e;
      }
    }

    return $return;
  }

}
