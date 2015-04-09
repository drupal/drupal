<?php

/**
 * @file
 * Contains \Drupal\content_translation\Tests\ContentTranslationOperationsTest.
 */

namespace Drupal\content_translation\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Tests\NodeTestBase;

/**
 * Tests the content translation operations available in the content listing.
 *
 * @group content_translation
 */
class ContentTranslationOperationsTest extends NodeTestBase {

  /**
   * A base user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $baseUser1;

  /**
   * A base user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $baseUser2;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['language', 'content_translation', 'node', 'views'];

  protected function setUp() {
    parent::setUp();

    // Enable additional languages.
    $langcodes = ['es', 'ast'];
    foreach ($langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    // Enable translation for the current entity type and ensure the change is
    // picked up.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'article', TRUE);
    drupal_static_reset();
    \Drupal::entityManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('entity.definition_update_manager')->applyUpdates();

    $this->baseUser1 = $this->drupalCreateUser(['access content overview']);
    $this->baseUser2 = $this->drupalCreateUser(['access content overview', 'create content translations', 'update content translations', 'delete content translations']);
  }

  /**
   * Test that the operation "Translate" is displayed in the content listing.
   */
  function testOperationTranslateLink() {
    $node = $this->drupalCreateNode(['type' => 'article', 'langcode' => 'es']);
    // Verify no translation operation links are displayed for users without
    // permission.
    $this->drupalLogin($this->baseUser1);
    $this->drupalGet('admin/content');
    $this->assertNoLinkByHref('node/' . $node->id() . '/translations');
    $this->drupalLogout();
    // Verify there's a translation operation link for users with enough
    // permissions.
    $this->drupalLogin($this->baseUser2);
    $this->drupalGet('admin/content');
    $this->assertLinkByHref('node/' . $node->id() . '/translations');
  }

}
