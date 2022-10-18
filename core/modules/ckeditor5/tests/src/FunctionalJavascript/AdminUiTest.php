<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

// cspell:ignore sourceediting

/**
 * Tests for CKEditor 5 in the admin UI.
 *
 * @group ckeditor5
 * @internal
 */
class AdminUiTest extends CKEditor5TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media_library',
    'editor_test',
    'ckeditor5_incompatible_filter_test',
  ];

  /**
   * Confirm settings only trigger AJAX when select value is CKEditor 5.
   */
  public function testSettingsOnlyFireAjaxWithCkeditor5() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->addNewTextFormat($page, $assert_session);
    $this->addNewTextFormat($page, $assert_session, 'unicorn');

    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');

    // Enable media embed to trigger an AJAX rebuild.
    $this->assertTrue($page->hasUncheckedField('filters[media_embed][status]'));
    $this->assertSame(0, $this->getAjaxResponseCount());
    $page->checkField('filters[media_embed][status]');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSame(1, $this->getAjaxResponseCount());

    // Perform the same steps as above with CKEditor, and confirm AJAX callbacks
    // are not triggered on settings changes.
    $this->drupalGet('admin/config/content/formats/manage/unicorn');

    // Enable media embed to confirm a format not using CKEditor 5 will not
    // trigger an AJAX rebuild.
    $this->assertTrue($page->hasUncheckedField('filters[media_embed][status]'));
    $page->checkField('filters[media_embed][status]');
    $this->assertSame(0, $this->getAjaxResponseCount());

    // Confirm that AJAX updates happen when attempting to switch to CKEditor 5,
    // even if prevented from doing so by validation.
    $this->drupalGet('admin/config/content/formats/add');
    $page->fillField('name', 'trigger validator');
    $assert_session->waitForText('Machine name');
    $page->checkField('roles[authenticated]');

    // Enable a filter that is incompatible with CKEditor 5, so validation is
    // triggered when attempting to switch.
    $incompatible_filter_name = 'filters[filter_incompatible][status]';
    $this->assertTrue($page->hasUncheckedField($incompatible_filter_name));
    $page->checkField($incompatible_filter_name);
    $this->assertSame(0, $this->getAjaxResponseCount());

    $page->selectFieldOption('editor[editor]', 'ckeditor5');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSame(1, $this->getAjaxResponseCount());

    $filter_warning = 'CKEditor 5 only works with HTML-based text formats. The "A TYPE_MARKUP_LANGUAGE filter incompatible with CKEditor 5" (filter_incompatible) filter implies this text format is not HTML anymore.';

    // The presence of this validation error message confirms the AJAX callback
    // was invoked.
    $assert_session->pageTextContains($filter_warning);

    // Disable the incompatible filter. This should trigger another AJAX rebuild
    // which will include the removal of the validation error as the issue has
    // been corrected.
    $this->assertTrue($page->hasCheckedField($incompatible_filter_name));
    $page->uncheckField($incompatible_filter_name);
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSame(2, $this->getAjaxResponseCount());
    $assert_session->pageTextNotContains($filter_warning);
  }

  /**
   * Gets the Drupal AJAX response count observed on this page.
   *
   * @return int
   *   The number of completed XHR requests observed since the page was loaded.
   */
  protected function getAjaxResponseCount(): int {
    // Half a second should suffice for any of the test's DOM interactions to
    // have triggered an AJAX request, if any.
    try {
      $this->assertSession()->assertWaitOnAjaxRequest(500);
    }
    catch (\RuntimeException $e) {
      throw new \LogicException('An AJAX request was still being processed, this suggests a assertWaitOnAjaxRequest() call is missing.');
    }

    // Now that there definitely is no more AJAX request in progress, count the
    // number of AJAX responses.
    $javascript = <<<JS
(function(){
  return window.performance
    .getEntries()
    .filter(entry => entry.initiatorType === 'xmlhttprequest' && entry.name.indexOf('_wrapper_format=drupal_ajax') !== -1)
    .length
})()
JS;
    return $this->getSession()->evaluateScript($javascript);
  }

  /**
   * CKEditor5's filter UI modifications should not break it for other editors.
   */
  public function testUnavailableFiltersHiddenWhenSwitching() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->createNewTextFormat($page, $assert_session, 'unicorn');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Filter settings');

    // Switching to CKEditor 5 should keep the filter settings hidden.
    $page->selectFieldOption('editor[editor]', 'ckeditor5');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Filter settings');
  }

  /**
   * Test that filter settings are only visible when the filter is enabled.
   */
  public function testFilterCheckboxesToggleSettings() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);

    $assert_session->assertWaitOnAjaxRequest();

    $media_tab = $page->find('css', '[href^="#edit-filters-media-embed-settings"]');
    $this->assertFalse($media_tab->isVisible(), 'Media filter settings should not be present because media filter is not enabled');

    $this->assertTrue($page->hasUncheckedField('filters[media_embed][status]'));
    $page->checkField('filters[media_embed][status]');
    $assert_session->assertWaitOnAjaxRequest();

    $media_tab = $assert_session->waitForElementVisible('css', '[href^="#edit-filters-media-embed-settings"]');
    $this->assertTrue($media_tab->isVisible(), 'Media settings should appear when media filter enabled');

    $page->uncheckField('filters[media_embed][status]');
    $assert_session->assertWaitOnAjaxRequest();

    $media_tab = $page->find('css', '[href^="#edit-filters-media-embed-settings"]');
    $this->assertFalse($media_tab->isVisible(), 'Media settings should be removed when media filter disabled');
  }

  /**
   * Ensure CKEditor 5 admin UI's real-time validation errors do not accumulate.
   */
  public function testMessagesDoNotAccumulate(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->addNewTextFormat($page, $assert_session);
    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');

    // Add the source editing plugin to the CKEditor 5 toolbar.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-sourceEditing'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-sourceEditing', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();

    $find_validation_error_messages = function () use ($page): array {
      return $page->findAll('css', '[role=alert]:contains("The following tag(s) are already supported by enabled plugins and should not be added to the Source Editing "Manually editable HTML tags" field: Bold (<strong>).")');
    };

    // No validation errors when we start.
    $this->assertCount(0, $find_validation_error_messages());

    // Configure Source Editing to allow editing `<strong>` to trigger
    // validation error.
    $assert_session->waitForText('Source editing');
    $page->find('css', '[href^="#edit-editor-settings-plugins-ckeditor5-sourceediting"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->waitForText('Manually editable HTML tags');
    $source_edit_tags_field = $assert_session->fieldExists('editor[settings][plugins][ckeditor5_sourceEditing][allowed_tags]');
    $source_edit_tags_field->setValue('<strong>');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertCount(1, $find_validation_error_messages());

    // Revert Source Editing it: validation messages should be gone.
    $source_edit_tags_field->setValue('');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertCount(0, $find_validation_error_messages());

    // Add `<strong>` again: validation messages should be back.
    $source_edit_tags_field->setValue('<strong>');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertCount(1, $find_validation_error_messages());
  }

  /**
   * Tests the plugin settings form section.
   */
  public function testPluginSettingsFormSection() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);
    $assert_session->assertWaitOnAjaxRequest();

    // The default toolbar only enables the configurable heading plugin and the
    // non-configurable bold and italic plugins.
    $assert_session->fieldValueEquals('editor[settings][toolbar][items]', '["heading","bold","italic"]');
    // The heading plugin config form should be present.
    $assert_session->elementExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-heading"]');

    // Remove the heading plugin from the toolbar.
    $this->triggerKeyUp('.ckeditor5-toolbar-item-heading', 'ArrowUp');
    $assert_session->assertWaitOnAjaxRequest();

    // The heading plugin config form should no longer be present.
    $assert_session->elementNotExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-heading"]');
    // The plugin settings wrapper should still be present, but empty.
    $assert_session->elementExists('css', '#plugin-settings-wrapper');
    $assert_session->elementNotContains('css', '#plugin-settings-wrapper', '<div');

    // Enable the source plugin.
    $this->triggerKeyUp('.ckeditor5-toolbar-item-sourceEditing', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();

    // The source plugin config form should be present.
    $assert_session->elementExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-sourceediting"]');

    // The filter-dependent configurable plugin should not be present.
    $assert_session->elementNotExists('css', '[data-drupal-selector="edit-editor-settings-plugins-media-media"]');

    // Enable the filter that the configurable plugin depends on.
    $this->assertTrue($page->hasUncheckedField('filters[media_embed][status]'));
    $page->checkField('filters[media_embed][status]');
    $assert_session->assertWaitOnAjaxRequest();

    // The filter-dependent configurable plugin should be present.
    $assert_session->elementExists('css', '[data-drupal-selector="edit-editor-settings-plugins-media-media"]');
  }

  /**
   * Tests the language config form.
   */
  public function testLanguageConfigForm() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);
    $assert_session->assertWaitOnAjaxRequest();

    // The language plugin config form should not be present.
    $assert_session->elementNotExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-language"]');

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

    // The language plugin config form should now be present.
    $assert_session->elementExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-language"]');

    // It must also be possible to remove the language plugin again.
    $this->triggerKeyUp('.ckeditor5-toolbar-item-textPartLanguage', 'ArrowUp');
    $assert_session->assertWaitOnAjaxRequest();

    // The language plugin config form should not be present anymore.
    $assert_session->elementNotExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-language"]');
  }

}
