<?php
/**
 * @file
 * Contains \Drupal\error_test\Controller\ErrorTestController.
 */

namespace Drupal\error_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for error_test routes.
 */
class ErrorTestController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection;
   */
  protected $database;

  /**
   * Constructs a \Drupal\error_test\Controller\ErrorTestController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Generate warnings to test the error handler.
   */
  public function generateWarnings($collect_errors = FALSE) {
    // Tell Drupal error reporter to send errors to Simpletest or not.
    define('SIMPLETEST_COLLECT_ERRORS', $collect_errors);
    // This will generate a notice.
    $monkey_love = $bananas;
    // This will generate a warning.
    $awesomely_big = 1/0;
    // This will generate a user error.
    trigger_error("Drupal is awesome", E_USER_WARNING);
    return [];
  }

  /**
   * Trigger an exception to test the exception handler.
   */
  public function triggerException() {
    define('SIMPLETEST_COLLECT_ERRORS', FALSE);
    throw new \Exception("Drupal is awesome");
  }

  /**
   * Trigger an exception to test the PDO exception handler.
   */
  public function triggerPDOException() {
    define('SIMPLETEST_COLLECT_ERRORS', FALSE);
    $this->database->query('SELECT * FROM bananas_are_awesome');
  }

  /**
   * Trigger an exception during rendering.
   */
  public function triggerRendererException() {
    return [
      '#type' => 'page',
      '#post_render' => [
        function () {
          throw new \Exception('This is an exception that occurs during rendering');
        }
      ],
    ];
  }

}
