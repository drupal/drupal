<?php

namespace Drupal\Tests\file\Functional;

use Drupal\node\Entity\Node;
use Drupal\user\RoleInterface;
use Drupal\file\Entity\File;

/**
 * Confirm that file field submissions work correctly for anonymous visitors.
 *
 * @group file
 */
class FileFieldAnonymousSubmissionTest extends FileFieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Set up permissions for anonymous attacker user.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, [
      'create article content' => TRUE,
      'access content' => TRUE,
    ]);
  }

  /**
   * Tests the basic node submission for an anonymous visitor.
   */
  public function testAnonymousNode() {
    $bundle_label = 'Article';
    $node_title = 'test page';

    // Load the node form.
    $this->drupalLogout();
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(strip_tags(t('Create @name', ['@name' => $bundle_label])));

    $edit = [
      'title[0][value]' => $node_title,
      'body[0][value]' => 'Test article',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $t_args = ['@type' => $bundle_label, '%title' => $node_title];
    $this->assertSession()->pageTextContains(strip_tags(t('@type %title has been created.', $t_args)));
    $matches = [];
    if (preg_match('@node/(\d+)$@', $this->getUrl(), $matches)) {
      $nid = end($matches);
      $this->assertNotEquals(0, $nid, 'The node ID was extracted from the URL.');
      $node = Node::load($nid);
      $this->assertNotNull($node, 'The node was loaded successfully.');
    }
  }

  /**
   * Tests file submission for an anonymous visitor.
   */
  public function testAnonymousNodeWithFile() {
    $bundle_label = 'Article';
    $node_title = 'Test page';
    $this->createFileField('field_image', 'node', 'article', [], ['file_extensions' => 'txt png']);

    // Load the node form.
    $this->drupalLogout();
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(strip_tags(t('Create @name', ['@name' => $bundle_label])));

    // Generate an image file.
    $image = $this->getTestFile('image');

    // Submit the form.
    $edit = [
      'title[0][value]' => $node_title,
      'body[0][value]' => 'Test article',
      'files[field_image_0]' => $this->container->get('file_system')->realpath($image->getFileUri()),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $t_args = ['@type' => $bundle_label, '%title' => $node_title];
    $this->assertSession()->pageTextContains(strip_tags(t('@type %title has been created.', $t_args)));
    $matches = [];
    if (preg_match('@node/(\d+)$@', $this->getUrl(), $matches)) {
      $nid = end($matches);
      $this->assertNotEquals(0, $nid, 'The node ID was extracted from the URL.');
      $node = Node::load($nid);
      $this->assertNotNull($node, 'The node was loaded successfully.');
      $this->assertFileExists(File::load($node->field_image->target_id)->getFileUri());
    }
  }

  /**
   * Tests file submission for an anonymous visitor with a missing node title.
   */
  public function testAnonymousNodeWithFileWithoutTitle() {
    $this->drupalLogout();
    $this->doTestNodeWithFileWithoutTitle();
  }

  /**
   * Tests file submission for an authenticated user with a missing node title.
   */
  public function testAuthenticatedNodeWithFileWithoutTitle() {
    $admin_user = $this->drupalCreateUser([
      'bypass node access',
      'access content overview',
      'administer nodes',
    ]);
    $this->drupalLogin($admin_user);
    $this->doTestNodeWithFileWithoutTitle();
  }

  /**
   * Helper method to test file submissions with missing node titles.
   */
  protected function doTestNodeWithFileWithoutTitle() {
    $bundle_label = 'Article';
    $node_title = 'Test page';
    $this->createFileField('field_image', 'node', 'article', [], ['file_extensions' => 'txt png']);

    // Load the node form.
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(strip_tags(t('Create @name', ['@name' => $bundle_label])));

    // Generate an image file.
    $image = $this->getTestFile('image');

    // Submit the form but exclude the title field.
    $edit = [
      'body[0][value]' => 'Test article',
      'files[field_image_0]' => $this->container->get('file_system')->realpath($image->getFileUri()),
    ];
    if (!$this->loggedInUser) {
      $label = 'Save';
    }
    else {
      $label = 'Save';
    }
    $this->submitForm($edit, $label);
    $this->assertSession()->statusCodeEquals(200);
    $t_args = ['@type' => $bundle_label, '%title' => $node_title];
    $this->assertSession()->pageTextNotContains(strip_tags(t('@type %title has been created.', $t_args)));
    $this->assertSession()->pageTextContains('Title field is required.');

    // Submit the form again but this time with the missing title field. This
    // should still work.
    $edit = [
      'title[0][value]' => $node_title,
    ];
    $this->submitForm($edit, $label);

    // Confirm the final submission actually worked.
    $t_args = ['@type' => $bundle_label, '%title' => $node_title];
    $this->assertSession()->pageTextContains(strip_tags(t('@type %title has been created.', $t_args)));
    $matches = [];
    if (preg_match('@node/(\d+)$@', $this->getUrl(), $matches)) {
      $nid = end($matches);
      $this->assertNotEquals(0, $nid, 'The node ID was extracted from the URL.');
      $node = Node::load($nid);
      $this->assertNotNull($node, 'The node was loaded successfully.');
      $this->assertFileExists(File::load($node->field_image->target_id)->getFileUri());
    }
  }

}
