<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\HandlerFieldUserNameTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the field username handler.
 *
 * @see views_handler_field_user_name
 */
class HandlerFieldUserNameTest extends UserTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_views_handler_field_user_name');

  public static function getInfo() {
    return array(
      'name' => 'User: Name Field',
      'description' => 'Tests the handler of the user: name field.',
      'group' => 'Views module integration',
    );
  }

  public function testUserName() {
    $this->drupalLogin($this->drupalCreateUser(array('access user profiles')));

    $view = Views::getView('test_views_handler_field_user_name');
    $this->executeView($view);

    $view->row_index = 0;

    $view->field['name']->options['link_to_user'] = TRUE;
    $username = $view->result[0]->users_name = $this->randomName();
    $view->result[0]->uid = 1;
    $render = $view->field['name']->advancedRender($view->result[0]);
    $this->assertTrue(strpos($render, $username) !== FALSE, 'If link to user is checked the username should be part of the output.');
    $this->assertTrue(strpos($render, 'user/1') !== FALSE, 'If link to user is checked the link to the user should appear as well.');

    $view->field['name']->options['link_to_user'] = FALSE;
    $username = $view->result[0]->users_name = $this->randomName();
    $view->result[0]->uid = 1;
    $render = $view->field['name']->advancedRender($view->result[0]);
    $this->assertIdentical($render, $username, 'If the user is not linked the username should be printed out for a normal user.');

    $view->result[0]->uid = 0;
    $anon_name = \Drupal::config('user.settings')->get('anonymous');
    $view->result[0]->users_name = '';
    $render = $view->field['name']->advancedRender($view->result[0]);
    $this->assertIdentical($render, $anon_name , 'For user0 it should use the default anonymous name by default.');

    $view->field['name']->options['overwrite_anonymous'] = TRUE;
    $anon_name = $view->field['name']->options['anonymous_text'] = $this->randomName();
    $render = $view->field['name']->advancedRender($view->result[0]);
    $this->assertIdentical($render, $anon_name , 'For user0 it should use the configured anonymous text if overwrite_anonymous is checked.');
  }

}
