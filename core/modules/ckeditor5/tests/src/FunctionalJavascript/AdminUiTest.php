<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

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
    'ckeditor',
  ];

  /**
   * Confirm settings only trigger AJAX when select value is CKEditor 5.
   */
  public function testSettingsOnlyFireAjaxWithCkeditor5() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->addNewTextFormat($page, $assert_session);
    $this->addNewTextFormat($page, $assert_session, 'ckeditor');

    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');
    $number_ajax_instances_before = $this->getSession()->evaluateScript('Drupal.ajax.instances.length');

    // Enable media embed to trigger an AJAX rebuild.
    $this->assertTrue($page->hasUncheckedField('filters[media_embed][status]'));
    $page->checkField('filters[media_embed][status]');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ajax-progress-throbber'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->responseContains('Media types selectable in the Media Library');
    $assert_session->assertWaitOnAjaxRequest();
    $number_ajax_instances_after = $this->getSession()->evaluateScript('Drupal.ajax.instances.length');

    // After the rebuild, there should be more AJAX instances.
    $this->assertGreaterThan($number_ajax_instances_before, $number_ajax_instances_after);

    // Perform the same steps as above with CKEditor, and confirm AJAX callbacks
    // are not triggered on settings changes.
    $this->drupalGet('admin/config/content/formats/manage/ckeditor');
    $number_ajax_instances_before = $this->getSession()->evaluateScript('Drupal.ajax.instances.length');

    // Enable media embed to confirm a format not using CKEditor 5 will not
    // trigger an AJAX rebuild.
    $this->assertTrue($page->hasUncheckedField('filters[media_embed][status]'));
    $page->checkField('filters[media_embed][status]');
    $this->assertEmpty($assert_session->waitForElement('css', '.ajax-progress-throbber'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->responseContains('Media types selectable in the Media Library');
    $assert_session->assertWaitOnAjaxRequest();

    $number_ajax_instances_after = $this->getSession()->evaluateScript('Drupal.ajax.instances.length');
    $this->assertSame($number_ajax_instances_before, $number_ajax_instances_after);

    // Confirm that AJAX updates happen when attempting to switch to CKEditor 5,
    // even if prevented from doing so by validation.
    $this->drupalGet('admin/config/content/formats/add');
    $page->fillField('name', 'trigger validator');
    $assert_session->waitForText('Machine name');
    $page->checkField('roles[authenticated]');

    // Enable a filter that is incompatible with CKEditor 5, so validation is
    // triggered when attempting to switch.
    $number_ajax_instances_before = $this->getSession()->evaluateScript('Drupal.ajax.instances.length');
    $this->assertTrue($page->hasUncheckedField('filters[filter_autop][status]'));
    $page->checkField('filters[filter_autop][status]');
    $this->assertEmpty($assert_session->waitForElement('css', '.ajax-progress-throbber'));
    $assert_session->assertWaitOnAjaxRequest();
    $number_ajax_instances_after = $this->getSession()->evaluateScript('Drupal.ajax.instances.length');
    $this->assertSame($number_ajax_instances_before, $number_ajax_instances_after);

    $page->selectFieldOption('editor[editor]', 'ckeditor5');
    $assert_session->assertWaitOnAjaxRequest();

    // The presence of this validation error message confirms the AJAX callback
    // was invoked.
    $assert_session->pageTextContains('CKEditor 5 only works with HTML-based text formats');

    // Disable the incompatible filter. This should trigger another AJAX rebuild
    // which will include the removal of the validation error as the issue has
    // been corrected.
    $this->assertTrue($page->hasCheckedField('filters[filter_autop][status]'));
    $page->uncheckField('filters[filter_autop][status]');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ajax-progress-throbber'));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('CKEditor 5 only works with HTML-based text formats');
  }

  /**
   * CKEditor5's filter UI modifications should not break it for other editors.
   */
  public function testUnavailableFiltersHiddenWhenSwitching() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->createNewTextFormat($page, $assert_session, 'ckeditor');
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

    $find_validation_error_messages = function () use ($page): array {
      return $page->findAll('css', '[role=alert]:contains("CKEditor 5 only works with HTML-based text formats.")');
    };

    // No validation errors when we start.
    $this->assertCount(0, $find_validation_error_messages());

    // Enable a filter which is not compatible with CKEditor 5, to trigger a
    // validation error.
    $page->checkField('Convert URLs into links');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertCount(1, $find_validation_error_messages());

    // Disable it: validation messages should be gone.
    $page->uncheckField('Convert URLs into links');
    $assert_session->assertWaitOnAjaxRequest();

    // Re-enable it: validation messages should be back.
    $page->checkField('Convert URLs into links');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertCount(1, $find_validation_error_messages());
  }

}
