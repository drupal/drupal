<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Actions\ConfigurationTest.
 */

namespace Drupal\system\Tests\Actions;

use Drupal\simpletest\WebTestBase;

/**
 * Actions configuration.
 */
class ConfigurationTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Actions configuration',
      'description' => 'Tests complex actions configuration by adding, editing, and deleting a complex action.',
      'group' => 'Actions',
    );
  }

  /**
   * Test the configuration of advanced actions through the administration
   * interface.
   */
  function testActionConfiguration() {
    // Create a user with permission to view the actions administration pages.
    $user = $this->drupalCreateUser(array('administer actions'));
    $this->drupalLogin($user);

    // Make a POST request to admin/config/system/actions/manage.
    $edit = array();
    $edit['action'] = drupal_hash_base64('system_goto_action');
    $this->drupalPost('admin/config/system/actions/manage', $edit, t('Create'));

    // Make a POST request to the individual action configuration page.
    $edit = array();
    $action_label = $this->randomName();
    $edit['actions_label'] = $action_label;
    $edit['url'] = 'admin';
    $this->drupalPost('admin/config/system/actions/configure/' . drupal_hash_base64('system_goto_action'), $edit, t('Save'));

    // Make sure that the new complex action was saved properly.
    $this->assertText(t('The action has been successfully saved.'), t("Make sure we get a confirmation that we've successfully saved the complex action."));
    $this->assertText($action_label, t("Make sure the action label appears on the configuration page after we've saved the complex action."));

    // Make another POST request to the action edit page.
    $this->clickLink(t('configure'));
    preg_match('|admin/config/system/actions/configure/(\d+)|', $this->getUrl(), $matches);
    $aid = $matches[1];
    $edit = array();
    $new_action_label = $this->randomName();
    $edit['actions_label'] = $new_action_label;
    $edit['url'] = 'admin';
    $this->drupalPost(NULL, $edit, t('Save'));

    // Make sure that the action updated properly.
    $this->assertText(t('The action has been successfully saved.'), t("Make sure we get a confirmation that we've successfully updated the complex action."));
    $this->assertNoText($action_label, t("Make sure the old action label does NOT appear on the configuration page after we've updated the complex action."));
    $this->assertText($new_action_label, t("Make sure the action label appears on the configuration page after we've updated the complex action."));

    // Make sure that deletions work properly.
    $this->clickLink(t('delete'));
    $edit = array();
    $this->drupalPost("admin/config/system/actions/delete/$aid", $edit, t('Delete'));

    // Make sure that the action was actually deleted.
    $this->assertRaw(t('Action %action was deleted', array('%action' => $new_action_label)), t('Make sure that we get a delete confirmation message.'));
    $this->drupalGet('admin/config/system/actions/manage');
    $this->assertNoText($new_action_label, t("Make sure the action label does not appear on the overview page after we've deleted the action."));
    $exists = db_query('SELECT aid FROM {actions} WHERE callback = :callback', array(':callback' => 'drupal_goto_action'))->fetchField();
    $this->assertFalse($exists, t('Make sure the action is gone from the database after being deleted.'));
  }
}
