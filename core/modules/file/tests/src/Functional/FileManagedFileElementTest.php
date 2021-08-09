<?php

namespace Drupal\Tests\file\Functional;

use Drupal\file\Entity\File;

/**
 * Tests the 'managed_file' element type.
 *
 * @group file
 * @todo Create a FileTestBase class and move FileFieldTestBase methods
 *   that aren't related to fields into it.
 */
class FileManagedFileElementTest extends FileFieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the managed_file element type.
   */
  public function testManagedFile() {
    // Check that $element['#size'] is passed to the child upload element.
    $this->drupalGet('file/test');
    $field = $this->assertSession()->fieldExists("files[nested_file]");
    $this->assertEquals(13, $field->getAttribute('size'));

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
          $this->drupalGet($path);
          $this->submitForm([], 'Save');
          $this->assertSession()->pageTextContains("The file ids are .");

          // Submit with a file, but with an invalid form token. Ensure the file
          // was not saved.
          $last_fid_prior = $this->getLastFileId();
          $this->drupalGet($path);
          $form_token_field = $this->assertSession()->hiddenFieldExists('form_token');
          $form_token_field->setValue('invalid token');
          $edit = [
            $file_field_name => \Drupal::service('file_system')->realpath($test_file->getFileUri()),
          ];
          $this->submitForm($edit, 'Save');
          $this->assertSession()->pageTextContains('The form has become outdated.');
          $last_fid = $this->getLastFileId();
          $this->assertEquals($last_fid_prior, $last_fid, 'File was not saved when uploaded with an invalid form token.');

          // Submit a new file, without using the Upload button.
          $last_fid_prior = $this->getLastFileId();
          $edit = [$file_field_name => \Drupal::service('file_system')->realpath($test_file->getFileUri())];
          $this->drupalGet($path);
          $this->submitForm($edit, 'Save');
          $last_fid = $this->getLastFileId();
          $this->assertGreaterThan($last_fid_prior, $last_fid, 'New file got saved.');
          $this->assertSession()->pageTextContains("The file ids are $last_fid.");

          // Submit no new input, but with a default file.
          $this->drupalGet($path . '/' . $last_fid);
          $this->submitForm([], 'Save');
          $this->assertSession()->pageTextContains("The file ids are $last_fid.");

          // Upload, then Submit.
          $last_fid_prior = $this->getLastFileId();
          $this->drupalGet($path);
          $edit = [$file_field_name => \Drupal::service('file_system')->realpath($test_file->getFileUri())];
          $this->submitForm($edit, 'Upload');
          $last_fid = $this->getLastFileId();
          $this->assertGreaterThan($last_fid_prior, $last_fid, 'New file got uploaded.');
          $this->submitForm([], 'Save');
          $this->assertSession()->pageTextContains("The file ids are $last_fid.");

          // Remove, then Submit.
          $remove_button_title = $multiple ? 'Remove selected' : 'Remove';
          $remove_edit = [];
          if ($multiple) {
            $selected_checkbox = ($tree ? 'nested[file]' : 'file') . '[file_' . $last_fid . '][selected]';
            $remove_edit = [$selected_checkbox => '1'];
          }
          $this->drupalGet($path . '/' . $last_fid);
          $this->submitForm($remove_edit, $remove_button_title);
          $this->submitForm([], 'Save');
          $this->assertSession()->pageTextContains("The file ids are .");

          // Upload, then Remove, then Submit.
          $this->drupalGet($path);
          $edit = [$file_field_name => \Drupal::service('file_system')->realpath($test_file->getFileUri())];
          $this->submitForm($edit, 'Upload');
          $remove_edit = [];
          if ($multiple) {
            $selected_checkbox = ($tree ? 'nested[file]' : 'file') . '[file_' . $this->getLastFileId() . '][selected]';
            $remove_edit = [$selected_checkbox => '1'];
          }
          $this->submitForm($remove_edit, $remove_button_title);

          $this->submitForm([], 'Save');
          $this->assertSession()->pageTextContains("The file ids are .");
        }
      }
    }

    // The multiple file upload has additional conditions that need checking.
    $path = 'file/test/1/1/1';
    $edit = ['files[nested_file][]' => \Drupal::service('file_system')->realpath($test_file->getFileUri())];
    $fid_list = [];

    $this->drupalGet($path);

    // Add a single file to the upload field.
    $this->submitForm($edit, 'Upload');
    $fid_list[] = $this->getLastFileId();
    $this->assertSession()->fieldExists("nested[file][file_{$fid_list[0]}][selected]");

    // Add another file to the same upload field.
    $this->submitForm($edit, 'Upload');
    $fid_list[] = $this->getLastFileId();
    $this->assertSession()->fieldExists("nested[file][file_{$fid_list[1]}][selected]");

    // Save the entire form.
    $this->submitForm([], 'Save');
    // Check that two files are saved into a single multiple file element.
    $this->assertSession()->pageTextContains("The file ids are " . implode(',', $fid_list) . ".");

    // Delete only the first file.
    $edit = [
      'nested[file][file_' . $fid_list[0] . '][selected]' => '1',
    ];
    $this->drupalGet($path . '/' . implode(',', $fid_list));
    $this->submitForm($edit, 'Remove selected');

    // Check that the first file has been deleted but not the second.
    $this->assertSession()->fieldNotExists("nested[file][file_{$fid_list[0]}][selected]");
    $this->assertSession()->fieldExists("nested[file][file_{$fid_list[1]}][selected]");
  }

  /**
   * Ensure that warning is shown if file on the field has been removed.
   */
  public function testManagedFileRemoved() {
    $this->drupalGet('file/test/1/0/1');
    $test_file = $this->getTestFile('text');
    $file_field_name = 'files[nested_file][]';

    $edit = [$file_field_name => \Drupal::service('file_system')->realpath($test_file->getFileUri())];
    $this->submitForm($edit, 'Upload');

    $fid = $this->getLastFileId();
    $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
    $file->delete();

    $this->submitForm($edit, 'Upload');
    // We expect the title 'Managed <em>file & butter</em>' which got escaped
    // via a t() call before.
    $this->assertSession()->responseContains('The file referenced by the Managed <em>file &amp; butter</em> field does not exist.');
  }

  /**
   * Tests file names have leading . removed.
   */
  public function testFileNameTrim() {
    file_put_contents('public://.leading-period.txt', $this->randomString(32));
    $last_fid_prior = $this->getLastFileId();
    $this->drupalGet('file/test/0/0/0');
    $this->submitForm(['files[file]' => \Drupal::service('file_system')->realpath('public://.leading-period.txt')], 'Save');
    $next_fid = $this->getLastFileId();
    $this->assertGreaterThan($last_fid_prior, $next_fid);
    $file = File::load($next_fid);
    $this->assertEquals('leading-period.txt', $file->getFilename());
  }

  /**
   * Ensure a file entity can be saved when the file does not exist on disk.
   */
  public function testFileRemovedFromDisk() {
    $this->drupalGet('file/test/1/0/1');
    $test_file = $this->getTestFile('text');
    $file_field_name = 'files[nested_file][]';

    $edit = [$file_field_name => \Drupal::service('file_system')->realpath($test_file->getFileUri())];
    $this->submitForm($edit, 'Upload');
    $this->submitForm([], 'Save');

    $fid = $this->getLastFileId();
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->container->get('entity_type.manager')->getStorage('file')->load($fid);
    $file->setPermanent();
    $file->save();
    $this->assertTrue(\Drupal::service('file_system')->delete($file->getFileUri()));
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
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextNotContains('The file used in the Managed file & butter field may not be referenced.');
    $this->assertSession()->pageTextContains('The file ids are ' . $file->id());

    // Enable marking unused files as temporary, unused permanent files must not
    // be referenced now.
    $this->config('file.settings')
      ->set('make_unused_managed_files_temporary', TRUE)
      ->save();
    $this->drupalGet('file/test/1/0/1/' . $file->id());
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('The file used in the Managed file & butter field may not be referenced.');
    $this->assertSession()->pageTextNotContains('The file ids are ' . $file->id());

    // Make the file temporary, now using it is allowed.
    $file->setTemporary();
    $file->save();

    $this->drupalGet('file/test/1/0/1/' . $file->id());
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextNotContains('The file used in the Managed file & butter field may not be referenced.');
    $this->assertSession()->pageTextContains('The file ids are ' . $file->id());

    // Make the file permanent again and add a usage from itself, referencing is
    // still allowed.
    $file->setPermanent();
    $file->save();

    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = \Drupal::service('file.usage');
    $file_usage->add($file, 'file', 'file', $file->id());

    $this->drupalGet('file/test/1/0/1/' . $file->id());
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextNotContains('The file used in the Managed file & butter field may not be referenced.');
    $this->assertSession()->pageTextContains('The file ids are ' . $file->id());
  }

}
