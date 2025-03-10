<?php

declare(strict_types=1);

namespace Drupal\Tests\big_pipe\Unit\Render;

use Drupal\big_pipe\Render\BigPipe;
use Drupal\big_pipe\Render\BigPipeResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\PlaceholderGeneratorInterface;
use Drupal\Core\Render\RenderCacheInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Utility\CallableResolver;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\big_pipe\Render\BigPipe
 * @group big_pipe
 */
class FiberPlaceholderTest extends UnitTestCase {

  /**
   * @covers \Drupal\big_pipe\Render\BigPipe::sendPlaceholders
   */
  public function testLongPlaceholderFiberSuspendingLoop(): void {
    $request_stack = $this->prophesize(RequestStack::class);
    $request_stack->getMainRequest()
      ->willReturn(new Request());
    $request_stack->getCurrentRequest()
      ->willReturn(new Request());

    $callableResolver = $this->prophesize(CallableResolver::class);
    $callableResolver->getCallableFromDefinition(Argument::any())
      ->willReturn([TurtleLazyBuilder::class, 'turtle']);
    $renderer = new Renderer(
      $callableResolver->reveal(),
      $this->prophesize(ThemeManagerInterface::class)->reveal(),
      $this->prophesize(ElementInfoManagerInterface::class)->reveal(),
      $this->prophesize(PlaceholderGeneratorInterface::class)->reveal(),
      $this->prophesize(RenderCacheInterface::class)->reveal(),
      $request_stack->reveal(),
      [
        'required_cache_contexts' => [
          'languages:language_interface',
          'theme',
        ],
      ],
    );

    $session = $this->prophesize(SessionInterface::class);
    $session->start()->willReturn(TRUE);

    $bigpipe = new BigPipe(
      $renderer,
      $session->reveal(),
      $request_stack->reveal(),
      $this->prophesize(HttpKernelInterface::class)->reveal(),
      $this->createMock(EventDispatcherInterface::class),
      $this->prophesize(ConfigFactoryInterface::class)->reveal(),
      $this->prophesize(MessengerInterface::class)->reveal(),
      $this->prophesize(RequestContext::class)->reveal(),
      $this->prophesize(LoggerInterface::class)->reveal(),
    );
    $response = new BigPipeResponse(new HtmlResponse());

    $attachments = [
      'library' => [],
      'drupalSettings' => [
        'ajaxPageState' => [],
      ],
      'big_pipe_placeholders' => [
        // cspell:disable-next-line
        'callback=%5CDrupal%5CTests%5Cbig_pipe%5CUnit%5CRender%5CTurtleLazyBuilder%3A%3Aturtle&amp;&amp;token=uhKFNfT4eF449_W-kDQX8E5z4yHyt0-nSHUlwaGAQeU' => [
          '#lazy_builder' => [
            '\Drupal\Tests\big_pipe\Unit\Render\TurtleLazyBuilder::turtle',
            [],
          ],
        ],
      ],
    ];
    $response->setAttachments($attachments);

    // Construct minimal HTML response.
    // cspell:disable-next-line
    $content = '<html><body><span data-big-pipe-placeholder-id="callback=%5CDrupal%5CTests%5Cbig_pipe%5CUnit%5CRender%5CTurtleLazyBuilder%3A%3Aturtle&amp;&amp;token=uhKFNfT4eF449_W-kDQX8E5z4yHyt0-nSHUlwaGAQeU"></body></html>';
    $response->setContent($content);

    // Capture the result to avoid PHPUnit complaining.
    ob_start();
    $fiber = new \Fiber(function () use ($bigpipe, $response) {
      $bigpipe->sendContent($response);
    });
    $fiber->start();
    $this->assertFalse($fiber->isTerminated(), 'Placeholder fibers with long execution time supposed to return control before terminating');
    ob_get_clean();
  }

}

/**
 * Test class for testing fiber placeholders.
 */
class TurtleLazyBuilder implements TrustedCallbackInterface {

  /**
   * Render API callback: Suspends execution twice to simulate a long operation.
   *
   * This function is assigned as a #lazy_builder callback.
   *
   * @return array
   *   The lazy builder callback.
   */
  public static function turtle(): array {
    if (\Fiber::getCurrent() !== NULL) {
      \Fiber::suspend();
    }
    if (\Fiber::getCurrent() !== NULL) {
      \Fiber::suspend();
    }
    return [
      '#markup' => '<span>Turtle is finally here. But how?</span>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['turtle'];
  }

}
