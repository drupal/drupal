<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\language\Entity\ConfigurableLanguage;

// cspell:ignore คำพูดบล็อก sourceediting

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
    // Special case: textPartLanguage toolbar item can only create `<span lang>`
    // but not `<span>`. The purpose of this test is to test translations, not
    // the configuration of the textPartLanguage functionality. So, make sure
    // that `<span>` can be created so we can test how UI translations work when
    // using `textPartLanguage`.
    if ($toolbar_item_name === 'textPartLanguage') {
      $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-sourceEditing'));
      $this->triggerKeyUp('.ckeditor5-toolbar-item-sourceEditing', 'ArrowDown');
      $assert_session->assertWaitOnAjaxRequest();

      // The Source Editing plugin settings form should now be present and should
      // have no allowed tags configured.
      $page->clickLink('Source editing');
      $this->assertNotNull($assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-sourceediting-allowed-tags"]'));

      $javascript = <<<JS
        const allowedTags = document.querySelector('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-sourceediting-allowed-tags"]');
        allowedTags.value = '<span>';
        allowedTags.dispatchEvent(new Event('input'));
JS;
      $this->getSession()->executeScript($javascript);
    }
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
   * @return string[][]
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
