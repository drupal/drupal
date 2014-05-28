<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserTranslationUITest.
 */

namespace Drupal\user\Tests;

use Drupal\content_translation\Tests\ContentTranslationUITest;

/**
 * Tests the User Translation UI.
 */
class UserTranslationUITest extends ContentTranslationUITest {

  /**
   * The user name of the test user.
   */
  protected $name;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'user', 'views');

  public static function getInfo() {
    return array(
      'name' => 'User translation UI',
      'description' => 'Tests the user translation UI.',
      'group' => 'User',
    );
  }

  function setUp() {
    $this->entityTypeId = 'user';
    $this->testLanguageSelector = FALSE;
    $this->name = $this->randomName();
    parent::setUp();

    entity_get_controller('user')->resetCache();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getTranslatorPermission().
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('administer users'));
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    // User name is not translatable hence we use a fixed value.
    return array('name' => $this->name) + parent::getNewEntityValues($langcode);
  }

  /**
   * Tests translate link on user admin list.
   */
  function testTranslateLinkUserAdminPage() {
    $this->admin_user = $this->drupalCreateUser(array_merge(parent::getTranslatorPermissions(), array('access administration pages', 'administer users')));
    $this->drupalLogin($this->admin_user);

    $uid = $this->createEntity(array('name' => $this->randomName()), $this->langcodes[0]);

    // Verify translation links.
    $this->drupalGet('admin/people');
    $this->assertResponse(200);
    $this->assertLinkByHref('user/' . $uid . '/translations');
  }

}
