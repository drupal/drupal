<?php

namespace Drupal\Tests\node\Functional;

use Drupal\node\Entity\NodeType;

/**
 * Tests the revision tab display.
 *
 * This test is similar to NodeRevisionsUITest except that it uses a user with
 * the bypass node access permission to make sure that the revision access
 * check adds correct cacheability metadata.
 *
 * @group node
 */
class NodeRevisionsUiBypassAccessTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with bypass node access permission.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $editor;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a user.
    $this->editor = $this->drupalCreateUser([
      'administer nodes',
      'edit any page content',
      'view page revisions',
      'bypass node access',
      'access user profiles',
    ]);
  }

  /**
   * Checks that the Revision tab is displayed correctly.
   */
  public function testDisplayRevisionTab() {
    $this->drupalPlaceBlock('local_tasks_block');

    $this->drupalLogin($this->editor);

    // Set page revision setting 'create new revision'. This will mean new
    // revisions are created by default when the node is edited.
    $type = NodeType::load('page');
    $type->setNewRevision(TRUE);
    $type->save();

    // Create the node.
    $node = $this->drupalCreateNode();

    // Verify the checkbox is checked on the node edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->checkboxChecked('edit-revision');

    // Uncheck the create new revision checkbox and save the node.
    $edit = ['revision' => FALSE];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->addressEquals($node->toUrl());
    // Verify revisions exist since the content type has revisions enabled.
    $this->assertSession()->linkExists('Revisions');

    // Verify the checkbox is checked on the node edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->checkboxChecked('edit-revision');

    // Submit the form without changing the checkbox.
    $edit = [];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->addressEquals($node->toUrl());
    $this->assertSession()->linkExists('Revisions');

    // Unset page revision setting 'create new revision'. This will mean new
    // revisions are not created by default when the node is edited.
    $type = NodeType::load('page');
    $type->setNewRevision(FALSE);
    $type->save();

    // Create the node.
    $node = $this->drupalCreateNode();

    // Verify the checkbox is unchecked on the node edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->checkboxNotChecked('edit-revision');
    // Submit the form without changing the checkbox.
    $edit = [];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->addressEquals($node->toUrl());
    // Verify that no link to revisions is displayed since the type
    // has the 'create new revision' setting unset.
    $this->assertSession()->linkNotExists('Revisions');

    // Verify the checkbox is unchecked on the node edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->checkboxNotChecked('edit-revision');

    // Check the 'create new revision' checkbox and save the node.
    $edit = ['revision' => TRUE];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->addressEquals($node->toUrl());
    // Verify that the link is displayed since a new revision is created and
    // the 'create new revision' checkbox on the node is checked.
    $this->assertSession()->linkExists('Revisions');
  }

}
