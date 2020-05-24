<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\system\Entity\Action;

/**
 * Tests configuration of actions provided by the Node module.
 *
 * @group node
 */
class NodeActionsConfigurationTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['action', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests configuration of the node_assign_owner_action action.
   */
  public function testAssignOwnerNodeActionConfiguration() {
    // Create a user with permission to view the actions administration pages.
    $user = $this->drupalCreateUser(['administer actions']);
    $this->drupalLogin($user);

    // Make a POST request to admin/config/system/actions.
    $edit = [];
    $edit['action'] = 'node_assign_owner_action';
    $this->drupalPostForm('admin/config/system/actions', $edit, t('Create'));
    $this->assertSession()->statusCodeEquals(200);

    // Make a POST request to the individual action configuration page.
    $edit = [];
    $action_label = $this->randomMachineName();
    $edit['label'] = $action_label;
    $edit['id'] = strtolower($action_label);
    $edit['owner_uid'] = $user->id();
    $this->drupalPostForm('admin/config/system/actions/add/node_assign_owner_action', $edit, t('Save'));
    $this->assertSession()->statusCodeEquals(200);

    $action_id = $edit['id'];

    // Make sure that the new action was saved properly.
    $this->assertText(t('The action has been successfully saved.'), 'The node_assign_owner_action action has been successfully saved.');
    $this->assertText($action_label, 'The label of the node_assign_owner_action action appears on the actions administration page after saving.');

    // Make another POST request to the action edit page.
    $this->clickLink(t('Configure'));
    $edit = [];
    $new_action_label = $this->randomMachineName();
    $edit['label'] = $new_action_label;
    $edit['owner_uid'] = $user->id();
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->statusCodeEquals(200);

    // Make sure that the action updated properly.
    $this->assertText(t('The action has been successfully saved.'), 'The node_assign_owner_action action has been successfully updated.');
    $this->assertNoText($action_label, 'The old label for the node_assign_owner_action action does not appear on the actions administration page after updating.');
    $this->assertText($new_action_label, 'The new label for the node_assign_owner_action action appears on the actions administration page after updating.');

    // Make sure that deletions work properly.
    $this->drupalGet('admin/config/system/actions');
    $this->clickLink(t('Delete'));
    $this->assertSession()->statusCodeEquals(200);
    $edit = [];
    $this->drupalPostForm(NULL, $edit, t('Delete'));
    $this->assertSession()->statusCodeEquals(200);

    // Make sure that the action was actually deleted.
    $this->assertRaw(t('The action %action has been deleted.', ['%action' => $new_action_label]), 'The delete confirmation message appears after deleting the node_assign_owner_action action.');
    $this->drupalGet('admin/config/system/actions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoText($new_action_label, 'The label for the node_assign_owner_action action does not appear on the actions administration page after deleting.');

    $action = Action::load($action_id);
    $this->assertNull($action, 'The node_assign_owner_action action is not available after being deleted.');
  }

}
