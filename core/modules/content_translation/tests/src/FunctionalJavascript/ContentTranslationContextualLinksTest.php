<?php

namespace Drupal\Tests\content_translation\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests that contextual links are available for content translation.
 *
 * @group content_translation
 */
class ContentTranslationContextualLinksTest extends WebDriverTestBase {

  /**
   * The 'translator' user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $translator;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['content_translation', 'contextual', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set up an additional language.
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Create a content type.
    $this->drupalCreateContentType(['type' => 'page']);

    // Enable content translation.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/config/regional/content-language');
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][page][translatable]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->drupalLogout();

    // Create a translator user.
    $permissions = [
      'access contextual links',
      'administer nodes',
      'edit any page content',
      'translate any entity',
    ];
    $this->translator = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests that a contextual link is available for translating a node.
   */
  public function testContentTranslationContextualLinks() {
    $node = $this->drupalCreateNode(['type' => 'page', 'title' => 'Test']);

    // Check that the translate link appears on the node page.
    $this->drupalLogin($this->translator);
    $this->drupalGet('node/' . $node->id());
    $link = $this->assertSession()->waitForElement('css', '[data-contextual-id^="node:node=1"] .contextual-links a:contains("Translate")');
    $this->assertContains('node/1/translations', $link->getAttribute('href'));
  }

}
