<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that uploads in the Media library's widget works as expected.
 *
 * @group media_library
 *
 * @todo This test will occasionally fail with SQLite until
 *   https://www.drupal.org/node/3066447 is addressed.
 */
class WidgetOverflowTest extends MediaLibraryTestBase {

  use TestFileCreationTrait;

  /**
   * The test image file.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $image;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    foreach ($this->getTestFiles('image') as $image) {
      $extension = pathinfo($image->filename, PATHINFO_EXTENSION);
      if ($extension === 'png') {
        $this->image = $image;
      }
    }

    if (!isset($this->image)) {
      $this->fail('Expected test files not present.');
    }

    // Create a user that can only add media of type four.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'create basic_page content',
      'create type_one media',
      'create type_three media',
      'view media',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests overflow validation.
   *
   * @dataProvider overflowProvider
   */
  public function testWidgetOverflow($advanced_ui, $button_text) {
    $this->config('media_library.settings')->set('advanced_ui', $advanced_ui)->save();
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $driver = $this->getSession()->getDriver();
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = $this->container->get('file_system');
    // Visit a node create page and open the media library.
    $this->drupalGet('node/add/basic_page');
    $this->openMediaLibraryForField('field_twin_media');
    $assert_session->pageTextContains('Add or select media');
    $assert_session->fieldExists('Add files');
    // Create a list of new files to upload.
    $filenames = [];
    $remote_paths = [];
    foreach (range(1, 5) as $i) {
      $path = $file_system->copy($this->image->uri, 'public://');
      $filenames[] = $file_system->basename($path);
      $remote_paths[] = $driver->uploadFileAndGetRemoteFilePath($file_system->realpath($path));
    }
    $page->findField('Add files')->setValue(implode("\n", $remote_paths));
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->assertMediaAdded();
    // Assert all files have been added.
    foreach (range(0, 4) as $i) {
      $assert_session->fieldValueEquals("media[$i][fields][name][0][value]", $filenames[$i]);
      $page->fillField("media[$i][fields][field_media_test_image][0][alt]", $filenames[$i]);
    }
    // When the user uploads more items than allowed, the media items are
    // saved but when the user is returned to the media library there is a
    // warning message.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')
      ->pressButton($button_text);
    $this->waitForText('Please remove 3 items from the selection.');
    $assert_session->elementTextContains('css', '.messages--warning', 'There are currently 5 items selected. The maximum number of items for the field is 2. Please remove 3 items from the selection.');
    // When the user tries insert more items than allowed, the user is returned
    // to the media library with an error message.
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $assert_session->waitForElement('css', '.messages--error');
    $assert_session->elementNotExists('css', '.messages--warning');
    $assert_session->elementTextContains('css', '.messages--error', 'There are currently 5 items selected. The maximum number of items for the field is 2. Please remove 3 items from the selection.');
    // Uncheck the extra items.
    $page->uncheckField('media_library_select_form[2]');
    $page->uncheckField('media_library_select_form[3]');
    $page->uncheckField('media_library_select_form[4]');
    $this->pressInsertSelected('Added 2 media items.');
  }

  /**
   * Tests overflow validation skips fields with unlimited cardinality.
   *
   * @dataProvider overflowProvider
   */
  public function testUnlimitedCardinality($advanced_ui, $button_text) {
    $this->config('media_library.settings')->set('advanced_ui', $advanced_ui)->save();
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $driver = $this->getSession()->getDriver();
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = $this->container->get('file_system');
    // Visit a node create page and open the media library.
    $this->drupalGet('node/add/basic_page');
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    $assert_session->pageTextContains('Add or select media');
    $assert_session->fieldExists('Add files');
    // Create a list of new files to upload.
    $filenames = [];
    $remote_paths = [];
    foreach (range(1, 5) as $i) {
      $path = $file_system->copy($this->image->uri, 'public://');
      $filenames[] = $file_system->basename($path);
      $remote_paths[] = $driver->uploadFileAndGetRemoteFilePath($file_system->realpath($path));
    }
    $page->findField('Add files')->setValue(implode("\n", $remote_paths));
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->assertMediaAdded();
    // Assert all files have been added.
    foreach (range(0, 4) as $i) {
      $assert_session->fieldValueEquals("media[$i][fields][name][0][value]", $filenames[$i]);
      $page->fillField("media[$i][fields][field_media_test_image][0][alt]", $filenames[$i]);
    }
    // When the user is returned to the media library there should not
    // be a warning message.
    $buttons = $assert_session->elementExists('css', '.ui-dialog-buttonpane');
    $buttons->pressButton($button_text);

    if ($button_text === 'Save and insert') {
      $this->waitForText('Added 5 media items.');
    }
    else {
      $result = $buttons->waitFor(10, function ($buttons) {
        /** @var \Behat\Mink\Element\NodeElement $buttons */
        return $buttons->findButton('Insert selected');
      });
      $this->assertNotEmpty($result);
      $assert_session->elementNotExists('css', '.messages--warning');
      // When the user tries insert more items than allowed,
      // the user is returned
      // to the media library with an error message.
      $this->pressInsertSelected('Added 5 media items.');
    }
  }

  /**
   * Data provider for ::testWidgetOverflow().
   *
   * @return array
   *   Test data.
   */
  public function overflowProvider() {
    return [
      'Save button' => [
        'advancedUi' => FALSE,
        'buttonText' => 'Save',
      ],
      'Save and insert button' => [
        'advancedUi' => TRUE,
        'buttonText' => 'Save and insert',
      ],
      'Save and select button' => [
        'advancedUi' => TRUE,
        'buttonText' => 'Save and select',
      ],
    ];
  }

}
