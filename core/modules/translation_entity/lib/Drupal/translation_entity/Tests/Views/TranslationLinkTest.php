<?php

/**
 * @file
 * Contains \Drupal\translation_entity\Tests\Views\TranslationLinkTest.
 */

namespace Drupal\translation_entity\Tests\Views;

use Drupal\views\Tests\ViewTestBase;
use Drupal\translation_entity\Tests\EntityTranslationUITest;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the Entity translation overview link field handler.
 *
 * @see \Drupal\translation_entity\Plugin\views\field\TranslationLink
 */
class TranslationLinkTest extends EntityTranslationUITest {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_translations_link');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('translation_entity_test_views');

  public static function getInfo() {
    return array(
      'name' => 'Entity Translation: Link field',
      'description' => 'Tests the Entity translation overview link field handler.',
      'group' => 'Views Modules',
    );
  }

  function setUp() {
    // @todo Use entity_type once it is has multilingual Views integration.
    $this->entityType = 'user';

    parent::setUp();

    ViewTestData::importTestViews(get_class($this), array('translation_entity_test_views'));
  }

  /**
   * Implements \Drupal\translation_entity\Tests\EntityTranslationUITest::getTranslatorPermission().
   */
  function getTranslatorPermissions() {
    return array("translate $this->entityType entities", 'edit original values');
  }

  /**
   * Tests the Entity translation overview link field handler.
   */
  public function testTranslationLink() {
    $this->drupalGet('test-entity-translations-link');
    $this->assertLinkByHref('user/1/translations');
    $this->assertNoLinkByHref('user/2/translations', 'The translations link is not present when translation_entity_translate_access() is FALSE.');
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::testTranslationUI().
   */
  public function testTranslationUI() {
    // @todo \Drupal\translation_entity\Tests\EntityTranslationUITest contains
    //   essential helper methods that should be seprarated from test methods.
  }

}
