<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Ensures the language config schema is correct.
 *
 * @group language
 */
class LanguageConfigSchemaTest extends BrowserTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'menu_link_content'];

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

    // Create user.
    $this->adminUser = $this->drupalCreateUser(['administer languages']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests whether the language config schema is valid.
   */
  public function testValidLanguageConfigSchema(): void {
    // Make sure no language configuration available by default.
    $config_data = $this->config('language.settings')->get();
    $this->assertEmpty($config_data);

    $settings_path = 'admin/config/regional/content-language';

    // Enable translation for menu link.
    $edit['entity_types[menu_link_content]'] = TRUE;
    $edit['settings[menu_link_content][menu_link_content][settings][language][language_alterable]'] = TRUE;

    // Enable translation for user.
    $edit['entity_types[user]'] = TRUE;
    $edit['settings[user][user][settings][language][language_alterable]'] = TRUE;
    $edit['settings[user][user][settings][language][langcode]'] = 'en';

    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save configuration');

    $config_data = $this->config('language.content_settings.menu_link_content.menu_link_content');
    // Make sure configuration saved correctly.
    $this->assertTrue($config_data->get('language_alterable'));

    $this->assertConfigSchema(\Drupal::service('config.typed'), $config_data->getName(), $config_data->get());
  }

}
