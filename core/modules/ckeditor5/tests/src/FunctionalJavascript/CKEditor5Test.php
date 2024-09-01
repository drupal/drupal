<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\Core\Language\LanguageManager;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\RoleInterface;
use Symfony\Component\Validator\ConstraintViolation;

// cspell:ignore esque māori sourceediting splitbutton upcasted

/**
 * Tests for CKEditor 5.
 *
 * @group ckeditor5
 * @group #slow
 * @internal
 */
class CKEditor5Test extends CKEditor5TestBase {

  use TestFileCreationTrait;
  use CKEditor5TestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media_library',
    'language',
  ];

  /**
   * Tests configuring CKEditor 5 for existing content.
   */
  public function testExistingContent(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add a node with text rendered via the Plain Text format.
    $this->drupalGet('node/add/page');
    $page->fillField('title[0][value]', 'My test content');
    $page->fillField('body[0][value]', '<p>This is test content</p>');
    $page->pressButton('Save');
    $assert_session->responseNotContains('<p>This is test content</p>');
    $assert_session->responseContains('&lt;p&gt;This is test content&lt;/p&gt;');

    $this->addNewTextFormat();

    // Change the node to use the new text format.
    $this->drupalGet('node/1/edit');

    $page->selectFieldOption('body[0][format]', 'ckeditor5');
    $this->assertNotEmpty($assert_session->waitForText('Change text format?'));
    $page->pressButton('Continue');
    // Ensure the editor is loaded.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
    $page->pressButton('Save');

    // Assert that the HTML is rendered correctly.
    $assert_session->responseContains('<p>This is test content</p>');
    $assert_session->responseNotContains('&lt;p&gt;This is test content&lt;/p&gt;');
  }

  /**
   * Test headings configuration.
   */
  public function testHeadingsPlugin(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->addNewTextFormat();
    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');
    $this->assertHtmlEsqueFieldValueEquals('filters[filter_html][settings][allowed_html]', '<br> <p> <h2> <h3> <h4> <h5> <h6> <strong> <em>');

    $this->drupalGet('node/add/page');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-heading-dropdown button'));

    $page->find('css', '.ck-heading-dropdown button')->click();

    // Get all the headings available in dropdown.
    $headings_dropdown = $page->findAll('css', '.ck-heading-dropdown li .ck-button__label');

    // Create array of available headings.
    $available_headings = [];
    foreach ($headings_dropdown as $item) {
      $available_headings[] = $item->getText();
    }

    $this->assertSame([
      'Paragraph',
      'Heading 2',
      'Heading 3',
      'Heading 4',
      'Heading 5',
      'Heading 6',
    ], $available_headings);

    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');
    $this->assertTrue($page->hasUncheckedField('editor[settings][plugins][ckeditor5_heading][enabled_headings][heading1]'));
    $page->checkField('editor[settings][plugins][ckeditor5_heading][enabled_headings][heading1]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTrue($page->hasCheckedField('editor[settings][plugins][ckeditor5_heading][enabled_headings][heading1]'));
    $this->assertTrue($page->hasCheckedField('editor[settings][plugins][ckeditor5_heading][enabled_headings][heading2]'));
    $page->uncheckField('editor[settings][plugins][ckeditor5_heading][enabled_headings][heading2]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTrue($page->hasUncheckedField('editor[settings][plugins][ckeditor5_heading][enabled_headings][heading2]'));
    $this->assertTrue($page->hasCheckedField('editor[settings][plugins][ckeditor5_heading][enabled_headings][heading4]'));
    $page->uncheckField('editor[settings][plugins][ckeditor5_heading][enabled_headings][heading4]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTrue($page->hasUncheckedField('editor[settings][plugins][ckeditor5_heading][enabled_headings][heading4]'));
    $this->assertHtmlEsqueFieldValueEquals('filters[filter_html][settings][allowed_html]', '<br> <p> <h1> <h3> <h5> <h6> <strong> <em>');
    $this->assertTrue($page->hasUncheckedField('editor[settings][plugins][ckeditor5_heading][enabled_headings][heading4]'));

    $page->pressButton('Save configuration');

    $this->drupalGet('node/add/page');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-heading-dropdown button'));

    $page->find('css', '.ck-heading-dropdown button')->click();

    // Get all the headings available in dropdown.
    $headings_dropdown = $page->findAll('css', '.ck-heading-dropdown li .ck-button__label');

    // Create array of available headings.
    $available_headings = [];
    foreach ($headings_dropdown as $item) {
      $available_headings[] = $item->getText();
    }

    $this->assertSame([
      'Paragraph',
      'Heading 1',
      'Heading 3',
      'Heading 5',
      'Heading 6',
    ], $available_headings);
  }

  /**
   * Test for Language of Parts plugin.
   */
  public function testLanguageOfPartsPlugin(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->languageOfPartsPluginInitialConfigurationHelper($page, $assert_session);

    // Test for "United Nations' official languages" option.
    $languages = LanguageManager::getUnitedNationsLanguageList();
    $this->languageOfPartsPluginConfigureLanguageListHelper($page, $assert_session, 'un');
    $this->languageOfPartsPluginTestHelper($page, $assert_session, $languages);

    // Test for "Drupal predefined languages" option.
    $languages = LanguageManager::getStandardLanguageList();
    $this->languageOfPartsPluginConfigureLanguageListHelper($page, $assert_session, 'all');
    $this->languageOfPartsPluginTestHelper($page, $assert_session, $languages);

    // Test for "Site-configured languages" option.
    ConfigurableLanguage::createFromLangcode('ar')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('mi')->setName('Māori')->save();
    $configured_languages = \Drupal::languageManager()->getLanguages();
    $languages = [];
    foreach ($configured_languages as $language) {
      $language_name = $language->getName();
      $language_code = $language->getId();
      $languages[$language_code] = [$language_name];
    }
    $this->languageOfPartsPluginConfigureLanguageListHelper($page, $assert_session, 'site_configured');
    $this->languageOfPartsPluginTestHelper($page, $assert_session, $languages);
  }

  /**
   * Helper to configure CKEditor5 with Language plugin.
   */
  public function languageOfPartsPluginInitialConfigurationHelper($page, $assert_session) {
    $this->createNewTextFormat($page, $assert_session);
    // Press arrow down key to add the button to the active toolbar.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-textPartLanguage'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-textPartLanguage', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();

    // The CKEditor 5 module should warn that `<span>` cannot be created.
    $assert_session->waitForElement('css', '[role=alert][data-drupal-message-type="warning"]:contains("The Language plugin needs another plugin to create <span>, for it to be able to create the following attributes: <span lang dir>. Enable a plugin that supports creating this tag. If none exists, you can configure the Source Editing plugin to support it.")');

    // Make `<span>` creatable.
    $this->assertNotEmpty($assert_session->elementExists('css', '.ckeditor5-toolbar-item-sourceEditing'));
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
    // Dispatching an `input` event does not work in WebDriver. Enabling another
    // toolbar item which has no associated HTML elements forces it.
    $this->triggerKeyUp('.ckeditor5-toolbar-item-undo', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();

    // Confirm there are no longer any warnings.
    $assert_session->waitForElementRemoved('css', '[data-drupal-messages] [role="alert"]');
    $page->pressButton('Save configuration');
    $assert_session->responseContains('Added text format <em class="placeholder">ckeditor5</em>.');
  }

  /**
   * Helper to set language list option for CKEditor.
   */
  public function languageOfPartsPluginConfigureLanguageListHelper($page, $assert_session, $option) {
    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');
    $this->assertNotEmpty($assert_session->waitForElement('css', 'a[href^="#edit-editor-settings-plugins-ckeditor5-language"]'));

    // Set correct value.
    $vertical_tab_link = $page->find('xpath', "//ul[contains(@class, 'vertical-tabs__menu')]/li/a[starts-with(@href, '#edit-editor-settings-plugins-ckeditor5-language')]");
    $vertical_tab_link->click();
    $select = $page->findField('editor[settings][plugins][ckeditor5_language][language_list]');
    if ($select->getValue() !== $option) {
      $select->selectOption($option);
      $assert_session->assertWaitOnAjaxRequest();
    }
    $page->pressButton('Save configuration');
    $assert_session->responseContains('The text format <em class="placeholder">ckeditor5</em> has been updated.');
  }

  /**
   * Validate expected languages available in editor.
   */
  public function languageOfPartsPluginTestHelper($page, $assert_session, $configured_languages) {
    $this->drupalGet('node/add/page');
    $this->assertNotEmpty($assert_session->waitForText('Choose language'));

    // Click on the dropdown button.
    $page->find('css', '.ck-text-fragment-language-dropdown button')->click();

    // Get all the languages available in dropdown.
    $current_languages = $page->findAll('css', '.ck-text-fragment-language-dropdown li .ck-button__label');

    // Remove "Remove language" element from current languages.
    array_shift($current_languages);

    // Create array of full language name.
    $languages = [];
    foreach ($current_languages as $item) {
      $languages[] = $item->getText();
    }

    // Return the values from a single column.
    $configured_languages = array_column($configured_languages, 0);

    // Sort on full language name.
    asort($configured_languages);
    $this->assertSame(array_values($configured_languages), $languages);
  }

  /**
   * Gets the titles of the vertical tabs in the given container.
   *
   * @param string $container_selector
   *   The container in which to look for vertical tabs.
   * @param bool $visible_only
   *   (optional) Whether to restrict to only the visible vertical tabs. TRUE by
   *   default.
   *
   * @return string[]
   *   The titles of all vertical tabs menu items, restricted to only
   *   visible ones by default.
   *
   * @throws \LogicException
   */
  private function getVerticalTabs(string $container_selector, bool $visible_only = TRUE): array {
    $page = $this->getSession()->getPage();

    // Ensure the container exists.
    $container = $page->find('css', $container_selector);
    if ($container === NULL) {
      throw new \LogicException('The given container should exist.');
    }

    // Make sure that the container selector contains exactly one Vertical Tabs
    // UI component.
    $vertical_tabs = $container->findAll('css', '.vertical-tabs');
    if (count($vertical_tabs) != 1) {
      throw new \LogicException('The given container should contain exactly one Vertical Tabs component.');
    }

    $vertical_tabs = $container->findAll('css', '.vertical-tabs__menu-item');
    $vertical_tabs_titles = [];
    foreach ($vertical_tabs as $vertical_tab) {
      if ($visible_only && !$vertical_tab->isVisible()) {
        continue;
      }
      $title = $vertical_tab->find('css', '.vertical-tabs__menu-item-title')->getHtml();
      // When retrieving visible vertical tabs, mark the selected one.
      if ($visible_only && $vertical_tab->hasClass('is-selected')) {
        $title = "➡️$title";
      }
      $vertical_tabs_titles[] = $title;
    }
    return $vertical_tabs_titles;
  }

  /**
   * Enables a disabled CKEditor 5 toolbar item.
   *
   * @param string $toolbar_item_id
   *   The toolbar item to enable.
   */
  protected function enableDisabledToolbarItem(string $toolbar_item_id): void {
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', ".ckeditor5-toolbar-disabled .ckeditor5-toolbar-item-$toolbar_item_id");
    $this->triggerKeyUp(".ckeditor5-toolbar-item-$toolbar_item_id", 'ArrowDown');
    $assert_session->elementNotExists('css', ".ckeditor5-toolbar-disabled .ckeditor5-toolbar-item-$toolbar_item_id");
    $assert_session->elementExists('css', ".ckeditor5-toolbar-active .ckeditor5-toolbar-item-$toolbar_item_id");
  }

  /**
   * Confirms active tab status is intact after AJAX refresh.
   */
  public function testActiveTabsMaintained(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);

    // Initial vertical tabs: 3 for filters, 1 for CKE5 plugins.
    $this->assertSame([
      'Limit allowed HTML tags and correct faulty HTML',
      'Convert URLs into links',
      'Embed media',
    ], $this->getVerticalTabs('#filter-settings-wrapper', FALSE));
    $this->assertSame([
      'Headings',
    ], $this->getVerticalTabs('#plugin-settings-wrapper', FALSE));

    // Initial visible vertical tabs: 1 for filters, 1 for CKE5 plugins.
    $this->assertSame([
      '➡️Limit allowed HTML tags and correct faulty HTML',
    ], $this->getVerticalTabs('#filter-settings-wrapper'));
    $this->assertSame([
      '➡️Headings',
    ], $this->getVerticalTabs('#plugin-settings-wrapper'));

    // Enable media embed to make a second filter config vertical tab visible.
    $this->assertTrue($page->hasUncheckedField('filters[media_embed][status]'));
    $this->assertNull($assert_session->waitForElementVisible('css', '[data-drupal-selector=edit-filters-media-embed-settings]', 0));
    $page->checkField('filters[media_embed][status]');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '[data-drupal-selector=edit-filters-media-embed-settings]', 0));
    $assert_session->assertWaitOnAjaxRequest();
    // Filter plugins vertical tabs behavior: the filter plugin settings
    // vertical tab with the heaviest filter weight is active by default.
    // Hence enabling the media_embed filter (weight 100) results in its
    // vertical tab being activated (filter_html's weight is -10).
    // @see core/modules/filter/filter.admin.js
    $this->assertSame([
      'Limit allowed HTML tags and correct faulty HTML',
      '➡️Embed media',
    ], $this->getVerticalTabs('#filter-settings-wrapper'));
    $this->assertSame([
      '➡️Headings',
      'Media',
    ], $this->getVerticalTabs('#plugin-settings-wrapper'));

    // Enable upload image to add a third (and fourth) CKE5 plugin vertical tab.
    $this->enableDisabledToolbarItem('drupalInsertImage');
    $assert_session->assertWaitOnAjaxRequest();
    // The active CKE5 plugin settings vertical tab is unchanged.
    $this->assertSame([
      '➡️Headings',
      'Image',
      'Image resize',
      'Media',
    ], $this->getVerticalTabs('#plugin-settings-wrapper'));
    // The active filter plugin settings vertical tab is unchanged.
    $this->assertSame([
      'Limit allowed HTML tags and correct faulty HTML',
      '➡️Embed media',
    ], $this->getVerticalTabs('#filter-settings-wrapper'));

    // Open the CKE5 "Image" plugin settings vertical tab, interact with the
    // subform and observe that the AJAX requests those interactions trigger do
    // not change the active vertical tabs.
    $page->clickLink('Image');
    $assert_session->waitForText('Enable image uploads');
    $this->assertSame([
      'Headings',
      '➡️Image',
      'Image resize',
      'Media',
    ], $this->getVerticalTabs('#plugin-settings-wrapper'));
    $this->assertTrue($page->hasUncheckedField('editor[settings][plugins][ckeditor5_image][status]'));
    $page->checkField('editor[settings][plugins][ckeditor5_image][status]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSame([
      'Headings',
      '➡️Image',
      'Image resize',
      'Media',
    ], $this->getVerticalTabs('#plugin-settings-wrapper'));
    $this->assertSame([
      'Limit allowed HTML tags and correct faulty HTML',
      '➡️Embed media',
    ], $this->getVerticalTabs('#filter-settings-wrapper'));

    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('Added text format ckeditor5');

    // Leave and return to the config form, wait for initialized Vertical Tabs.
    $this->drupalGet('admin/config/content/formats/');
    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');
    $assert_session->waitForElement('css', '.vertical-tabs__menu-item.is-selected');

    // The first CKE5 plugin settings vertical tab is active by default.
    $this->assertSame([
      '➡️Headings',
      'Image',
      'Image resize',
      'Media',
    ], $this->getVerticalTabs('#plugin-settings-wrapper'));
    // Filter plugins vertical tabs behavior: the filter plugin settings
    // vertical tab with the heaviest filter weight is active by default.
    // Hence enabling the media_embed filter (weight 100) results in its
    // vertical tab being activated (filter_html's weight is -10).
    // @see core/modules/filter/filter.admin.js
    $this->assertSame([
      'Limit allowed HTML tags and correct faulty HTML',
      '➡️Embed media',
    ], $this->getVerticalTabs('#filter-settings-wrapper'));

    // Click the 3rd CKE5 plugin vertical tab.
    $page->clickLink($this->getVerticalTabs('#plugin-settings-wrapper')[2]);
    $this->assertSame([
      'Headings',
      'Image',
      '➡️Image resize',
      'Media',
    ], $this->getVerticalTabs('#plugin-settings-wrapper'));

    // Add another CKEditor 5 toolbar item just to trigger an AJAX refresh.
    $this->enableDisabledToolbarItem('blockQuote');
    $assert_session->assertWaitOnAjaxRequest();
    // The active CKE5 plugin settings vertical tab is unchanged.
    $this->assertSame([
      'Headings',
      'Image',
      '➡️Image resize',
      'Media',
    ], $this->getVerticalTabs('#plugin-settings-wrapper'));
    // The active filter plugin settings vertical tab is unchanged.
    $this->assertSame([
      'Limit allowed HTML tags and correct faulty HTML',
      '➡️Embed media',
    ], $this->getVerticalTabs('#filter-settings-wrapper'));
  }

  /**
   * Ensures that CKEditor 5 integrates with file reference filter.
   */
  public function testEditorFileReferenceIntegration(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-drupalInsertImage'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-drupalInsertImage', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();
    $page->clickLink('Image');
    $page->checkField('editor[settings][plugins][ckeditor5_image][status]');
    $assert_session->assertWaitOnAjaxRequest();
    $page->checkField('filters[editor_file_reference][status]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->saveNewTextFormat($page, $assert_session);

    $this->drupalGet('node/add/page');
    $page->fillField('title[0][value]', 'My test content');

    // Ensure that CKEditor 5 is focused.
    $this->click('.ck-content');

    $this->assertNotEmpty($image_upload_field = $page->find('css', '.ck-file-dialog-button input[type="file"]'));
    $image = $this->getTestFiles('image')[0];
    $image_upload_field->attachFile($this->container->get('file_system')->realpath($image->uri));
    // Wait until preview for the image has rendered to ensure that the image
    // upload has completed and the image has been downcast.
    // @see https://www.drupal.org/project/drupal/issues/3250587
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-content img[data-entity-uuid]'));

    // Add alt text to the image.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.image.ck-widget > img'));
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-balloon-panel .ck-text-alternative-form'));
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-text-alternative-form input[type=text]');
    $alt_override_input->setValue('There is now alt text');
    $this->getBalloonButton('Save')->click();
    $page->pressButton('Save');

    $uploaded_image = File::load(1);
    $image_url = $this->container->get('file_url_generator')->generateString($uploaded_image->getFileUri());
    $image_uuid = $uploaded_image->uuid();
    $assert_session->elementExists('xpath', sprintf('//img[@src="%s" and @width="40" and @height="20" and @data-entity-uuid="%s" and @data-entity-type="file"]', $image_url, $image_uuid));

    // Ensure that width, height, and length attributes are not stored in the
    // database.
    $this->assertEquals(sprintf('<img data-entity-uuid="%s" data-entity-type="file" src="%s" width="40" height="20" alt="There is now alt text">', $image_uuid, $image_url), Node::load(1)->get('body')->value);

    // Ensure that data-entity-uuid and data-entity-type attributes are upcasted
    // correctly to CKEditor model.
    $this->drupalGet('node/1/edit');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
    $page->pressButton('Save');

    $assert_session->elementExists('xpath', sprintf('//img[@src="%s" and @width="40" and @height="20" and @data-entity-uuid="%s" and @data-entity-type="file"]', $image_url, $image_uuid));
  }

  /**
   * Ensures that CKEditor italic model is converted to em.
   */
  public function testEmphasis(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add a node with text rendered via the Plain Text format.
    $this->drupalGet('node/add/page');
    $page->fillField('title[0][value]', 'My test content');
    $page->fillField('body[0][value]', '<p>This is a <em>test!</em></p>');
    $page->pressButton('Save');

    $this->addNewTextFormat();

    $this->drupalGet('node/1/edit');
    $page->selectFieldOption('body[0][format]', 'ckeditor5');
    $this->assertNotEmpty($assert_session->waitForText('Change text format?'));
    $page->pressButton('Continue');

    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
    $page->pressButton('Save');

    $assert_session->responseContains('<p>This is a <em>test!</em></p>');
  }

  /**
   * Tests list plugin.
   */
  public function testListPlugin(): void {
    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'CKEditor 5 with list',
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ])->save();
    Editor::create([
      'format' => 'test_format',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => ['sourceEditing', 'numberedList'],
        ],
        'plugins' => [
          'ckeditor5_list' => [
            'properties' => [
              'reversed' => FALSE,
              'startIndex' => FALSE,
            ],
            'multiBlock' => TRUE,
          ],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [],
          ],
        ],
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));
    $ordered_list_html = '<ol><li>apple</li><li>banana</li><li>cantaloupe</li></ol>';
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet('node/add/page');
    $page->fillField('title[0][value]', 'My test content');
    $this->pressEditorButton('Source');
    $source_text_area = $assert_session->waitForElement('css', '.ck-source-editing-area textarea');
    $source_text_area->setValue($ordered_list_html);
    // Click source again to make source inactive and have the numbered list
    // splitbutton active.
    $this->pressEditorButton('Source');
    $numbered_list_dropdown_selector = '.ck-splitbutton__arrow';

    // Check that there is no dropdown available for the numbered list because
    // both reversed and startIndex are FALSE.
    $assert_session->elementNotExists('css', $numbered_list_dropdown_selector);
    // Save content so source content is kept after changing the editor config.
    $page->pressButton('Save');
    $edit_url = $this->getSession()->getCurrentURL() . '/edit';
    $this->drupalGet($edit_url);
    $this->waitForEditor();

    // Enable the reversed functionality.
    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();
    $settings['plugins']['ckeditor5_list']['properties']['reversed'] = TRUE;
    $editor->setSettings($settings);
    $editor->save();
    $this->getSession()->reload();
    $this->waitForEditor();
    $this->click($numbered_list_dropdown_selector);
    $reversed_order_button_selector = '.ck.ck-button.ck-numbered-list-properties__reversed-order';
    $assert_session->elementExists('css', $reversed_order_button_selector);
    $assert_session->elementTextEquals('css', $reversed_order_button_selector, 'Reversed order');
    $start_index_element_selector = '.ck.ck-numbered-list-properties__start-index';
    $assert_session->elementNotExists('css', $start_index_element_selector);

    // Have both the reversed and the start index enabled.
    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();
    $settings['plugins']['ckeditor5_list']['properties']['startIndex'] = TRUE;
    $editor->setSettings($settings);
    $editor->save();
    $this->getSession()->reload();
    $this->waitForEditor();
    $this->click($numbered_list_dropdown_selector);
    $assert_session->elementExists('css', $reversed_order_button_selector);
    $assert_session->elementTextEquals('css', $reversed_order_button_selector, 'Reversed order');
    $assert_session->elementExists('css', $start_index_element_selector);
  }

  /**
   * Ensures that changes are saved in CKEditor 5.
   */
  public function testSave(): void {
    // To replicate the bug from https://www.drupal.org/i/3396742
    // We need 2 or more text formats and node edit page.
    FilterFormat::create([
      'format' => 'ckeditor5',
      'name' => 'CKEditor 5 HTML',
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ])->save();
    Editor::create([
      'format' => 'ckeditor5',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => [
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [],
          ],
        ],
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('ckeditor5'),
        FilterFormat::load('ckeditor5')
      ))
    ));
    FilterFormat::create([
      'format' => 'ckeditor5_2',
      'name' => 'CKEditor 5 HTML 2',
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ])->save();
    Editor::create([
      'format' => 'ckeditor5_2',
      'editor' => 'ckeditor5',
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('ckeditor5_2'),
        FilterFormat::load('ckeditor5_2')
      ))
    ));
    $this->drupalCreateNode([
      'title' => 'My test content',
    ]);

    // Test that entered text is saved.
    $this->drupalGet('node/1/edit');
    $page = $this->getSession()->getPage();
    $this->waitForEditor();
    $editor = $page->find('css', '.ck-content');
    $editor->setValue('Very important information');
    $page->pressButton('Save');
    $this->assertSession()->responseContains('Very important information');

    // Test that changes only in source are saved.
    $this->drupalGet('node/1/edit');
    $page = $this->getSession()->getPage();
    $this->waitForEditor();
    $this->pressEditorButton('Source');
    $editor = $page->find('css', '.ck-source-editing-area textarea');
    $editor->setValue('Text hidden in the source');
    $page->pressButton('Save');
    $this->assertSession()->responseContains('Text hidden in the source');
  }

}
