<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserTranslationUITest.
 */

namespace Drupal\user\Tests;

use Drupal\content_translation\Tests\ContentTranslationUITest;

/**
 * Tests the User Translation UI.
 *
 * @group user
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

  function setUp() {
    $this->entityTypeId = 'user';
    $this->testLanguageSelector = FALSE;
    $this->name = $this->randomMachineName();
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

}
