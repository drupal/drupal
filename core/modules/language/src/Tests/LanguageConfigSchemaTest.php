<?php

/**
 * @file
 * Contains \Drupal\language\Tests\LanguageConfigSchemaTest.
 */

namespace Drupal\language\Tests;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Ensures the language config schema is correct.
 *
 * @group language
 */
class LanguageConfigSchemaTest extends WebTestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'menu_link_content');

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user.
    $this->adminUser = $this->drupalCreateUser(array('administer languages'));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests whether the language config schema is valid.
   */
  function testValidLanguageConfigSchema() {
    // Make sure no language configuration available by default.
    $config_data = \Drupal::config('language.settings')->get();
    $this->assertTrue(empty($config_data));

    $settings_path = 'admin/config/regional/content-language';

    // Enable translation for menu link.
    $edit['entity_types[menu_link_content]'] = TRUE;
    $edit['settings[menu_link_content][menu_link_content][settings][language][language_show]'] = TRUE;

    // Enable translation for user.
    $edit['entity_types[user]'] = TRUE;
    $edit['settings[user][user][settings][language][language_show]'] = TRUE;
    $edit['settings[user][user][settings][language][langcode]'] = 'en';

    $this->drupalPostForm($settings_path, $edit, t('Save configuration'));

    $config_data = \Drupal::config('language.settings')->get();
    // Make sure configuration saved correctly.
    $this->assertTrue($config_data['entities']['menu_link_content']['menu_link_content']['language']['default_configuration']['language_show']);

    $this->assertConfigSchema(\Drupal::service('config.typed'), 'language.settings', $config_data);
  }

}
