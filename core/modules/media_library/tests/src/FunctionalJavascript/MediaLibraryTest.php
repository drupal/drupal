<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_test_oembed\Controller\ResourceController;
use Drupal\Tests\media\Traits\OEmbedTestTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Contains Media library integration tests.
 *
 * @group media_library
 */
class MediaLibraryTest extends WebDriverTestBase {

  use TestFileCreationTrait;
  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'media_library_test', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->lockHttpClientToFixtures();

    // Create a few example media items for use in selection.
    $media = [
      'type_one' => [
        'Horse',
        'Bear',
        'Cat',
        'Dog',
      ],
      'type_two' => [
        'Crocodile',
        'Lizard',
        'Snake',
        'Turtle',
      ],
    ];

    $time = time();
    foreach ($media as $type => $names) {
      foreach ($names as $name) {
        $entity = Media::create(['name' => $name, 'bundle' => $type]);
        $source_field = $type === 'type_one' ? 'field_media_test' : 'field_media_test_1';
        $entity->setCreatedTime(++$time);
        $entity->set($source_field, $name);
        $entity->save();
      }
    }

    // Create a user who can use the Media library.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'access media overview',
      'edit own basic_page content',
      'create basic_page content',
      'create media',
      'delete any media',
      'view media',
      'administer node form display',
    ]);
    $this->drupalLogin($user);
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests that the Media library's administration page works as expected.
   */
  public function testAdministrationPage() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Visit the administration page.
    $this->drupalGet('admin/content/media');

    // Verify that the "Add media" link is present.
    $assert_session->linkExists('Add media');

    // Verify that media from two separate types is present.
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Turtle');

    // Test that users can filter by type.
    $page->selectFieldOption('Media type', 'Type One');
    $page->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Turtle');
    $page->selectFieldOption('Media type', 'Type Two');
    $page->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextContains('Turtle');

    // Test that selecting elements as a part of bulk operations works.
    $page->selectFieldOption('Media type', '- Any -');
    $page->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    // This tests that anchor tags clicked inside the preview are suppressed.
    $this->getSession()->executeScript('jQuery(".js-click-to-select-trigger a")[4].click()');
    $this->submitForm([], 'Apply to selected items');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Cat');
    $this->submitForm([], 'Delete');
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextContains('Cat');

    // Test 'Select all media'.
    $this->getSession()->getPage()->checkField('Select all media');
    $this->getSession()->getPage()->selectFieldOption('Action', 'media_delete_action');
    $this->submitForm([], 'Apply to selected items');
    $this->getSession()->getPage()->pressButton('Delete');

    $assert_session->pageTextNotContains('Cat');
    $assert_session->pageTextNotContains('Turtle');
    $assert_session->pageTextNotContains('Snake');

    // Test empty text.
    $assert_session->pageTextContains('No media available.');

    // Verify that the "Table" link is present, click it and check address.
    $assert_session->linkExists('Table');
    $page->clickLink('Table');
    $assert_session->addressEquals('admin/content/media-table');
    // Verify that the "Add media" link is present.
    $assert_session->linkExists('Add media');
  }

  /**
   * Tests that the widget access works as expected.
   */
  public function testWidgetAccess() {
    $assert_session = $this->assertSession();

    $this->drupalLogout();

    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $role->revokePermission('view media');
    $role->save();

    // Create a working state.
    $allowed_types = ['type_one', 'type_two', 'type_three', 'type_four'];
    $state = MediaLibraryState::create('test', $allowed_types, 'type_three', 2);
    $url_options = ['query' => $state->all()];

    // Verify that unprivileged users can't access the widget view.
    $this->drupalGet('admin/content/media-widget', $url_options);
    $assert_session->responseContains('Access denied');
    $this->drupalGet('media-library', $url_options);
    $assert_session->responseContains('Access denied');

    // Allow users with 'view media' permission to access the media library view
    // and controller.
    $this->grantPermissions($role, [
      'view media',
    ]);
    $this->drupalGet('admin/content/media-widget', $url_options);
    $assert_session->elementExists('css', '.view-media-library');
    $this->drupalGet('media-library', $url_options);
    $assert_session->elementExists('css', '.view-media-library');
    // Assert the user does not have access to the media add form if the user
    // does not have the 'create media' permission.
    $assert_session->fieldNotExists('files[upload][]');

    // Assert users with the 'create media' permission can access the media add
    // form.
    $this->grantPermissions($role, [
      'create media',
    ]);
    $this->drupalGet('media-library', $url_options);
    $assert_session->elementExists('css', '.view-media-library');
    $assert_session->fieldExists('Add files');
  }

  /**
   * Tests that the Media library's widget works as expected.
   */
  public function testWidget() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    // Assert that media widget instances are present.
    $assert_session->pageTextContains('Unlimited media');
    $assert_session->pageTextContains('Twin media');
    $assert_session->pageTextContains('Single media type');
    $assert_session->pageTextContains('Empty types media');

    // Assert generic media library elements.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $this->assertFalse($assert_session->elementExists('css', '.media-library-select-all')->isVisible());
    $page->find('css', '.ui-dialog-titlebar-close')->click();

    // Assert that the media type menu is available when more than 1 type is
    // configured for the field.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $menu = $assert_session->elementExists('css', '.media-library-menu');
    $this->assertTrue($menu->hasLink('Type One'));
    $this->assertFalse($menu->hasLink('Type Two'));
    $this->assertTrue($menu->hasLink('Type Three'));
    $this->assertFalse($menu->hasLink('Type Four'));
    $page->find('css', '.ui-dialog-titlebar-close')->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Assert that the media type menu is available when no types are configured
    // for the field. All types should be available in this case.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_empty_types_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $menu = $assert_session->elementExists('css', '.media-library-menu');
    $this->assertTrue($menu->hasLink('Type One'));
    $this->assertTrue($menu->hasLink('Type Two'));
    $this->assertTrue($menu->hasLink('Type Three'));
    $this->assertTrue($menu->hasLink('Type Four'));
    $this->assertTrue($menu->hasLink('Type Five'));
    $page->find('css', '.ui-dialog-titlebar-close')->click();

    // Assert that the media type menu is not available when only 1 type is
    // configured for the field.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_single_media_type"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementTextContains('css', '.media-library-selected-count', '0 of 1 item selected');
    // Select a media item, assert the hidden selection field contains the ID of
    // the selected item.
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $checkboxes[0]->click();
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '4');
    $assert_session->elementTextContains('css', '.media-library-selected-count', '1 of 1 item selected');
    $assert_session->elementNotExists('css', '.media-library-menu');
    $page->find('css', '.ui-dialog-titlebar-close')->click();

    // Assert the menu links can be sorted through the widget configuration.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $links = $page->findAll('css', '.media-library-menu a');
    $link_titles = [];
    foreach ($links as $link) {
      $link_titles[] = $link->getText();
    }
    $expected_link_titles = ['Type One (active tab)', 'Type Two', 'Type Three', 'Type Four'];
    $this->assertSame($link_titles, $expected_link_titles);
    $this->drupalGet('admin/structure/types/manage/basic_page/form-display');
    $assert_session->buttonExists('field_twin_media_settings_edit')->press();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->buttonExists('Show row weights')->press();
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_one][weight]')->selectOption(0);
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_three][weight]')->selectOption(1);
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_four][weight]')->selectOption(2);
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_two][weight]')->selectOption(3);
    $assert_session->buttonExists('Save')->press();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->buttonExists('Hide row weights')->press();
    $this->drupalGet('node/add/basic_page');
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $link_titles = array_map(function ($link) {
      return $link->getText();
    }, $page->findAll('css', '.media-library-menu a'));
    $this->assertSame($link_titles, ['Type One (active tab)', 'Type Three', 'Type Four', 'Type Two']);
    $page->find('css', '.ui-dialog-titlebar-close')->click();

    // Assert media is only visible on the tab for the related media type.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('named', ['link', 'Type Three (active tab)']);
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $page->find('css', '.ui-dialog-titlebar-close')->click();

    // Assert the exposed name filter of the view.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $session = $this->getSession();
    $session->getPage()->fillField('Name', 'Dog');
    $session->getPage()->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $session->getPage()->fillField('Name', '');
    $session->getPage()->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Bear');
    $page->find('css', '.ui-dialog-titlebar-close')->click();

    // Assert the media library contains header links to switch between the grid
    // and table display.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '.media-library-view .media-library-item--grid');
    $assert_session->elementNotExists('css', '.media-library-view .media-library-item--table');
    // Assert the 'Apply filter' button is not moved to the button pane.
    $button_pane = $assert_session->elementExists('css', '.ui-dialog-buttonpane');
    $assert_session->buttonExists('Select media', $button_pane);
    $assert_session->buttonNotExists('Apply filters', $button_pane);
    $assert_session->linkExists('Grid');
    $page->clickLink('Table');
    // Assert the display change is correctly announced for screen readers.
    $this->assertNotEmpty($assert_session->waitForText('Loading table view.'));
    $this->assertNotEmpty($assert_session->waitForText('Changed to table view.'));
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.media-library-view .media-library-item--table'));
    $assert_session->elementNotExists('css', '.media-library-view .media-library-item--grid');
    // Assert the 'Apply filter' button is not moved to the button pane.
    $assert_session->buttonExists('Select media', $button_pane);
    $assert_session->buttonNotExists('Apply filters', $button_pane);
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    // Assert the exposed filters can be applied.
    $page->fillField('Name', 'Dog');
    $page->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $page->checkField('Select Dog');
    $assert_session->linkExists('Table');
    $page->clickLink('Grid');
    // Assert the display change is correctly announced for screen readers.
    $this->assertNotEmpty($assert_session->waitForText('Loading grid view.'));
    $this->assertNotEmpty($assert_session->waitForText('Changed to grid view.'));
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.media-library-view .media-library-item--grid'));
    $assert_session->elementNotExists('css', '.media-library-view .media-library-item--table');
    // Assert the exposed filters are persisted when changing display.
    $this->assertSame('Dog', $page->findField('Name')->getValue());
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $assert_session->linkExists('Grid');
    $assert_session->linkExists('Table');
    // Select the item.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Select media');
    $assert_session->assertWaitOnAjaxRequest();
    // Ensure that the selection completed successfully.
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    // Clear the selection.
    $assert_session->elementAttributeContains('css', '.media-library-item__remove', 'aria-label', 'Remove Dog');
    $assert_session->elementExists('css', '.media-library-item__remove')->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Assert the selection is persistent in the media library modal, and
    // the number of selected items is displayed correctly.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the number of selected items is displayed correctly.
    $assert_session->elementExists('css', '.media-library-selected-count');
    $assert_session->elementTextContains('css', '.media-library-selected-count', '0 of 2 items selected');
    $assert_session->elementAttributeContains('css', '.media-library-selected-count', 'aria-live', 'polite');
    // Select a media item, assert the hidden selection field contains the ID of
    // the selected item.
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $checkboxes[0]->click();
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '4');
    // Assert the number of selected items is displayed correctly.
    $assert_session->elementTextContains('css', '.media-library-selected-count', '1 of 2 items selected');
    // Select another item and assert the number of selected items is updated.
    $checkboxes[1]->click();
    $assert_session->elementTextContains('css', '.media-library-selected-count', '2 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '4,3');
    // Assert unselected items are disabled when the maximum allowed items are
    // selected (cardinality for this field is 2).
    $this->assertTrue($checkboxes[2]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[3]->hasAttribute('disabled'));
    // Assert the selected items are updated when deselecting an item.
    $checkboxes[0]->click();
    $assert_session->elementTextContains('css', '.media-library-selected-count', '1 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '3');
    // Assert deselected items are available again.
    $this->assertFalse($checkboxes[2]->hasAttribute('disabled'));
    $this->assertFalse($checkboxes[3]->hasAttribute('disabled'));
    // The selection should be persisted when navigating to other media types in
    // the modal.
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();
    $page->clickLink('Type One');
    $assert_session->assertWaitOnAjaxRequest();
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $selected_checkboxes = [];
    foreach ($checkboxes as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getValue();
      }
    }
    $this->assertCount(1, $selected_checkboxes);
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', implode(',', $selected_checkboxes));
    $assert_session->elementTextContains('css', '.media-library-selected-count', '1 of 2 items selected');
    // Add to selection from another type.
    $page->clickLink('Type Two');
    $assert_session->assertWaitOnAjaxRequest();
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $checkboxes[0]->click();
    // Assert the selection is updated correctly.
    $assert_session->elementTextContains('css', '.media-library-selected-count', '2 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '3,8');
    // Assert unselected items are disabled when the maximum allowed items are
    // selected (cardinality for this field is 2).
    $this->assertFalse($checkboxes[0]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[1]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[2]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[3]->hasAttribute('disabled'));
    // Assert the checkboxes are also disabled on other pages.
    $page->clickLink('Type One');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTrue($checkboxes[0]->hasAttribute('disabled'));
    $this->assertFalse($checkboxes[1]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[2]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[3]->hasAttribute('disabled'));
    // Select the items.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Select media');
    $assert_session->assertWaitOnAjaxRequest();

    // Ensure that the selection completed successfully.
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextContains('Cat');
    $assert_session->pageTextContains('Turtle');
    $assert_session->pageTextNotContains('Snake');

    // Remove "Cat" (happens to be the first remove button on the page).
    $assert_session->elementAttributeContains('css', '.media-library-item__remove', 'aria-label', 'Remove Cat');
    $assert_session->elementExists('css', '.media-library-item__remove')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Cat');
    $assert_session->pageTextContains('Turtle');

    // Open the media library again and select another item.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $checkboxes[0]->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Select media');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Cat');
    $assert_session->pageTextContains('Turtle');
    $assert_session->pageTextNotContains('Snake');

    // Assert we are not allowed to add more items to the field.
    $assert_session->elementNotExists('css', '.media-library-open-button[href*="field_twin_media"]');

    // Assert the selection is cleared when the modal is closed.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    // Nothing is selected yet.
    $this->assertFalse($checkboxes[0]->isChecked());
    $this->assertFalse($checkboxes[1]->isChecked());
    $this->assertFalse($checkboxes[2]->isChecked());
    $this->assertFalse($checkboxes[3]->isChecked());
    $assert_session->elementTextContains('css', '.media-library-selected-count', '0 items selected');
    // Select the first 2 items.
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $checkboxes[0]->click();
    $assert_session->elementTextContains('css', '.media-library-selected-count', '1 item selected');
    $checkboxes[1]->click();
    $assert_session->elementTextContains('css', '.media-library-selected-count', '2 items selected');
    $this->assertTrue($checkboxes[0]->isChecked());
    $this->assertTrue($checkboxes[1]->isChecked());
    $this->assertFalse($checkboxes[2]->isChecked());
    $this->assertFalse($checkboxes[3]->isChecked());
    // Close the dialog, reopen it and assert not is selected again.
    $page->find('css', '.ui-dialog-titlebar-close')->click();
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $this->assertFalse($checkboxes[0]->isChecked());
    $this->assertFalse($checkboxes[1]->isChecked());
    $this->assertFalse($checkboxes[2]->isChecked());
    $this->assertFalse($checkboxes[3]->isChecked());
    $page->find('css', '.ui-dialog-titlebar-close')->click();

    // Finally, save the form.
    $assert_session->elementExists('css', '.js-media-library-widget-toggle-weight')->click();
    $this->submitForm([
      'title[0][value]' => 'My page',
      'field_twin_media[selection][0][weight]' => '2',
    ], 'Save');
    $assert_session->pageTextContains('Basic Page My page has been created');
    // We removed this item earlier.
    $assert_session->pageTextNotContains('Cat');
    // This item was never selected.
    $assert_session->pageTextNotContains('Snake');
    // "Dog" should come after "Turtle", since we changed the weight.
    $assert_session->elementExists('css', '.field--name-field-twin-media > .field__items > .field__item:last-child:contains("Turtle")');
    // Make sure everything that was selected shows up.
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Turtle');

    // Re-edit the content and make a new selection.
    $this->drupalGet('node/1/edit');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Cat');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Horse');
    $assert_session->pageTextContains('Turtle');
    $assert_session->pageTextNotContains('Snake');
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    // Select all media items of type one (should also contain Dog, again).
    $checkbox_selector = '.media-library-view .js-click-to-select-checkbox input';
    $checkboxes = $page->findAll('css', $checkbox_selector);
    $checkboxes[0]->click();
    $checkboxes[1]->click();
    $checkboxes[2]->click();
    $checkboxes[3]->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Select media');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Cat');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextContains('Horse');
    $assert_session->pageTextContains('Turtle');
    $assert_session->pageTextNotContains('Snake');
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Cat');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextContains('Horse');
    $assert_session->pageTextContains('Turtle');
    $assert_session->pageTextNotContains('Snake');
  }

  /**
   * Tests that the widget works as expected for anonymous users.
   */
  public function testWidgetAnonymous() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogout();

    // Allow the anonymous user to create pages and view media.
    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $this->grantPermissions($role, [
      'access content',
      'create basic_page content',
      'view media',
    ]);

    // Ensure the widget works as an anonymous user.
    $this->drupalGet('node/add/basic_page');

    // Add to the unlimited cardinality field.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Select the first media item (should be Dog).
    $page->find('css', '.media-library-view .js-click-to-select-checkbox input')->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Select media');
    $assert_session->assertWaitOnAjaxRequest();

    // Ensure that the selection completed successfully.
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains('Dog');

    // Save the form.
    $assert_session->elementExists('css', '.js-media-library-widget-toggle-weight')->click();
    $this->submitForm([
      'title[0][value]' => 'My page',
      'field_unlimited_media[selection][0][weight]' => '0',
    ], 'Save');
    $assert_session->pageTextContains('Basic Page My page has been created');
    $assert_session->pageTextContains('Dog');
  }

  /**
   * Tests that uploads in the Media library's widget works as expected.
   */
  public function testWidgetUpload() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    foreach ($this->getTestFiles('image') as $image) {
      $extension = pathinfo($image->filename, PATHINFO_EXTENSION);
      if ($extension === 'png') {
        $png_image = $image;
      }
      elseif ($extension === 'jpg') {
        $jpg_image = $image;
      }
    }

    if (!isset($png_image) || !isset($jpg_image)) {
      $this->fail('Expected test files not present.');
    }

    // Create a user that can only add media of type four.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'create basic_page content',
      'create type_four media',
      'view media',
    ]);
    $this->drupalLogin($user);

    // Visit a node create page and open the media library.
    $this->drupalGet('node/add/basic_page');
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');

    // Assert the upload form is visible for type_four.
    $page->clickLink('Type Four');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldExists('Add files');
    $assert_session->pageTextContains('Maximum 2 files.');

    // Assert the upload form is not visible for type_three.
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldNotExists('files[upload][]');
    $assert_session->pageTextNotContains('Maximum 2 files.');

    // Create a user that can create media for all media types.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'create basic_page content',
      'create media',
      'view media',
    ]);
    $this->drupalLogin($user);

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    $file_storage = $this->container->get('entity_type.manager')->getStorage('file');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = $this->container->get('file_system');

    // Add to the twin media field.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');

    // Assert the default tab for media type one does not have an upload form.
    $assert_session->fieldNotExists('files[upload][]');

    // Assert we can upload a file to media type three.
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '.media-library-add-form--without-input');
    $assert_session->elementNotExists('css', '.media-library-add-form--with-input');
    $page->attachFileToField('Add files', $this->container->get('file_system')->realpath($png_image->uri));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '.media-library-add-form--with-input');
    $assert_session->elementNotExists('css', '.media-library-add-form--without-input');

    // Files are temporary until the form is saved.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertSame('public://type-three-dir', $file_system->dirname($file->getFileUri()));
    $this->assertTrue($file->isTemporary());

    // Assert the revision_log_message field is not shown.
    $upload_form = $assert_session->elementExists('css', '.media-library-add-form');
    $assert_session->fieldNotExists('Revision log message', $upload_form);

    // Assert the name field contains the filename and the alt text is required.
    $assert_session->fieldValueEquals('Name', $png_image->filename);
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Alternative text field is required');
    $page->fillField('Alternative text', $this->randomString());
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save');
    $assert_session->assertWaitOnAjaxRequest();

    // The file should be permanent now.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertFalse($file->isTemporary());

    // Load the created media item.
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');
    $media_items = $media_storage->loadMultiple();
    $added_media = array_pop($media_items);

    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains($png_image->filename);
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');
    $assert_session->pageTextContains('1 of 2 items selected');

    // Ensure the created item is added in the widget.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Select media');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains($png_image->filename);

    // Also make sure that we can upload to the unlimited cardinality field.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');

    // Navigate to the media type three tab first.
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();

    // Select a media item.
    $page->find('css', '.media-library-view .js-click-to-select-checkbox input')->click();
    $assert_session->pageTextContains('1 item selected');

    // Multiple uploads should be allowed.
    // @todo Add test when https://github.com/minkphp/Mink/issues/358 is closed
    $this->assertTrue($assert_session->fieldExists('Add files')->hasAttribute('multiple'));

    $page->attachFileToField('Add files', $this->container->get('file_system')->realpath($png_image->uri));
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Name', 'Unlimited Cardinality Image');
    $page->fillField('Alternative text', $this->randomString());
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save');
    $assert_session->assertWaitOnAjaxRequest();

    // Load the created media item.
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');
    $media_items = $media_storage->loadMultiple();
    $added_media = array_pop($media_items);

    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains('Unlimited Cardinality Image');
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');

    // Assert the item that was selected before uploading the file is still
    // selected.
    $assert_session->pageTextContains('2 items selected');
    $checkboxes = $page->findAll('css', '.media-library-view .js-click-to-select-checkbox input');
    $selected_checkboxes = [];
    foreach ($checkboxes as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getValue();
      }
    }
    $this->assertCount(2, $selected_checkboxes);

    // Ensure the created item is added in the widget.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Select media');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains('Unlimited Cardinality Image');

    // Verify we can only upload the files allowed by the media type.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_twin_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $page->clickLink('Type Four');
    $assert_session->assertWaitOnAjaxRequest();

    // Assert we can now only upload one more media item.
    $this->assertFalse($assert_session->fieldExists('Add file')->hasAttribute('multiple'));
    $assert_session->pageTextContains('One file only.');

    // Assert media type four should only allow jpg files by trying a png file
    // first.
    $page->attachFileToField('Add file', $file_system->realpath($png_image->uri));
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Only files with the following extensions are allowed');

    // Assert that jpg files are accepted by type four.
    $page->attachFileToField('Add file', $file_system->realpath($jpg_image->uri));
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Alternative text', $this->randomString());

    // The type_four media type has another optional image field.
    $assert_session->pageTextContains('Extra Image');
    $page->attachFileToField('Extra Image', $this->container->get('file_system')->realpath($jpg_image->uri));
    $assert_session->assertWaitOnAjaxRequest();
    // Ensure that the extra image was uploaded to the correct directory.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertSame('public://type-four-extra-dir', $file_system->dirname($file->getFileUri()));

    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save');
    $assert_session->assertWaitOnAjaxRequest();

    // Ensure the media item was saved to the library and automatically
    // selected.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains($jpg_image->filename);

    // Ensure the created item is added in the widget.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Select media');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains($jpg_image->filename);
  }

  /**
   * Tests that oEmbed media can be added in the Media library's widget.
   */
  public function testWidgetOEmbed() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $video_title = "Everyday I'm Drupalin' Drupal Rap (Rick Ross - Hustlin)";
    $video_url = 'https://www.youtube.com/watch?v=PWjcqE3QKBg';
    ResourceController::setResourceUrl($video_url, $this->getFixturesDirectory() . '/video_youtube.json');

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    // Add to the unlimited media field.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');

    // Assert the default tab for media type one does not have an oEmbed form.
    $assert_session->fieldNotExists('Add Type Five via URL');

    // Assert other media types don't have the oEmbed form fields.
    $page->clickLink('Type Three');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldNotExists('Add Type Five via URL');

    // Assert we can add an oEmbed video to media type five.
    $page->clickLink('Type Five');
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Add Type Five via URL', $video_url);
    $assert_session->pageTextContains('Allowed providers: YouTube, Vimeo.');
    $page->pressButton('Add');
    $assert_session->assertWaitOnAjaxRequest();
    // Assert the name field contains the remote video title.
    $assert_session->fieldValueEquals('Name', $video_title);
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save');
    $assert_session->assertWaitOnAjaxRequest();

    // Load the created media item.
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');
    $media_items = $media_storage->loadMultiple();
    $added_media = array_pop($media_items);

    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains($video_title);
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');

    // Assert the created oEmbed video is correctly added to the widget.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Select media');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains($video_title);

    // Open the media library again for the unlimited field and go to the tab
    // for media type five.
    $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $page->clickLink('Type Five');
    $assert_session->assertWaitOnAjaxRequest();

    // Assert the video is available on the tab.
    $assert_session->pageTextContains($video_title);

    // Assert we can only add supported URLs.
    $page->fillField('Add Type Five via URL', 'https://www.youtube.com/');
    $page->pressButton('Add');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('No matching provider found.');
    // Assert we can not add a video ID that doesn't exist. We need to use a
    // video ID that will not be filtered by the regex, because otherwise the
    // message 'No matching provider found.' will be returned.
    $page->fillField('Add Type Five via URL', 'https://www.youtube.com/watch?v=PWjcqE3QKBg1');
    $page->pressButton('Add');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Could not retrieve the oEmbed resource.');

    // Assert we can add a oEmbed video with a custom name.
    $page->fillField('Add Type Five via URL', $video_url);
    $page->pressButton('Add');
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Name', 'Custom video title');
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains('Custom video title');

    // Assert the created oEmbed video is correctly added to the widget.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Select media');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->pageTextContains('Custom video title');
  }

}
