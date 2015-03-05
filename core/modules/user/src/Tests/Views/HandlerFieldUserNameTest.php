<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\HandlerFieldUserNameTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the handler of the user: name field.
 *
 * @group user
 * @see views_handler_field_user_name
 */
class HandlerFieldUserNameTest extends UserTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_views_handler_field_user_name');

  public function testUserName() {
    $this->drupalLogin($this->drupalCreateUser(array('access user profiles')));

    // Set defaults.
    $view = Views::getView('test_views_handler_field_user_name');
    $view->initHandlers();
    $view->field['name']->options['link_to_user'] = TRUE;
    $view->field['name']->init($view, $view->getDisplay('default'));
    $this->executeView($view);

    $username = $view->result[0]->users_field_data_name = $this->randomMachineName();
    $view->result[0]->uid = 1;
    $render = $view->field['name']->advancedRender($view->result[0]);
    $this->assertTrue(strpos($render, $username) !== FALSE, 'If link to user is checked the username should be part of the output.');
    $this->assertTrue(strpos($render, 'user/1') !== FALSE, 'If link to user is checked the link to the user should appear as well.');

    $view->field['name']->options['link_to_user'] = FALSE;
    $username = $view->result[0]->users_field_data_name = $this->randomMachineName();
    $view->result[0]->uid = 1;
    $render = $view->field['name']->advancedRender($view->result[0]);
    $this->assertIdentical($render, $username, 'If the user is not linked the username should be printed out for a normal user.');

    $view->result[0]->uid = 0;
    $anon_name = $this->config('user.settings')->get('anonymous');
    $view->result[0]->users_field_data_name = '';
    $render = $view->field['name']->advancedRender($view->result[0]);
    $this->assertIdentical($render, $anon_name , 'For user0 it should use the default anonymous name by default.');

    $view->field['name']->options['overwrite_anonymous'] = TRUE;
    $anon_name = $view->field['name']->options['anonymous_text'] = $this->randomMachineName();
    $render = $view->field['name']->advancedRender($view->result[0]);
    $this->assertIdentical($render, $anon_name , 'For user0 it should use the configured anonymous text if overwrite_anonymous is checked.');
    $view->result[0]->uid = 1;
    $render = $view->field['name']->advancedRender($view->result[0]);
    $this->assertNotIdentical($render, $anon_name , 'For registered user it should not use the configured anonymous text if overwrite_anonymous is checked.');
  }

  /**
   * Tests that the field handler works when no additional fields are added.
   */
  public function testNoAdditionalFields() {
    $view = Views::getView('test_views_handler_field_user_name');
    $this->executeView($view);

    $username = $view->result[0]->users_field_data_name = $this->randomMachineName();
    $view->result[0]->users_field_data_uid = 1;
    $render = $view->field['name']->advancedRender($view->result[0]);
    $this->assertTrue(strpos($render, $username) !== FALSE, 'If link to user is checked the username should be part of the output.');
  }

}
