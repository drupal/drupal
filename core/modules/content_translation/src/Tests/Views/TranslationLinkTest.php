<?php

/**
 * @file
 * Contains \Drupal\content_translation\Tests\Views\TranslationLinkTest.
 */

namespace Drupal\content_translation\Tests\Views;

use Drupal\views\Tests\ViewTestBase;
use Drupal\content_translation\Tests\ContentTranslationTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the content translation overview link field handler.
 *
 * @group content_translation
 * @see \Drupal\content_translation\Plugin\views\field\TranslationLink
 */
class TranslationLinkTest extends ContentTranslationTestBase {

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
  public static $modules = array('content_translation_test_views');

  function setUp() {
    // @todo Use entity_type once it is has multilingual Views integration.
    $this->entityTypeId = 'user';

    parent::setUp();

    // Assign user 1  a language code so that the entity can be translated.
    $user = user_load(1);
    $user->langcode = 'en';
    $user->save();

    ViewTestData::createTestViews(get_class($this), array('content_translation_test_views'));
  }

  /**
   * Tests the content translation overview link field handler.
   */
  public function testTranslationLink() {
    $this->drupalGet('test-entity-translations-link');
    $this->assertLinkByHref('user/1/translations');
    $this->assertNoLinkByHref('user/2/translations', 'The translations link is not present when content_translation_translate_access() is FALSE.');
  }

}
