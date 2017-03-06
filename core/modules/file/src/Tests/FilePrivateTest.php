<?php

namespace Drupal\file\Tests;

use Drupal\Core\Entity\Plugin\Validation\Constraint\ReferenceAccessConstraint;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\file\Entity\File;
use Drupal\node\Entity\NodeType;

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
  public static $modules = ['node_access_test', 'field_test'];

  protected function setUp() {
    parent::setUp();
    node_access_test_add_field(NodeType::load('article'));
    node_access_rebuild();
    \Drupal::state()->set('node_access_test.private', TRUE);
  }

  /**
   * Tests file access for file uploaded to a private node.
   */
  public function testPrivateFile() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $type_name, ['uri_scheme' => 'private']);

    $test_file = $this->getTestFile('text');
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name, TRUE, ['private' => TRUE]);
    \Drupal::entityManager()->getStorage('node')->resetCache([$nid]);
    /* @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    // Ensure the file can be viewed.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw($node_file->getFilename(), 'File reference is displayed after attaching it');
    // Ensure the file can be downloaded.
    $this->drupalGet(file_create_url($node_file->getFileUri()));
    $this->assertResponse(200, 'Confirmed that the generated URL is correct by downloading the shipped file.');
    $this->drupalLogOut();
    $this->drupalGet(file_create_url($node_file->getFileUri()));
    $this->assertResponse(403, 'Confirmed that access is denied for the file without the needed permission.');

    // Create a field with no view access. See
    // field_test_entity_field_access().
    $no_access_field_name = 'field_no_view_access';
    $this->createFileField($no_access_field_name, 'node', $type_name, ['uri_scheme' => 'private']);
    // Test with the field that should deny access through field access.
    $this->drupalLogin($this->adminUser);
    $nid = $this->uploadNodeFile($test_file, $no_access_field_name, $type_name, TRUE, ['private' => TRUE]);
    \Drupal::entityManager()->getStorage('node')->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$no_access_field_name}->target_id);

    // Ensure the file cannot be downloaded.
    $file_url = file_create_url($node_file->getFileUri());
    $this->drupalGet($file_url);
    $this->assertResponse(403, 'Confirmed that access is denied for the file without view field access permission.');

    // Attempt to reuse the file when editing a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm('node/add/' . $type_name, $edit, t('Save and publish'));
    $new_node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $edit[$field_name . '[0][fids]'] = $node_file->id();
    $this->drupalPostForm('node/' . $new_node->id() . '/edit', $edit, t('Save and keep published'));
    // Make sure the form submit failed - we stayed on the edit form.
    $this->assertUrl('node/' . $new_node->id() . '/edit');
    // Check that we got the expected constraint form error.
    $constraint = new ReferenceAccessConstraint();
    $this->assertRaw(SafeMarkup::format($constraint->message, ['%type' => 'file', '%id' => $node_file->id()]));
    // Attempt to reuse the existing file when creating a new node, and confirm
    // that access is still denied.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit[$field_name . '[0][fids]'] = $node_file->id();
    $this->drupalPostForm('node/add/' . $type_name, $edit, t('Save and publish'));
    $new_node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue(empty($new_node), 'Node was not created.');
    $this->assertUrl('node/add/' . $type_name);
    $this->assertRaw(SafeMarkup::format($constraint->message, ['%type' => 'file', '%id' => $node_file->id()]));

    // Now make file_test_file_download() return everything.
    \Drupal::state()->set('file_test.allow_all', TRUE);
    // Delete the node.
    $node->delete();
    // Ensure the file can still be downloaded by the owner.
    $this->drupalGet($file_url);
    $this->assertResponse(200, 'Confirmed that the owner still has access to the temporary file.');

    // Ensure the file cannot be downloaded by an anonymous user.
    $this->drupalLogout();
    $this->drupalGet($file_url);
    $this->assertResponse(403, 'Confirmed that access is denied for an anonymous user to the temporary file.');

    // Ensure the file cannot be downloaded by another user.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalGet($file_url);
    $this->assertResponse(403, 'Confirmed that access is denied for another user to the temporary file.');
  }

}
