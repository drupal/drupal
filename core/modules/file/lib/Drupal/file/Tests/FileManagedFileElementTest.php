<?php

/**
 * @file
 * Definition of Drupal\file\Tests\FileManagedFileElementTest.
 */

namespace Drupal\file\Tests;

/**
 * Tests the 'managed_file' element type.
 *
 * @todo Create a FileTestBase class and move FileFieldTestBase methods
 *   that aren't related to fields into it.
 */
class FileManagedFileElementTest extends FileFieldTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Managed file element test',
      'description' => 'Tests the managed_file element type.',
      'group' => 'File',
    );
  }

  /**
   * Tests the managed_file element type.
   */
  function testManagedFile() {
    // Check that $element['#size'] is passed to the child upload element.
    $this->drupalGet('file/test');
    $this->assertFieldByXpath('//input[@name="files[nested_file]" and @size="13"]', NULL, 'The custom #size attribute is passed to the child upload element.');

    // Perform the tests with all permutations of $form['#tree'] and
    // $element['#extended'].
    foreach (array(0, 1) as $tree) {
      foreach (array(0, 1) as $extended) {
        $test_file = $this->getTestFile('text');
        $path = 'file/test/' . $tree . '/' . $extended;
        $input_base_name = $tree ? 'nested_file' : 'file';

        // Submit without a file.
        $this->drupalPost($path, array(), t('Save'));
        $this->assertRaw(t('The file id is %fid.', array('%fid' => 0)), t('Submitted without a file.'));

        // Submit a new file, without using the Upload button.
        $last_fid_prior = $this->getLastFileId();
        $edit = array('files[' . $input_base_name . ']' => drupal_realpath($test_file->uri));
        $this->drupalPost($path, $edit, t('Save'));
        $last_fid = $this->getLastFileId();
        $this->assertTrue($last_fid > $last_fid_prior, t('New file got saved.'));
        $this->assertRaw(t('The file id is %fid.', array('%fid' => $last_fid)), t('Submit handler has correct file info.'));

        // Submit no new input, but with a default file.
        $this->drupalPost($path . '/' . $last_fid, array(), t('Save'));
        $this->assertRaw(t('The file id is %fid.', array('%fid' => $last_fid)), t('Empty submission did not change an existing file.'));

        // Now, test the Upload and Remove buttons, with and without Ajax.
        foreach (array(FALSE, TRUE) as $ajax) {
          // Upload, then Submit.
          $last_fid_prior = $this->getLastFileId();
          $this->drupalGet($path);
          $edit = array('files[' . $input_base_name . ']' => drupal_realpath($test_file->uri));
          if ($ajax) {
            $this->drupalPostAJAX(NULL, $edit, $input_base_name . '_upload_button');
          }
          else {
            $this->drupalPost(NULL, $edit, t('Upload'));
          }
          $last_fid = $this->getLastFileId();
          $this->assertTrue($last_fid > $last_fid_prior, t('New file got uploaded.'));
          $this->drupalPost(NULL, array(), t('Save'));
          $this->assertRaw(t('The file id is %fid.', array('%fid' => $last_fid)), t('Submit handler has correct file info.'));

          // Remove, then Submit.
          $this->drupalGet($path . '/' . $last_fid);
          if ($ajax) {
            $this->drupalPostAJAX(NULL, array(), $input_base_name . '_remove_button');
          }
          else {
            $this->drupalPost(NULL, array(), t('Remove'));
          }
          $this->drupalPost(NULL, array(), t('Save'));
          $this->assertRaw(t('The file id is %fid.', array('%fid' => 0)), t('Submission after file removal was successful.'));

          // Upload, then Remove, then Submit.
          $this->drupalGet($path);
          $edit = array('files[' . $input_base_name . ']' => drupal_realpath($test_file->uri));
          if ($ajax) {
            $this->drupalPostAJAX(NULL, $edit, $input_base_name . '_upload_button');
            $this->drupalPostAJAX(NULL, array(), $input_base_name . '_remove_button');
          }
          else {
            $this->drupalPost(NULL, $edit, t('Upload'));
            $this->drupalPost(NULL, array(), t('Remove'));
          }
          $this->drupalPost(NULL, array(), t('Save'));
          $this->assertRaw(t('The file id is %fid.', array('%fid' => 0)), t('Submission after file upload and removal was successful.'));
        }
      }
    }
  }
}
