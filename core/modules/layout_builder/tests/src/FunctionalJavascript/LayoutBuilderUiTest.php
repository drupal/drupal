<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the Layout Builder UI.
 *
 * @group layout_builder
 */
class LayoutBuilderUiTest extends WebDriverTestBase {

  /**
   * Path prefix for the field UI for the test bundle.
   *
   * @var string
   */
  const FIELD_UI_PREFIX = 'admin/structure/types/manage/bundle_with_section_field';

  public static $modules = [
    'layout_builder',
    'block',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // @todo The Layout Builder UI relies on local tasks; fix in
    //   https://www.drupal.org/project/drupal/issues/2917777.
    $this->drupalPlaceBlock('local_tasks_block');

    $this->createContentType(['type' => 'bundle_with_section_field']);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));
  }

  /**
   * Tests the message indicating unsaved changes.
   */
  public function testUnsavedChangesMessage() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Enable layout builder.
    $this->drupalPostForm(
      static::FIELD_UI_PREFIX . '/display/default',
      ['layout[enabled]' => TRUE],
      'Save'
    );
    $page->clickLink('Manage layout');
    $assert_session->addressEquals(static::FIELD_UI_PREFIX . '/display-layout/default');

    // Add a new section.
    $page->clickLink('Add Section');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('You have unsaved changes.');
    $page->clickLink('One column');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContainsOnce('You have unsaved changes.');

    // Reload the page.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display-layout/default');
    $assert_session->pageTextContainsOnce('You have unsaved changes.');

    // Cancel the changes.
    $page->clickLink('Cancel Layout');
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display-layout/default');
    $assert_session->pageTextNotContains('You have unsaved changes.');

    // Add a new section.
    $page->clickLink('Add Section');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('You have unsaved changes.');
    $page->clickLink('One column');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContainsOnce('You have unsaved changes.');

    // Save the changes.
    $page->clickLink('Save Layout');
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display-layout/default');
    $assert_session->pageTextNotContains('You have unsaved changes.');
  }

}
