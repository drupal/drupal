<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

// cspell:ignore foobarbaz baznew

/**
 * Tests for navigation content_top section.
 *
 * @group navigation
 */
class NavigationContentTopTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['navigation', 'navigation_test', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->createUser([
      'access navigation',
    ]));
  }

  /**
   * Tests behavior of content_top section hooks.
   */
  public function testNavigationContentTop(): void {
    $test_page_url = Url::fromRoute('test_page_test.test_page');
    $this->drupalGet($test_page_url);
    $this->assertSession()->elementNotExists('css', '.admin-toolbar__content-top');
    \Drupal::keyValue('navigation_test')->set('content_top', 1);
    Cache::invalidateTags(['navigation_test']);
    $this->drupalGet($test_page_url);
    $this->assertSession()->elementTextContains('css', '.admin-toolbar__content-top', 'foobarbaz');
    \Drupal::keyValue('navigation_test')->set('content_top_alter', 1);
    Cache::invalidateTags(['navigation_test']);
    $this->drupalGet($test_page_url);
    $this->assertSession()->elementTextContains('css', '.admin-toolbar__content-top', 'baznew bar');
  }

}
