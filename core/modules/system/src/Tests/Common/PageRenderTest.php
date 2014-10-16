<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Common\PageRenderTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\KernelTestBase;

/**
 * Test page rendering hooks.
 *
 * @group system
 */
class PageRenderTest extends KernelTestBase {

  /**
   * Tests hook_page_attachments() exceptions.
   */
  function testHookPageAttachmentsExceptions() {
    $this->enableModules(['common_test']);
    $this->assertPageRenderHookExceptions('common_test', 'hook_page_attachments');
  }

  /**
   * Tests hook_page_attachments_alter() exceptions.
   */
  function testHookPageAlter() {
    $this->enableModules(['common_test']);
    $this->assertPageRenderHookExceptions('common_test', 'hook_page_attachments_alter');
  }

  /**
   * Tests hook_page_build() exceptions, a deprecated hook kept around for BC.
   */
  function testHookPageBuildExceptions() {
    // Also enable the system module, because that module invokes the BC hooks.
    $this->enableModules(['bc_test', 'system']);
    $this->assertPageRenderHookExceptions('bc_test', 'hook_page_build');
  }

  /**
   * Tests hook_page_alter(), a deprecated hook kept around for BC.
   */
  function testHookPageAttachmentsAlter() {
    // Also enable the system module, because that module invokes the BC hooks.
    $this->enableModules(['bc_test', 'system']);
    $this->assertPageRenderHookExceptions('bc_test', 'hook_page_alter');
  }

  /**
   * Asserts whether expected exceptions are thrown for invalid hook implementations.
   *
   * @param string $module
   *   The module whose invalid logic in its hooks to enable.
   * @param string $hook
   *   The page render hook to assert expected exceptions for.
   */
  function assertPageRenderHookExceptions($module, $hook) {
    // Assert a valid hook implementation doesn't trigger an exception.
    $page = [];
    drupal_prepare_page($page);

    // Assert an invalid hook implementation doesn't trigger an exception.
    \Drupal::state()->set($module . '.' . $hook . '.descendant_attached', TRUE);
    $assertion = $hook . '() implementation that sets #attached on a descendant triggers an exception';
    $page = [];
    try {
      drupal_prepare_page($page);
      $this->error($assertion);
    }
    catch (\LogicException $e) {
      $this->pass($assertion);
      $this->assertEqual($e->getMessage(), 'Only #attached and #post_render_cache may be set in ' . $hook . '().');
    }
    \Drupal::state()->set('bc_test.' . $hook . '.descendant_attached', FALSE);

    // Assert an invalid hook implementation doesn't trigger an exception.
    \Drupal::state()->set('bc_test.' . $hook . '.render_array', TRUE);
    $assertion = $hook . '() implementation that sets a child render array triggers an exception';
    $page = [];
    try {
      drupal_prepare_page($page);
      $this->error($assertion);
    }
    catch (\LogicException $e) {
      $this->pass($assertion);
      $this->assertEqual($e->getMessage(), 'Only #attached and #post_render_cache may be set in ' . $hook . '().');
    }
    \Drupal::state()->set($module . '.' . $hook . '.render_array', FALSE);
  }

}
