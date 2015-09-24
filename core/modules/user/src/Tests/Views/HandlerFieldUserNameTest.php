<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\HandlerFieldUserNameTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\Core\Render\RenderContext;
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
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $new_user = $this->drupalCreateUser(array('access user profiles'));
    $this->drupalLogin($new_user);

    // Set defaults.
    $view = Views::getView('test_views_handler_field_user_name');
    $view->initHandlers();
    $view->field['name']->options['link_to_user'] = TRUE;
    $view->field['name']->options['type'] = 'user_name';
    $view->field['name']->init($view, $view->getDisplay('default'));
    $view->field['name']->options['id'] = 'name';
    $this->executeView($view);

    $anon_name = $this->config('user.settings')->get('anonymous');
    $view->result[0]->_entity->setUsername('');
    $view->result[0]->_entity->uid->value = 0;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertTrue(strpos($render, $anon_name) !== FALSE, 'For user 0 it should use the default anonymous name by default.');

    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $new_user) {
      return $view->field['name']->advancedRender($view->result[$new_user->id()]);
    });
    $this->assertTrue(strpos($render, $new_user->getDisplayName()) !== FALSE, 'If link to user is checked the username should be part of the output.');
    $this->assertTrue(strpos($render, 'user/' . $new_user->id()) !== FALSE, 'If link to user is checked the link to the user should appear as well.');

    $view->field['name']->options['link_to_user'] = FALSE;
    $view->field['name']->options['type'] = 'string';
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $new_user) {
      return $view->field['name']->advancedRender($view->result[$new_user->id()]);
    });
    $this->assertEqual($render, $new_user->getDisplayName(), 'If the user is not linked the username should be printed out for a normal user.');

  }

  /**
   * Tests that the field handler works when no additional fields are added.
   */
  public function testNoAdditionalFields() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_views_handler_field_user_name');
    $this->executeView($view);

    $username = $this->randomMachineName();
    $view->result[0]->_entity->setUsername($username);
    $view->result[0]->_entity->uid->value = 1;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertTrue(strpos($render, $username) !== FALSE, 'If link to user is checked the username should be part of the output.');
  }

}
