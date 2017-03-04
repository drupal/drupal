<?php

namespace Drupal\views\Tests\Entity;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language_test\Entity\NoLanguageEntityTest;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the view creation of non-translatable entities.
 *
 * @group views
 */
class ViewNonTranslatableEntityTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_test',
    'content_translation',
    'language_test',
    'views_ui',
  ];

  /**
   * Tests displaying a view of non-translatable entities.
   */
  public function testViewNoTranslatableEntity() {
    // Add a new language.
    ConfigurableLanguage::createFromLangcode('sr')->save();

    // Create a non-translatable entity.
    $no_language_entity = NoLanguageEntityTest::create();
    $no_language_entity->save();

    // Visit the view page and assert it is displayed properly.
    $this->drupalGet('no-entity-translation-view');
    $this->assertResponse(200);
    $this->assertText('No Entity Translation View');
    $this->assertText($no_language_entity->uuid());
  }

}
