<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\user\RoleInterface;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Tests the file field widget with public and private files.
 *
 * @group file
 */
class FileFieldWidgetTest extends FileFieldTestBase {

  use CommentTestTrait;
  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Creates a temporary file, for a specific user.
   *
   * @param string $data
   *   A string containing the contents of the file.
   * @param \Drupal\user\UserInterface $user
   *   The user of the file owner.
   *
   * @return \Drupal\file\FileInterface
   *   A file object, or FALSE on error.
   */
  protected function createTemporaryFile($data, ?UserInterface $user = NULL) {
    /** @var \Drupal\file\FileRepositoryInterface $file_repository */
    $file_repository = \Drupal::service('file.repository');
    $file = $file_repository->writeData($data, "public://");

    if ($file) {
      if ($user) {
        $file->setOwner($user);
      }
      else {
        $file->setOwner($this->adminUser);
      }
      // Change the file status to be temporary.
      $file->setTemporary();
      // Save the changes.
      $file->save();
    }

    return $file;
  }

  /**
   * Tests upload and remove buttons for a single-valued File field.
   */
  public function testSingleValuedWidget(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $type_name = 'article';
    $field_name = $this->randomMachineName();
    $this->createFileField($field_name, 'node', $type_name);

    $test_file = $this->getTestFile('text');

    // Create a new node with the uploaded file and ensure it got uploaded
    // successfully.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $node = $node_storage->loadUnchanged($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file->getFileUri());

    // Ensure the file can be downloaded.
    $this->drupalGet($node_file->createFileUrl());
    $this->assertSession()->statusCodeEquals(200);

    // Ensure the edit page has a remove button instead of an upload button.
    $this->drupalGet("node/$nid/edit");
    $this->assertSession()->buttonNotExists('Upload');
    $this->assertSession()->buttonExists('Remove');
    $this->submitForm([], 'Remove');

    // Ensure the page now has an upload button instead of a remove button.
    $this->assertSession()->buttonNotExists('Remove');
    $this->assertSession()->buttonExists('Upload');
    // Test label has correct 'for' attribute.
    $input = $this->assertSession()->fieldExists("files[{$field_name}_0]");
    $this->assertSession()->elementExists('xpath', '//label[@for="' . $input->getAttribute('id') . '"]');

    // Save the node and ensure it does not have the file.
    $this->submitForm([], 'Save');
    $node = $node_storage->loadUnchanged($nid);
    $this->assertEmpty($node->{$field_name}->target_id, 'File was successfully removed from the node.');
  }

  /**
   * Tests upload and remove buttons for multiple multi-valued File fields.
   */
  public function testMultiValuedWidget(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $type_name = 'article';
    // Use explicit names instead of random names for those fields, because of a
    // bug in submitForm() with multiple file uploads in one form, where the
    // order of uploads depends on the order in which the upload elements are
    // added to the $form (which, in the current implementation of
    // FileStorage::listAll(), comes down to the alphabetical order on field
    // names).
    $field_name = 'test_file_field_1';
    $field_name2 = 'test_file_field_2';
    $cardinality = 3;
    $this->createFileField($field_name, 'node', $type_name, ['cardinality' => $cardinality]);
    $this->createFileField($field_name2, 'node', $type_name, ['cardinality' => $cardinality]);

    $test_file = $this->getTestFile('text');

    // Visit the node creation form, and upload 3 files for each field. Since
    // the field has cardinality of 3, ensure the "Upload" button is displayed
    // until after the 3rd file, and after that, isn't displayed. Because
    // the last button with a given name is triggered by default, upload to the
    // second field first.
    $this->drupalGet("node/add/$type_name");
    foreach ([$field_name2, $field_name] as $each_field_name) {
      for ($delta = 0; $delta < 3; $delta++) {
        $edit = ['files[' . $each_field_name . '_' . $delta . '][]' => \Drupal::service('file_system')->realpath($test_file->getFileUri())];
        // If the Upload button doesn't exist, submitForm() will
        // automatically fail with an assertion message.
        $this->submitForm($edit, 'Upload');
      }
    }
    $this->assertSession()->buttonNotExists('Upload');

    $num_expected_remove_buttons = 6;

    foreach ([$field_name, $field_name2] as $current_field_name) {
      // How many uploaded files for the current field are remaining.
      $remaining = 3;
      // Test clicking each "Remove" button. For extra robustness, test them out
      // of sequential order. They are 0-indexed, and get renumbered after each
      // iteration, so [1, 1, 0] means:
      // - First remove the 2nd file.
      // - Then remove what is then the 2nd file (was originally the 3rd file).
      // - Then remove the first file.
      foreach ([1, 1, 0] as $delta) {
        // Ensure we have the expected number of Remove buttons, and that they
        // are numbered sequentially.
        $buttons = $this->xpath('//input[@type="submit" and @value="Remove"]');
        $this->assertCount($num_expected_remove_buttons, $buttons, "There are $num_expected_remove_buttons \"Remove\" buttons displayed.");
        foreach ($buttons as $i => $button) {
          $key = $i >= $remaining ? $i - $remaining : $i;
          $check_field_name = $field_name2;
          if ($current_field_name == $field_name && $i < $remaining) {
            $check_field_name = $field_name;
          }

          $this->assertSame($check_field_name . '_' . $key . '_remove_button', $button->getAttribute('name'));
        }

        // "Click" the remove button (emulating either a nojs or js submission).
        $button_name = $current_field_name . '_' . $delta . '_remove_button';
        $this->getSession()->getPage()->findButton($button_name)->press();
        $num_expected_remove_buttons--;
        $remaining--;

        // Ensure an "Upload" button for the current field is displayed with the
        // correct name.
        $upload_button_name = $current_field_name . '_' . $remaining . '_upload_button';
        $button = $this->assertSession()->buttonExists($upload_button_name);
        $this->assertSame('Upload', $button->getValue());

        // Ensure only at most one button per field is displayed.
        $expected = $current_field_name == $field_name ? 1 : 2;
        $this->assertSession()->elementsCount('xpath', '//input[@type="submit" and @value="Upload"]', $expected);
      }
    }

    // Ensure the page now has no Remove buttons.
    $this->assertSession()->buttonNotExists('Remove');

    // Save the node and ensure it does not have any files.
    $this->submitForm(['title[0][value]' => $this->randomMachineName()], 'Save');
    preg_match('/node\/([0-9])/', $this->getUrl(), $matches);
    $nid = $matches[1];
    $node = $node_storage->loadUnchanged($nid);
    $this->assertEmpty($node->{$field_name}->target_id, 'Node was successfully saved without any files.');

    // Try to upload more files than allowed on revision.
    $upload_files_node_revision = [$test_file, $test_file, $test_file, $test_file];
    foreach ($upload_files_node_revision as $i => $file) {
      $edit['files[test_file_field_1_0][' . $i . ']'] = \Drupal::service('file_system')->realpath($test_file->getFileUri());
    }

    // @todo Replace after https://www.drupal.org/project/drupal/issues/2917885
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldExists('files[test_file_field_1_0][]');
    $submit_xpath = $this->assertSession()->buttonExists('Save')->getXpath();
    $client = $this->getSession()->getDriver()->getClient();
    $form = $client->getCrawler()->filterXPath($submit_xpath)->form();
    $client->request($form->getMethod(), $form->getUri(), $form->getPhpValues(), $edit);

    $node = $node_storage->loadUnchanged($nid);
    $this->assertCount($cardinality, $node->{$field_name}, 'More files than allowed could not be saved to node.');

    $upload_files_node_creation = [$test_file, $test_file];
    // Try to upload multiple files, but fewer than the maximum.
    $nid = $this->uploadNodeFiles($upload_files_node_creation, $field_name, $type_name, TRUE, []);
    $node = $node_storage->loadUnchanged($nid);
    $this->assertSameSize($upload_files_node_creation, $node->{$field_name}, 'Node was successfully saved with multiple files.');

    // Try to upload exactly the allowed number of files on revision.
    $this->uploadNodeFile($test_file, $field_name, $node->id(), 1);
    $node = $node_storage->loadUnchanged($nid);
    $this->assertCount($cardinality, $node->{$field_name}, 'Node was successfully revised to maximum number of files.');

    // Try to upload exactly the allowed number of files, new node.
    $upload_files = [$test_file, $test_file, $test_file];
    $nid = $this->uploadNodeFiles($upload_files, $field_name, $type_name, TRUE, []);
    $node = $node_storage->loadUnchanged($nid);
    $this->assertCount($cardinality, $node->{$field_name}, 'Node was successfully saved with maximum number of files.');
  }

  /**
   * Tests a file field with a "Private files" upload destination setting.
   */
  public function testPrivateFileSetting(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    // Grant the admin user required permissions.
    user_role_grant_permissions($this->adminUser->roles[0]->target_id, ['administer node fields']);

    $type_name = 'article';
    $field_name = $this->randomMachineName();
    $this->createFileField($field_name, 'node', $type_name);
    $field = FieldConfig::loadByName('node', $type_name, $field_name);
    $field_id = $field->id();

    $test_file = $this->getTestFile('text');

    // Change the field setting to make its files private, and upload a file.
    $edit = ['field_storage[subform][settings][uri_scheme]' => 'private'];
    $this->drupalGet("admin/structure/types/manage/{$type_name}/fields/{$field_id}");
    $this->submitForm($edit, 'Save');
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $node = $node_storage->loadUnchanged($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file->getFileUri());

    // Ensure the private file is available to the user who uploaded it.
    $this->drupalGet($node_file->createFileUrl());
    $this->assertSession()->statusCodeEquals(200);

    // Ensure we can't change 'uri_scheme' field settings while there are some
    // entities with uploaded files.
    $this->drupalGet("admin/structure/types/manage/$type_name/fields/$field_id");
    $this->assertSession()->fieldDisabled("edit-field-storage-subform-settings-uri-scheme-public");

    // Delete node and confirm that setting could be changed.
    $node->delete();
    $this->drupalGet("admin/structure/types/manage/$type_name/fields/$field_id");
    $this->assertSession()->fieldEnabled("edit-field-storage-subform-settings-uri-scheme-public");
  }

  /**
   * Tests that download restrictions on private files work on comments.
   */
  public function testPrivateFileComment(): void {
    $user = $this->drupalCreateUser(['access comments']);

    // Grant the admin user required comment permissions.
    $roles = $this->adminUser->getRoles();
    user_role_grant_permissions($roles[1], ['administer comment fields', 'administer comments']);

    // Revoke access comments permission from anon user, grant post to
    // authenticated.
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, ['access comments']);
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['post comments', 'skip comment approval']);

    // Create a new field.
    $this->addDefaultCommentField('node', 'article');

    $name = $this->randomMachineName();
    $label = $this->randomMachineName();
    $storage_edit = ['settings[uri_scheme]' => 'private'];
    $this->fieldUIAddNewField('admin/structure/comment/manage/comment', $name, $label, 'file', $storage_edit);

    // Manually clear cache on the tester side.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Create node.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // Add a comment with a file.
    $text_file = $this->getTestFile('text');
    $edit = [
      'files[field_' . $name . '_' . 0 . ']' => \Drupal::service('file_system')->realpath($text_file->getFileUri()),
      'comment_body[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalGet('node/' . $node->id());
    $this->submitForm($edit, 'Save');

    // Get the comment ID.
    preg_match('/comment-([0-9]+)/', $this->getUrl(), $matches);
    $cid = $matches[1];

    // Log in as normal user.
    $this->drupalLogin($user);

    $comment = Comment::load($cid);
    $comment_file = $comment->{'field_' . $name}->entity;
    $this->assertFileExists($comment_file->getFileUri());
    // Test authenticated file download.
    $url = $comment_file->createFileUrl();
    $this->assertNotNull($url, 'Confirmed that the URL is valid');
    $this->drupalGet($comment_file->createFileUrl());
    $this->assertSession()->statusCodeEquals(200);

    // Ensure that the anonymous user cannot download the file.
    $this->drupalLogout();
    $this->drupalGet($comment_file->createFileUrl());
    $this->assertSession()->statusCodeEquals(403);

    // Unpublishes node.
    $this->drupalLogin($this->adminUser);
    $edit = ['status[value]' => FALSE];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Ensures normal user can no longer download the file.
    $this->drupalLogin($user);
    $this->drupalGet($comment_file->createFileUrl());
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests validation with the Upload button.
   */
  public function testWidgetValidation(): void {
    $type_name = 'article';
    $field_name = $this->randomMachineName();
    $this->createFileField($field_name, 'node', $type_name);
    $this->updateFileField($field_name, $type_name, ['file_extensions' => 'txt']);

    // Create node and prepare files for upload.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $nid = $node->id();
    $this->drupalGet("node/$nid/edit");
    $test_file_text = $this->getTestFile('text');
    $test_file_image = $this->getTestFile('image');
    $name = 'files[' . $field_name . '_0]';

    // Upload file with incorrect extension, check for validation error.
    $edit[$name] = \Drupal::service('file_system')->realpath($test_file_image->getFileUri());
    $this->submitForm($edit, 'Upload');

    $this->assertSession()->pageTextContains("Only files with the following extensions are allowed: txt.");

    // Upload file with correct extension, check that error message is removed.
    $edit[$name] = \Drupal::service('file_system')->realpath($test_file_text->getFileUri());
    $this->submitForm($edit, 'Upload');
    $this->assertSession()->pageTextNotContains("Only files with the following extensions are allowed: txt.");
  }

  /**
   * Tests file widget element.
   */
  public function testWidgetElement(): void {
    $field_name = $this->randomMachineName();
    $html_name = str_replace('_', '-', $field_name);
    $this->createFileField($field_name, 'node', 'article', ['cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED]);
    $file = $this->getTestFile('text');
    $xpath = "//details[@data-drupal-selector='edit-$html_name']/table";

    $this->drupalGet('node/add/article');

    // If the field has no item, the table should not be visible.
    $this->assertSession()->elementNotExists('xpath', $xpath);

    // Upload a file.
    $edit['files[' . $field_name . '_0][]'] = $this->container->get('file_system')->realpath($file->getFileUri());
    $this->submitForm($edit, "{$field_name}_0_upload_button");

    // If the field has at least one item, the table should be visible.
    $this->assertSession()->elementsCount('xpath', $xpath, 1);

    // Test for AJAX error when using progress bar on file field widget.
    $http_client = $this->getHttpClient();
    $key = $this->randomMachineName();
    $post_request = $http_client->request('POST', $this->buildUrl('file/progress/' . $key), [
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
      'http_errors' => FALSE,
    ]);
    $this->assertNotEquals(500, $post_request->getStatusCode());
    $body = Json::decode($post_request->getBody());
    $this->assertStringContainsString('Starting upload...', $body['message']);
  }

  /**
   * Tests exploiting the temporary file removal of another user using fid.
   */
  public function testTemporaryFileRemovalExploit(): void {
    // Create a victim user.
    $victim_user = $this->drupalCreateUser();

    // Create an attacker user.
    $attacker_user = $this->drupalCreateUser([
      'access content',
      'create article content',
      'edit any article content',
    ]);

    // Log in as the attacker user.
    $this->drupalLogin($attacker_user);

    // Perform tests using the newly created users.
    $this->doTestTemporaryFileRemovalExploit($victim_user, $attacker_user);
  }

  /**
   * Tests exploiting the temporary file removal for anonymous users using fid.
   */
  public function testTemporaryFileRemovalExploitAnonymous(): void {
    // Set up an anonymous victim user.
    $victim_user = User::getAnonymousUser();

    // Set up an anonymous attacker user.
    $attacker_user = User::getAnonymousUser();

    // Set up permissions for anonymous attacker user.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, [
      'access content' => TRUE,
      'create article content' => TRUE,
      'edit any article content' => TRUE,
    ]);

    // Log out so as to be the anonymous attacker user.
    $this->drupalLogout();

    // Perform tests using the newly set up anonymous users.
    $this->doTestTemporaryFileRemovalExploit($victim_user, $attacker_user);
  }

  /**
   * Tests maximum upload file size validation.
   */
  public function testMaximumUploadFileSizeValidation(): void {
    // Grant the admin user required permissions.
    user_role_grant_permissions($this->adminUser->roles[0]->target_id, ['administer node fields']);

    $type_name = 'article';
    $field_name = $this->randomMachineName();
    $this->createFileField($field_name, 'node', $type_name);
    /** @var \Drupal\Field\FieldConfigInterface $field */
    $field = FieldConfig::loadByName('node', $type_name, $field_name);
    $field_id = $field->id();
    $this->drupalGet("admin/structure/types/manage/$type_name/fields/$field_id");

    // Tests that form validation trims the user input.
    $edit = ['settings[max_filesize]' => ' 5.1 megabytes '];
    $this->submitForm($edit, 'Save settings');
    $this->assertSession()->pageTextContains('Saved ' . $field_name . ' configuration.');

    // Reload the field config to check for the saved value.
    /** @var \Drupal\Field\FieldConfigInterface $field */
    $field = FieldConfig::loadByName('node', $type_name, $field_name);
    $settings = $field->getSettings();
    $this->assertEquals('5.1 megabytes', $settings['max_filesize'], 'The max filesize value had been trimmed on save.');
  }

  /**
   * Tests configuring file field's allowed file extensions setting.
   */
  public function testFileExtensionsSetting(): void {
    // Grant the admin user required permissions.
    user_role_grant_permissions($this->adminUser->roles[0]->target_id, ['administer node fields']);

    $type_name = 'article';
    $field_name = $this->randomMachineName();
    $this->createFileField($field_name, 'node', $type_name);
    $field = FieldConfig::loadByName('node', $type_name, $field_name);
    $field_id = $field->id();

    // By default allowing .php files without .txt is not permitted.
    $this->drupalGet("admin/structure/types/manage/$type_name/fields/$field_id");
    $edit = ['settings[file_extensions]' => 'jpg php'];
    $this->submitForm($edit, 'Save settings');
    $this->assertSession()->pageTextContains('Add txt to the list of allowed extensions to securely upload files with a php extension. The txt extension will then be added automatically.');

    // Test allowing .php and .txt.
    $edit = ['settings[file_extensions]' => 'jpg php txt'];
    $this->submitForm($edit, 'Save settings');
    $this->assertSession()->pageTextContains('Saved ' . $field_name . ' configuration.');

    // If the system is configured to allow insecure uploads, .txt is not
    // required when allowing .php.
    $this->config('system.file')->set('allow_insecure_uploads', TRUE)->save();
    $this->drupalGet("admin/structure/types/manage/$type_name/fields/$field_id");
    $edit = ['settings[file_extensions]' => 'jpg php'];
    $this->submitForm($edit, 'Save settings');
    $this->assertSession()->pageTextContains('Saved ' . $field_name . ' configuration.');

    // Check that a file extension with an underscore can be configured.
    $edit = [
      'settings[file_extensions]' => 'x_t x.t xt x_y_t',
    ];
    $this->drupalGet("admin/structure/types/manage/$type_name/fields/$field_id");
    $this->submitForm($edit, 'Save settings');
    $field = FieldConfig::loadByName('node', $type_name, $field_name);
    $this->assertEquals('x_t x.t xt x_y_t', $field->getSetting('file_extensions'));

    // Check that a file field with an invalid value in allowed extensions
    // property throws an error message.
    $invalid_extensions = ['x_.t', 'x._t', 'xt_', 'x__t', '_xt'];
    foreach ($invalid_extensions as $value) {
      $edit = [
        'settings[file_extensions]' => $value,
      ];
      $this->drupalGet("admin/structure/types/manage/$type_name/fields/$field_id");
      $this->submitForm($edit, 'Save settings');
      $this->assertSession()->pageTextContains("The list of allowed extensions is not valid. Allowed characters are a-z, 0-9, '.', and '_'. The first and last characters cannot be '.' or '_', and these two characters cannot appear next to each other. Separate extensions with a comma or space.");
    }
  }

  /**
   * Helper for testing exploiting the temporary file removal using fid.
   *
   * @param \Drupal\user\UserInterface $victim_user
   *   The victim user.
   * @param \Drupal\user\UserInterface $attacker_user
   *   The attacker user.
   */
  protected function doTestTemporaryFileRemovalExploit(UserInterface $victim_user, UserInterface $attacker_user): void {
    $type_name = 'article';
    $field_name = 'test_file_field';
    $this->createFileField($field_name, 'node', $type_name);

    $test_file = $this->getTestFile('text');
    $type = 'no-js';
    // Create a temporary file owned by the victim user. This will be as if
    // they had uploaded the file, but not saved the node they were editing
    // or creating.
    $victim_tmp_file = $this->createTemporaryFile('some text', $victim_user);
    $victim_tmp_file = File::load($victim_tmp_file->id());
    $this->assertTrue($victim_tmp_file->isTemporary(), 'New file saved to disk is temporary.');
    $this->assertNotEmpty($victim_tmp_file->id(), 'New file has an fid.');
    $this->assertEquals($victim_user->id(), $victim_tmp_file->getOwnerId(), 'New file belongs to the victim.');

    // Have attacker create a new node with a different uploaded file and
    // ensure it got uploaded successfully.
    $edit = [
      'title[0][value]' => $type . '-title',
    ];

    // Attach a file to a node.
    $edit['files[' . $field_name . '_0]'] = $this->container->get('file_system')->realpath($test_file->getFileUri());
    $this->drupalGet(Url::fromRoute('node.add', ['node_type' => $type_name]));
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    /** @var \Drupal\file\FileInterface $node_file */
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file->getFileUri());
    $this->assertEquals($attacker_user->id(), $node_file->getOwnerId(), 'New file belongs to the attacker.');

    // Ensure the file can be downloaded.
    $this->drupalGet($node_file->createFileUrl());
    $this->assertSession()->statusCodeEquals(200);

    // "Click" the remove button (emulating either a nojs or js submission).
    // In this POST request, the attacker "guesses" the fid of the victim's
    // temporary file and uses that to remove this file.
    $this->drupalGet($node->toUrl('edit-form'));

    $file_id_field = $this->assertSession()->hiddenFieldExists($field_name . '[0][fids]');
    $file_id_field->setValue((string) $victim_tmp_file->id());
    $this->submitForm([], 'Remove');

    // The victim's temporary file should not be removed by the attacker's
    // POST request.
    $this->assertFileExists($victim_tmp_file->getFileUri());
  }

}
