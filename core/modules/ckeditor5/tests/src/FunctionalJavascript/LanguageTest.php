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
   * @param string $blockquote_translation
   *   The expected translation for blockquote toolbar button.
   *
   * @dataProvider provider
   */
  public function test(string $langcode, string $blockquote_translation): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-blockQuote'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-blockQuote', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();
    $this->saveNewTextFormat($page, $assert_session);

    ConfigurableLanguage::createFromLangcode($langcode)->save();
    $this->config('system.site')->set('default_langcode', $langcode)->save();

    $this->drupalGet('node/add');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
    // Ensure that blockquote button is translated.
    $assert_session->elementExists('xpath', "//span[text()='$blockquote_translation']");
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
        'blockquote_translation' => 'คำพูดบล็อก',
      ],
      'Language code transformed from browser mappings' => [
        'langcode' => 'zh-hans',
        'blockquote_translation' => '块引用',
      ],
    ];
  }

}
