<?php

/**
 * @file
 * Contains \Drupal\color\Tests\ColorConfigSchemaTest.
 */

namespace Drupal\color\Tests;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Color config schema.
 */
class ColorConfigSchemaTest extends WebTestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('color');

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  public static function getInfo() {
    return array(
      'name' => 'Color config schema',
      'description' => 'Ensures the color config schema is correct.',
      'group' => 'Color',
    );
  }

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
    \Drupal::service('theme_handler')->enable(array('bartik'));

    // Create user.
    $this->adminUser = $this->drupalCreateUser(array('administer themes'));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests whether the color config schema is valid.
   */
  function testValidColorConfigSchema() {
    $settings_path = 'admin/appearance/settings/bartik';
    $edit['scheme'] = '';
    $edit['palette[bg]'] = '#123456';
    $this->drupalPostForm($settings_path, $edit, t('Save configuration'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'color.theme.bartik', \Drupal::config('color.theme.bartik')->get());
  }

}
