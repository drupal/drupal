<?php

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
  public static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $account = $this->drupalCreateUser(['administer blocks', 'view the administration theme']);
    $this->drupalLogin($account);
  }

  /**
   * Tests if the 'theme_token' key of 'ajaxPageState' is computed.
   */
  public function testThemeToken() {
    // Visit the block administrative page with default theme. We use that page
    // because 'misc/ajax.js' is loaded there and we can test the token
    // generation.
    $this->drupalGet('admin/structure/block');
    $settings = $this->getDrupalSettings();
    $this->assertNull($settings['ajaxPageState']['theme_token']);

    // Install 'seven' and configure it as administrative theme.
    $this->container->get('theme_installer')->install(['seven']);
    $this->config('system.theme')->set('admin', 'seven')->save();

    // Revisit the page. This time the page is displayed using the 'seven' theme
    // and that is different from the default theme ('classy').
    $this->drupalGet('admin/structure/block');
    $settings = $this->getDrupalSettings();
    $this->assertNotNull($settings['ajaxPageState']['theme_token']);
    // The CSRF token is a 43 length string.
    $this->assertTrue(is_string($settings['ajaxPageState']['theme_token']));
    $this->assertEqual(strlen($settings['ajaxPageState']['theme_token']), 43);
  }

}
