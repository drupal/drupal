<?php

namespace Drupal\Tests\file\Functional;

use Drupal\file\Entity\File;
use Drupal\node\Entity\NodeType;
use Drupal\user\RoleInterface;

/**
 * Uploads a test to a private node and checks access.
 *
 * @group file
 */
class FilePrivateTest extends FileFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node_access_test', 'field_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    node_access_test_add_field(NodeType::load('article'));
    node_access_rebuild();
    \Drupal::state()->set('node_access_test.private', TRUE);
    // This test expects unused managed files to be marked as a temporary file.
    $this->config('file.settings')
      ->set('make_unused_managed_files_temporary', TRUE)
      ->save();
  }

  /**
   * Tests file access for file uploaded to a private node.
   */
  public function testPrivateFile() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $type_name, ['uri_scheme' => 'private']);

    $test_file = $this->getTestFile('text');
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name, TRUE, ['private' => TRUE]);
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$nid]);
    /* @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    // Ensure the file can be viewed.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw($node_file->getFilename());
    // Ensure the file can be downloaded.
    $this->drupalGet(file_create_url($node_file->getFileUri()));
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogOut();
    // Ensure the file cannot be downloaded after logging out.
    $this->drupalGet(file_create_url($node_file->getFileUri()));
    $this->assertSession()->statusCodeEquals(403);

    // Create a field with no view access. See
    // field_test_entity_field_access().
    $no_access_field_name = 'field_no_view_access';
    $this->createFileField($no_access_field_name, 'node', $type_name, ['uri_scheme' => 'private']);
    // Test with the field that should deny access through field access.
    $this->drupalLogin($this->adminUser);
    $nid = $this->uploadNodeFile($test_file, $no_access_field_name, $type_name, TRUE, ['private' => TRUE]);
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$no_access_field_name}->target_id);

    // Ensure the file cannot be downloaded.
    $file_url = file_create_url($node_file->getFileUri());
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(403);

    // Attempt to reuse the file when editing a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm('node/add/' . $type_name, $edit, 'Save');
    $new_node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // Can't use drupalPostForm() to set hidden fields.
    $this->drupalGet('node/' . $new_node->id() . '/edit');
    $this->getSession()->getPage()->find('css', 'input[name="' . $field_name . '[0][fids]"]')->setValue($node_file->id());
    $this->getSession()->getPage()->pressButton(t('Save'));
    $this->assertSession()->addressEquals('node/' . $new_node->id());
    // Make sure the submitted hidden file field is empty.
    $new_node = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($new_node->id());
    $this->assertTrue($new_node->get($field_name)->isEmpty());
    // Attempt to reuse the existing file when creating a new node, and confirm
    // that access is still denied.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    // Can't use drupalPostForm() to set hidden fields.
    $this->drupalGet('node/add/' . $type_name);
    $this->getSession()->getPage()->find('css', 'input[name="title[0][value]"]')->setValue($edit['title[0][value]']);
    $this->getSession()->getPage()->find('css', 'input[name="' . $field_name . '[0][fids]"]')->setValue($node_file->id());
    $this->getSession()->getPage()->pressButton(t('Save'));
    $new_node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertSession()->addressEquals('node/' . $new_node->id());
    // Make sure the submitted hidden file field is empty.
    $new_node = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($new_node->id());
    $this->assertTrue($new_node->get($field_name)->isEmpty());

    // Now make file_test_file_download() return everything.
    \Drupal::state()->set('file_test.allow_all', TRUE);
    // Delete the node.
    $node->delete();
    // Ensure the temporary file can still be downloaded by the owner.
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(200);

    // Ensure the temporary file cannot be downloaded by an anonymous user.
    $this->drupalLogout();
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(403);

    // Ensure the temporary file cannot be downloaded by another user.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(403);

    // As an anonymous user, create a temporary file with no references and
    // confirm that only the session that uploaded it may view it.
    $this->drupalLogout();
    user_role_change_permissions(
      RoleInterface::ANONYMOUS_ID,
      [
        "create $type_name content" => TRUE,
        'access content' => TRUE,
      ]
    );
    $test_file = $this->getTestFile('text');
    $this->drupalGet('node/add/' . $type_name);
    $edit = ['files[' . $field_name . '_0]' => $file_system->realpath($test_file->getFileUri())];
    $this->submitForm($edit, 'Upload');
    /** @var \Drupal\file\FileStorageInterface $file_storage */
    $file_storage = $this->container->get('entity_type.manager')->getStorage('file');
    $files = $file_storage->loadByProperties(['uid' => 0]);
    $this->assertCount(1, $files, 'Loaded one anonymous file.');
    $file = end($files);
    $this->assertTrue($file->isTemporary(), 'File is temporary.');
    $usage = $this->container->get('file.usage')->listUsage($file);
    $this->assertEmpty($usage, 'No file usage found.');
    $file_url = file_create_url($file->getFileUri());
    // Ensure the anonymous uploader has access to the temporary file.
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(200);
    // Close the prior connection and remove the session cookie.
    $this->getSession()->reset();
    // Ensure that a different anonymous user cannot access the temporary file.
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(403);

    // As an anonymous user, create a permanent file, then remove all
    // references to the file (so that it becomes temporary again) and confirm
    // that only the session that uploaded it may view it.
    $test_file = $this->getTestFile('text');
    $this->drupalGet('node/add/' . $type_name);
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['files[' . $field_name . '_0]'] = $file_system->realpath($test_file->getFileUri());
    $this->submitForm($edit, 'Save');
    $new_node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $file_id = $new_node->{$field_name}->target_id;
    $file = File::load($file_id);
    $this->assertTrue($file->isPermanent(), 'File is permanent.');
    // Remove the reference to this file.
    $new_node->{$field_name} = [];
    $new_node->save();
    $file = File::load($file_id);
    $this->assertTrue($file->isTemporary(), 'File is temporary.');
    $usage = $this->container->get('file.usage')->listUsage($file);
    $this->assertEmpty($usage, 'No file usage found.');
    $file_url = file_create_url($file->getFileUri());
    // Ensure the anonymous uploader has access to the temporary file.
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(200);
    // Close the prior connection and remove the session cookie.
    $this->getSession()->reset();
    // Ensure that a different anonymous user cannot access the temporary file.
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(403);

    // As an anonymous user, create a permanent file that is referenced by a
    // published node and confirm that all anonymous users may view it.
    $test_file = $this->getTestFile('text');
    $this->drupalGet('node/add/' . $type_name);
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['files[' . $field_name . '_0]'] = $file_system->realpath($test_file->getFileUri());
    $this->submitForm($edit, 'Save');
    $new_node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $file = File::load($new_node->{$field_name}->target_id);
    $this->assertTrue($file->isPermanent(), 'File is permanent.');
    $usage = $this->container->get('file.usage')->listUsage($file);
    $this->assertCount(1, $usage, 'File usage found.');
    $file_url = file_create_url($file->getFileUri());
    // Ensure the anonymous uploader has access to the file.
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(200);
    // Close the prior connection and remove the session cookie.
    $this->getSession()->reset();
    // Ensure that a different anonymous user can access the file.
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(200);

    // As an anonymous user, create a permanent file that is referenced by an
    // unpublished node and confirm that no anonymous users may view it (even
    // the session that uploaded the file) because they cannot view the
    // unpublished node.
    $test_file = $this->getTestFile('text');
    $this->drupalGet('node/add/' . $type_name);
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['files[' . $field_name . '_0]'] = $file_system->realpath($test_file->getFileUri());
    $this->submitForm($edit, 'Save');
    $new_node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $new_node->setUnpublished();
    $new_node->save();
    $file = File::load($new_node->{$field_name}->target_id);
    $this->assertTrue($file->isPermanent(), 'File is permanent.');
    $usage = $this->container->get('file.usage')->listUsage($file);
    $this->assertCount(1, $usage, 'File usage found.');
    $file_url = file_create_url($file->getFileUri());
    // Ensure the anonymous uploader cannot access to the file.
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(403);
    // Close the prior connection and remove the session cookie.
    $this->getSession()->reset();
    // Ensure that a different anonymous user cannot access the temporary file.
    $this->drupalGet($file_url);
    $this->assertSession()->statusCodeEquals(403);
  }

}
