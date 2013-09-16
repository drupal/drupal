<?php

/**
 * @file
 * Contains \Drupal\book\Controller\BookController.
 */

namespace Drupal\book\Controller;

use Drupal\book\BookManager;
use Drupal\book\BookExport;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for book routes.
 */
class BookController implements ContainerInjectionInterface {

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManager
   */
  protected $bookManager;

  /**
   * The book export service.
   *
   * @var \Drupal\book\BookExport
   */
  protected $bookExport;

  /**
   * Constructs a BookController object.
   *
   * @param \Drupal\book\BookManager $bookManager
   *   The book manager.
   * @param \Drupal\book\BookExport $bookExport
   *   The book export service.
   */
  public function __construct(BookManager $bookManager, BookExport $bookExport) {
    $this->bookManager = $bookManager;
    $this->bookExport = $bookExport;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('book.manager'),
      $container->get('book.export')
    );
  }

  /**
   * Returns an administrative overview of all books.
   *
   * @return array
   *   A render array representing the administrative page content.
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
    return array(
      '#theme' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('No books available.'),
    );
  }

  /**
   * Prints a listing of all books.
   *
   * @return array
   *   A render array representing the listing of all books content.
   */
  public function bookRender() {
    $book_list = array();
    foreach ($this->bookManager->getAllBooks() as $book) {
      $book_list[] = l($book['title'], $book['href'], $book['options']);
    }
    return array(
      '#theme' => 'item_list',
      '#items' => $book_list,
    );
  }

  /**
   * Generates representations of a book page and its children.
   *
   * The method delegates the generation of output to helper methods. The method
   * name is derived by prepending 'bookExport' to the camelized form of given
   * output type. For example, a type of 'html' results in a call to the method
   * bookExportHtml().
   *
   * @param string $type
   *   A string encoding the type of output requested. The following types are
   *   currently supported in book module:
   *   - html: Printer-friendly HTML.
   *   Other types may be supported in contributed modules.
   * @param \Drupal\node\NodeInterface $node
   *   The node to export.
   *
   * @return array
   *   A render array representing the node and its children in the book
   *   hierarchy in a format determined by the $type parameter.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function bookExport($type, NodeInterface $node) {
    $method = 'bookExport' . Container::camelize($type);

    // @todo Convert the custom export functionality to serializer.
    if (!method_exists($this->bookExport, $method)) {
      drupal_set_message(t('Unknown export format.'));
      throw new NotFoundHttpException();
    }

    $exported_book = $this->bookExport->{$method}($node);
    return new Response(drupal_render($exported_book));
  }

}
