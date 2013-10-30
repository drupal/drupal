<?php

/**
 * @file
 * Contains \Drupal\aggregator\Access\CategoriesAccess.
 */

namespace Drupal\aggregator\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Provides an access check for aggregator categories routes.
 */
class CategoriesAccessCheck implements StaticAccessCheckInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a CategoriesAccessCheck object.
   *
   * @param \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_aggregator_categories');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    return $account->hasPermission('access news feeds') && (bool) $this->database->queryRange('SELECT 1 FROM {aggregator_category}', 0, 1)->fetchField() ? static::ALLOW : static::DENY;
  }

}
