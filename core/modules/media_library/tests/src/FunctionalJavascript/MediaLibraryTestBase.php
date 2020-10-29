<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;

/**
 * Base class for functional tests of Media Library functionality.
 */
abstract class MediaLibraryTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media_library_test', 'hold_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Create media items.
   *
   * @param array $media_items
   *   A nested array of media item names keyed by media type.
   *
   * @return \Drupal\media\MediaInterface[]
   *   An array of media entities keyed by the names passed in.
   */
  protected function createMediaItems(array $media_items) {
    $created_items = [];
    $time = time();
    foreach ($media_items as $type => $names) {
      foreach ($names as $name) {
        /** @var \Drupal\media\MediaInterface $media */
        $media = Media::create([
          'name' => $name,
          'bundle' => $type,
        ]);
        $source_field = $media->getSource()
          ->getSourceFieldDefinition($media->bundle->entity)
          ->getName();
        $media->set($source_field, $name)->setCreatedTime(++$time)->save();
        $created_items[$name] = $media;
      }
    }
    return $created_items;
  }

  /**
   * Asserts that text appears on page after a wait.
   *
   * @param string $text
   *   The text that should appear on the page.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function waitForText($text, $timeout = 10000) {
    $result = $this->assertSession()->waitForText($text, $timeout);
    $this->assertNotEmpty($result, "\"$text\" not found");
  }

  /**
   * Asserts that text does not appear on page after a wait.
   *
   * @param string $text
   *   The text that should not be on the page.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function waitForNoText($text, $timeout = 10000) {
    $page = $this->getSession()->getPage();
    $result = $page->waitFor($timeout / 1000, function ($page) use ($text) {
      $actual = preg_replace('/\s+/u', ' ', $page->getText());
      $regex = '/' . preg_quote($text, '/') . '/ui';
      return (bool) !preg_match($regex, $actual);
    });
    $this->assertNotEmpty($result, "\"$text\" was found but shouldn't be there.");
  }

  /**
   * Checks for a specified number of specific elements on page after wait.
   *
   * @param string $selector_type
   *   Element selector type (css, xpath)
   * @param string|array $selector
   *   Element selector.
   * @param int $count
   *   Expected count.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function waitForElementsCount($selector_type, $selector, $count, $timeout = 10000) {
    $page = $this->getSession()->getPage();

    $start = microtime(TRUE);
    $end = $start + ($timeout / 1000);
    do {
      $nodes = $page->findAll($selector_type, $selector);
      if (count($nodes) === $count) {
        return;
      }
      usleep(100000);
    } while (microtime(TRUE) < $end);

    $this->assertSession()->elementsCount($selector_type, $selector, $count);
  }

  /**
   * Asserts that text appears in an element after a wait.
   *
   * @param string $selector
   *   The CSS selector of the element to check.
   * @param string $text
   *   The text that should appear in the element.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function waitForElementTextContains($selector, $text, $timeout = 10000) {
    $element = $this->assertSession()->waitForElement('css', "$selector:contains('$text')", $timeout);
    $this->assertNotEmpty($element);
  }

  /**
   * Waits for the specified selector and returns it if not empty.
   *
   * @param string $selector
   *   The selector engine name. See ElementInterface::findAll() for the
   *   supported selectors.
   * @param string|array $locator
   *   The selector locator.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The page element node if found. If not found, the test fails.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function assertElementExistsAfterWait($selector, $locator, $timeout = 10000) {
    $element = $this->assertSession()->waitForElement($selector, $locator, $timeout);
    $this->assertNotEmpty($element);
    return $element;
  }

  /**
   * Gets the menu of available media types.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The menu of available media types.
   */
  protected function getTypesMenu() {
    return $this->assertSession()
      ->elementExists('css', '.js-media-library-menu');
  }

  /**
   * Clicks a media type tab and waits for it to appear.
   */
  protected function switchToMediaType($type) {
    $link = $this->assertSession()
      ->elementExists('named', ['link', "Type $type"], $this->getTypesMenu());

    if ($link->hasClass('active')) {
      // There is nothing to do as the type is already active.
      return;
    }

    $link->click();
    $result = $link->waitFor(10, function ($link) {
      /** @var \Behat\Mink\Element\NodeElement $link */
      return $link->hasClass('active');
    });
    $this->assertNotEmpty($result);

    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Checks for the existence of a field on page after wait.
   *
   * @param string $field
   *   The field to find.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The element if found, otherwise null.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function waitForFieldExists($field, $timeout = 10000) {
    $assert_session = $this->assertSession();
    $assert_session->waitForField($field, $timeout);
    return $assert_session->fieldExists($field);
  }

  /**
   * Waits for a file field to exist before uploading.
   */
  public function addMediaFileToField($locator, $path) {
    $page = $this->getSession()->getPage();
    $this->waitForFieldExists($locator);
    $page->attachFileToField($locator, $path);
  }

  /**
   * Clicks "Save and select||insert" button and waits for AJAX completion.
   *
   * @param string $operation
   *   The final word of the button to be clicked.
   */
  protected function saveAnd($operation) {
    $this->assertElementExistsAfterWait('css', '.ui-dialog-buttonpane')->pressButton("Save and $operation");

    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Clicks "Save" button and waits for AJAX completion.
   *
   * @param bool $expect_errors
   *   Whether validation errors are expected after the "Save" button is
   *   pressed. Defaults to FALSE.
   */
  protected function pressSaveButton($expect_errors = FALSE) {
    $buttons = $this->assertElementExistsAfterWait('css', '.ui-dialog-buttonpane');
    $buttons->pressButton('Save');

    // If no validation errors are expected, wait for the "Insert selected"
    // button to return.
    if (!$expect_errors) {
      $result = $buttons->waitFor(10, function ($buttons) {
        /** @var \Behat\Mink\Element\NodeElement $buttons */
        return $buttons->findButton('Insert selected');
      });
      $this->assertNotEmpty($result);
    }

    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Clicks a button that opens a media widget and confirms it is open.
   *
   * @param string $field_name
   *   The machine name of the field for which to open the media library.
   * @param string $after_open_selector
   *   The selector to look for after the button is clicked.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The NodeElement found via $after_open_selector.
   */
  protected function openMediaLibraryForField($field_name, $after_open_selector = '.js-media-library-menu') {
    $this->assertElementExistsAfterWait('css', "#$field_name-media-library-wrapper.js-media-library-widget")
      ->pressButton('Add media');
    $this->waitForText('Add or select media');

    // Assert that the grid display is visible and the links to toggle between
    // the grid and table displays are present.
    $this->assertMediaLibraryGrid();
    $assert_session = $this->assertSession();
    $assert_session->linkExists('Grid');
    $assert_session->linkExists('Table');

    // The "select all" checkbox should never be present in the modal.
    $assert_session->elementNotExists('css', '.media-library-select-all');

    return $this->assertElementExistsAfterWait('css', $after_open_selector);
  }

  /**
   * Gets the "Additional selected media" area after adding new media.
   *
   * @param bool $open
   *   Whether or not to open the area before returning it. Defaults to TRUE.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The "additional selected media" area.
   */
  protected function getSelectionArea($open = TRUE) {
    $summary = $this->assertElementExistsAfterWait('css', 'summary:contains("Additional selected media")');
    if ($open) {
      $summary->click();
    }
    return $summary->getParent();
  }

  /**
   * Asserts a media item was added, but not yet saved.
   *
   * @param int $index
   *   (optional) The index of the media item, if multiple items can be added at
   *   once. Defaults to 0.
   */
  protected function assertMediaAdded($index = 0) {
    $selector = '.js-media-library-add-form-added-media';

    // Assert that focus is shifted to the new media items.
    $this->assertJsCondition('jQuery("' . $selector . '").is(":focus")');

    $assert_session = $this->assertSession();
    $assert_session->pageTextMatches('/The media items? ha(s|ve) been created but ha(s|ve) not yet been saved. Fill in any required fields and save to add (it|them) to the media library./');
    $assert_session->elementAttributeContains('css', $selector, 'aria-label', 'Added media items');

    $fields = $this->assertElementExistsAfterWait('css', '[data-drupal-selector="edit-media-' . $index . '-fields"]');
    $assert_session->elementNotExists('css', '.js-media-library-menu');

    // Assert extraneous components were removed in
    // FileUploadForm::hideExtraSourceFieldComponents().
    $assert_session->elementNotExists('css', '[data-drupal-selector$="preview"]', $fields);
    $assert_session->buttonNotExists('Remove', $fields);
    $assert_session->elementNotExists('css', '[data-drupal-selector$="filename"]', $fields);
    $assert_session->elementNotExists('css', '.file-size', $fields);
  }

  /**
   * Asserts that media was not added, i.e. due to a validation error.
   */
  protected function assertNoMediaAdded() {
    // Assert the focus is shifted to the first tabbable element of the add
    // form, which should be the source field.
    $this->assertJsCondition('jQuery("#media-library-add-form-wrapper :tabbable").is(":focus")');

    $this->assertSession()
      ->elementNotExists('css', '[data-drupal-selector="edit-media-0-fields"]');
    $this->getTypesMenu();
  }

  /**
   * Presses the modal's "Insert selected" button.
   *
   * @param string $expected_announcement
   *   (optional) The expected screen reader announcement once the modal is
   *   closed.
   *
   * @todo Consider requiring screen reader assertion every time "Insert
   *   selected" is pressed in
   *   https://www.drupal.org/project/drupal/issues/3087227.
   */
  protected function pressInsertSelected($expected_announcement = NULL) {
    $this->assertSession()
      ->elementExists('css', '.ui-dialog-buttonpane')
      ->pressButton('Insert selected');
    $this->waitForNoText('Add or select media');

    if ($expected_announcement) {
      $this->waitForText($expected_announcement);
    }
  }

  /**
   * Gets all available media item checkboxes.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The available checkboxes.
   */
  protected function getCheckboxes() {
    return $this->getSession()
      ->getPage()
      ->findAll('css', '.js-media-library-view .js-click-to-select-checkbox input');
  }

  /**
   * Selects an item in the media library modal.
   *
   * @param int $index
   *   The zero-based index of the item to select.
   * @param string $expected_selected_count
   *   (optional) The expected text of the selection counter.
   */
  protected function selectMediaItem($index, $expected_selected_count = NULL) {
    $checkboxes = $this->getCheckboxes();
    $this->assertGreaterThan($index, count($checkboxes));
    $checkboxes[$index]->check();

    if ($expected_selected_count) {
      $this->assertSelectedMediaCount($expected_selected_count);
    }
  }

  /**
   * Switches to the grid display of the widget view.
   */
  protected function switchToMediaLibraryGrid() {
    $this->getSession()->getPage()->clickLink('Grid');
    // Assert the display change is correctly announced for screen readers.
    $this->waitForText('Loading grid view.');
    $this->waitForText('Changed to grid view.');
    $this->assertMediaLibraryGrid();
  }

  /**
   * Switches to the table display of the widget view.
   */
  protected function switchToMediaLibraryTable() {
    hold_test_response(TRUE);
    $this->getSession()->getPage()->clickLink('Table');
    // Assert the display change is correctly announced for screen readers.
    $this->waitForText('Loading table view.');
    hold_test_response(FALSE);
    $this->waitForText('Changed to table view.');
    $this->assertMediaLibraryTable();
  }

  /**
   * Asserts that the grid display of the widget view is visible.
   */
  protected function assertMediaLibraryGrid() {
    $this->assertSession()
      ->elementExists('css', '.js-media-library-view[data-view-display-id="widget"]');
  }

  /**
   * Asserts that the table display of the widget view is visible.
   */
  protected function assertMediaLibraryTable() {
    $this->assertSession()
      ->elementExists('css', '.js-media-library-view[data-view-display-id="widget_table"]');
  }

  /**
   * Asserts the current text of the selected item counter.
   *
   * @param string $text
   *   The expected text of the counter.
   */
  protected function assertSelectedMediaCount($text) {
    $selected_count = $this->assertSession()
      ->elementExists('css', '.js-media-library-selected-count');

    $this->assertSame('status', $selected_count->getAttribute('role'));
    $this->assertSame('polite', $selected_count->getAttribute('aria-live'));
    $this->assertSame('true', $selected_count->getAttribute('aria-atomic'));
    $this->assertSame($text, $selected_count->getText());
  }

}
