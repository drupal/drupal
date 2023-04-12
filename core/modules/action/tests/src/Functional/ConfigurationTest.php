<?php

namespace Drupal\Tests\action\Functional;

use Drupal\system\Entity\Action;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests complex actions configuration.
 *
 * @group action
 */
class ConfigurationTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['action'];


  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests configuration of advanced actions through administration interface.
   */
  public function testActionConfiguration() {
    // Create a user with permission to view the actions administration pages.
    $user = $this->drupalCreateUser(['administer actions']);
    $this->drupalLogin($user);

    // Make a POST request to admin/config/system/actions.
    $edit = [];
    $edit['action'] = 'action_goto_action';
    $this->drupalGet('admin/config/system/actions');
    $this->submitForm($edit, 'Create');
    $this->assertSession()->statusCodeEquals(200);

    // Make a POST request to the individual action configuration page.
    $edit = [];
    $action_label = $this->randomMachineName();
    $edit['label'] = $action_label;
    $edit['id'] = strtolower($action_label);
    $edit['url'] = 'admin';
    $this->drupalGet('admin/config/system/actions/add/action_goto_action');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    $action_id = $edit['id'];

    // Make sure that the new complex action was saved properly.
    $this->assertSession()->pageTextContains('The action has been successfully saved.');
    // The action label appears on the configuration page.
    $this->assertSession()->pageTextContains($action_label);

    // Make another POST request to the action edit page.
    $this->clickLink('Configure');

    $edit = [];
    $new_action_label = $this->randomMachineName();
    $edit['label'] = $new_action_label;
    $edit['url'] = 'admin';
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure that the action updated properly.
    $this->assertSession()->pageTextContains('The action has been successfully saved.');
    // The old action label does NOT appear on the configuration page.
    $this->assertSession()->pageTextNotContains($action_label);
    // The action label appears on the configuration page after we've updated
    // the complex action.
    $this->assertSession()->pageTextContains($new_action_label);

    // Make sure the URL appears when re-editing the action.
    $this->clickLink('Configure');
    $this->assertSession()->fieldValueEquals('url', 'admin');

    // Make sure that deletions work properly.
    $this->drupalGet('admin/config/system/actions');
    $this->clickLink('Delete');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [];
    $this->submitForm($edit, 'Delete');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure that the action was actually deleted.
    $this->assertSession()->pageTextContains("The action $new_action_label has been deleted.");
    $this->drupalGet('admin/config/system/actions');
    $this->assertSession()->statusCodeEquals(200);
    // The action label does not appear on the overview page.
    $this->assertSession()->pageTextNotContains($new_action_label);

    $action = Action::load($action_id);
    $this->assertNull($action, 'Make sure the action is gone after being deleted.');
  }

}
