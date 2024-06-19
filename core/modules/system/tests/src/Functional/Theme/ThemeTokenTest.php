<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the generation of 'theme_token' key in Drupal settings.
 *
 * @group Theme
 */
class ThemeTokenTest extends BrowserTestBase {

  /**
   * We want to visit the 'admin/structure/block' page.
   *
   * @var array
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $account = $this->drupalCreateUser([
      'administer blocks',
      'view the administration theme',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests if the 'theme_token' key of 'ajaxPageState' is computed.
   */
  public function testThemeToken(): void {
    // Visit the block administrative page with default theme. We use that page
    // because 'misc/ajax.js' is loaded there and we can test the token
    // generation.
    $this->drupalGet('admin/structure/block');
    $settings = $this->getDrupalSettings();
    $this->assertNull($settings['ajaxPageState']['theme_token']);

    // Install 'claro' and configure it as administrative theme.
    $this->container->get('theme_installer')->install(['claro']);
    $this->config('system.theme')->set('admin', 'claro')->save();

    // Revisit the page. This time the page is displayed using the 'claro' theme
    // and that is different from the default theme ('stark').
    $this->drupalGet('admin/structure/block');
    $settings = $this->getDrupalSettings();
    $this->assertNotNull($settings['ajaxPageState']['theme_token']);
    // The CSRF token is a 43 length string.
    $this->assertIsString($settings['ajaxPageState']['theme_token']);
    $this->assertEquals(43, strlen($settings['ajaxPageState']['theme_token']));
  }

}
