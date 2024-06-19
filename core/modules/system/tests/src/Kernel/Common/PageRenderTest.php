<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Common;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test page rendering hooks.
 *
 * @group system
 */
class PageRenderTest extends KernelTestBase {

  /**
   * Tests hook_page_attachments() exceptions.
   */
  public function testHookPageAttachmentsExceptions(): void {
    $this->enableModules(['common_test', 'system']);

    $this->assertPageRenderHookExceptions('common_test', 'hook_page_attachments');
  }

  /**
   * Tests hook_page_attachments_alter() exceptions.
   */
  public function testHookPageAlter(): void {
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
   *
   * @internal
   */
  public function assertPageRenderHookExceptions(string $module, string $hook): void {
    $html_renderer = \Drupal::getContainer()->get('main_content_renderer.html');

    // Assert a valid hook implementation doesn't trigger an exception.
    $page = [];
    $html_renderer->invokePageAttachmentHooks($page);

    // Assert that hooks can set cache tags.
    $this->assertEquals(['example'], $page['#cache']['tags']);
    $this->assertEquals(['user.permissions'], $page['#cache']['contexts']);

    // Assert an invalid hook implementation doesn't trigger an exception.
    \Drupal::state()->set($module . '.' . $hook . '.descendant_attached', TRUE);
    $assertion = $hook . '() implementation that sets #attached on a descendant triggers an exception';
    $page = [];
    try {
      $html_renderer->invokePageAttachmentHooks($page);
      $this->fail($assertion);
    }
    catch (\LogicException $e) {
      $this->assertEquals('Only #attached and #cache may be set in ' . $hook . '().', $e->getMessage());
    }
    \Drupal::state()->set('bc_test.' . $hook . '.descendant_attached', FALSE);

    // Assert an invalid hook implementation doesn't trigger an exception.
    \Drupal::state()->set('bc_test.' . $hook . '.render_array', TRUE);
    $assertion = $hook . '() implementation that sets a child render array triggers an exception';
    $page = [];
    try {
      $html_renderer->invokePageAttachmentHooks($page);
      $this->fail($assertion);
    }
    catch (\LogicException $e) {
      $this->assertEquals('Only #attached and #cache may be set in ' . $hook . '().', $e->getMessage());
    }
    \Drupal::state()->set($module . '.' . $hook . '.render_array', FALSE);
  }

  /**
   * Tests HtmlRenderer::invokePageAttachmentHooks in a render context.
   */
  public function testHtmlRendererAttachmentsRenderContext(): void {
    $this->enableModules(['common_test', 'system']);
    \Drupal::state()->set('common_test.hook_page_attachments.render_url', TRUE);
    $uri = '/common/attachments-test';
    $request = new Request([], [], [], [], [], ['REQUEST_URI' => $uri, 'SCRIPT_NAME' => $uri]);
    $response = \Drupal::service('http_kernel')->handle($request);

    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
  }

}
