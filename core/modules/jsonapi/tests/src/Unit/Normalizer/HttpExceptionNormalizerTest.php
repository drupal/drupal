<?php

namespace Drupal\Tests\jsonapi\Unit\Normalizer;

use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\Normalizer\HttpExceptionNormalizer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\HttpExceptionNormalizer
 * @group jsonapi
 *
 * @internal
 */
class HttpExceptionNormalizerTest extends UnitTestCase {

  /**
   * @covers ::normalize
   */
  public function testNormalize() {
    $request_stack = $this->prophesize(RequestStack::class);
    $request_stack->getCurrentRequest()->willReturn(Request::create('http://localhost/'));
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('request_stack')->willReturn($request_stack->reveal());
    \Drupal::setContainer($container->reveal());
    $exception = new AccessDeniedHttpException('lorem', NULL, 13);
    $current_user = $this->prophesize(AccountInterface::class);
    $current_user->hasPermission('access site reports')->willReturn(TRUE);
    $normalizer = new HttpExceptionNormalizer($current_user->reveal());
    $normalized = $normalizer->normalize($exception, 'api_json');
    $normalized = $normalized->getNormalization();
    $error = $normalized[0];
    $this->assertNotEmpty($error['meta']);
    $this->assertNotEmpty($error['source']);
    $this->assertSame('13', $error['code']);
    $this->assertSame('403', $error['status']);
    $this->assertEquals('Forbidden', $error['title']);
    $this->assertEquals('lorem', $error['detail']);
    $this->assertArrayHasKey('trace', $error['meta']);
    $this->assertNotEmpty($error['meta']['trace']);

    $current_user = $this->prophesize(AccountInterface::class);
    $current_user->hasPermission('access site reports')->willReturn(FALSE);
    $normalizer = new HttpExceptionNormalizer($current_user->reveal());
    $normalized = $normalizer->normalize($exception, 'api_json');
    $normalized = $normalized->getNormalization();
    $error = $normalized[0];
    $this->assertTrue(empty($error['meta']));
    $this->assertTrue(empty($error['source']));
  }

}
