<?php

namespace Drupal\file\Tests;

/**
 * Tests the 'managed_file' element type.
 *
 * @group file
 * @todo Create a FileTestBase class and move FileFieldTestBase methods
 *   that aren't related to fields into it.
 */
class FileManagedFileElementTest extends FileFieldTestBase {
  /**
   * Tests the managed_file element type.
   */
  public function testManagedFile() {
    // Check that $element['#size'] is passed to the child upload element.
    $this->drupalGet('file/test');
    $this->assertFieldByXpath('//input[@name="files[nested_file]" and @size="13"]', NULL, 'The custom #size attribute is passed to the child upload element.');

    // Perform the tests with all permutations of $form['#tree'],
    // $element['#extended'], and $element['#multiple'].
    $test_file = $this->getTestFile('text');
    foreach ([0, 1] as $tree) {
      foreach ([0, 1] as $extended) {
        foreach ([0, 1] as $multiple) {
          $path = 'file/test/' . $tree . '/' . $extended . '/' . $multiple;
          $input_base_name = $tree ? 'nested_file' : 'file';
          $file_field_name = $multiple ? 'files[' . $input_base_name . '][]' : 'files[' . $input_base_name . ']';

          // Submit without a file.
          $this->drupalPostForm($path, [], t('Save'));
          $this->assertRaw(t('The file ids are %fids.', ['%fids' => implode(',', [])]), 'Submitted without a file.');

          // Submit with a file, but with an invalid form token. Ensure the file
          // was not saved.
          $last_fid_prior = $this->getLastFileId();
          $edit = [
            $file_field_name => drupal_realpath($test_file->getFileUri()),
            'form_token' => 'invalid token',
          ];
          $this->drupalPostForm($path, $edit, t('Save'));
          $this->assertText('The form has become outdated. Copy any unsaved work in the form below');
          $last_fid = $this->getLastFileId();
          $this->assertEqual($last_fid_prior, $last_fid, 'File was not saved when uploaded with an invalid form token.');

          // Submit a new file, without using the Upload button.
          $last_fid_prior = $this->getLastFileId();
          $edit = [$file_field_name => drupal_realpath($test_file->getFileUri())];
          $this->drupalPostForm($path, $edit, t('Save'));
          $last_fid = $this->getLastFileId();
          $this->assertTrue($last_fid > $last_fid_prior, 'New file got saved.');
          $this->assertRaw(t('The file ids are %fids.', ['%fids' => implode(',', [$last_fid])]), 'Submit handler has correct file info.');

          // Submit no new input, but with a default file.
          $this->drupalPostForm($path . '/' . $last_fid, [], t('Save'));
          $this->assertRaw(t('The file ids are %fids.', ['%fids' => implode(',', [$last_fid])]), 'Empty submission did not change an existing file.');

          // Now, test the Upload and Remove buttons, with and without Ajax.
          foreach ([FALSE, TRUE] as $ajax) {
            // Upload, then Submit.
            $last_fid_prior = $this->getLastFileId();
            $this->drupalGet($path);
            $edit = [$file_field_name => drupal_realpath($test_file->getFileUri())];
            if ($ajax) {
              $this->drupalPostAjaxForm(NULL, $edit, $input_base_name . '_upload_button');
            }
            else {
              $this->drupalPostForm(NULL, $edit, t('Upload'));
            }
            $last_fid = $this->getLastFileId();
            $this->assertTrue($last_fid > $last_fid_prior, 'New file got uploaded.');
            $this->drupalPostForm(NULL, [], t('Save'));
            $this->assertRaw(t('The file ids are %fids.', ['%fids' => implode(',', [$last_fid])]), 'Submit handler has correct file info.');

            // Remove, then Submit.
            $remove_button_title = $multiple ? t('Remove selected') : t('Remove');
            $remove_edit = [];
            if ($multiple) {
              $selected_checkbox = ($tree ? 'nested[file]' : 'file') . '[file_' . $last_fid . '][selected]';
              $remove_edit = [$selected_checkbox => '1'];
            }
            $this->drupalGet($path . '/' . $last_fid);
            if ($ajax) {
              $this->drupalPostAjaxForm(NULL, $remove_edit, $input_base_name . '_remove_button');
            }
            else {
              $this->drupalPostForm(NULL, $remove_edit, $remove_button_title);
            }
            $this->drupalPostForm(NULL, [], t('Save'));
            $this->assertRaw(t('The file ids are %fids.', ['%fids' => '']), 'Submission after file removal was successful.');

            // Upload, then Remove, then Submit.
            $this->drupalGet($path);
            $edit = [$file_field_name => drupal_realpath($test_file->getFileUri())];
            if ($ajax) {
              $this->drupalPostAjaxForm(NULL, $edit, $input_base_name . '_upload_button');
            }
            else {
              $this->drupalPostForm(NULL, $edit, t('Upload'));
            }
            $remove_edit = [];
            if ($multiple) {
              $selected_checkbox = ($tree ? 'nested[file]' : 'file') . '[file_' . $this->getLastFileId() . '][selected]';
              $remove_edit = [$selected_checkbox => '1'];
            }
            if ($ajax) {
              $this->drupalPostAjaxForm(NULL, $remove_edit, $input_base_name . '_remove_button');
            }
            else {
              $this->drupalPostForm(NULL, $remove_edit, $remove_button_title);
            }

            $this->drupalPostForm(NULL, [], t('Save'));
            $this->assertRaw(t('The file ids are %fids.', ['%fids' => '']), 'Submission after file upload and removal was successful.');
          }
        }
      }
    }

    // The multiple file upload has additional conditions that need checking.
    $path = 'file/test/1/1/1';
    $edit = ['files[nested_file][]' => drupal_realpath($test_file->getFileUri())];
    $fid_list = [];

    $this->drupalGet($path);

    // Add a single file to the upload field.
    $this->drupalPostForm(NULL, $edit, t('Upload'));
    $fid_list[] = $this->getLastFileId();
    $this->assertFieldByXpath('//input[@name="nested[file][file_' . $fid_list[0] . '][selected]"]', NULL, 'First file successfully uploaded to multiple file element.');

    // Add another file to the same upload field.
    $this->drupalPostForm(NULL, $edit, t('Upload'));
    $fid_list[] = $this->getLastFileId();
    $this->assertFieldByXpath('//input[@name="nested[file][file_' . $fid_list[1] . '][selected]"]', NULL, 'Second file successfully uploaded to multiple file element.');

    // Save the entire form.
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertRaw(t('The file ids are %fids.', ['%fids' => implode(',', $fid_list)]), 'Two files saved into a single multiple file element.');

    // Delete only the first file.
    $edit = [
      'nested[file][file_' . $fid_list[0] . '][selected]' => '1',
    ];
    $this->drupalPostForm($path . '/' . implode(',', $fid_list), $edit, t('Remove selected'));

    // Check that the first file has been deleted but not the second.
    $this->assertNoFieldByXpath('//input[@name="nested[file][file_' . $fid_list[0] . '][selected]"]', NULL, 'An individual file can be deleted from a multiple file element.');
    $this->assertFieldByXpath('//input[@name="nested[file][file_' . $fid_list[1] . '][selected]"]', NULL, 'Second individual file not deleted when the first file is deleted from a multiple file element.');
  }

  /**
   * Ensure that warning is shown if file on the field has been removed.
   */
  public function testManagedFileRemoved() {
    $this->drupalGet('file/test/1/0/1');
    $test_file = $this->getTestFile('text');
    $file_field_name = 'files[nested_file][]';

    $edit = [$file_field_name => drupal_realpath($test_file->getFileUri())];
    $this->drupalPostForm(NULL, $edit, t('Upload'));

    $fid = $this->getLastFileId();
    $file = \Drupal::entityManager()->getStorage('file')->load($fid);
    $file->delete();

    $this->drupalPostForm(NULL, $edit, t('Upload'));
    // We expect the title 'Managed <em>file & butter</em>' which got escaped
    // via a t() call before.
    $this->assertRaw('The file referenced by the Managed <em>file &amp; butter</em> field does not exist.');
  }

  /**
   * Ensure a file entity can be saved when the file does not exist on disk.
   */
  public function testFileRemovedFromDisk() {
    $this->drupalGet('file/test/1/0/1');
    $test_file = $this->getTestFile('text');
    $file_field_name = 'files[nested_file][]';

    $edit = [$file_field_name => drupal_realpath($test_file->getFileUri())];
    $this->drupalPostForm(NULL, $edit, t('Upload'));
    $this->drupalPostForm(NULL, [], t('Save'));

    $fid = $this->getLastFileId();
    /** @var $file \Drupal\file\FileInterface */
    $file = $this->container->get('entity_type.manager')->getStorage('file')->load($fid);
    $file->setPermanent();
    $file->save();
    $this->assertTrue(file_unmanaged_delete($file->getFileUri()));
    $file->save();
    $this->assertTrue($file->isPermanent());
    $file->delete();
  }

  /**
   * Verify that unused permanent files can be used.
   */
  public function testUnusedPermanentFileValidation() {

    // Create a permanent file without usages.
    $file = $this->getTestFile('image');
    $file->setPermanent();
    $file->save();

    // By default, unused files are no longer marked temporary, and it must be
    // allowed to reference an unused file.
    $this->drupalGet('file/test/1/0/1/' . $file->id());
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertNoText('The file used in the Managed file &amp; butter field may not be referenced.');
    $this->assertText('The file ids are ' . $file->id());

    // Enable marking unused files as tempory, unused permanent files must not
    // be referenced now.
    $this->config('file.settings')
      ->set('make_unused_managed_files_temporary', TRUE)
      ->save();
    $this->drupalGet('file/test/1/0/1/' . $file->id());
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertText('The file used in the Managed file &amp; butter field may not be referenced.');
    $this->assertNoText('The file ids are ' . $file->id());

    // Make the file temporary, now using it is allowed.
    $file->setTemporary();
    $file->save();

    $this->drupalGet('file/test/1/0/1/' . $file->id());
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertNoText('The file used in the Managed file &amp; butter field may not be referenced.');
    $this->assertText('The file ids are ' . $file->id());

    // Make the file permanent again and add a usage from itself, referencing is
    // still allowed.
    $file->setPermanent();
    $file->save();

    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = \Drupal::service('file.usage');
    $file_usage->add($file, 'file', 'file', $file->id());

    $this->drupalGet('file/test/1/0/1/' . $file->id());
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertNoText('The file used in the Managed file &amp; butter field may not be referenced.');
    $this->assertText('The file ids are ' . $file->id());
  }

}
