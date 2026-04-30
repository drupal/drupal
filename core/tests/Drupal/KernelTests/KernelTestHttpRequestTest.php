<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

use Drupal\Core\Url;
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
    'test_page_test',
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

    // Test drupalGet() with Url options.
    $this->drupalGet('/system-test/set-header', [
      'query' => [
        'name' => 'meaning',
        'value' => '42',
      ],
    ]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The following header was set: meaning: 42');

    // Test drupalGet() with a URL object.
    $url = Url::fromRoute('system_test.main_content_handling');
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Test drupalGet() with a URL object with options.
    $url = Url::fromRoute('system_test.set_header', [], [
      'query' => [
        'name' => 'meaning',
        'value' => '42',
      ],
    ]);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The following header was set: meaning: 42');

    // Test that setting headers with drupalGet() works.
    $this->drupalGet('system-test/header', [], [
      'Test-Header' => 'header value',
    ]);
    // We can't use WebAssert::responseHeaderExists() because of how header
    // names are normalized by Mink and Symfony.
    $this->assertEquals('header value', $this->getSession()->getResponseHeader('Test-Header'));
  }

  /**
   * Tests clickLink() functionality.
   */
  public function testClickLink(): void {
    $this->drupalGet('test-page');
    $this->clickLink('Visually identical test links');
    $this->assertStringContainsString('user/login', $this->getSession()->getCurrentUrl());
    $this->drupalGet('test-page');
    $this->clickLink('Visually identical test links', 0);
    $this->assertStringContainsString('user/login', $this->getSession()->getCurrentUrl());
    $this->drupalGet('test-page');
    $this->clickLink('Visually identical test links', 1);
    $this->assertStringContainsString('user/register', $this->getSession()->getCurrentUrl());
  }

}
