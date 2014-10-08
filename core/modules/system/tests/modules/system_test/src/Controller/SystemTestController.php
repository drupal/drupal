<?php

/**
 * @file
 * Contains \Drupal\system_test\Controller\SystemTestController.
 */

namespace Drupal\system_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for system_test routes.
 */
class SystemTestController extends ControllerBase {

  /**
   * Tests main content fallback.
   *
   * @return string
   *   The text to display.
   */
  public function mainContentFallback() {
    return $this->t('Content to test main content fallback');
  }

  /**
   * Tests setting messages and removing one before it is displayed.
   *
   * @return string
   *   Empty string, we just test the setting of messages.
   */
  public function drupalSetMessageTest() {
    // Set two messages.
    drupal_set_message('First message (removed).');
    drupal_set_message('Second message (not removed).');

    // Remove the first.
    unset($_SESSION['messages']['status'][0]);
    return '';
  }

  /**
   * @todo Remove system_test_lock_acquire().
   */
  public function lockAcquire() {
    return system_test_lock_acquire();
  }

  /**
   * @todo Remove system_test_lock_exit().
   */
  public function lockExit() {
    return system_test_lock_exit();
  }

  /**
   * Set cache tag on on the returned render array.
   */
  public function system_test_cache_tags_page() {
    $build['main'] = array(
      '#cache' => array('tags' => array('system_test_cache_tags_page')),
      '#pre_render' => array(
        '\Drupal\system_test\Controller\SystemTestController::preRenderCacheTags',
      ),
      'message' => array(
        '#markup' => 'Cache tags page example',
      ),
    );
    return $build;
  }

  /**
   * Sets a cache tag on an element to help test #pre_render and cache tags.
   */
  public static function preRenderCacheTags($elements) {
    $elements['#cache']['tags'][] = 'pre_render';
    return $elements;
  }

  /**
   * @todo Remove system_test_authorize_init_page().
   */
  public function authorizeInit($page_title) {
    return system_test_authorize_init_page($page_title);
  }

  /**
   * @todo Remove system_test_set_header().
   */
  public function setHeader() {
    return system_test_set_header();
  }

  /**
   * @todo Remove system_test_page_shutdown_functions().
   */
  public function shutdownFunctions($arg1, $arg2) {
    system_test_page_shutdown_functions($arg1, $arg2);
    // If using PHP-FPM then fastcgi_finish_request() will have been fired
    // preventing further output to the browser which means that the escaping of
    // the exception message can not be tested.
    // @see _drupal_shutdown_function()
    // @see \Drupal\system\Tests\System\ShutdownFunctionsTest
    if (function_exists('fastcgi_finish_request')) {
      return 'The function fastcgi_finish_request exists when serving the request.';
    }
  }

  /**
   * Returns the title for system_test.info.yml's configure route.
   *
   * @param string $foo
   *   Any string for the {foo} slug.
   *
   * @return string
   */
  public function configureTitle($foo) {
    return 'Bar.' . $foo;
  }

}
