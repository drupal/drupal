<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that uploads in the 'media_library_widget' works as expected.
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
   * Uploads multiple test images into the media library.
   *
   * @param int $number
   *   The number of images to upload.
   */
  private function uploadFiles(int $number): void {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = $this->container->get('file_system');

    // Create a list of new files to upload.
    $filenames = $remote_paths = [];
    for ($i = 0; $i < $number; $i++) {
      $path = $file_system->copy($this->image->uri, 'public://');
      $path = $file_system->realpath($path);
      $this->assertNotEmpty($path);
      $this->assertFileExists($path);

      $filenames[] = $file_system->basename($path);
      $remote_paths[] = $this->getSession()
        ->getDriver()
        ->uploadFileAndGetRemoteFilePath($path);
    }
    $page = $this->getSession()->getPage();
    $page->fillField('Add files', implode("\n", $remote_paths));
    $this->assertMediaAdded();
    $assert_session = $this->assertSession();
    foreach ($filenames as $i => $filename) {
      $assert_session->fieldValueEquals("media[$i][fields][name][0][value]", $filename);
      $page->fillField("media[$i][fields][field_media_test_image][0][alt]", $filename);
    }
  }

  /**
   * Tests that the Media Library constrains the number of selected items.
   *
   * @param string|null $selected_operation
   *   The operation of the button to click. For example, if this is "insert",
   *   the "Save and insert" button will be pressed. If NULL, the "Save" button
   *   will be pressed.
   *
   * @dataProvider providerWidgetOverflow
   */
  public function testWidgetOverflow(?string $selected_operation): void {
    // If we want to press the "Save and insert" or "Save and select" buttons,
    // we need to enable the advanced UI.
    if ($selected_operation) {
      $this->config('media_library.settings')->set('advanced_ui', TRUE)->save();
    }

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('node/add/basic_page');
    // Upload 5 files into a media field that only allows 2.
    $this->openMediaLibraryForField('field_twin_media');
    $this->uploadFiles(5);
    // Save the media items and ensure that the user is warned that they have
    // selected too many items.
    if ($selected_operation) {
      $this->saveAnd($selected_operation);
    }
    else {
      $this->pressSaveButton();
    }
    $this->waitForElementTextContains('.messages--warning', 'There are currently 5 items selected. The maximum number of items for the field is 2. Remove 3 items from the selection.');
    // If the user tries to insert the selected items anyway, they should get
    // an error.
    $this->pressInsertSelected(NULL, FALSE);
    $this->waitForElementTextContains('.messages--error', 'There are currently 5 items selected. The maximum number of items for the field is 2. Remove 3 items from the selection.');
    $assert_session->elementNotExists('css', '.messages--warning');
    // Once the extra items are deselected, all should be well.
    $this->deselectMediaItem(2);
    $this->deselectMediaItem(3);
    $this->deselectMediaItem(4);
    $this->pressInsertSelected('Added 2 media items.');
  }

  /**
   * Tests that unlimited fields' selection count is not constrained.
   *
   * @param string|null $selected_operation
   *   The operation of the button to click. For example, if this is "insert",
   *   the "Save and insert" button will be pressed. If NULL, the "Save" button
   *   will be pressed.
   *
   * @dataProvider providerWidgetOverflow
   */
  public function testUnlimitedCardinality(?string $selected_operation): void {
    if ($selected_operation) {
      $this->config('media_library.settings')->set('advanced_ui', TRUE)->save();
    }

    $assert_session = $this->assertSession();
    // Visit a node create page and open the media library.
    $this->drupalGet('node/add/basic_page');
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    $this->uploadFiles(5);
    if ($selected_operation) {
      $this->saveAnd($selected_operation);
    }
    else {
      $this->pressSaveButton();
    }

    if ($selected_operation !== 'insert') {
      $this->pressInsertSelected();
    }
    // There should not be any warnings or errors.
    $assert_session->elementNotExists('css', '.messages--error');
    $assert_session->elementNotExists('css', '.messages--warning');
    $this->waitForText('Added 5 media items.');
  }

  /**
   * Data provider for ::testWidgetOverflow() and ::testUnlimitedCardinality().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public static function providerWidgetOverflow(): array {
    return [
      'Save' => [NULL],
      'Save and insert' => ['insert'],
      'Save and select' => ['select'],
    ];
  }

}
