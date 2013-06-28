<?php

/**
 * @file
 * Contains \Drupal\aggregator\Access\CategoriesAccess.
 */

namespace Drupal\aggregator\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Provides an access check for aggregator categories routes.
 */
class CategoriesAccessCheck implements AccessCheckInterface {

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
  public function applies(Route $route) {
    return array_key_exists('_access_aggregator_categories', $route->getRequirements());
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    // @todo Replace user_access() with a correctly injected and session-using
    // alternative.
    return user_access('access news feeds') && (bool) $this->database->queryRange('SELECT 1 FROM {aggregator_category}', 0, 1)->fetchField();
  }

}
