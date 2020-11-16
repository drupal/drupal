<?php

namespace Drupal\Tests\color\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Ensures the color config schema is correct.
 *
 * @group color
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
  protected $defaultTheme = 'stark';

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
    \Drupal::service('theme_installer')->install(['bartik']);

    // Create user.
    $this->adminUser = $this->drupalCreateUser(['administer themes']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests whether the color config schema is valid.
   */
  public function testValidColorConfigSchema() {
    $settings_path = 'admin/appearance/settings/bartik';
    $edit['scheme'] = '';
    $edit['palette[bg]'] = '#123456';
    $this->drupalPostForm($settings_path, $edit, 'Save configuration');
  }

}
