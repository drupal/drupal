<?php

declare(strict_types=1);

namespace Drupal\Tests\config_translation\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Translate settings and entities to various languages.
 *
 * @group config_translation
 */
class ConfigTranslationUiTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'contextual',
    'node',
    'views',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that contextual link related to views.
   */
  public function testViewContextualLink(): void {
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

  /**
   * Tests that the add, edit and delete operations open in a modal.
   */
  public function testConfigTranslationDialog(): void {
    $page = $this->getSession()->getPage();
    ConfigurableLanguage::createFromLangcode('fi')->save();

    $user = $this->drupalCreateUser([
      'translate configuration',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('admin/structure/views/view/content/translate');
    $this->clickLink('Add');
    $this->assertEquals('Add Finnish translation for Content view', $this->assertSession()->waitForElement('css', '.ui-dialog-title')->getText());
    $this->assertSession()->fieldExists('translation[config_names][views.view.content][label]')->setValue('Content FI');
    $page->find('css', '.ui-dialog-buttonset')->pressButton('Save translation');
    $this->assertSession()->pageTextContains('Successfully saved Finnish translation.');

    $this->clickLink('Edit');
    $this->assertEquals('Edit Finnish translation for Content view', $this->assertSession()->waitForElement('css', '.ui-dialog-title')->getText());
    $this->getSession()->getPage()->find('css', '.ui-dialog-buttonset')->pressButton('Save translation');
    $this->assertSession()->pageTextContains('Successfully updated Finnish translation.');

    $page->find('css', '.dropbutton-toggle button')->click();
    $this->clickLink('Delete');
    $this->assertEquals('Are you sure you want to delete the Finnish translation of Content view?', $this->assertSession()->waitForElement('css', '.ui-dialog-title')->getText());
    $page->find('css', '.ui-dialog-buttonset')->pressButton('Delete');
    $this->assertSession()->pageTextContains('Finnish translation of Content view was deleted');
  }

}
