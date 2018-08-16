<?php

namespace Drupal\Tests\config_translation\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Translate settings and entities to various languages.
 *
 * @group config_translation
 */
class ConfigTranslationUiTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'config_translation',
    'contextual',
    'node',
    'views',
    'views_ui',
  ];

  /**
   * Tests that contextual link related to views.
   */
  public function testViewContextualLink() {
    $user = $this->drupalCreateUser([
      'translate configuration',
      'access contextual links',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('node');
    $contextualLinks = $this->assertSession()->waitForElement('css', '.contextual-links');
    $link = $contextualLinks->findLink('Translate view');
    $this->assertNotNull($link, 'Translate view contextual link added.');
  }

}
