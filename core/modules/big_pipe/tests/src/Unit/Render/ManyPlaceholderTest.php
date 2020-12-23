<?php

namespace Drupal\Tests\big_pipe\Unit\Render;

use Drupal\big_pipe\Render\BigPipe;
use Drupal\big_pipe\Render\BigPipeResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\big_pipe\Render\BigPipe
 * @group big_pipe
 */
class ManyPlaceholderTest extends UnitTestCase {

  /**
   * @covers \Drupal\big_pipe\Render\BigPipe::sendNoJsPlaceholders
   */
  public function testManyNoJsPlaceHolders() {
    $bigpipe = new BigPipe(
      $this->prophesize(RendererInterface::class)->reveal(),
      $this->prophesize(SessionInterface::class)->reveal(),
      $this->prophesize(RequestStack::class)->reveal(),
      $this->prophesize(HttpKernelInterface::class)->reveal(),
      $this->prophesize(EventDispatcherInterface::class)->reveal(),
      $this->prophesize(ConfigFactoryInterface::class)->reveal()
    );
    $response = new BigPipeResponse(new HtmlResponse());

    // Add many placeholders.
    $many_placeholders = [];
    for ($i = 0; $i < 400; $i++) {
      $many_placeholders[$this->randomMachineName(80)] = $this->randomMachineName(80);
    }
    $attachments = [
      'library' => [],
      'big_pipe_nojs_placeholders' => $many_placeholders,
    ];
    $response->setAttachments($attachments);

    // Construct minimal HTML response.
    $content = '<html><body>content<drupal-big-pipe-scripts-bottom-marker>script-bottom<drupal-big-pipe-scripts-bottom-marker></body></html>';
    $response->setContent($content);

    // Capture the result to avoid PHPUnit complaining.
    ob_start();
    $bigpipe->sendContent($response);
    $result = ob_get_clean();

    $this->assertNotEmpty($result);
  }

}
