<?php

/**
 * @file
 * Contains \Drupal\file\Tests\FileManagedFileElementTest.
 */

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
  function testManagedFile() {
    // Check that $element['#size'] is passed to the child upload element.
    $this->drupalGet('file/test');
    $this->assertFieldByXpath('//input[@name="files[nested_file]" and @size="13"]', NULL, 'The custom #size attribute is passed to the child upload element.');

    // Perform the tests with all permutations of $form['#tree'],
    // $element['#extended'], and $element['#multiple'].
    $test_file = $this->getTestFile('text');
    foreach (array(0, 1) as $tree) {
      foreach (array(0, 1) as $extended) {
        foreach (array(0, 1) as $multiple) {
          $path = 'file/test/' . $tree . '/' . $extended . '/' . $multiple;
          $input_base_name = $tree ? 'nested_file' : 'file';
          $file_field_name = $multiple ? 'files[' . $input_base_name . '][]' : 'files[' . $input_base_name . ']';

          // Submit without a file.
          $this->drupalPostForm($path, array(), t('Save'));
          $this->assertRaw(t('The file ids are %fids.', array('%fids' => implode(',', array()))), 'Submitted without a file.');

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
          $edit = array($file_field_name => drupal_realpath($test_file->getFileUri()));
          $this->drupalPostForm($path, $edit, t('Save'));
          $last_fid = $this->getLastFileId();
          $this->assertTrue($last_fid > $last_fid_prior, 'New file got saved.');
          $this->assertRaw(t('The file ids are %fids.', array('%fids' => implode(',', array($last_fid)))), 'Submit handler has correct file info.');

          // Submit no new input, but with a default file.
          $this->drupalPostForm($path . '/' . $last_fid, array(), t('Save'));
          $this->assertRaw(t('The file ids are %fids.', array('%fids' => implode(',', array($last_fid)))), 'Empty submission did not change an existing file.');

          // Now, test the Upload and Remove buttons, with and without Ajax.
          foreach (array(FALSE, TRUE) as $ajax) {
            // Upload, then Submit.
            $last_fid_prior = $this->getLastFileId();
            $this->drupalGet($path);
            $edit = array($file_field_name => drupal_realpath($test_file->getFileUri()));
            if ($ajax) {
              $this->drupalPostAjaxForm(NULL, $edit, $input_base_name . '_upload_button');
            }
            else {
              $this->drupalPostForm(NULL, $edit, t('Upload'));
            }
            $last_fid = $this->getLastFileId();
            $this->assertTrue($last_fid > $last_fid_prior, 'New file got uploaded.');
            $this->drupalPostForm(NULL, array(), t('Save'));
            $this->assertRaw(t('The file ids are %fids.', array('%fids' => implode(',', array($last_fid)))), 'Submit handler has correct file info.');

            // Remove, then Submit.
            $remove_button_title = $multiple ? t('Remove selected') : t('Remove');
            $remove_edit = array();
            if ($multiple) {
              $selected_checkbox = ($tree ? 'nested[file]' : 'file') . '[file_' . $last_fid . '][selected]';
              $remove_edit = array($selected_checkbox => '1');
            }
            $this->drupalGet($path . '/' . $last_fid);
            if ($ajax) {
              $this->drupalPostAjaxForm(NULL, $remove_edit, $input_base_name . '_remove_button');
            }
            else {
              $this->drupalPostForm(NULL, $remove_edit, $remove_button_title);
            }
            $this->drupalPostForm(NULL, array(), t('Save'));
            $this->assertRaw(t('The file ids are %fids.', array('%fids' => '')), 'Submission after file removal was successful.');

            // Upload, then Remove, then Submit.
            $this->drupalGet($path);
            $edit = array($file_field_name => drupal_realpath($test_file->getFileUri()));
            if ($ajax) {
              $this->drupalPostAjaxForm(NULL, $edit, $input_base_name . '_upload_button');
            }
            else {
              $this->drupalPostForm(NULL, $edit, t('Upload'));
            }
            $remove_edit = array();
            if ($multiple) {
              $selected_checkbox = ($tree ? 'nested[file]' : 'file') . '[file_' . $this->getLastFileId() . '][selected]';
              $remove_edit = array($selected_checkbox => '1');
            }
            if ($ajax) {
              $this->drupalPostAjaxForm(NULL, $remove_edit, $input_base_name . '_remove_button');
            }
            else {
              $this->drupalPostForm(NULL, $remove_edit, $remove_button_title);
            }

            $this->drupalPostForm(NULL, array(), t('Save'));
            $this->assertRaw(t('The file ids are %fids.', array('%fids' => '')), 'Submission after file upload and removal was successful.');
          }
        }
      }
    }

    // The multiple file upload has additional conditions that need checking.
    $path = 'file/test/1/1/1';
    $edit = array('files[nested_file][]' => drupal_realpath($test_file->getFileUri()));
    $fid_list = array();

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
    $this->drupalPostForm(NULL, array(), t('Save'));
    $this->assertRaw(t('The file ids are %fids.', array('%fids' => implode(',', $fid_list))), 'Two files saved into a single multiple file element.');

    // Delete only the first file.
    $edit = array(
      'nested[file][file_' . $fid_list[0] . '][selected]' => '1',
    );
    $this->drupalPostForm($path . '/' . implode(',', $fid_list), $edit, t('Remove selected'));

    // Check that the first file has been deleted but not the second.
    $this->assertNoFieldByXpath('//input[@name="nested[file][file_' . $fid_list[0] . '][selected]"]', NULL, 'An individual file can be deleted from a multiple file element.');
    $this->assertFieldByXpath('//input[@name="nested[file][file_' . $fid_list[1] . '][selected]"]', NULL, 'Second individual file not deleted when the first file is deleted from a multiple file element.');
  }
}
