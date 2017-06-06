<?php

namespace Drupal\Tests\node\Functional;

use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Create a node and test node edit functionality.
 *
 * @group node
 */
class NodeEditFormTest extends NodeTestBase {

  /**
   * A normal logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * A user with permission to bypass content access checks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['block', 'node', 'datetime'];

  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(['edit own page content', 'create page content']);
    $this->adminUser = $this->drupalCreateUser(['bypass node access', 'administer nodes']);
    $this->drupalPlaceBlock('local_tasks_block');

    $this->nodeStorage = $this->container->get('entity.manager')->getStorage('node');
  }

  /**
   * Checks node edit functionality.
   */
  public function testNodeEdit() {
    $this->drupalLogin($this->webUser);

    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    // Create node to edit.
    $edit = [];
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit[$title_key]);
    $this->assertTrue($node, 'Node found in database.');

    // Check that "edit" link points to correct page.
    $this->clickLink(t('Edit'));
    $this->assertUrl($node->url('edit-form', ['absolute' => TRUE]));

    // Check that the title and body fields are displayed with the correct values.
    // @todo Ideally assertLink would support HTML, but it doesn't.
    $this->assertRaw('Edit<span class="visually-hidden">(active tab)</span>', 'Edit tab found and marked active.');
    $this->assertFieldByName($title_key, $edit[$title_key], 'Title field displayed.');
    $this->assertFieldByName($body_key, $edit[$body_key], 'Body field displayed.');

    // Edit the content of the node.
    $edit = [];
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    // Stay on the current page, without reloading.
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check that the title and body fields are displayed with the updated values.
    $this->assertText($edit[$title_key], 'Title displayed.');
    $this->assertText($edit[$body_key], 'Body displayed.');

    // Log in as a second administrator user.
    $second_web_user = $this->drupalCreateUser(['administer nodes', 'edit any page content']);
    $this->drupalLogin($second_web_user);
    // Edit the same node, creating a new revision.
    $this->drupalGet("node/" . $node->id() . "/edit");
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit['revision'] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));

    // Ensure that the node revision has been created.
    $revised_node = $this->drupalGetNodeByTitle($edit['title[0][value]'], TRUE);
    $this->assertNotIdentical($node->getRevisionId(), $revised_node->getRevisionId(), 'A new revision has been created.');
    // Ensure that the node author is preserved when it was not changed in the
    // edit form.
    $this->assertIdentical($node->getOwnerId(), $revised_node->getOwnerId(), 'The node author has been preserved.');
    // Ensure that the revision authors are different since the revisions were
    // made by different users.
    $first_node_version = node_revision_load($node->getRevisionId());
    $second_node_version = node_revision_load($revised_node->getRevisionId());
    $this->assertNotIdentical($first_node_version->getRevisionUser()->id(), $second_node_version->getRevisionUser()->id(), 'Each revision has a distinct user.');

    // Check if the node revision checkbox is rendered on node edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldById('edit-revision', NULL, 'The revision field is present.');

    // Check that details form element opens when there are errors on child
    // elements.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = [];
    // This invalid date will trigger an error.
    $edit['created[0][value][date]'] = $this->randomMachineName(8);
    // Get the current amount of open details elements.
    $open_details_elements = count($this->cssSelect('details[open="open"]'));
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    // The node author details must be open.
    $this->assertRaw('<details class="node-form-author js-form-wrapper form-wrapper" data-drupal-selector="edit-author" id="edit-author" open="open">');
    // Only one extra details element should now be open.
    $open_details_elements++;
    $this->assertEqual(count($this->cssSelect('details[open="open"]')), $open_details_elements, 'Exactly one extra open &lt;details&gt; element found.');
  }

  /**
   * Tests changing a node's "authored by" field.
   */
  public function testNodeEditAuthoredBy() {
    $this->drupalLogin($this->adminUser);

    // Create node to edit.
    $body_key = 'body[0][value]';
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));

    // Check that the node was authored by the currently logged in user.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertIdentical($node->getOwnerId(), $this->adminUser->id(), 'Node authored by admin user.');

    $this->checkVariousAuthoredByValues($node, 'uid[0][target_id]');

    // Check that normal users cannot change the authored by information.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertNoFieldByName('uid[0][target_id]');

    // Now test with the Autcomplete (Tags) field widget.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = \Drupal::entityManager()->getStorage('entity_form_display')->load('node.page.default');
    $widget = $form_display->getComponent('uid');
    $widget['type'] = 'entity_reference_autocomplete_tags';
    $widget['settings'] = [
      'match_operator' => 'CONTAINS',
      'size' => 60,
      'placeholder' => '',
    ];
    $form_display->setComponent('uid', $widget);
    $form_display->save();

    $this->drupalLogin($this->adminUser);

    // Save the node without making any changes.
    $this->drupalPostForm('node/' . $node->id() . '/edit', [], t('Save and keep published'));
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertIdentical($this->webUser->id(), $node->getOwner()->id());

    $this->checkVariousAuthoredByValues($node, 'uid[target_id]');

    // Hide the 'authored by' field from the form.
    $form_display->removeComponent('uid')->save();

    // Check that saving the node without making any changes keeps the proper
    // author ID.
    $this->drupalPostForm('node/' . $node->id() . '/edit', [], t('Save and keep published'));
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertIdentical($this->webUser->id(), $node->getOwner()->id());
  }

  /**
   * Checks that the "authored by" works correctly with various values.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   * @param string $form_element_name
   *   The name of the form element to populate.
   */
  protected function checkVariousAuthoredByValues(NodeInterface $node, $form_element_name) {
    // Try to change the 'authored by' field to an invalid user name.
    $edit = [
      $form_element_name => 'invalid-name',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $this->assertRaw(t('There are no entities matching "%name".', ['%name' => 'invalid-name']));

    // Change the authored by field to an empty string, which should assign
    // authorship to the anonymous user (uid 0).
    $edit[$form_element_name] = '';
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $uid = $node->getOwnerId();
    // Most SQL database drivers stringify fetches but entities are not
    // necessarily stored in a SQL database. At the same time, NULL/FALSE/""
    // won't do.
    $this->assertTrue($uid === 0 || $uid === '0', 'Node authored by anonymous user.');

    // Go back to the edit form and check that the correct value is displayed
    // in the author widget.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $anonymous_user = User::getAnonymousUser();
    $expected = $anonymous_user->label() . ' (' . $anonymous_user->id() . ')';
    $this->assertFieldByName($form_element_name, $expected, 'Authored by field displays the correct value for the anonymous user.');

    // Change the authored by field to another user's name (that is not
    // logged in).
    $edit[$form_element_name] = $this->webUser->getUsername();
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertIdentical($node->getOwnerId(), $this->webUser->id(), 'Node authored by normal user.');
  }

}
