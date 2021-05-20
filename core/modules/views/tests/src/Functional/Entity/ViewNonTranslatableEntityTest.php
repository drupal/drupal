<?php

namespace Drupal\Tests\views\Functional\Entity;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language_test\Entity\NoLanguageEntityTest;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the view creation of non-translatable entities.
 *
 * @group views
 */
class ViewNonTranslatableEntityTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'entity_test',
    'content_translation',
    'language_test',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('No Entity Translation View');
    $this->assertSession()->pageTextContains($no_language_entity->uuid());
  }

}
