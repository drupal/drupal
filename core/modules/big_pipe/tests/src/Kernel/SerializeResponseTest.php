<?php

declare(strict_types=1);

namespace Drupal\Tests\big_pipe\Kernel;

use Drupal\big_pipe\Render\BigPipeResponse;
use Drupal\Core\Render\HtmlResponse;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that big_pipe responses can be serialized.
 *
 * @group big_pipe
 */
class SerializeResponseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['big_pipe'];

  /**
   * Tests that big_pipe responses can be serialized.
   *
   * @throws \Exception
   */
  public function testSerialize(): void {
    $response = new BigPipeResponse(new HtmlResponse());
    $this->assertIsString(serialize($response));

    // Checks that the response can be serialized after the big_pipe service is injected.
    $response->setBigPipeService($this->container->get('big_pipe'));
    $this->assertIsString(serialize($response));
  }

}
