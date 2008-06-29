<?php

class ActionsConfigurationTestCase extends DrupalWebTestCase {
  /**
   * Implementation of getInfo().
   */
  function getInfo() {
    return array(
      'name' => t('Actions configuration'),
      'description' => t('Tests complex actions configuration by adding, editing, and deleting a complex action.'),
      'group' => t('System'),
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

    // Make a POST request to admin/settings/actions/manage.
    $edit = array();
    $edit['action'] = md5('system_goto_action');
    $this->drupalPost('admin/settings/actions/manage', $edit, t('Create'));

    // Make a POST request to the individual action configuration page.
    $edit = array();
    $action_description = $this->randomName();
    $edit['actions_description'] = $action_description;
    $edit['url'] = 'admin';
    $this->drupalPost('admin/settings/actions/configure/' . md5('system_goto_action'), $edit, t('Save'));

    // Make sure that the new complex action was saved properly.
    $this->assertText(t('The action has been successfully saved.'), t("Make sure we get a confirmation that we've successfully saved the complex action."));
    $this->assertText($action_description, t("Make sure the action description appears on the configuration page after we've saved the complex action."));

    // Make another POST request to the action edit page.
    $this->clickLink(t('configure'));
    $edit = array();
    $new_action_description = $this->randomName();
    $edit['actions_description'] = $new_action_description;
    $edit['url'] = 'admin';
    $this->drupalPost('admin/settings/actions/configure/1', $edit, t('Save'));

    // Make sure that the action updated properly.
    $this->assertText(t('The action has been successfully saved.'), t("Make sure we get a confirmation that we've successfully updated the complex action."));
    $this->assertNoText($action_description, t("Make sure the old action description does NOT appear on the configuration page after we've updated the complex action."));
    $this->assertText($new_action_description, t("Make sure the action description appears on the configuration page after we've updated the complex action."));

    // Make sure that deletions work properly.
    $this->clickLink(t('delete'));
    $edit = array();
    $this->drupalPost('admin/settings/actions/delete/1', $edit, t('Delete'));

    // Make sure that the action was actually deleted.
    $this->assertRaw(t('Action %action was deleted', array('%action' => $new_action_description)), t('Make sure that we get a delete confirmation message.'));
    $this->drupalGet('admin/settings/actions/manage');
    $this->assertNoText($new_action_description, t("Make sure the action description does not appear on the overview page after we've deleted the action."));
    $exists = db_result(db_query("SELECT aid FROM {actions} WHERE callback = 'drupal_goto_action'"));
    $this->assertFalse($exists, t('Make sure the action is gone from the database after being deleted.'));
  }
}
