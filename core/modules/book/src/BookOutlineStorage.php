<?php

/**
 * @file
 * Definition of Drupal\book\BookOutlineStorage.
 */

namespace Drupal\book;

use Drupal\Core\Database\Connection;

/**
 * Defines a storage class for books outline.
 */
class BookOutlineStorage implements BookOutlineStorageInterface {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a BookOutlineStorage object.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function getBooks() {
    return $this->connection->query("SELECT DISTINCT(bid) FROM {book}")->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function hasBooks() {
    return (bool) $this->connection
      ->query('SELECT count(bid) FROM {book}')
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple($nids, $access = TRUE) {
    $query = $this->connection->select('book', 'b', array('fetch' => \PDO::FETCH_ASSOC));
    $query->fields('b');
    $query->condition('b.nid', $nids, 'IN');

    if ($access) {
      $query->addTag('node_access');
      $query->addMetaData('base_table', 'book');
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getChildRelativeDepth($book_link, $max_depth) {
    $query = $this->connection->select('book');
    $query->addField('book', 'depth');
    $query->condition('bid', $book_link['bid']);
    $query->orderBy('depth', 'DESC');
    $query->range(0, 1);

    $i = 1;
    $p = 'p1';
    while ($i <= $max_depth && $book_link[$p]) {
      $query->condition($p, $book_link[$p]);
      $p = 'p' . ++$i;
    }

    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($nid) {
    return $this->connection->delete('book')
      ->condition('nid', $nid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function loadBookChildren($pid) {
    return $this->connection
      ->query("SELECT * FROM {book} WHERE pid = :pid", array(':pid' => $pid))
      ->fetchAllAssoc('nid', \PDO::FETCH_ASSOC);
  }

  /**
   * {@inheritdoc}
   */
  public function getBookMenuTree($bid, $parameters, $min_depth, $max_depth) {
    $query = $this->connection->select('book');
    $query->fields('book');
    for ($i = 1; $i <= $max_depth; $i++) {
      $query->orderBy('p' . $i, 'ASC');
    }
    $query->condition('bid', $bid);
    if (!empty($parameters['expanded'])) {
      $query->condition('pid', $parameters['expanded'], 'IN');
    }
    if ($min_depth != 1) {
      $query->condition('depth', $min_depth, '>=');
    }
    if (isset($parameters['max_depth'])) {
      $query->condition('depth', $parameters['max_depth'], '<=');
    }
    // Add custom query conditions, if any were passed.
    if (isset($parameters['conditions'])) {
      foreach ($parameters['conditions'] as $column => $value) {
        $query->condition($column, $value);
      }
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function insert($link, $parents) {
    return $this->connection
      ->insert('book')
      ->fields(array(
        'nid' => $link['nid'],
        'bid' => $link['bid'],
        'pid' => $link['pid'],
        'weight' => $link['weight'],
        ) + $parents
      )
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function update($nid, $fields) {
    return $this->connection
      ->update('book')
      ->fields($fields)
      ->condition('nid', $nid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function updateMovedChildren($bid, $original, $expressions, $shift) {
    $query = $this->connection->update('book');
    $query->fields(array('bid' => $bid));

    foreach ($expressions as $expression) {
      $query->expression($expression[0], $expression[1], $expression[2]);
    }

    $query->expression('depth', 'depth + :depth', array(':depth' => $shift));
    $query->condition('bid', $original['bid']);
    $p = 'p1';
    for ($i = 1; !empty($original[$p]); $p = 'p' . ++$i) {
      $query->condition($p, $original[$p]);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function countOriginalLinkChildren($original) {
    return $this->connection->select('book', 'b')
      ->condition('bid', $original['bid'])
      ->condition('pid', $original['pid'])
      ->condition('nid', $original['nid'], '<>')
      ->countQuery()
      ->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getBookSubtree($link, $max_depth) {
    $query = db_select('book', 'b', array('fetch' => \PDO::FETCH_ASSOC));
    $query->fields('b');
    $query->condition('b.bid', $link['bid']);

    for ($i = 1; $i <= $max_depth && $link["p$i"]; ++$i) {
      $query->condition("p$i", $link["p$i"]);
    }
    for ($i = 1; $i <= $max_depth; ++$i) {
      $query->orderBy("p$i");
    }
    return $query->execute();
  }
}
