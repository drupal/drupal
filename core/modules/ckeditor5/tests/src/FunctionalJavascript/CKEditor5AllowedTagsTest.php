<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Symfony\Component\Yaml\Yaml;

// cspell:ignore esque imageUpload nofilter noeditor sourceediting Editing's

/**
 * Tests for CKEditor5.
 *
 * @group ckeditor5
 * @internal
 */
class CKEditor5AllowedTagsTest extends CKEditor5TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ckeditor',
    'ckeditor5',
    'media',
    'media_library',
    'ckeditor5_incompatible_filter_test',
  ];

  /**
   * The default CKEditor 5 allowed elements.
   *
   * @var string
   */
  protected $allowedElements = '<br> <p> <h2> <h3> <h4> <h5> <h6> <strong> <em>';

  /**
   * The default allowed elements when updating a non-CKEditor 5 editor.
   *
   * @var string
   */
  protected $defaultElementsWhenUpdatingNotCkeditor5 = '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <h2 id> <h3 id> <h4 id> <h5 id> <h6 id> <img src alt data-entity-type data-entity-uuid>';

  /**
   * The expected allowed elements after updating to CKEditor5.
   *
   * @var string
   */
  protected $defaultElementsAfterUpdatingToCkeditor5 = '<br> <p> <h2 id> <h3 id> <h4 id> <h5 id> <h6 id> <cite> <dl> <dt> <dd> <a hreflang href> <blockquote cite> <ul type> <ol type start> <img src alt data-entity-type data-entity-uuid> <strong> <em> <code> <li>';

  /**
   * Test enabling CKEditor 5 in a way that triggers validation.
   */
  public function testEnablingToVersion5Validation() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $incompatible_filter_name = 'filters[filter_incompatible][status]';
    $filter_warning = 'CKEditor 5 only works with HTML-based text formats. The "A TYPE_MARKUP_LANGUAGE filter incompatible with CKEditor 5" (filter_incompatible) filter implies this text format is not HTML anymore.';

    $this->createNewTextFormat($page, $assert_session, 'ckeditor');
    $page->selectFieldOption('editor[editor]', 'ckeditor');
    $assert_session->assertWaitOnAjaxRequest();
    $page->checkField('filters[filter_html][status]');
    $page->checkField($incompatible_filter_name);
    $assert_session->assertWaitOnAjaxRequest();
    $page->selectFieldOption('editor[editor]', 'ckeditor5');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains($filter_warning);

    // Disable the incompatible filter.
    $page->uncheckField($incompatible_filter_name);

    // Confirm there are no longer any warnings.
    $assert_session->waitForElementRemoved('css', '[data-drupal-messages] [role="alert"]');

    // Confirm the text format can be saved.
    $this->saveNewTextFormat($page, $assert_session);
  }

  /**
   * Tests that when image uploads are enabled in CKEditor 4, they remain in 5.
   */
  public function testImageUploadsRemainEnabled(): void {
    FilterFormat::create([
      'format' => 'cke4_image_uploads',
      'name' => 'CKEditor 4, image uploads',
    ])->save();
    Editor::create([
      'format' => 'cke4_image_uploads',
      'editor' => 'ckeditor',
      'settings' => [
        'toolbar' => [
          'rows' => [
            0 => [
              [
                'name' => 'Media',
                'items' => [
                  'DrupalImage',
                ],
              ],
            ],
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => TRUE,
        'scheme' => 'public',
        'directory' => 'inline-images',
        'max_size' => '',
        'max_dimensions' => [
          'width' => 0,
          'height' => 0,
        ],
      ],
    ])->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Assert that image uploads are enabled initially.
    $this->drupalGet('admin/config/content/formats/manage/cke4_image_uploads');
    $this->assertTrue($page->hasCheckedField('Enable image uploads'));

    // Switch the text format to CKEditor 5.
    $page->selectFieldOption('editor[editor]', 'ckeditor5');
    $assert_session->assertWaitOnAjaxRequest();

    // Assert that image uploads are still enabled.
    $this->assertTrue($page->hasCheckedField('Enable image uploads'));
  }

  /**
   * Confirm that switching to CKEditor 5 from another editor updates tags.
   */
  public function testSwitchToVersion5() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session, 'ckeditor');
    $assert_session->assertWaitOnAjaxRequest();

    // Enable the HTML filter.
    $this->assertTrue($page->hasUncheckedField('filters[filter_html][status]'));
    $page->checkField('filters[filter_html][status]');
    $assert_session->assertWaitOnAjaxRequest();

    // Confirm the allowed HTML tags are the defaults for non-Ckeditor5 editors.
    $this->assertHtmlEsqueFieldValueEquals('filters[filter_html][settings][allowed_html]', $this->defaultElementsWhenUpdatingNotCkeditor5);

    $this->saveNewTextFormat($page, $assert_session);
    $assert_session->pageTextContains('Added text format ckeditor');

    // Return to the config form to confirm that switching text editors on
    // existing formats will properly switch allowed tags.
    $this->drupalGet('admin/config/content/formats/manage/ckeditor');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertHtmlEsqueFieldValueEquals('filters[filter_html][settings][allowed_html]', $this->defaultElementsWhenUpdatingNotCkeditor5);

    $page->selectFieldOption('editor[editor]', 'ckeditor5');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('The <br>, <p> tags were added because they are required by CKEditor 5');
    $this->assertHtmlEsqueFieldValueEquals('filters[filter_html][settings][allowed_html]', $this->defaultElementsAfterUpdatingToCkeditor5);

    $page->pressButton('Save configuration');

    $assert_session->pageTextContains('The Image upload toolbar item requires image uploads to be enabled.');
    $page->clickLink('Image Upload');
    $assert_session->waitForText('Enable image uploads');
    $this->assertTrue($page->hasUncheckedField('editor[settings][plugins][ckeditor5_imageUpload][status]'));
    $page->checkField('editor[settings][plugins][ckeditor5_imageUpload][status]');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The following attribute(s) are already supported by enabled plugins and should not be added to the Source Editing "Manually editable HTML tags" field: Image (<img src alt data-entity-uuid data-entity-type>)');

    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->waitForText('Manually editable HTML tags');
    $source_edit_tags_field = $assert_session->fieldExists('editor[settings][plugins][ckeditor5_sourceEditing][allowed_tags]');
    $source_edit_tags_field_value = $source_edit_tags_field->getValue();
    $source_edit_tags_field->setValue(str_replace('<img src alt data-entity-type data-entity-uuid>', '', $source_edit_tags_field_value));
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save configuration');

    $assert_session->pageTextContains('The text format ckeditor has been updated');
  }

  /**
   * Tests that the img tag is added after enabling image uploads.
   */
  public function testImgAddedViaUploadPlugin() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);
    $assert_session->assertWaitOnAjaxRequest();

    $allowed_html_field = $assert_session->fieldExists('filters[filter_html][settings][allowed_html]');
    $this->assertTrue($allowed_html_field->hasAttribute('readonly'));

    // Allowed tags are currently the default, with no <img>.
    $this->assertEquals($this->allowedElements, $allowed_html_field->getValue());

    // The image upload settings form should not be present.
    $assert_session->elementNotExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-imageupload"]');

    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-uploadImage'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-uploadImage', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();

    // The image upload settings form should now be present.
    $assert_session->elementExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-imageupload"]');

    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-active .ckeditor5-toolbar-item-uploadImage'));

    // The image upload plugin is enabled, but <img> not yet allowed.
    $this->assertEquals($this->allowedElements, $allowed_html_field->getValue());

    $page->clickLink('Image Upload');
    $assert_session->waitForText('Enable image uploads');
    $this->assertTrue($page->hasUncheckedField('editor[settings][plugins][ckeditor5_imageUpload][status]'));
    $page->checkField('editor[settings][plugins][ckeditor5_imageUpload][status]');
    $assert_session->assertWaitOnAjaxRequest();

    // Enabling image uploads adds <img> with several attributes to allowed
    // tags.
    $this->assertEquals($this->allowedElements . ' <img src alt data-entity-uuid data-entity-type height width>', $allowed_html_field->getValue());

    // Also enabling the caption filter will add the data-caption attribute to
    // <img>.
    $this->assertTrue($page->hasUncheckedField('filters[filter_caption][status]'));
    $page->checkField('filters[filter_caption][status]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertEquals($this->allowedElements . ' <img src alt data-entity-uuid data-entity-type height width data-caption>', $allowed_html_field->getValue());

    // Also enabling the alignment filter will add the data-align attribute to
    // <img>.
    $this->assertTrue($page->hasUncheckedField('filters[filter_align][status]'));
    $page->checkField('filters[filter_align][status]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertEquals($this->allowedElements . ' <img src alt data-entity-uuid data-entity-type height width data-caption data-align>', $allowed_html_field->getValue());

    // Disable image upload.
    $page->clickLink('Image Upload');
    $assert_session->waitForText('Enable image uploads');
    $this->assertTrue($page->hasCheckedField('editor[settings][plugins][ckeditor5_imageUpload][status]'));
    $page->uncheckField('editor[settings][plugins][ckeditor5_imageUpload][status]');
    $assert_session->assertWaitOnAjaxRequest();

    // Confirm <img> is no longer an allowed tag, once image upload disabled.
    $this->assertEquals($this->allowedElements, $allowed_html_field->getValue());
  }

  /**
   * Test filter_html allowed tags.
   */
  public function testAllowedTags() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);
    $assert_session->assertWaitOnAjaxRequest();

    // Confirm the "allowed tags" field is  read only, and the value
    // matches the tags required by CKEditor.
    // Allowed HTML field is readonly and its wrapper has a form-disabled class.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.js-form-item-filters-filter-html-settings-allowed-html.form-disabled'));
    $allowed_html_field = $assert_session->fieldExists('filters[filter_html][settings][allowed_html]');
    $this->assertTrue($allowed_html_field->hasAttribute('readonly'));
    $this->assertSame($this->allowedElements, $allowed_html_field->getValue());
    $this->saveNewTextFormat($page, $assert_session);

    $assert_session->pageTextContains('Added text format ckeditor5');
    $assert_session->pageTextContains('Text formats and editors');

    // Confirm the filter config was updated with the correct allowed tags.
    $this->assertSame($this->allowedElements, FilterFormat::load('ckeditor5')->filters('filter_html')->getConfiguration()['settings']['allowed_html']);

    $page->find('css', '[data-drupal-selector="edit-formats-ckeditor5"]')->clickLink('Configure');
    $assert_session->assertWaitOnAjaxRequest();

    // Add the block quote plugin to the CKEditor 5 toolbar.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-blockQuote'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-blockQuote', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();

    $allowed_with_blockquote = $this->allowedElements . ' <blockquote>';
    $assert_session->fieldExists('filters[filter_html][settings][allowed_html]');
    $this->assertHtmlEsqueFieldValueEquals('filters[filter_html][settings][allowed_html]', $allowed_with_blockquote);

    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The text format ckeditor5 has been updated.');

    // Flush caches so the updated config can be checked.
    drupal_flush_all_caches();

    // Confirm that the tags required by the newly-added plugins were correctly
    // saved.
    $this->assertSame($allowed_with_blockquote, FilterFormat::load('ckeditor5')->filters('filter_html')->getConfiguration()['settings']['allowed_html']);

    $page->find('css', '[data-drupal-selector="edit-formats-ckeditor5"]')->clickLink('Configure');

    // And for good measure, confirm the correct tags are in the form field when
    // returning to the form.
    $this->assertHtmlEsqueFieldValueEquals('filters[filter_html][settings][allowed_html]', $allowed_with_blockquote);

    // Add the source editing plugin to the CKEditor 5 toolbar.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-sourceEditing'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-sourceEditing', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();

    // Updating Source Editing's editable tags should automatically update
    // filter_html to include those additional tags.
    $assert_session->waitForText('Source editing');
    $page->find('css', '[href^="#edit-editor-settings-plugins-ckeditor5-sourceediting"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->waitForText('Manually editable HTML tags');
    $source_edit_tags_field = $assert_session->fieldExists('editor[settings][plugins][ckeditor5_sourceEditing][allowed_tags]');
    $source_edit_tags_field->setValue('<aside>');
    $assert_session->assertWaitOnAjaxRequest();

    $this->assertHtmlEsqueFieldValueEquals('filters[filter_html][settings][allowed_html]', '<br> <p> <h2> <h3> <h4> <h5> <h6> <aside> <strong> <em> <blockquote>');
    $allowed_html_field = $assert_session->fieldExists('filters[filter_html][settings][allowed_html]');
    $this->assertTrue($allowed_html_field->hasAttribute('readonly'));

    // Adding tags to Source Editing's editable tags that are already supported
    // by enabled CKEditor 5 plugins must trigger a validation error, and that
    // error must be associated with the correct form item.
    $source_edit_tags_field->setValue('<aside><strong>');
    $assert_session->waitForText('The following tag(s) are already supported by enabled plugins and should not be added to the Source Editing "Manually editable HTML tags" field: Bold (<strong>)');
    $this->assertTrue($page->find('css', '[href^="#edit-editor-settings-plugins-ckeditor5-sourceediting"]')->getParent()->hasClass('is-selected'));
    $this->assertSame('true', $page->findField('editor[settings][plugins][ckeditor5_sourceEditing][allowed_tags]')->getAttribute('aria-invalid'));
    $this->assertTrue($allowed_html_field->hasAttribute('readonly'));

    // The same validation error appears when saving the form regardless of the
    // immediate AJAX validation error above.
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The following tag(s) are already supported by enabled plugins and should not be added to the Source Editing "Manually editable HTML tags" field: Bold (<strong>)');
    $this->assertTrue($page->find('css', '[href^="#edit-editor-settings-plugins-ckeditor5-sourceediting"]')->getParent()->hasClass('is-selected'));
    $this->assertSame('true', $page->findField('editor[settings][plugins][ckeditor5_sourceEditing][allowed_tags]')->getAttribute('aria-invalid'));
    $assert_session->pageTextNotContains('The text format ckeditor5 has been updated');

    // Wait for the "Source editing" vertical tab to appear, remove the already
    // supported tags and re-save. Now the text format should save successfully.
    $assert_session->waitForText('Source editing');
    $page->find('css', '[href^="#edit-editor-settings-plugins-ckeditor5-sourceediting"]')->click();
    $assert_session->pageTextContains('Manually editable HTML tags');
    $source_edit_tags_field = $assert_session->fieldExists('editor[settings][plugins][ckeditor5_sourceEditing][allowed_tags]');
    $source_edit_tags_field->setValue('<aside>');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The text format ckeditor5 has been updated');
    $assert_session->pageTextNotContains('The following tag(s) are already supported by enabled plugins and should not be added to the Source Editing "Manually editable HTML tags" field: Bold (<strong>)');

    // Ensure that CKEditor can be initialized with Source Editing.
    // @see https://www.drupal.org/i/3231427
    $this->drupalGet('node/add');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
  }

  /**
   * Test that <drupal-media> is added to allowed tags when media embed enabled.
   */
  public function testMediaElementAllowedTags() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);

    EntityViewMode::create([
      'id' => 'media.view_mode_1',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 1',
    ])->save();
    EntityViewMode::create([
      'id' => 'media.view_mode_2',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 2',
    ])->save();
    // Allowed HTML field is readonly and its wrapper has a form-disabled class.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.js-form-item-filters-filter-html-settings-allowed-html.form-disabled'));
    $allowed_html_field = $assert_session->fieldExists('filters[filter_html][settings][allowed_html]');
    $this->assertTrue($allowed_html_field->hasAttribute('readonly'));

    // Allowed tags are currently the default, with no <drupal-media>.
    $this->assertEquals($this->allowedElements, $allowed_html_field->getValue());

    // Enable media embed.
    $this->assertTrue($page->hasUncheckedField('filters[media_embed][status]'));
    $page->checkField('filters[media_embed][status]');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->responseContains('Media types selectable in the Media Library');

    $page->clickLink('Embed media');
    $page->checkField('filters[media_embed][settings][allowed_view_modes][view_mode_1]');
    $page->checkField('filters[media_embed][settings][allowed_view_modes][view_mode_2]');
    $assert_session->assertWaitOnAjaxRequest();

    $allowed_with_media = $this->allowedElements . ' <drupal-media data-entity-type data-entity-uuid alt data-view-mode>';
    $allowed_with_media_without_view_mode = $this->allowedElements . ' <drupal-media data-entity-type data-entity-uuid alt>';
    $assert_session->responseContains('Media types selectable in the Media Library');
    $page->clickLink('Media');
    $assert_session->waitForText('Allow the user to override the default view mode');
    $this->assertTrue($page->hasUncheckedField('editor[settings][plugins][media_media][allow_view_mode_override]'));
    $this->assertHtmlEsqueFieldValueEquals('filters[filter_html][settings][allowed_html]', $allowed_with_media_without_view_mode);
    $page->checkField('editor[settings][plugins][media_media][allow_view_mode_override]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertHtmlEsqueFieldValueEquals('filters[filter_html][settings][allowed_html]', $allowed_with_media);
    $this->saveNewTextFormat($page, $assert_session);
    $assert_session->pageTextContains('Added text format ckeditor5.');

    // Confirm <drupal-media> was added to allowed tags on save, as a result of
    // enabling the media embed filter.
    $this->assertSame($allowed_with_media, FilterFormat::load('ckeditor5')->filters('filter_html')->getConfiguration()['settings']['allowed_html']);

    $page->find('css', '[data-drupal-selector="edit-formats-ckeditor5"]')->clickLink('Configure');

    // Confirm that <drupal-media> is now included in the "Allowed tags" form
    // field.
    $this->assertHtmlEsqueFieldValueEquals('filters[filter_html][settings][allowed_html]', $allowed_with_media);

    // Ensure that data-align attribute is added to <drupal-media> when
    // filter_align is enabled.
    $page->checkField('filters[filter_align][status]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertEquals($this->allowedElements . ' <drupal-media data-entity-type data-entity-uuid alt data-view-mode data-align>', $allowed_html_field->getValue());

    // Disable media embed.
    $this->assertTrue($page->hasCheckedField('filters[media_embed][status]'));
    $page->uncheckField('filters[media_embed][status]');
    $assert_session->assertWaitOnAjaxRequest();

    // Confirm allowed tags no longer has <drupal-media>.
    $this->assertHtmlEsqueFieldValueEquals('filters[filter_html][settings][allowed_html]', $this->allowedElements);
  }

  /**
   * Tests the presence of the IE warning when CKEditor 5 is selected.
   */
  public function testInternetExplorerWarning() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $warning_text = 'CKEditor 5 is not compatible with Internet Explorer. Text fields using CKEditor 5 will fall back to plain HTML editing without CKEditor for users of Internet Explorer.';
    $this->createNewTextFormat($page, $assert_session);
    $assert_session->waitForText($warning_text);
    $page->selectFieldOption('editor[editor]', 'None');
    $this->getSession()->getDriver()->executeScript("document.querySelector('#drupal-live-announce').innerHTML = ''");
    $assert_session->assertNoElementAfterWait('css', '.messages--warning');
    $assert_session->pageTextNotContains($warning_text);
  }

  /**
   * Tests full HTML text format.
   */
  public function testFullHtml() {
    FilterFormat::create(
      Yaml::parseFile('core/profiles/standard/config/install/filter.format.full_html.yml')
    )->save();
    FilterFormat::create(
      Yaml::parseFile('core/profiles/standard/config/install/filter.format.basic_html.yml')
    )->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add a node with text rendered via the Plain Text format.
    $this->drupalGet('node/add');
    $page->fillField('title[0][value]', 'My test content');
    $page->fillField('body[0][value]', '<p><a style="color:#ff0000;" foo="bar" hreflang="en" href="https://example.com"><abbr title="National Aeronautics and Space Administration">NASA</abbr> is an acronym.</a></p>');
    $page->pressButton('Save');

    // Configure Full HTML text format to use CKEditor 5.
    $this->drupalGet('admin/config/content/formats/manage/full_html');
    $page->checkField('roles[authenticated]');
    $page->selectFieldOption('editor[editor]', 'ckeditor5');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save configuration');
    $this->assertTrue($assert_session->waitForText('The text format Full HTML has been updated.'));

    // Change the node's text format to Full HTML.
    $this->drupalGet('node/1/edit');
    $page->selectFieldOption('body[0][format]', 'full_html');
    $this->assertNotEmpty($assert_session->waitForText('Change text format?'));
    $page->pressButton('Continue');

    // Ensure the editor is loaded and ensure that arbitrary markup is retained.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
    $page->pressButton('Save');

    // But note that the `style` attribute was stripped by
    // \Drupal\editor\EditorXssFilter\Standard.
    $assert_session->responseContains('<p><a href="https://example.com" hreflang="en" foo="bar"><abbr title="National Aeronautics and Space Administration">NASA</abbr> is an acronym.</a></p>');

    // Ensure attributes are retained after enabling link plugin.
    $this->drupalGet('admin/config/content/formats/manage/full_html');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-link'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-link', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save configuration');

    $this->drupalGet('node/1/edit');
    $page->pressButton('Save');

    $assert_session->responseContains('<p><a href="https://example.com" hreflang="en" foo="bar"><abbr title="National Aeronautics and Space Administration">NASA</abbr> is an acronym.</a></p>');

    // Configure Basic HTML text format to use CKE5 and enable the link plugin.
    $this->drupalGet('admin/config/content/formats/manage/basic_html');
    $page->checkField('roles[authenticated]');
    $page->selectFieldOption('editor[editor]', 'ckeditor5');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-link'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-link', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save configuration');
    $this->assertTrue($assert_session->waitForText('The text format Basic HTML has been updated.'));

    // Change the node's text format to Basic HTML.
    $this->drupalGet('node/1/edit');
    $page->selectFieldOption('body[0][format]', 'basic_html');
    $this->assertNotEmpty($assert_session->waitForText('Change text format?'));
    $page->pressButton('Continue');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save');

    // The `style` and foo` attributes should have been removed, as should the
    // `<abbr>` tag.
    $assert_session->responseContains('<p><a href="https://example.com" hreflang="en">NASA is an acronym.</a></p>');
  }

}
