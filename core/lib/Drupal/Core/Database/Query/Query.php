<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;

/**
 * Base class for query builders.
 *
 * Note that query builders use PHP's magic __toString() method to compile the
 * query object into a prepared statement.
 */
abstract class Query implements PlaceholderInterface {

  /**
   * The connection object on which to run this query.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The target of the connection object.
   *
   * @var string
   */
  protected $connectionTarget;

  /**
   * The key of the connection object.
   *
   * @var string
   */
  protected $connectionKey;

  /**
   * The query options to pass on to the connection object.
   *
   * @var array
   */
  protected $queryOptions;

  /**
   * A unique identifier for this query object.
   */
  protected $uniqueIdentifier;

  /**
   * The placeholder counter.
   */
  protected $nextPlaceholder = 0;

  /**
   * An array of comments that can be prepended to a query.
   *
   * @var array
   */
  protected $comments = [];

  /**
   * Constructs a Query object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   * @param array $options
   *   Array of query options.
   */
  public function __construct(Connection $connection, $options) {
    $this->uniqueIdentifier = uniqid('', TRUE);

    $this->connection = $connection;
    $this->connectionKey = $this->connection->getKey();
    $this->connectionTarget = $this->connection->getTarget();

    $this->queryOptions = $options;
  }

  /**
   * Implements the magic __sleep function to disconnect from the database.
   */
  public function __sleep() {
    $keys = get_object_vars($this);
    unset($keys['connection']);
    return array_keys($keys);
  }

  /**
   * Implements the magic __wakeup function to reconnect to the database.
   */
  public function __wakeup() {
    $this->connection = Database::getConnection($this->connectionTarget, $this->connectionKey);
  }

  /**
   * Implements the magic __clone function.
   */
  public function __clone() {
    $this->uniqueIdentifier = uniqid('', TRUE);
  }

  /**
   * Runs the query against the database.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   A prepared statement, or NULL if the query is not valid.
   */
  abstract protected function execute();

  /**
   * Implements PHP magic __toString method to convert the query to a string.
   *
   * The toString operation is how we compile a query object to a prepared
   * statement.
   *
   * @return string
   *   A prepared statement query string for this object.
   */
  abstract public function __toString();

  /**
   * Returns a unique identifier for this object.
   */
  public function uniqueIdentifier() {
    return $this->uniqueIdentifier;
  }

  /**
   * Gets the next placeholder value for this query object.
   *
   * @return int
   *   The next placeholder value.
   */
  public function nextPlaceholder() {
    return $this->nextPlaceholder++;
  }

  /**
   * Adds a comment to the query.
   *
   * By adding a comment to a query, you can more easily find it in your
   * query log or the list of active queries on an SQL server. This allows
   * for easier debugging and allows you to more easily find where a query
   * with a performance problem is being generated.
   *
   * The comment string will be sanitized to remove * / and other characters
   * that may terminate the string early so as to avoid SQL injection attacks.
   *
   * @param $comment
   *   The comment string to be inserted into the query.
   *
   * @return $this
   */
  public function comment($comment) {
    $this->comments[] = $comment;
    return $this;
  }

  /**
   * Returns a reference to the comments array for the query.
   *
   * Because this method returns by reference, alter hooks may edit the comments
   * array directly to make their changes. If just adding comments, however, the
   * use of comment() is preferred.
   *
   * Note that this method must be called by reference as well:
   * @code
   * $comments =& $query->getComments();
   * @endcode
   *
   * @return array
   *   A reference to the comments array structure.
   */
  public function &getComments() {
    return $this->comments;
  }

}
