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
  function system_test_cache_tags_page() {
    $build['main'] = array(
      '#markup' => 'Cache tags page example',
      '#cache' => array('tags' => array('system_test_cache_tags_page' => TRUE)),
    );
    return $build;
  }

  /**
   * @todo Remove system_test_authorize_init_page().
   */
  public function authorizeInit($page_title) {
    return system_test_authorize_init_page($page_title);
  }

  /**
   * @todo Remove as part of https://drupal.org/node/1775842.
   */
  public function variableGet() {
    return variable_get('simpletest_bootstrap_variable_test');
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
  }

}
