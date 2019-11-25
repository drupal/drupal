<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\media\Entity\Media;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that uploads in the Media library's widget works as expected.
 *
 * @group media_library
 *
 * @todo This test will occasionally fail with SQLite until
 *   https://www.drupal.org/node/3066447 is addressed.
 */
class WidgetUploadTest extends MediaLibraryTestBase {

  use TestFileCreationTrait;

  /**
   * Tests that uploads in the Media library's widget works as expected.
   */
  public function testWidgetUpload() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $driver = $this->getSession()->getDriver();

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
      'create type_one media',
      'create type_four media',
      'view media',
    ]);
    $this->drupalLogin($user);

    // Visit a node create page and open the media library.
    $this->drupalGet('node/add/basic_page');
    $this->openMediaLibraryForField('field_twin_media');

    // Assert the upload form is not visible for default tab type_three without
    // the proper permissions.
    $assert_session->elementNotExists('css', '.js-media-library-add-form');

    // Assert the upload form is not visible for the non-file based media type
    // type_one.
    $this->switchToMediaType('One');
    $assert_session->elementNotExists('css', '.js-media-library-add-form');

    // Assert the upload form is visible for type_four.
    $this->switchToMediaType('Four');
    $assert_session->fieldExists('Add files');
    $assert_session->pageTextContains('Maximum 2 files.');

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
    $this->openMediaLibraryForField('field_twin_media');

    // Assert the upload form is now visible for default tab type_three.
    $assert_session->elementExists('css', '.js-media-library-add-form');
    $assert_session->fieldExists('Add files');

    // Assert we can upload a file to the default tab type_three.
    $assert_session->elementNotExists('css', '.js-media-library-add-form[data-input]');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_image->uri));
    $this->assertMediaAdded();
    $assert_session->elementExists('css', '.js-media-library-add-form[data-input]');
    // We do not have pre-selected items, so the container should not be added
    // to the form.
    $assert_session->pageTextNotContains('Additional selected media');
    // Files are temporary until the form is saved.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertSame('public://type-three-dir', $file_system->dirname($file->getFileUri()));
    $this->assertTrue($file->isTemporary());
    // Assert the revision_log_message field is not shown.
    $upload_form = $assert_session->elementExists('css', '.js-media-library-add-form');
    $assert_session->fieldNotExists('Revision log message', $upload_form);
    // Assert the name field contains the filename and the alt text is required.
    $assert_session->fieldValueEquals('Name', $png_image->filename);
    $this->pressSaveButton(TRUE);
    $this->waitForText('Alternative text field is required');
    $page->fillField('Alternative text', $this->randomString());
    $this->pressSaveButton();
    $this->assertJsCondition('jQuery("input[name=\'media_library_select_form[0]\']").is(":focus")');
    // The file should be permanent now.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertFalse($file->isTemporary());
    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains($png_image->filename);
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');
    $assert_session->pageTextContains('1 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('current_selection', $added_media->id());
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($png_image->filename);

    // Remove the item.
    $assert_session->elementExists('css', '.field--name-field-twin-media')->pressButton('Remove');
    $this->waitForNoText($png_image->filename);

    $this->openMediaLibraryForField('field_twin_media');
    $this->switchToMediaType('Three');
    $png_uri_2 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_2));
    $this->waitForFieldExists('Alternative text')->setValue($this->randomString());
    $this->pressSaveButton();
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($file_system->basename($png_uri_2));

    // Also make sure that we can upload to the unlimited cardinality field.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');

    // Select a media item to check if the selection is persisted when adding
    // new items.
    $existing_media_name = $file_system->basename($png_uri_2);
    $checkbox = $page->findField("Select $existing_media_name");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $png_uri_3 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_3));
    $this->waitForText('The media item has been created but has not yet been saved.');
    $page->fillField('Name', 'Unlimited Cardinality Image');
    $page->fillField('Alternative text', $this->randomString());
    $this->pressSaveButton();
    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    $added_media_name = $added_media->label();
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
    $assert_session->checkboxChecked("Select $added_media_name");
    $assert_session->checkboxChecked("Select $existing_media_name");
    $assert_session->hiddenFieldValueEquals('current_selection', implode(',', [$selected_item_id, $added_media->id()]));
    $selected_checkboxes = [];
    foreach ($this->getCheckboxes() as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getAttribute('value');
      }
    }
    $this->assertCount(2, $selected_checkboxes);
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added 2 media items.');
    $this->waitForText('Unlimited Cardinality Image');

    // Assert we can now only upload one more media item.
    $this->openMediaLibraryForField('field_twin_media');
    $this->switchToMediaType('Four');
    $this->assertFalse($assert_session->fieldExists('Add file')->hasAttribute('multiple'));
    $assert_session->pageTextContains('One file only.');

    // Assert media type four should only allow jpg files by trying a png file
    // first.
    $png_uri_4 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add file', $file_system->realpath($png_uri_4), FALSE);
    $this->waitForText('Only files with the following extensions are allowed');
    // Assert that jpg files are accepted by type four.
    $jpg_uri_2 = $file_system->copy($jpg_image->uri, 'public://');
    $this->addMediaFileToField('Add file', $file_system->realpath($jpg_uri_2));
    $this->waitForFieldExists('Alternative text')->setValue($this->randomString());
    // The type_four media type has another optional image field.
    $assert_session->pageTextContains('Extra Image');
    $jpg_uri_3 = $file_system->copy($jpg_image->uri, 'public://');
    $this->addMediaFileToField('Extra Image', $this->container->get('file_system')->realpath($jpg_uri_3));
    $this->waitForText($file_system->basename($jpg_uri_3));
    // Ensure that the extra image was uploaded to the correct directory.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertSame('public://type-four-extra-dir', $file_system->dirname($file->getFileUri()));
    $this->pressSaveButton();
    // Ensure the media item was saved to the library and automatically
    // selected.
    $this->waitForText('Add or select media');
    $this->waitForText($file_system->basename($jpg_uri_2));
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added one media item.');
    $assert_session->pageTextContains($file_system->basename($jpg_uri_2));

    // Assert we can also remove selected items from the selection area in the
    // upload form.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    $checkbox = $page->findField("Select $existing_media_name");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $this->assertTrue($assert_session->fieldExists('Add files')->hasAttribute('multiple'));
    $png_uri_5 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_5));
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Alternative text', $this->randomString());
    $this->pressSaveButton();
    $page->uncheckField('media_library_select_form[2]');
    $this->waitForText('1 item selected');
    $this->waitForText("Select $existing_media_name");
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    $added_media_name = $added_media->label();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->checkboxChecked("Select $added_media_name");
    $assert_session->checkboxNotChecked("Select $existing_media_name");
    $assert_session->hiddenFieldValueEquals('current_selection', $added_media->id());
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($file_system->basename($png_uri_5));

    // Assert removing an uploaded media item before save works as expected.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_image->uri));
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->waitForFieldExists('Alternative text');
    $this->assertMediaAdded();
    // Press the 'Remove button' and assert the user is sent back to the media
    // library.
    $page->pressButton('media-0-remove-button');
    // Assert the remove message is shown.
    $this->waitForText("The media item $png_image->filename has been removed.");
    // Assert the focus is shifted to the first tabbable element of the add
    // form, which should be the source field.
    $this->assertNoMediaAdded();
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert uploading multiple files.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    // Assert the existing items are remembered when adding and removing media.
    $checkbox = $page->findField("Select $existing_media_name");
    $checkbox->click();
    // Assert we can add multiple files.
    $this->assertTrue($assert_session->fieldExists('Add files')->hasAttribute('multiple'));
    // Create a list of new files to upload.
    $filenames = [];
    $remote_paths = [];
    foreach (range(1, 4) as $i) {
      $path = $file_system->copy($png_image->uri, 'public://');
      $filenames[] = $file_system->basename($path);
      $remote_paths[] = $driver->uploadFileAndGetRemoteFilePath($file_system->realpath($path));
    }
    $page->findField('Add files')->setValue(implode("\n", $remote_paths));
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->assertMediaAdded();
    // Assert all files have been added.
    $assert_session->fieldValueEquals('media[0][fields][name][0][value]', $filenames[0]);
    $assert_session->fieldValueEquals('media[1][fields][name][0][value]', $filenames[1]);
    $assert_session->fieldValueEquals('media[2][fields][name][0][value]', $filenames[2]);
    $assert_session->fieldValueEquals('media[3][fields][name][0][value]', $filenames[3]);
    // Set alt texts for items 1 and 2, leave the alt text empty for items 3
    // and 4 to assert the field validation does not stop users from removing
    // items.
    $page->fillField('media[0][fields][field_media_test_image][0][alt]', $filenames[0]);
    $page->fillField('media[1][fields][field_media_test_image][0][alt]', $filenames[1]);
    // Assert the file is available in the file storage.
    $files = $file_storage->loadByProperties(['filename' => $filenames[1]]);
    $this->assertCount(1, $files);
    $file_1_uri = reset($files)->getFileUri();
    // Remove the second file and assert the focus is shifted to the container
    // of the next media item and field values are still correct.
    $page->pressButton('media-1-remove-button');
    $this->assertJsCondition('jQuery("[data-media-library-added-delta=2]").is(":focus")');
    $assert_session->pageTextContains('The media item ' . $filenames[1] . ' has been removed.');
    // Assert the file was deleted.
    $this->assertEmpty($file_storage->loadByProperties(['filename' => $filenames[1]]));
    $this->assertFileNotExists($file_1_uri);

    // When a file is already in usage, it should not be deleted. To test,
    // let's add a usage for $filenames[3] (now in the third position).
    $files = $file_storage->loadByProperties(['filename' => $filenames[3]]);
    $this->assertCount(1, $files);
    $target_file = reset($files);
    Media::create([
      'bundle' => 'type_three',
      'name' => 'Disturbing',
      'field_media_test_image' => [
        ['target_id' => $target_file->id()],
      ],
    ])->save();
    // Remove $filenames[3] (now in the third position) and assert the focus is
    // shifted to the container of the previous media item and field values are
    // still correct.
    $page->pressButton('media-3-remove-button');
    $this->assertTrue($assert_session->waitForText('The media item ' . $filenames[3] . ' has been removed.'));
    // Assert the file was not deleted, due to being in use elsewhere.
    $this->assertNotEmpty($file_storage->loadByProperties(['filename' => $filenames[3]]));
    $this->assertFileExists($target_file->getFileUri());

    // The second media item should be removed (this has the delta 1 since we
    // start counting from 0).
    $assert_session->elementNotExists('css', '[data-media-library-added-delta=1]');
    $media_item_one = $assert_session->elementExists('css', '[data-media-library-added-delta=0]');
    $assert_session->fieldValueEquals('Name', $filenames[0], $media_item_one);
    $assert_session->fieldValueEquals('Alternative text', $filenames[0], $media_item_one);
    $media_item_three = $assert_session->elementExists('css', '[data-media-library-added-delta=2]');
    $assert_session->fieldValueEquals('Name', $filenames[2], $media_item_three);
    $assert_session->fieldValueEquals('Alternative text', '', $media_item_three);
  }

  /**
   * Tests that uploads in the widget's advanced UI works as expected.
   *
   * @todo Merge this with testWidgetUpload() in
   *   https://www.drupal.org/project/drupal/issues/3087227
   */
  public function testWidgetUploadAdvancedUi() {
    $this->config('media_library.settings')->set('advanced_ui', TRUE)->save();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $driver = $this->getSession()->getDriver();

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
      'create type_one media',
      'create type_four media',
      'view media',
    ]);
    $this->drupalLogin($user);

    // Visit a node create page and open the media library.
    $this->drupalGet('node/add/basic_page');
    $this->openMediaLibraryForField('field_twin_media');

    // Assert the upload form is not visible for default tab type_three without
    // the proper permissions.
    $assert_session->elementNotExists('css', '.js-media-library-add-form');

    // Assert the upload form is not visible for the non-file based media type
    // type_one.
    $this->switchToMediaType('One');
    $assert_session->elementNotExists('css', '.js-media-library-add-form');

    // Assert the upload form is visible for type_four.
    $this->switchToMediaType('Four');
    $assert_session->fieldExists('Add files');
    $assert_session->pageTextContains('Maximum 2 files.');

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
    $this->openMediaLibraryForField('field_twin_media');

    // Assert the upload form is now visible for default tab type_three.
    $assert_session->elementExists('css', '.js-media-library-add-form');
    $assert_session->fieldExists('Add files');

    // Assert we can upload a file to the default tab type_three.
    $assert_session->elementNotExists('css', '.js-media-library-add-form[data-input]');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_image->uri));
    $this->assertMediaAdded();
    $assert_session->elementExists('css', '.js-media-library-add-form[data-input]');
    // We do not have a pre-selected items, so the container should not be added
    // to the form.
    $assert_session->elementNotExists('css', 'details summary:contains(Additional selected media)');
    // Files are temporary until the form is saved.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertSame('public://type-three-dir', $file_system->dirname($file->getFileUri()));
    $this->assertTrue($file->isTemporary());
    // Assert the revision_log_message field is not shown.
    $upload_form = $assert_session->elementExists('css', '.js-media-library-add-form');
    $assert_session->fieldNotExists('Revision log message', $upload_form);
    // Assert the name field contains the filename and the alt text is required.
    $assert_session->fieldValueEquals('Name', $png_image->filename);
    $this->saveAnd('select');
    $this->waitForText('Alternative text field is required');
    $page->fillField('Alternative text', $this->randomString());
    $this->saveAnd('select');
    $this->assertJsCondition('jQuery("input[name=\'media_library_select_form[0]\']").is(":focus")');
    // The file should be permanent now.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertFalse($file->isTemporary());
    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains($png_image->filename);
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');
    $assert_session->pageTextContains('1 of 2 items selected');
    $assert_session->hiddenFieldValueEquals('current_selection', $added_media->id());
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($png_image->filename);

    // Remove the item.
    $assert_session->elementExists('css', '.field--name-field-twin-media')->pressButton('Remove');
    $this->waitForNoText($png_image->filename);

    $this->openMediaLibraryForField('field_twin_media');
    $this->switchToMediaType('Three');
    $png_uri_2 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_2));
    $this->waitForFieldExists('Alternative text')->setValue($this->randomString());
    // Assert we can also directly insert uploaded files in the widget.
    $this->saveAnd('insert');
    $this->waitForText('Added one media item.');
    $this->waitForNoText('Add or select media');
    $this->waitForText($file_system->basename($png_uri_2));

    // Also make sure that we can upload to the unlimited cardinality field.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');

    // Select a media item to check if the selection is persisted when adding
    // new items.
    $existing_media_name = $file_system->basename($png_uri_2);
    $checkbox = $page->findField("Select $existing_media_name");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $png_uri_3 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_3));
    $this->waitForText('The media item has been created but has not yet been saved.');
    $assert_session->checkboxChecked("Select $existing_media_name");
    $page->fillField('Name', 'Unlimited Cardinality Image');
    $page->fillField('Alternative text', $this->randomString());
    $this->saveAnd('select');
    $this->waitForNoText('Save and select');
    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    $added_media_name = $added_media->label();
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
    $assert_session->checkboxChecked("Select $added_media_name");
    $assert_session->checkboxChecked("Select $existing_media_name");
    $assert_session->hiddenFieldValueEquals('current_selection', implode(',', [$selected_item_id, $added_media->id()]));
    $selected_checkboxes = [];
    foreach ($this->getCheckboxes() as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getAttribute('value');
      }
    }
    $this->assertCount(2, $selected_checkboxes);
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added 2 media items.');
    $this->waitForText('Unlimited Cardinality Image');

    // Assert we can now only upload one more media item.
    $this->openMediaLibraryForField('field_twin_media');
    $this->switchToMediaType('Four');
    $this->assertFalse($assert_session->fieldExists('Add file')->hasAttribute('multiple'));
    $assert_session->pageTextContains('One file only.');

    // Assert media type four should only allow jpg files by trying a png file
    // first.
    $png_uri_4 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add file', $file_system->realpath($png_uri_4), FALSE);
    $this->waitForText('Only files with the following extensions are allowed');
    // Assert that jpg files are accepted by type four.
    $jpg_uri_2 = $file_system->copy($jpg_image->uri, 'public://');
    $this->addMediaFileToField('Add file', $file_system->realpath($jpg_uri_2));
    $this->waitForFieldExists('Alternative text')->setValue($this->randomString());
    // The type_four media type has another optional image field.
    $assert_session->pageTextContains('Extra Image');
    $jpg_uri_3 = $file_system->copy($jpg_image->uri, 'public://');
    $this->addMediaFileToField('Extra Image', $this->container->get('file_system')->realpath($jpg_uri_3));
    $this->waitForText($file_system->basename($jpg_uri_3));
    // Ensure that the extra image was uploaded to the correct directory.
    $files = $file_storage->loadMultiple();
    $file = array_pop($files);
    $this->assertSame('public://type-four-extra-dir', $file_system->dirname($file->getFileUri()));
    $this->saveAnd('select');
    // Ensure the media item was saved to the library and automatically
    // selected.
    $this->waitForText('Add or select media');
    $this->waitForText($file_system->basename($jpg_uri_2));
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added one media item.');
    $assert_session->pageTextContains($file_system->basename($jpg_uri_2));

    // Assert we can also remove selected items from the selection area in the
    // upload form.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    $checkbox = $page->findField("Select $existing_media_name");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $this->assertTrue($assert_session->fieldExists('Add files')->hasAttribute('multiple'));
    $png_uri_5 = $file_system->copy($png_image->uri, 'public://');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_uri_5));
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('Alternative text', $this->randomString());
    // Assert the pre-selected items are shown.
    $selection_area = $this->getSelectionArea();
    $assert_session->checkboxChecked("Select $existing_media_name", $selection_area);
    $selection_area->uncheckField("Select $existing_media_name");
    $assert_session->hiddenFieldValueEquals('current_selection', '');
    // Close the details element so that clicking the Save and select works.
    // @todo Fix dialog or test so this is not necessary to prevent random
    //   fails. https://www.drupal.org/project/drupal/issues/3055648
    $selection_area->find('css', 'summary')->click();
    $this->saveAnd('select');
    $this->waitForText("Select $existing_media_name");
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    $added_media_name = $added_media->label();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->checkboxChecked("Select $added_media_name");
    $assert_session->checkboxNotChecked("Select $existing_media_name");
    $assert_session->hiddenFieldValueEquals('current_selection', $added_media->id());
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($file_system->basename($png_uri_5));

    // Assert removing an uploaded media item before save works as expected.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($png_image->uri));
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->assertMediaAdded();
    // Press the 'Remove button' and assert the user is sent back to the media
    // library.
    $page->pressButton('media-0-remove-button');
    // Assert the remove message is shown.
    $this->waitForText("The media item $png_image->filename has been removed.");
    $this->assertNoMediaAdded();
    $assert_session->elementExists('css', '.ui-dialog-titlebar-close')->click();

    // Assert uploading multiple files.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Three');
    // Assert the existing items are remembered when adding and removing media.
    $checkbox = $page->findField("Select $existing_media_name");
    $checkbox->click();
    // Assert we can add multiple files.
    $this->assertTrue($assert_session->fieldExists('Add files')->hasAttribute('multiple'));
    // Create a list of new files to upload.
    $filenames = [];
    $remote_paths = [];
    foreach (range(1, 4) as $i) {
      $path = $file_system->copy($png_image->uri, 'public://');
      $filenames[] = $file_system->basename($path);
      $remote_paths[] = $driver->uploadFileAndGetRemoteFilePath($file_system->realpath($path));
    }
    $page->findField('Add files')->setValue(implode("\n", $remote_paths));
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->assertMediaAdded();
    // Assert all files have been added.
    $assert_session->fieldValueEquals('media[0][fields][name][0][value]', $filenames[0]);
    $assert_session->fieldValueEquals('media[1][fields][name][0][value]', $filenames[1]);
    $assert_session->fieldValueEquals('media[2][fields][name][0][value]', $filenames[2]);
    $assert_session->fieldValueEquals('media[3][fields][name][0][value]', $filenames[3]);
    // Assert the pre-selected items are shown.
    $assert_session->checkboxChecked("Select $existing_media_name", $this->getSelectionArea());
    // Set alt texts for items 1 and 2, leave the alt text empty for items 3
    // and 4 to assert the field validation does not stop users from removing
    // items.
    $page->fillField('media[0][fields][field_media_test_image][0][alt]', $filenames[0]);
    $page->fillField('media[1][fields][field_media_test_image][0][alt]', $filenames[1]);
    // Assert the file is available in the file storage.
    $files = $file_storage->loadByProperties(['filename' => $filenames[1]]);
    $this->assertCount(1, $files);
    $file_1_uri = reset($files)->getFileUri();
    // Remove the second file and assert the focus is shifted to the container
    // of the next media item and field values are still correct.
    $page->pressButton('media-1-remove-button');
    $this->assertJsCondition('jQuery("[data-media-library-added-delta=2]").is(":focus")');
    $assert_session->pageTextContains('The media item ' . $filenames[1] . ' has been removed.');
    // Assert the file was deleted.
    $this->assertEmpty($file_storage->loadByProperties(['filename' => $filenames[1]]));
    $this->assertFileNotExists($file_1_uri);

    // When a file is already in usage, it should not be deleted. To test,
    // let's add a usage for $filenames[3] (now in the third position).
    $files = $file_storage->loadByProperties(['filename' => $filenames[3]]);
    $this->assertCount(1, $files);
    $target_file = reset($files);
    Media::create([
      'bundle' => 'type_three',
      'name' => 'Disturbing',
      'field_media_test_image' => [
        ['target_id' => $target_file->id()],
      ],
    ])->save();
    // Remove $filenames[3] (now in the third position) and assert the focus is
    // shifted to the container of the previous media item and field values are
    // still correct.
    $page->pressButton('media-3-remove-button');
    $this->assertTrue($assert_session->waitForText('The media item ' . $filenames[3] . ' has been removed.'));
    // Assert the file was not deleted, due to being in use elsewhere.
    $this->assertNotEmpty($file_storage->loadByProperties(['filename' => $filenames[3]]));
    $this->assertFileExists($target_file->getFileUri());

    // The second media item should be removed (this has the delta 1 since we
    // start counting from 0).
    $assert_session->elementNotExists('css', '[data-media-library-added-delta=1]');
    $media_item_one = $assert_session->elementExists('css', '[data-media-library-added-delta=0]');
    $assert_session->fieldValueEquals('Name', $filenames[0], $media_item_one);
    $assert_session->fieldValueEquals('Alternative text', $filenames[0], $media_item_one);
    $media_item_three = $assert_session->elementExists('css', '[data-media-library-added-delta=2]');
    $assert_session->fieldValueEquals('Name', $filenames[2], $media_item_three);
    $assert_session->fieldValueEquals('Alternative text', '', $media_item_three);
    // Assert the pre-selected items are still shown.
    $assert_session->checkboxChecked("Select $existing_media_name", $this->getSelectionArea());

    // Remove the last file and assert the focus is shifted to the container
    // of the first media item and field values are still correct.
    $page->pressButton('media-2-remove-button');
    $this->assertJsCondition('jQuery("[data-media-library-added-delta=0]").is(":focus")');
    $assert_session->pageTextContains('The media item ' . $filenames[2] . ' has been removed.');
    $assert_session->elementNotExists('css', '[data-media-library-added-delta=1]');
    $assert_session->elementNotExists('css', '[data-media-library-added-delta=2]');
    $media_item_one = $assert_session->elementExists('css', '[data-media-library-added-delta=0]');
    $assert_session->fieldValueEquals('Name', $filenames[0], $media_item_one);
    $assert_session->fieldValueEquals('Alternative text', $filenames[0], $media_item_one);
  }

}
