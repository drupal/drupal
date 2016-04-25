<?php

namespace Drupal\language\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests entity type without language support.
 *
 * This is to ensure that an entity type without language support can not
 * enable the language select from the content language settings page.
 *
 * @group language
 */
class EntityTypeWithoutLanguageFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'language',
    'language_test',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and login administrative user.
    $admin_user = $this->drupalCreateUser(array(
      'administer languages',
    ));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests configuration options with an entity without language definition.
   */
  public function testEmptyLangcode() {
    // Assert that we can not enable language select from
    // content language settings page.
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertNoField('entity_types[no_language_entity_test]');
  }

}
