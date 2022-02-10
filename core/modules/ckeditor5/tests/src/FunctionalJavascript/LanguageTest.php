<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\language\Entity\ConfigurableLanguage;

// cspell:ignore คำพูดบล็อก

/**
 * Tests for CKEditor 5 UI translations.
 *
 * @group ckeditor5
 * @internal
 */
class LanguageTest extends CKEditor5TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'locale',
  ];

  /**
   * Integration test to ensure that CKEditor 5 UI translations are loaded.
   *
   * @param string $langcode
   *   The language code.
   * @param string $toolbar_item_name
   *   The CKEditor 5 plugin to enable.
   * @param string $toolbar_item_translation
   *   The expected translation for CKEditor 5 plugin toolbar button.
   *
   * @dataProvider provider
   */
  public function test(string $langcode, string $toolbar_item_name, string $toolbar_item_translation): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);
    $this->assertNotEmpty($assert_session->waitForElement('css', ".ckeditor5-toolbar-item-$toolbar_item_name"));
    $this->triggerKeyUp(".ckeditor5-toolbar-item-$toolbar_item_name", 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();
    $this->saveNewTextFormat($page, $assert_session);

    ConfigurableLanguage::createFromLangcode($langcode)->save();
    $this->config('system.site')->set('default_langcode', $langcode)->save();

    $this->drupalGet('node/add');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
    // Ensure that blockquote button is translated.
    $assert_session->elementExists('xpath', "//span[text()='$toolbar_item_translation']");
  }

  /**
   * Data provider for ensuring CKEditor 5 UI translations are loaded.
   *
   * @return \string[][]
   */
  public function provider(): array {
    return [
      'Language code both in Drupal and CKEditor' => [
        'langcode' => 'th',
        'toolbar_item_name' => 'blockQuote',
        'toolbar_item_translation' => 'คำพูดบล็อก',
      ],
      'Language code transformed from browser mappings' => [
        'langcode' => 'zh-hans',
        'toolbar_item_name' => 'blockQuote',
        'toolbar_item_translation' => '块引用',
      ],
      'Language configuration conflict' => [
        'langcode' => 'fr',
        'toolbar_item_name' => 'textPartLanguage',
        // cSpell:disable-next-line
        'toolbar_item_translation' => 'Choisir la langue',
      ],
    ];
  }

}
