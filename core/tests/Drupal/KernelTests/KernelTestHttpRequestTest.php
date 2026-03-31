<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

use Drupal\Tests\HttpKernelUiHelperTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests making HTTP requests in a kernel test.
 */
#[CoversTrait(HttpKernelUiHelperTrait::class)]
#[Group('PHPUnit')]
#[Group('Test')]
#[Group('KernelTests')]
#[RunTestsInSeparateProcesses]
class KernelTestHttpRequestTest extends KernelTestBase {


  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'system_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Tests making a request.
   */
  public function testRequest(): void {
    $this->drupalGet('/system-test/main-content-handling');
    $this->assertEquals(Response::HTTP_OK, $this->getSession()->getStatusCode());
    $this->assertSession()->pageTextContains('Content to test main content fallback');
  }

}
