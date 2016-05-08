<?php

namespace Drupal\file\Tests;

use Drupal\file\Entity\File;

/**
 * Tests creating and deleting revisions with files attached.
 *
 * @group file
 */
class FileFieldRevisionTest extends FileFieldTestBase {
  /**
   * Tests creating multiple revisions of a node and managing attached files.
   *
   * Expected behaviors:
   *  - Adding a new revision will make another entry in the field table, but
   *    the original file will not be duplicated.
   *  - Deleting a revision should not delete the original file if the file
   *    is in use by another revision.
   *  - When the last revision that uses a file is deleted, the original file
   *    should be deleted also.
   */
  function testRevisions() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $type_name);
    // Create the same fields for users.
    $this->createFileField($field_name, 'user', 'user');

    $test_file = $this->getTestFile('text');

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);

    // Check that the file exists on disk and in the database.
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $node_file_r1 = File::load($node->{$field_name}->target_id);
    $node_vid_r1 = $node->getRevisionId();
    $this->assertFileExists($node_file_r1, 'New file saved to disk on node creation.');
    $this->assertFileEntryExists($node_file_r1, 'File entry exists in database on node creation.');
    $this->assertFileIsPermanent($node_file_r1, 'File is permanent.');

    // Upload another file to the same node in a new revision.
    $this->replaceNodeFile($test_file, $field_name, $nid);
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $node_file_r2 = File::load($node->{$field_name}->target_id);
    $node_vid_r2 = $node->getRevisionId();
    $this->assertFileExists($node_file_r2, 'Replacement file exists on disk after creating new revision.');
    $this->assertFileEntryExists($node_file_r2, 'Replacement file entry exists in database after creating new revision.');
    $this->assertFileIsPermanent($node_file_r2, 'Replacement file is permanent.');

    // Check that the original file is still in place on the first revision.
    $node = node_revision_load($node_vid_r1);
    $current_file = File::load($node->{$field_name}->target_id);
    $this->assertEqual($node_file_r1->id(), $current_file->id(), 'Original file still in place after replacing file in new revision.');
    $this->assertFileExists($node_file_r1, 'Original file still in place after replacing file in new revision.');
    $this->assertFileEntryExists($node_file_r1, 'Original file entry still in place after replacing file in new revision');
    $this->assertFileIsPermanent($node_file_r1, 'Original file is still permanent.');

    // Save a new version of the node without any changes.
    // Check that the file is still the same as the previous revision.
    $this->drupalPostForm('node/' . $nid . '/edit', array('revision' => '1'), t('Save and keep published'));
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $node_file_r3 = File::load($node->{$field_name}->target_id);
    $node_vid_r3 = $node->getRevisionId();
    $this->assertEqual($node_file_r2->id(), $node_file_r3->id(), 'Previous revision file still in place after creating a new revision without a new file.');
    $this->assertFileIsPermanent($node_file_r3, 'New revision file is permanent.');

    // Revert to the first revision and check that the original file is active.
    $this->drupalPostForm('node/' . $nid . '/revisions/' . $node_vid_r1 . '/revert', array(), t('Revert'));
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $node_file_r4 = File::load($node->{$field_name}->target_id);
    $this->assertEqual($node_file_r1->id(), $node_file_r4->id(), 'Original revision file still in place after reverting to the original revision.');
    $this->assertFileIsPermanent($node_file_r4, 'Original revision file still permanent after reverting to the original revision.');

    // Delete the second revision and check that the file is kept (since it is
    // still being used by the third revision).
    $this->drupalPostForm('node/' . $nid . '/revisions/' . $node_vid_r2 . '/delete', array(), t('Delete'));
    $this->assertFileExists($node_file_r3, 'Second file is still available after deleting second revision, since it is being used by the third revision.');
    $this->assertFileEntryExists($node_file_r3, 'Second file entry is still available after deleting second revision, since it is being used by the third revision.');
    $this->assertFileIsPermanent($node_file_r3, 'Second file entry is still permanent after deleting second revision, since it is being used by the third revision.');

    // Attach the second file to a user.
    $user = $this->drupalCreateUser();
    $user->$field_name->target_id = $node_file_r3->id();
    $user->$field_name->display = 1;
    $user->save();
    $this->drupalGet('user/' . $user->id() . '/edit');

    // Delete the third revision and check that the file is not deleted yet.
    $this->drupalPostForm('node/' . $nid . '/revisions/' . $node_vid_r3 . '/delete', array(), t('Delete'));
    $this->assertFileExists($node_file_r3, 'Second file is still available after deleting third revision, since it is being used by the user.');
    $this->assertFileEntryExists($node_file_r3, 'Second file entry is still available after deleting third revision, since it is being used by the user.');
    $this->assertFileIsPermanent($node_file_r3, 'Second file entry is still permanent after deleting third revision, since it is being used by the user.');

    // Delete the user and check that the file is also deleted.
    $user->delete();
    // TODO: This seems like a bug in File API. Clearing the stat cache should
    // not be necessary here. The file really is deleted, but stream wrappers
    // doesn't seem to think so unless we clear the PHP file stat() cache.
    clearstatcache($node_file_r1->getFileUri());
    clearstatcache($node_file_r2->getFileUri());
    clearstatcache($node_file_r3->getFileUri());
    clearstatcache($node_file_r4->getFileUri());

    // Call file_cron() to clean up the file. Make sure the changed timestamp
    // of the file is older than the system.file.temporary_maximum_age
    // configuration value.
    db_update('file_managed')
      ->fields(array(
        'changed' => REQUEST_TIME - ($this->config('system.file')->get('temporary_maximum_age') + 1),
      ))
      ->condition('fid', $node_file_r3->id())
      ->execute();
    \Drupal::service('cron')->run();

    $this->assertFileNotExists($node_file_r3, 'Second file is now deleted after deleting third revision, since it is no longer being used by any other nodes.');
    $this->assertFileEntryNotExists($node_file_r3, 'Second file entry is now deleted after deleting third revision, since it is no longer being used by any other nodes.');

    // Delete the entire node and check that the original file is deleted.
    $this->drupalPostForm('node/' . $nid . '/delete', array(), t('Delete'));
    // Call file_cron() to clean up the file. Make sure the changed timestamp
    // of the file is older than the system.file.temporary_maximum_age
    // configuration value.
    db_update('file_managed')
      ->fields(array(
        'changed' => REQUEST_TIME - ($this->config('system.file')->get('temporary_maximum_age') + 1),
      ))
      ->condition('fid', $node_file_r1->id())
      ->execute();
    \Drupal::service('cron')->run();
    $this->assertFileNotExists($node_file_r1, 'Original file is deleted after deleting the entire node with two revisions remaining.');
    $this->assertFileEntryNotExists($node_file_r1, 'Original file entry is deleted after deleting the entire node with two revisions remaining.');
  }

}
