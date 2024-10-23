<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\SortableTestTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the Media library entity reference widget.
 *
 * @group media_library
 */
class EntityReferenceWidgetTest extends MediaLibraryTestBase {

  use SortableTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_ui'];

  /**
   * The theme to install as the default for testing.
   *
   * @var string
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * Test media items.
   *
   * @var \Drupal\media\MediaInterface[]
   */
  protected $mediaItems = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a few example media items for use in selection.
    $this->mediaItems = $this->createMediaItems([
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
    ]);

    // Create a user who can use the Media library.
    $user = $this->drupalCreateUser([
      'access content',
      'create basic_page content',
      'edit own basic_page content',
      'view media',
      'create media',
      'administer node form display',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests that disabled media items don't capture focus on page load.
   */
  public function testFocusNotAppliedWithoutSelectionChange(): void {
    // Create a node with the maximum number of values for the field_twin_media
    // field.
    $node = $this->drupalCreateNode([
      'type' => 'basic_page',
      'field_twin_media' => [
        $this->mediaItems['Horse'],
        $this->mediaItems['Bear'],
      ],
    ]);
    $this->drupalGet($node->toUrl('edit-form'));
    $open_button = $this->assertElementExistsAfterWait('css', '.js-media-library-open-button[name^="field_twin_media"]');
    // The open button should be disabled, but not have the
    // 'data-disabled-focus' attribute.
    $this->assertFalse($open_button->hasAttribute('data-disabled-focus'));
    $this->assertTrue($open_button->hasAttribute('disabled'));
    // The button should be disabled.
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").is(":disabled")');
    // The button should not have focus.
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").not(":focus")');
  }

  /**
   * Tests that the Media library's widget works as expected.
   */
  public function testWidget(): void {
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
    $this->openMediaLibraryForField('field_unlimited_media');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert that the media type menu is available when more than 1 type is
    // configured for the field.
    $menu = $this->openMediaLibraryForField('field_unlimited_media');
    $this->assertTrue($menu->hasLink('Show Type One media (selected)'));
    $this->assertFalse($menu->hasLink('Type Two'));
    $this->assertTrue($menu->hasLink('Type Three'));
    $this->assertFalse($menu->hasLink('Type Four'));
    $this->switchToMediaType('Three');
    // Assert the active tab is set correctly.
    $this->assertFalse($menu->hasLink('Show Type One media (selected)'));
    $this->assertTrue($menu->hasLink('Show Type Three media (selected)'));
    // Assert the focus is set to the first tabbable element when a vertical tab
    // is clicked.
    $this->assertJsCondition('jQuery(tabbable.tabbable(document.getElementById("media-library-content"))[0]).is(":focus")');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert that there are no links in the media library view.
    $this->openMediaLibraryForField('field_unlimited_media');
    $assert_session->elementNotExists('css', '.media-library-item__name a');
    $assert_session->elementNotExists('css', '.view-media-library .media-library-item__edit');
    $assert_session->elementNotExists('css', '.view-media-library .media-library-item__remove');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert that the media type menu is available when the target_bundles
    // setting for the entity reference field is null. All types should be
    // allowed in this case.
    $menu = $this->openMediaLibraryForField('field_null_types_media');

    // Assert that the button to open the media library does not submit the
    // parent form. We can do this by checking if the validation of the parent
    // form is not triggered.
    $assert_session->pageTextNotContains('Title field is required.');

    $this->assertTrue($menu->hasLink('Type One'));
    $this->assertTrue($menu->hasLink('Type Two'));
    $this->assertTrue($menu->hasLink('Type Three'));
    $this->assertTrue($menu->hasLink('Type Four'));
    $this->assertTrue($menu->hasLink('Type Five'));

    // Insert media to test validation with null target_bundles.
    $this->switchToMediaType('One');
    $this->assertAnnounceContains('Showing Type One media.');
    $this->selectMediaItem(0);
    $this->pressInsertSelected('Added one media item.');

    // Assert that the media type menu is not available when only 1 type is
    // configured for the field.
    $this->openMediaLibraryForField('field_single_media_type', '#media-library-wrapper');
    $this->waitForElementTextContains('.media-library-selected-count', '0 of 1 item selected');

    // Select a media item, assert the hidden selection field contains the ID of
    // the selected item.
    $this->selectMediaItem(0);
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '4');
    $this->assertSelectedMediaCount('1 of 1 item selected');
    $assert_session->elementNotExists('css', '.js-media-library-menu');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert the menu links can be sorted through the widget configuration.
    $this->openMediaLibraryForField('field_twin_media');
    $links = $this->getTypesMenu()->findAll('css', 'a');
    $link_titles = [];
    foreach ($links as $link) {
      $link_titles[] = $link->getText();
    }
    $expected_link_titles = ['Show Type Three media (selected)', 'Show Type One media', 'Show Type Two media', 'Show Type Four media'];
    $this->assertSame($link_titles, $expected_link_titles);
    $this->drupalGet('admin/structure/types/manage/basic_page/form-display');

    // Ensure that the widget settings form is not displayed when only
    // one media type is allowed.
    $assert_session->pageTextContains('Single media type');
    $assert_session->buttonNotExists('field_single_media_type_settings_edit');

    $assert_session->buttonExists('field_twin_media_settings_edit')->press();
    $this->assertElementExistsAfterWait('css', '#field-twin-media .tabledrag-toggle-weight')->press();
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_one][weight]')->selectOption('0');
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_three][weight]')->selectOption('1');
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_four][weight]')->selectOption('2');
    $assert_session->fieldExists('fields[field_twin_media][settings_edit_form][settings][media_types][type_two][weight]')->selectOption('3');
    $assert_session->buttonExists('Save')->press();

    $this->drupalGet('node/add/basic_page');
    $this->openMediaLibraryForField('field_twin_media');
    $link_titles = array_map(function ($link) {
      return $link->getText();
    }, $links);
    $this->assertSame($link_titles, ['Show Type One media (selected)', 'Show Type Three media', 'Show Type Four media', 'Show Type Two media']);
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert the announcements for media type navigation in the media library.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    $this->assertAnnounceContains('Showing Type Three media.');
    $this->switchToMediaType('One');
    $this->assertAnnounceContains('Showing Type One media.');
    // Assert the links can be triggered by via the space bar.
    $assert_session->elementExists('named', ['link', 'Type Three'])->keyPress(32);
    $this->assertAnnounceContains('Showing Type Three media.');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert media is only visible on the tab for the related media type.
    $this->openMediaLibraryForField('field_unlimited_media');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $this->switchToMediaType('Three');
    $this->assertAnnounceContains('Showing Type Three media.');
    $assert_session->elementExists('named', ['link', 'Show Type Three media (selected)']);
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert the exposed name filter of the view.
    $this->openMediaLibraryForField('field_unlimited_media');
    $session = $this->getSession();
    $session->getPage()->fillField('Name', 'Dog');
    $session->getPage()->pressButton('Apply filters');
    $this->waitForText('Dog');
    $this->waitForNoText('Bear');
    $session->getPage()->fillField('Name', '');
    $session->getPage()->pressButton('Apply filters');
    $this->waitForText('Dog');
    $this->waitForText('Bear');
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert adding a single media item and removing it.
    $this->openMediaLibraryForField('field_twin_media');
    $this->selectMediaItem(0);
    $this->pressInsertSelected('Added one media item.');
    // Assert the focus is set back on the open button of the media field.
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").is(":focus")');

    // The toggle for weight inputs' visibility should not be available when the
    // field contains a single item.
    $wrapper = $assert_session->elementExists('css', '.field--name-field-twin-media');
    $assert_session->elementNotExists('named', ['button', 'Show media item weights'], $wrapper);

    // Remove the selected item.
    $button = $assert_session->buttonExists('Remove', $wrapper);
    $this->assertSame('Remove Dog', $button->getAttribute('aria-label'));
    $button->press();
    $this->waitForText('Dog has been removed.');
    // Assert the focus is set back on the open button of the media field.
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").is(":focus")');

    // Assert we can select the same media item twice.
    $this->openMediaLibraryForField('field_twin_media');
    $page->checkField('Select Dog');
    $this->pressInsertSelected('Added one media item.');
    $this->openMediaLibraryForField('field_twin_media');
    $page->checkField('Select Dog');
    $this->pressInsertSelected('Added one media item.');
    $this->waitForElementsCount('css', '.field--name-field-twin-media [data-media-library-item-delta]', 2);
    // Assert that we can toggle the visibility of the weight inputs when the
    // field contains more than one item.
    $wrapper = $assert_session->elementExists('css', '.field--name-field-twin-media');
    $wrapper->pressButton('Show media item weights');
    // Ensure that the styling doesn't accidentally render the weight field
    // unusable.
    $assert_session->fieldExists('Weight', $wrapper)->click();
    $wrapper->pressButton('Hide media item weights');

    // Assert the same has been added twice and remove the items again.
    $this->waitForElementsCount('css', '.field--name-field-twin-media [data-media-library-item-delta]', 2);
    $assert_session->hiddenFieldValueEquals('field_twin_media[selection][0][target_id]', '4');
    $assert_session->hiddenFieldValueEquals('field_twin_media[selection][1][target_id]', '4');
    $wrapper->pressButton('Remove');
    $this->waitForText('Dog has been removed.');
    $wrapper->pressButton('Remove');
    $this->waitForText('Dog has been removed.');
    $result = $wrapper->waitFor(10, function ($wrapper) {
      /** @var \Behat\Mink\Element\NodeElement $wrapper */
      return $wrapper->findButton('Remove') == NULL;
    });
    $this->assertTrue($result);

    // Assert the selection is persistent in the media library modal, and
    // the number of selected items is displayed correctly.
    $this->openMediaLibraryForField('field_twin_media');
    // Assert the number of selected items is displayed correctly.
    $this->assertSelectedMediaCount('0 of 2 items selected');
    // Select a media item, assert the hidden selection field contains the ID of
    // the selected item.
    $checkboxes = $this->getCheckboxes();
    $this->assertCount(4, $checkboxes);
    $this->selectMediaItem(0, '1 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '4');
    // Select another item and assert the number of selected items is updated.
    $this->selectMediaItem(1, '2 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '4,3');
    // Assert unselected items are disabled when the maximum allowed items are
    // selected (cardinality for this field is 2).
    $this->assertTrue($checkboxes[2]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[3]->hasAttribute('disabled'));
    // Assert the selected items are updated when deselecting an item.
    $checkboxes[0]->click();
    $this->assertSelectedMediaCount('1 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '3');
    // Assert deselected items are available again.
    $this->assertFalse($checkboxes[2]->hasAttribute('disabled'));
    $this->assertFalse($checkboxes[3]->hasAttribute('disabled'));
    // The selection should be persisted when navigating to other media types in
    // the modal.
    $this->switchToMediaType('Three');
    $this->switchToMediaType('One');
    $selected_checkboxes = [];
    foreach ($this->getCheckboxes() as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getValue();
      }
    }
    $this->assertCount(1, $selected_checkboxes);
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', implode(',', $selected_checkboxes));
    $this->assertSelectedMediaCount('1 of 2 items selected');
    // Add to selection from another type.
    $this->switchToMediaType('Two');
    $checkboxes = $this->getCheckboxes();
    $this->assertCount(4, $checkboxes);
    $this->selectMediaItem(0, '2 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('media-library-modal-selection', '3,8');
    // Assert unselected items are disabled when the maximum allowed items are
    // selected (cardinality for this field is 2).
    $this->assertFalse($checkboxes[0]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[1]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[2]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[3]->hasAttribute('disabled'));
    // Assert the checkboxes are also disabled on other pages.
    $this->switchToMediaType('One');
    $this->assertTrue($checkboxes[0]->hasAttribute('disabled'));
    $this->assertFalse($checkboxes[1]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[2]->hasAttribute('disabled'));
    $this->assertTrue($checkboxes[3]->hasAttribute('disabled'));
    // Select the items.
    $this->pressInsertSelected('Added 2 media items.');
    // Assert the open button is disabled.
    $open_button = $this->assertElementExistsAfterWait('css', '.js-media-library-open-button[name^="field_twin_media"]');
    $this->assertTrue($open_button->hasAttribute('data-disabled-focus'));
    $this->assertTrue($open_button->hasAttribute('disabled'));
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").is(":disabled")');

    // Ensure that the selection completed successfully.
    $assert_session->pageTextNotContains('Add or select media');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Dog');
    $assert_session->elementTextContains('css', '#field_twin_media-media-library-wrapper', 'Cat');
    $assert_session->elementTextContains('css', '#field_twin_media-media-library-wrapper', 'Turtle');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Snake');

    // Remove "Cat" (happens to be the first remove button on the page).
    $button = $assert_session->buttonExists('Remove', $wrapper);
    $this->assertSame('Remove Cat', $button->getAttribute('aria-label'));
    $button->press();
    $this->waitForText('Cat has been removed.');
    // Assert the focus is set to the wrapper of the other selected item.
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper [data-media-library-item-delta]").is(":focus")');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Cat');
    $assert_session->elementTextContains('css', '#field_twin_media-media-library-wrapper', 'Turtle');
    // Assert the open button is no longer disabled.
    $open_button = $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_twin_media"]');
    $this->assertFalse($open_button->hasAttribute('data-disabled-focus'));
    $this->assertFalse($open_button->hasAttribute('disabled'));
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").is(":not(:disabled)")');

    // Open the media library again and select another item.
    $this->openMediaLibraryForField('field_twin_media');
    $this->selectMediaItem(0);
    $this->pressInsertSelected('Added one media item.');
    $this->waitForElementTextContains('#field_twin_media-media-library-wrapper', 'Dog');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Cat');
    $assert_session->elementTextContains('css', '#field_twin_media-media-library-wrapper', 'Turtle');
    $assert_session->elementTextNotContains('css', '#field_twin_media-media-library-wrapper', 'Snake');
    // Assert the open button is disabled.
    $this->assertTrue($assert_session->elementExists('css', '.js-media-library-open-button[name^="field_twin_media"]')->hasAttribute('data-disabled-focus'));
    $this->assertTrue($assert_session->elementExists('css', '.js-media-library-open-button[name^="field_twin_media"]')->hasAttribute('disabled'));
    $this->assertJsCondition('jQuery("#field_twin_media-media-library-wrapper .js-media-library-open-button").is(":disabled")');

    // Assert the selection is cleared when the modal is closed.
    $this->openMediaLibraryForField('field_unlimited_media');
    $checkboxes = $this->getCheckboxes();
    $this->assertGreaterThanOrEqual(4, count($checkboxes));
    // Nothing is selected yet.
    $this->assertFalse($checkboxes[0]->isChecked());
    $this->assertFalse($checkboxes[1]->isChecked());
    $this->assertFalse($checkboxes[2]->isChecked());
    $this->assertFalse($checkboxes[3]->isChecked());
    $this->assertSelectedMediaCount('0 items selected');
    // Select the first 2 items.
    $checkboxes[0]->click();
    $this->assertSelectedMediaCount('1 item selected');
    $checkboxes[1]->click();
    $this->assertSelectedMediaCount('2 items selected');
    $this->assertTrue($checkboxes[0]->isChecked());
    $this->assertTrue($checkboxes[1]->isChecked());
    $this->assertFalse($checkboxes[2]->isChecked());
    $this->assertFalse($checkboxes[3]->isChecked());
    // Close the dialog, reopen it and assert not is selected again.
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();
    $this->openMediaLibraryForField('field_unlimited_media');
    $checkboxes = $this->getCheckboxes();
    $this->assertGreaterThanOrEqual(4, count($checkboxes));
    $this->assertFalse($checkboxes[0]->isChecked());
    $this->assertFalse($checkboxes[1]->isChecked());
    $this->assertFalse($checkboxes[2]->isChecked());
    $this->assertFalse($checkboxes[3]->isChecked());
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Finally, save the form.
    $assert_session->elementExists('css', '.js-media-library-widget-toggle-weight')->click();
    $this->submitForm([
      'title[0][value]' => 'My page',
      'field_twin_media[selection][0][weight]' => '3',
    ], 'Save');
    $assert_session->pageTextContains('Basic Page My page has been created');
    // We removed this item earlier.
    $assert_session->pageTextNotContains('Cat');
    // This item was never selected.
    $assert_session->pageTextNotContains('Snake');
    // "Turtle" should come after "Dog", since we changed the weight.
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
    $this->openMediaLibraryForField('field_unlimited_media');
    // Select all media items of type one (should also contain Dog, again).
    $this->selectMediaItem(0);
    $this->selectMediaItem(1);
    $this->selectMediaItem(2);
    $this->selectMediaItem(3);
    $this->pressInsertSelected('Added 4 media items.');
    $this->waitForText('Dog');
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
   * Tests saving a required media library field.
   */
  public function testRequiredMediaField(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Make field_unlimited_media required.
    $field_config = FieldConfig::loadByName('node', 'basic_page', 'field_unlimited_media');
    $field_config->setRequired(TRUE)->save();

    $this->drupalGet('node/add/basic_page');

    $page->fillField('Title', 'My page');
    $page->pressButton('Save');

    // Check that a clear error message is shown.
    $assert_session->pageTextNotContains('This value should not be null.');
    $assert_session->pageTextContains(sprintf('%s field is required.', $field_config->label()));

    // Open the media library, select an item and save the node.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->selectMediaItem(0);
    $this->pressInsertSelected('Added one media item.');
    $page->pressButton('Save');

    // Confirm that the node was created.
    $this->assertSession()->pageTextContains('Basic page My page has been created.');
  }

  /**
   * Tests that changed order is maintained after removing a selection.
   */
  public function testRemoveAfterReordering(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/basic_page');
    $page->fillField('Title', 'My page');

    $this->openMediaLibraryForField('field_unlimited_media');
    $page->checkField('Select Dog');
    $page->checkField('Select Cat');
    $page->checkField('Select Bear');
    // Order: Dog - Cat - Bear.
    $this->pressInsertSelected('Added 3 media items.');

    // Move first item (Dog) to the end.
    // Order: Cat - Bear - Dog.
    $this->sortableAfter('[data-media-library-item-delta="0"]', '[data-media-library-item-delta="2"]', '.js-media-library-selection');

    $wrapper = $assert_session->elementExists('css', '.field--name-field-unlimited-media');
    // Remove second item (Bear).
    // Order: Cat - Dog.
    $wrapper->find('css', "[aria-label='Remove Bear']")->press();
    $this->waitForText('Bear has been removed.');
    $page->pressButton('Save');

    $assert_session->elementTextContains('css', '.field--name-field-unlimited-media > .field__items > .field__item:last-child', 'Dog');
  }

  /**
   * Tests that order is correct after re-order and adding another item.
   */
  public function testAddAfterReordering(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/basic_page');
    $page->fillField('Title', 'My page');

    $this->openMediaLibraryForField('field_unlimited_media');
    $page->checkField('Select Dog');
    $page->checkField('Select Cat');
    // Order: Dog - Cat.
    $this->pressInsertSelected('Added 2 media items.');

    // Change positions.
    // Order: Cat - Dog.
    $this->sortableAfter('[data-media-library-item-delta="0"]', '[data-media-library-item-delta="1"]', '.js-media-library-selection');

    $this->openMediaLibraryForField('field_unlimited_media');
    $this->selectMediaItem(2);
    // Order: Cat - Dog - Bear.
    $this->pressInsertSelected('Added one media item.');

    $page->pressButton('Save');

    $assert_session->elementTextContains('css', '.field--name-field-unlimited-media > .field__items > .field__item:first-child', 'Cat');
    $assert_session->elementTextContains('css', '.field--name-field-unlimited-media > .field__items > .field__item:last-child', 'Bear');
  }

  /**
   * Checks for inclusion of text in #drupal-live-announce.
   *
   * @param string $expected_message
   *   The text that is expected to be present in the #drupal-live-announce element.
   *
   * @internal
   */
  protected function assertAnnounceContains(string $expected_message): void {
    $assert_session = $this->assertSession();
    $this->assertNotEmpty($assert_session->waitForElement('css', "#drupal-live-announce:contains('$expected_message')"));
  }

  /**
   * {@inheritdoc}
   */
  protected function sortableUpdate($item, $from, $to = NULL): void {
    // See core/modules/media_library/js/media_library.widget.js.
    $script = <<<JS
(function ($) {
    var selection = document.querySelectorAll('.js-media-library-selection');
    selection.forEach(function (widget) {
        $(widget).children().each(function (index, child) {
            $(child).find('.js-media-library-item-weight').val(index);
        });
    });
})(jQuery)

JS;

    $this->getSession()->executeScript($script);
  }

  /**
   * Tests the preview displayed by the field widget.
   */
  public function testWidgetPreview(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $node = $this->drupalCreateNode([
      'type' => 'basic_page',
      'field_unlimited_media' => [
        $this->mediaItems['Horse'],
      ],
    ]);
    $media_id = $this->mediaItems['Horse']->id();

    // Assert that preview is present for current user, who can view media.
    $this->drupalGet($node->toUrl('edit-form'));
    $assert_session->elementTextContains('css', '[data-drupal-selector="edit-field-unlimited-media-selection-0"]', 'Horse');
    $remove_button = $page->find('css', '[data-drupal-selector="edit-field-unlimited-media-selection-0-remove-button"]');
    $this->assertSame('Remove Horse', $remove_button->getAttribute('aria-label'));
    $assert_session->pageTextNotContains('You do not have permission to view media item');
    $remove_button->press();
    $this->waitForText("Removing Horse.");
    $this->waitForText("Horse has been removed.");
    // Logout without saving.
    $this->drupalLogout();

    // Create a user who can edit content but not view media.
    // Must remove permission from authenticated role first, otherwise the new
    // user will inherit that permission.
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);
    $role->revokePermission('view media');
    $role->save();
    $non_media_editor = $this->drupalCreateUser([
      'access content',
      'create basic_page content',
      'edit any basic_page content',
    ]);
    $this->drupalLogin($non_media_editor);

    // Assert that preview does not reveal media name.
    $this->drupalGet($node->toUrl('edit-form'));
    // There should be no preview name.
    $assert_session->elementTextNotContains('css', '[data-drupal-selector="edit-field-unlimited-media-selection-0"]', 'Horse');
    // The remove button should have a generic message.
    $remove_button = $page->find('css', '[data-drupal-selector="edit-field-unlimited-media-selection-0-remove-button"]');
    $this->assertSame('Remove media', $remove_button->getAttribute('aria-label'));
    $assert_session->pageTextContains("You do not have permission to view media item $media_id.");
    // Confirm ajax text does not reveal media name.
    $remove_button->press();
    $this->waitForText("Removing media.");
    $this->waitForText("Media has been removed.");
  }

}
