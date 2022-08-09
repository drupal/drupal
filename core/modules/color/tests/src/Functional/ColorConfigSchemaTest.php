<?php

namespace Drupal\Tests\color\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Ensures the color config schema is correct.
 *
 * @group color
 * @group legacy
 */
class ColorConfigSchemaTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['color'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'color_test_theme';

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create user.
    $this->adminUser = $this->drupalCreateUser(['administer themes']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests whether the color config schema is valid.
   */
  public function testValidColorConfigSchema(): void {
    $settings_path = 'admin/appearance/settings/color_test_theme';
    $edit['scheme'] = '';
    $edit['palette[bg]'] = '#123456';
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save configuration');
  }

}
