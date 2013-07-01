<?php
/**
 * @file
 * Contains \Drupal\book\BookManager.
 */

namespace Drupal\book;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Entity manager Service Object.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Books Array.
   *
   * @var array
   */
  protected $books;

  /**
   * Constructs a BookManager object.
   */
  public function __construct(Connection $database, EntityManager $entityManager) {
    $this->database = $database;
    $this->entityManager = $entityManager;
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
      $query->join('menu_links', 'ml', 'b.mlid = ml.mlid');
      $query->fields('b');
      $query->fields('ml');
      $query->condition('b.nid', $nids);
      $query->orderBy('ml.weight');
      $query->orderBy('ml.link_title');
      $query->addTag('node_access');
      $query->addMetaData('base_table', 'book');
      $book_links = $query->execute();

      $nodes = $this->entityManager->getStorageController('node')->loadMultiple($nids);

      foreach ($book_links as $link) {
        $nid = $link['nid'];
        if (isset($nodes[$nid]) && $nodes[$nid]->status) {
          $link['href'] = $link['link_path'];
          $link['options'] = unserialize($link['options']);
          $link['title'] = $nodes[$nid]->label();
          $link['type'] = $nodes[$nid]->bundle();
          $this->books[$link['bid']] = $link;
        }
      }
    }
  }

}
