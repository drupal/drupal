<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\Core\Language\LanguageManager;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\RoleInterface;
use Symfony\Component\Validator\ConstraintViolation;

// cspell:ignore esque splitbutton upcasted sourceediting

/**
 * Tests for CKEditor5.
 *
 * @group ckeditor5
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
  ];

  /**
   * Tests configuring CKEditor5 for existing content.
   */
  public function testExistingContent() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add a node with text rendered via the Plain Text format.
    $this->drupalGet('node/add');
    $page->fillField('title[0][value]', 'My test content');
    $page->fillField('body[0][value]', '<p>This is test content</p>');
    $page->pressButton('Save');
    $assert_session->responseNotContains('<p>This is test content</p>');
    $assert_session->responseContains('&lt;p&gt;This is test content&lt;/p&gt;');

    $this->addNewTextFormat($page, $assert_session);

    // Change the node to use the new text format.
    $this->drupalGet('node/1/edit');

    // Confirm that the JavaScript that generates IE11 warnings loads.
    $assert_session->elementExists('css', 'script[src*="ckeditor5/js/ie11.user.warnings.js"]');

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
   * Ensures that attribute values are encoded.
   */
  public function testAttributeEncoding() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    FilterFormat::create([
      'format' => 'ckeditor5',
      'name' => 'CKEditor 5 with image upload',
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ])->save();
    Editor::create([
      'format' => 'ckeditor5',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => ['uploadImage'],
        ],
        'plugins' => ['ckeditor5_imageResize' => ['allow_resize' => FALSE]],
      ],
      'image_upload' => [
        'status' => TRUE,
        'scheme' => 'public',
        'directory' => 'inline-images',
        'max_size' => '',
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

    $this->drupalGet('node/add');
    $this->waitForEditor();
    $page->fillField('title[0][value]', 'My test content');

    // Ensure that CKEditor 5 is focused.
    $this->click('.ck-content');

    $this->assertNotEmpty($image_upload_field = $page->find('css', '.ck-file-dialog-button input[type="file"]'));
    $image = $this->getTestFiles('image')[0];
    $image_upload_field->attachFile($this->container->get('file_system')->realpath($image->uri));
    $assert_session->waitForElementVisible('css', '.ck-widget.image');

    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-balloon-panel .ck-text-alternative-form'));
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-text-alternative-form input[type=text]');
    $this->assertSame('', $alt_override_input->getValue());
    $alt_override_input->setValue('</em> Kittens & llamas are cute');
    $this->getBalloonButton('Save')->click();
    $page->pressButton('Save');

    $uploaded_image = File::load(1);
    $image_uuid = $uploaded_image->uuid();
    $image_url = $this->container->get('file_url_generator')->generateString($uploaded_image->getFileUri());
    $this->drupalGet('node/1');
    $this->assertNotEmpty($assert_session->waitForElement('xpath', sprintf('//img[@alt="</em> Kittens & llamas are cute" and @data-entity-uuid="%s" and @data-entity-type="file"]', $image_uuid)));

    // Drupal CKEditor 5 integrations overrides the CKEditor 5 HTML writer to
    // escape ampersand characters (&) and the angle brackets (< and >). This is
    // required because \Drupal\Component\Utility\Xss::filter fails to parse
    // element attributes with unescaped entities in value.
    // @see https://www.drupal.org/project/drupal/issues/3227831
    $this->assertEquals(sprintf('<img data-entity-uuid="%s" data-entity-type="file" src="%s" alt="&lt;/em&gt; Kittens &amp; llamas are cute">', $image_uuid, $image_url), Node::load(1)->get('body')->value);
  }

  /**
   * Test headings configuration.
   */
  public function testHeadingsPlugin() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->addNewTextFormat($page, $assert_session);
    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');
    $this->assertHtmlEsqueFieldValueEquals('filters[filter_html][settings][allowed_html]', '<br> <p> <h2> <h3> <h4> <h5> <h6> <strong> <em>');

    $this->drupalGet('node/add');
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

    $this->drupalGet('node/add');
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
   * Test for plugin Language of parts.
   */
  public function testLanguageOfPartsPlugin() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

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

    // Test for "United Nations' official languages" option.
    $languages = LanguageManager::getUnitedNationsLanguageList();
    $this->languageOfPartsPluginTestHelper($page, $assert_session, $languages, "un");

    // Test for "All 95 languages" option.
    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');
    $languages = LanguageManager::getStandardLanguageList();
    $this->languageOfPartsPluginTestHelper($page, $assert_session, $languages, "all");
  }

  /**
   * Validate the available languages on the basis of selected language option.
   */
  public function languageOfPartsPluginTestHelper($page, $assert_session, $predefined_languages, $option) {
    $this->assertNotEmpty($assert_session->waitForElement('css', 'a[href^="#edit-editor-settings-plugins-ckeditor5-language"]'));

    // Set correct value.
    $vertical_tab_link = $page->find('xpath', "//ul[contains(@class, 'vertical-tabs__menu')]/li/a[starts-with(@href, '#edit-editor-settings-plugins-ckeditor5-language')]");
    $vertical_tab_link->click();
    $page->selectFieldOption('editor[settings][plugins][ckeditor5_language][language_list]', $option);
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save configuration');

    // Validate plugin on node add page.
    $this->drupalGet('node/add');
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
    $predefined_languages = array_column($predefined_languages, 0);

    // Sort on full language name.
    asort($predefined_languages);

    $this->assertSame(array_values($predefined_languages), $languages);
  }

  /**
   * Confirms active tab status is intact after AJAX refresh.
   */
  public function testActiveTabsMaintained() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);
    $assert_session->assertWaitOnAjaxRequest();

    // Ensure the HTML filter tab is visible.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'a[href^="#edit-filters-filter-html-settings"]'));

    // Enable media embed to make a second filter config tab visible.
    $this->assertTrue($page->hasUncheckedField('filters[media_embed][status]'));
    $page->checkField('filters[media_embed][status]');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->responseContains('Media types selectable in the Media Library');
    $assert_session->assertWaitOnAjaxRequest();

    // Enable upload image to add one plugin config form.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-uploadImage'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-uploadImage', 'ArrowDown');
    // cSpell:disable-next-line
    $this->assertNotEmpty($assert_session->waitForElement('css', 'a[href^="#edit-editor-settings-plugins-ckeditor5-imageupload"]'));
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-active .ckeditor5-toolbar-item-uploadImage'));
    $assert_session->assertWaitOnAjaxRequest();

    $page->clickLink('Image Upload');
    $assert_session->waitForText('Enable image uploads');
    $this->assertTrue($page->hasUncheckedField('editor[settings][plugins][ckeditor5_imageUpload][status]'));
    $page->checkField('editor[settings][plugins][ckeditor5_imageUpload][status]');
    $assert_session->assertWaitOnAjaxRequest();

    // Enable Heading to add a second plugin config form.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-button-heading'));
    $this->triggerKeyUp('.ckeditor5-toolbar-button-heading', 'ArrowDown');
    $this->assertNotEmpty($assert_session->waitForElement('css', 'a[href^="#edit-editor-settings-plugins-ckeditor5-heading"]'));
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-active .ckeditor5-toolbar-button-heading'));
    $assert_session->assertWaitOnAjaxRequest();

    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('Added text format ckeditor5');

    // Leave and return to the config form, both sets of tabs should then have
    // the first tab active by default.
    $this->drupalGet('admin/config/content/formats/');
    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');

    $assert_session->waitForElement('css', '.vertical-tabs__menu-item.is-selected');

    $plugin_settings_vertical_tabs = $page->findAll('css', '#plugin-settings-wrapper .vertical-tabs__menu-item');
    $filter_settings = $page->find('xpath', '//*[contains(@class, "js-form-type-vertical-tabs")]/label[contains(text(), "Filter settings")]/..');
    $filter_settings_vertical_tabs = $filter_settings->findAll('css', '.vertical-tabs__menu-item');

    $this->assertTrue($plugin_settings_vertical_tabs[0]->hasClass('is-selected'), "Expected plugin tab 1 selected on initial build");
    $this->assertFalse($plugin_settings_vertical_tabs[1]->hasClass('is-selected'), "Expected plugin tab 2 not selected on initial build");

    $this->assertFalse($filter_settings_vertical_tabs[0]->hasClass('is-selected'), "Expected filter tab 1 not selected on initial build");
    $this->assertTrue($filter_settings_vertical_tabs[2]->hasClass('is-selected'), "Expected (visible) filter tab 2 selected on initial build");

    $plugin_settings_vertical_tabs[1]->click();
    $filter_settings_vertical_tabs[0]->click();
    $assert_session->assertWaitOnAjaxRequest();

    $this->assertFalse($plugin_settings_vertical_tabs[0]->hasClass('is-selected'), "Expected plugin tab 1 deselected after click");
    $this->assertTrue($plugin_settings_vertical_tabs[1]->hasClass('is-selected'), "Expected plugin tab 2 selected after click");

    $this->assertTrue($filter_settings_vertical_tabs[0]->hasClass('is-selected'), "Expected filter tab 1 selected after click");
    $this->assertFalse($filter_settings_vertical_tabs[2]->hasClass('is-selected'), "Expected (visible) filter tab 2 deselected after click");

    // Add a plugin just to trigger AJAX refresh.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-blockQuote'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-blockQuote', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();

    $this->assertFalse($plugin_settings_vertical_tabs[0]->hasClass('is-selected'), "Expected plugin tab 1 deselected after AJAX refresh");
    $this->assertTrue($plugin_settings_vertical_tabs[1]->hasClass('is-selected'), "Expected plugin tab 2 selected after AJAX refresh");

    $this->assertTrue($filter_settings_vertical_tabs[0]->hasClass('is-selected'), "Expected filter tab 1 selected after AJAX refresh");
    $this->assertFalse($filter_settings_vertical_tabs[1]->hasClass('is-selected'), "Expected filter tab 2 deselected after AJAX refresh");
  }

  /**
   * Ensures that CKEditor 5 integrates with file reference filter.
   */
  public function testEditorFileReferenceIntegration() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-uploadImage'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-uploadImage', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();
    $page->clickLink('Image Upload');
    $page->checkField('editor[settings][plugins][ckeditor5_imageUpload][status]');
    $assert_session->assertWaitOnAjaxRequest();
    $page->checkField('filters[editor_file_reference][status]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->saveNewTextFormat($page, $assert_session);

    $this->drupalGet('node/add');
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
    $assert_session->elementExists('xpath', sprintf('//img[@src="%s" and @loading="lazy" and @width and @height and @data-entity-uuid="%s" and @data-entity-type="file"]', $image_url, $image_uuid));

    // Ensure that width, height, and length attributes are not stored in the
    // database.
    $this->assertEquals(sprintf('<img data-entity-uuid="%s" data-entity-type="file" src="%s" alt="There is now alt text">', $image_uuid, $image_url), Node::load(1)->get('body')->value);

    // Ensure that data-entity-uuid and data-entity-type attributes are upcasted
    // correctly to CKEditor model.
    $this->drupalGet('node/1/edit');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
    $page->pressButton('Save');

    $assert_session->elementExists('xpath', sprintf('//img[@src="%s" and @loading="lazy" and @width and @height and @data-entity-uuid="%s" and @data-entity-type="file"]', $image_url, $image_uuid));
  }

  /**
   * Ensures that CKEditor italic model is converted to em.
   */
  public function testEmphasis() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add a node with text rendered via the Plain Text format.
    $this->drupalGet('node/add');
    $page->fillField('title[0][value]', 'My test content');
    $page->fillField('body[0][value]', '<p>This is a <em>test!</em></p>');
    $page->pressButton('Save');

    $this->createNewTextFormat($page, $assert_session);
    $this->saveNewTextFormat($page, $assert_session);

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
  public function testListPlugin() {
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
            'reversed' => FALSE,
            'startIndex' => FALSE,
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
    $this->drupalGet('node/add');
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
    $settings['plugins']['ckeditor5_list']['reversed'] = TRUE;
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
    $settings['plugins']['ckeditor5_list']['startIndex'] = TRUE;
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
   * Ensures that CKEditor 5 retains filter_html's allowed global attributes.
   *
   * FilterHtml always forbids the `style` and `on*` attributes, and always
   * allows the `lang` attribute (with any value) and the `dir` attribute (with
   * either `ltr` or `rtl` as value). It's important that those last two
   * attributes are guaranteed to be retained.
   *
   * @see \Drupal\filter\Plugin\Filter\FilterHtml::getHTMLRestrictions()
   * @see ckeditor5_globalAttributeDir
   * @see ckeditor5_globalAttributeLang
   * @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
   */
  public function testFilterHtmlAllowedGlobalAttributes(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add a node with text rendered via the Plain Text format.
    $this->drupalGet('node/add');
    $page->fillField('title[0][value]', 'Multilingual Hello World');
    // cSpell:disable-next-line
    $page->fillField('body[0][value]', '<p dir="ltr" lang="en">Hello World</p><p dir="rtl" lang="ar">مرحبا بالعالم</p>');
    $page->pressButton('Save');

    $this->createNewTextFormat($page, $assert_session);
    $this->saveNewTextFormat($page, $assert_session);

    $this->drupalGet('node/1/edit');
    $page->selectFieldOption('body[0][format]', 'ckeditor5');
    $this->assertNotEmpty($assert_session->waitForText('Change text format?'));
    $page->pressButton('Continue');

    $this->waitForEditor();
    $page->pressButton('Save');

    // @todo Remove the expected `xml:lang` attributes in https://www.drupal.org/project/drupal/issues/1333730
    // cSpell:disable-next-line
    $assert_session->responseContains('<p dir="ltr" lang="en" xml:lang="en">Hello World</p><p dir="rtl" lang="ar" xml:lang="ar">مرحبا بالعالم</p>');
  }

}
