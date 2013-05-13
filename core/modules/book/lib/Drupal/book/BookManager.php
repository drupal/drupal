<?php
/**
 * @file
 * Contains \Drupal\book\BookManager.
 */

namespace Drupal\book;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Book Manager Service.
 */
class BookManager {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Books Array.
   *
   * @var array
   */
  protected $books;

  /**
   * Constructs a BookManager object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Returns an array of all books.
   *
   * This list may be used for generating a list of all the books, or for building
   * the options for a form select.
   *
   * @return
   *   An array of all books.
   */
  public function getAllBooks() {
    if (!isset($this->books)) {
      $this->loadBooks();
    }
    return $this->books;
  }

  /**
   * Loads Books Array.
   */
  protected function loadBooks() {
    $this->books = array();
    $nids = $this->database->query("SELECT DISTINCT(bid) FROM {book}")->fetchCol();
    if ($nids) {
      $query = $this->database->select('book', 'b', array('fetch' => \PDO::FETCH_ASSOC));
      $query->join('node', 'n', 'b.nid = n.nid');
      $query->join('menu_links', 'ml', 'b.mlid = ml.mlid');
      $query->addField('n', 'type', 'type');
      $query->addField('n', 'title', 'title');
      $query->fields('b');
      $query->fields('ml');
      $query->condition('n.nid', $nids, 'IN');
      $query->condition('n.status', 1);
      $query->orderBy('ml.weight');
      $query->orderBy('ml.link_title');
      $query->addTag('node_access');
      $book_links = $query->execute();
      foreach ($book_links as $link) {
        $link['href'] = $link['link_path'];
        $link['options'] = unserialize($link['options']);
        $this->books[$link['bid']] = $link;
      }
    }
  }

}
