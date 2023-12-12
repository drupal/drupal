<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\locale\SourceString;
use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewTestData;
use Drupal\Tests\node\Traits\NodeCreationTrait;

// cSpell:ignore Blokk hozzáadása

/**
 * Tests the display UI.
 *
 * @group views_ui
 */
class DisplayTest extends WebDriverTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'contextual',
    'node',
    'language',
    'locale',
    'views',
    'views_ui',
    'views_test_config',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public static $testViews = ['test_content_ajax', 'test_display'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ViewTestData::createTestViews(self::class, ['views_test_config']);

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer views',
      'administer nodes',
      'access content overview',
      'access contextual links',
    ]);

    // Disable automatic live preview to make the sequence of calls clearer.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.always_live_preview', FALSE)->save();
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests adding a display.
   */
  public function testAddDisplay() {
    $this->drupalGet('admin/structure/views/view/test_content_ajax');
    $page = $this->getSession()->getPage();

    $page->find('css', '#views-display-menu-tabs .add')->click();

    // Wait for the animation to complete.
    $this->getSession()->wait(1000, "jQuery(':animated').length === 0;");

    // Add the display.
    $page->find('css', '#edit-displays-top-add-display-block')->click();

    $element = $page->findById('views-display-menu-tabs')->findLink('Block');
    $this->assertNotEmpty($element);
  }

  /**
   * Tests setting the administrative title.
   */
  public function testRenameDisplayAdminName() {
    $titles = ['New admin title', '</title><script>alert("alert!")</script>'];
    foreach ($titles as $new_title) {
      $this->drupalGet('admin/structure/views/view/test_content_ajax');
      $page = $this->getSession()->getPage();

      $page->findLink('Edit view name/description')->click();
      $this->getSession()->executeScript("document.title = 'Initial title | " . \Drupal::config('system.site')->get('name') . "'");

      $admin_name_field = $this->assertSession()
        ->waitForField('Administrative name');
      $dialog_buttons = $page->find('css', '.ui-dialog-buttonset');
      $admin_name_field->setValue($new_title);

      $dialog_buttons->pressButton('Apply');
      $this->assertJsCondition("document.title === '" . $new_title . " (Content) | " . \Drupal::config('system.site')->get('name') . "'");
    }
  }

  /**
   * Tests contextual links on Views page displays.
   */
  public function testPageContextualLinks() {
    $view = View::load('test_display');
    $view->enable()->save();
    $this->container->get('router.builder')->rebuildIfNeeded();

    // Create node so the view has content and the contextual area is higher
    // than 0 pixels.
    $this->drupalCreateContentType(['type' => 'page']);
    $this->createNode();

    // When no "main content" block is placed, we find a contextual link
    // placeholder for editing just the view.
    $this->drupalGet('test-display');
    $page = $this->getSession()->getPage();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $selector = '.views-element-container';
    $this->toggleContextualTriggerVisibility($selector);

    $element = $this->getSession()->getPage()->find('css', $selector);
    $element->find('css', '.contextual button')->press();

    $contextual_container_id = 'entity.view.edit_form:view=test_display:location=page&name=test_display&display_id=page_1&langcode=en';
    $contextual_container = $page->find('css', '[data-contextual-id="' . $contextual_container_id . '"]');
    $this->assertNotEmpty($contextual_container);

    $edit_link = $contextual_container->findLink('Edit view');
    $this->assertNotEmpty($edit_link);

    // When a "main content" is placed, we still find a contextual link
    // placeholder for editing just the view (not the main content block).
    // @see system_block_view_system_main_block_alter()
    $this->drupalPlaceBlock('system_main_block', ['id' => 'main_content']);
    $contextual_container = $page->find('css', '[data-contextual-id="' . $contextual_container_id . '"]');
    $this->assertNotEmpty($contextual_container);
  }

  /**
   * Toggles the visibility of a contextual trigger.
   *
   * @param string $selector
   *   The selector for the element that contains the contextual Rink.
   */
  protected function toggleContextualTriggerVisibility($selector) {
    // Hovering over the element itself with should be enough, but does not
    // work. Manually remove the visually-hidden class.
    $this->getSession()->executeScript("jQuery('{$selector} .contextual .trigger').toggleClass('visually-hidden');");
  }

  /**
   * Test if 'add' translations are filtered from multilingual display options.
   */
  public function testAddDisplayBlockTranslation() {

    // Set up an additional language (Hungarian).
    $langcode = 'hu';
    ConfigurableLanguage::createFromLangcode($langcode)->save();
    $config = $this->config('language.negotiation');
    $config->set('url.prefixes', [$langcode => $langcode])->save();
    \Drupal::service('kernel')->rebuildContainer();
    \Drupal::languageManager()->reset();

    // Add Hungarian translations.
    $this->addTranslation($langcode, 'Block', 'Blokk');
    $this->addTranslation($langcode, 'Add @display', '@display hozzáadása');

    $this->drupalGet('hu/admin/structure/views/view/test_display');
    $page = $this->getSession()->getPage();

    $page->find('css', '#views-display-menu-tabs .add')->click();

    // Wait for the animation to complete.
    $this->getSession()->wait(1000, "jQuery(':animated').length === 0;");

    // Look for the input element, always in second spot.
    $elements = $page->findAll('css', '.add ul input');
    $this->assertEquals('Blokk', $elements[1]->getAttribute('value'));
  }

  /**
   * Helper function for adding interface text translations.
   */
  private function addTranslation($langcode, $source_string, $translation_string) {
    $storage = \Drupal::service('locale.storage');
    $string = $storage->findString(['source' => $source_string]);
    if (is_null($string)) {
      $string = new SourceString();
      $string
        ->setString($source_string)
        ->setStorage($storage)
        ->save();
    }
    $storage->createTranslation([
      'lid' => $string->getId(),
      'language' => $langcode,
      'translation' => $translation_string,
    ])->save();
  }

}
