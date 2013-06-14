<?php
/**
 * @file
 * Contains \Drupal\book\Controller\BookController.
 */

namespace Drupal\book\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\book\BookManager;

/**
 * Controller routines for book routes.
 */
class BookController implements ControllerInterface {

  /**
   * Book Manager Service.
   *
   * @var \Drupal\book\BookManager
   */
  protected $bookManager;

  /**
   * Injects BookManager Service.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('book.manager'));
  }

  /**
   * Constructs a BookController object.
   */
  public function __construct(BookManager $bookManager) {
    $this->bookManager = $bookManager;
  }

  /**
   * Returns an administrative overview of all books.
   *
   * @return string
   *   A HTML-formatted string with the administrative page content.
   *
   */
  public function adminOverview() {
    $rows = array();

    $headers = array(t('Book'), t('Operations'));

    // Add any recognized books to the table list.
    foreach ($this->bookManager->getAllBooks() as $book) {
      $row = array(
        l($book['title'], $book['href'], $book['options']),
      );
      $links = array();
      $links['edit'] = array(
        'title' => t('Edit order and titles'),
        'href' => 'admin/structure/book/' . $book['nid'],
      );
      $row[] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $links,
        ),
      );
      $rows[] = $row;
    }
    $table = array('#theme' => 'table', '#header' => $headers, '#rows' => $rows, '#empty' => t('No books available.'));
    return drupal_render($table);
  }

  /**
   * Prints a listing of all books.
   *
   * @return string
   *   A HTML-formatted string with the listing of all books content.
   */
  public function bookRender() {
    $book_list = array();
    foreach ($this->bookManager->getAllBooks() as $book) {
      $book_list[] = l($book['title'], $book['href'], $book['options']);
    }
    $item_list = array('#theme' => 'item_list' , '#items' => $book_list);
    return drupal_render($item_list);
  }

}
