<?php

namespace Drupal\Core\Database\Query;

use Drupal\Core\Database\Connection;
use Drupal\Core\Utility\TableSort;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Query extender class for tablesort queries.
 */
class TableSortExtender extends SelectExtender {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a TableSortExtender object.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   Select query object.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(SelectInterface $query, Connection $connection, RequestStack $request_stack = NULL) {
    if (is_null($request_stack)) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $request_stack argument is deprecated in drupal:9.4.0 and will be required in drupal:10.0.0. Use the relevant service to instantiate extenders. See https://www.drupal.org/node/3218001', E_USER_DEPRECATED);
      $request_stack = \Drupal::service('request_stack');
    }
    parent::__construct($query, $connection);
    $this->requestStack = $request_stack;

    // Add convenience tag to mark that this is an extended query. We have to
    // do this in the constructor to ensure that it is set before preExecute()
    // gets called.
    $this->addTag('tablesort');
  }

  /**
   * Order the query based on a header array.
   *
   * @param array $header
   *   Table header array.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The called object.
   *
   * @see table.html.twig
   */
  public function orderByHeader(array $header) {
    $context = TableSort::getContextFromRequest($header, $this->requestStack->getCurrentRequest());
    if (!empty($context['sql'])) {
      // Based on code from \Drupal\Core\Database\Connection::escapeTable(),
      // but this can also contain a dot.
      $field = preg_replace('/[^A-Za-z0-9_.]+/', '', $context['sql']);

      // orderBy() will ensure that only ASC/DESC values are accepted, so we
      // don't need to sanitize that here.
      $this->orderBy($field, $context['sort']);
    }
    return $this;
  }

}
