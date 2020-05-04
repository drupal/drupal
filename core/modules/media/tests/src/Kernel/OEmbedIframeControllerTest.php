<?php

namespace Drupal\Tests\media\Kernel;

use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\media\Controller\OEmbedIframeController
 *
 * @group media
 */
class OEmbedIframeControllerTest extends MediaKernelTestBase {

  /**
   * Data provider for testBadHashParameter().
   *
   * @return array
   */
  public function providerBadHashParameter() {
    return [
      'no hash' => [
        '',
      ],
      'invalid hash' => [
        $this->randomString(),
      ],
    ];
  }

  /**
   * Tests validation of the 'hash' query string parameter.
   *
   * @param string $hash
   *   The 'hash' query string parameter.
   *
   * @dataProvider providerBadHashParameter
   *
   * @covers ::render
   */
  public function testBadHashParameter($hash) {
    /** @var callable $controller */
    $controller = $this->container
      ->get('controller_resolver')
      ->getControllerFromDefinition('\Drupal\media\Controller\OEmbedIframeController::render');

    $this->assertIsCallable($controller);

    $this->expectException('\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException');
    $this->expectExceptionMessage('This resource is not available');
    $request = new Request([
      'url' => 'https://example.com/path/to/resource',
      'hash' => $hash,
    ]);
    $controller($request);
  }

}
