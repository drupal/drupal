<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;
use Drupal\system\Entity\Action;
use Drupal\user\Entity\User;

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
    $this->drupalPostForm('admin/config/system/actions', $edit, 'Create');
    $this->assertSession()->statusCodeEquals(200);

    // Make a POST request to the individual action configuration page.
    $edit = [];
    $action_label = $this->randomMachineName();
    $edit['label'] = $action_label;
    $edit['id'] = strtolower($action_label);
    $edit['owner_uid'] = $user->id();
    $this->drupalPostForm('admin/config/system/actions/add/node_assign_owner_action', $edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    $action_id = $edit['id'];

    // Make sure that the new action was saved properly.
    $this->assertText('The action has been successfully saved.', 'The node_assign_owner_action action has been successfully saved.');
    $this->assertText($action_label, 'The label of the node_assign_owner_action action appears on the actions administration page after saving.');

    // Make another POST request to the action edit page.
    $this->clickLink(t('Configure'));
    $edit = [];
    $new_action_label = $this->randomMachineName();
    $edit['label'] = $new_action_label;
    $edit['owner_uid'] = $user->id();
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure that the action updated properly.
    $this->assertText('The action has been successfully saved.', 'The node_assign_owner_action action has been successfully updated.');
    $this->assertNoText($action_label, 'The old label for the node_assign_owner_action action does not appear on the actions administration page after updating.');
    $this->assertText($new_action_label, 'The new label for the node_assign_owner_action action appears on the actions administration page after updating.');

    // Make sure that deletions work properly.
    $this->drupalGet('admin/config/system/actions');
    $this->clickLink(t('Delete'));
    $this->assertSession()->statusCodeEquals(200);
    $edit = [];
    $this->submitForm($edit, 'Delete');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure that the action was actually deleted.
    $this->assertRaw(t('The action %action has been deleted.', ['%action' => $new_action_label]));
    $this->drupalGet('admin/config/system/actions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoText($new_action_label, 'The label for the node_assign_owner_action action does not appear on the actions administration page after deleting.');

    $action = Action::load($action_id);
    $this->assertNull($action, 'The node_assign_owner_action action is not available after being deleted.');
  }

  /**
   * Tests the autocomplete field when configuring the AssignOwnerNode action.
   */
  public function testAssignOwnerNodeActionAutocomplete() {
    // Create 200 users to force the action's configuration page to use an
    // autocomplete field instead of a select field. See
    // \Drupal\node\Plugin\Action\AssignOwnerNode::buildConfigurationForm().
    for ($i = 0; $i < 200; $i++) {
      $this->drupalCreateUser();
    }

    // Create a user with permission to view the actions administration pages
    // and additionally permission to administer users. Otherwise the user would
    // not be able to reference the anonymous user.
    $this->drupalLogin($this->drupalCreateUser(['administer actions', 'administer users']));
    // Create AssignOwnerNode action.
    $this->drupalPostForm('admin/config/system/actions', ['action' => 'node_assign_owner_action'], 'Create');

    // Get the autocomplete URL of the owner_uid textfield.
    $autocomplete_field = $this->getSession()->getPage()->findField('owner_uid');
    $autocomplete_url = $this->getAbsoluteUrl($autocomplete_field->getAttribute('data-autocomplete-path'));

    // Make sure that autocomplete works.
    $user = $this->drupalCreateUser();
    $data = Json::decode($this->drupalGet($autocomplete_url, ['query' => ['q' => $user->getDisplayName(), '_format' => 'json']]));
    $this->assertNotEmpty($data);

    $anonymous = User::getAnonymousUser();
    // Ensure that the anonymous user exists.
    $this->assertNotNull($anonymous);
    // Make sure the autocomplete does not show the anonymous user.
    $data = Json::decode($this->drupalGet($autocomplete_url, ['query' => ['q' => $anonymous->getDisplayName(), '_format' => 'json']]));
    $this->assertEmpty($data);

  }

}
