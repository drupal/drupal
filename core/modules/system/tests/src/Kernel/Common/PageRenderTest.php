<?php

namespace Drupal\Tests\system\Kernel\Common;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test page rendering hooks.
 *
 * @group system
 */
class PageRenderTest extends KernelTestBase {

  /**
   * Tests hook_page_attachments() exceptions.
   */
  public function testHookPageAttachmentsExceptions() {
    $this->enableModules(['common_test', 'system']);

    $this->assertPageRenderHookExceptions('common_test', 'hook_page_attachments');
  }

  /**
   * Tests hook_page_attachments_alter() exceptions.
   */
  public function testHookPageAlter() {
    $this->enableModules(['common_test', 'system']);

    $this->assertPageRenderHookExceptions('common_test', 'hook_page_attachments_alter');
  }

  /**
   * Asserts whether expected exceptions are thrown for invalid hook implementations.
   *
   * @param string $module
   *   The module whose invalid logic in its hooks to enable.
   * @param string $hook
   *   The page render hook to assert expected exceptions for.
   */
  public function assertPageRenderHookExceptions($module, $hook) {
    $html_renderer = \Drupal::getContainer()->get('main_content_renderer.html');

    // Assert a valid hook implementation doesn't trigger an exception.
    $page = [];
    $html_renderer->invokePageAttachmentHooks($page);

    // Assert that hooks can set cache tags.
    $this->assertEqual(['example'], $page['#cache']['tags']);
    $this->assertEqual(['user.permissions'], $page['#cache']['contexts']);

    // Assert an invalid hook implementation doesn't trigger an exception.
    \Drupal::state()->set($module . '.' . $hook . '.descendant_attached', TRUE);
    $assertion = $hook . '() implementation that sets #attached on a descendant triggers an exception';
    $page = [];
    try {
      $html_renderer->invokePageAttachmentHooks($page);
      $this->error($assertion);
    }
    catch (\LogicException $e) {
      $this->assertEqual('Only #attached and #cache may be set in ' . $hook . '().', $e->getMessage());
    }
    \Drupal::state()->set('bc_test.' . $hook . '.descendant_attached', FALSE);

    // Assert an invalid hook implementation doesn't trigger an exception.
    \Drupal::state()->set('bc_test.' . $hook . '.render_array', TRUE);
    $assertion = $hook . '() implementation that sets a child render array triggers an exception';
    $page = [];
    try {
      $html_renderer->invokePageAttachmentHooks($page);
      $this->error($assertion);
    }
    catch (\LogicException $e) {
      $this->assertEqual('Only #attached and #cache may be set in ' . $hook . '().', $e->getMessage());
    }
    \Drupal::state()->set($module . '.' . $hook . '.render_array', FALSE);
  }

}
