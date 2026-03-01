<?php

declare(strict_types=1);

namespace Drupal\Tests\admin\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Admin theme.
 */
#[Group('admin')]
#[RunTestsInSeparateProcesses]
class AdminTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
  // Install the shortcut module so that admin.settings has its schema checked.
  // There's currently no way for Admin to provide a default and have valid
  // configuration as themes cannot react to a module install.
    'shortcut',
    'toolbar',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->assertTrue(\Drupal::service('theme_installer')->install(['admin']));
    $this->container->get('config.factory')
      ->getEditable('system.theme')
      ->set('default', 'admin')
      ->set('admin', 'admin')
      ->save();

    $adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer themes',
      'access toolbar',
      'access content overview',
    ]);
    $this->drupalLogin($adminUser);
  }

  /**
   * Tests that the Admin theme always adds its message CSS and Classy's.
   */
  public function testDefaultAdminSettings(): void {
    $response = $this->drupalGet('/admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertStringContainsString('"dark_mode":"0"', $response);
    $this->assertStringContainsString('"preset_accent_color":"blue"', $response);
    $this->assertStringContainsString('"preset_focus_color":"gin"', $response);
    $this->assertSession()->responseContains('admin.css');
  }

  /**
   * Tests the dark mode setting.
   */
  public function testDarkModeSetting(): void {
    \Drupal::configFactory()->getEditable('admin.settings')->set('enable_dark_mode', '1')->save();
    $response = $this->drupalGet('/admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertStringContainsString('"dark_mode":"1"', $response);
  }

  /**
   * Tests color accent setting.
   */
  public function testAccentColorSetting(): void {
    \Drupal::configFactory()->getEditable('admin.settings')->set('preset_accent_color', 'red')->save();
    $response = $this->drupalGet('/admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertStringContainsString('"preset_accent_color":"red"', $response);
  }

  /**
   * Tests focus color setting.
   */
  public function testFocusColorSetting(): void {
    \Drupal::configFactory()->getEditable('admin.settings')->set('preset_focus_color', 'blue')->save();
    $response = $this->drupalGet('/admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertStringContainsString('"preset_focus_color":"blue"', $response);
  }

  /**
   * Test user settings.
   */
  public function testUserSettings(): void {
    \Drupal::configFactory()->getEditable('admin.settings')->set('show_user_theme_settings', TRUE)->save();

    $user1 = $this->createUser();
    $this->drupalLogin($user1);

    // Change something on the logged in user form.
    $this->assertStringContainsString('"dark_mode":"0"', $this->drupalGet($user1->toUrl('edit-form')));

    $this->submitForm([
      'enable_user_settings' => TRUE,
      'enable_dark_mode' => '1',
    ], 'Save');
    $this->assertStringContainsString('"dark_mode":"1"', $this->drupalGet($user1->toUrl('edit-form')));

    // Login as admin.
    $this->drupalLogin($this->rootUser);
    $this->assertStringContainsString('"dark_mode":"0"', $this->drupalGet('edit-form'));
  }

  /**
   * Disabled: Test user settings.
   */
  public function disabledTestUserSettings(): void {
    $user1 = $this->createUser();
    $this->drupalLogin($user1);
    // Change something on user1 edit form.
    $this->drupalGet($user1->toUrl('edit-form'));
    $this->submitForm([
      'enable_user_settings' => TRUE,
      'high_contrast_mode' => TRUE,
      'enable_dark_mode' => '1',
    ], 'Save');

    // Check logged-in's user is not affected.
    $loggedInUserResponse = $this->drupalGet('edit-form');
    $this->assertStringContainsString('"high_contrast_mode":false', $loggedInUserResponse);
    $this->assertStringContainsString('"dark_mode":"0"', $loggedInUserResponse);

    // Check settings of user1.
    $this->drupalLogin($user1);
    $rootUserResponse = $this->drupalGet($user1->toUrl('edit-form'));
    $this->assertStringContainsString('"high_contrast_mode":true', $rootUserResponse);
    $this->assertStringContainsString('"dark_mode":"1"', $rootUserResponse);
  }

}
