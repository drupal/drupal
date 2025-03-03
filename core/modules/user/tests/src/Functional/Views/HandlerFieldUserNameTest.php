<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional\Views;

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
  public static $testViews = ['test_views_handler_field_user_name'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the rendering of the user name field in Views.
   */
  public function testUserName(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $new_user = $this->drupalCreateUser(['access user profiles']);
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
    $render = (string) $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertStringContainsString($anon_name, $render, 'For user 0 it should use the default anonymous name by default.');

    $render = (string) $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $new_user) {
      return $view->field['name']->advancedRender($view->result[$new_user->id()]);
    });
    $this->assertStringContainsString($new_user->getDisplayName(), $render, 'If link to user is checked the username should be part of the output.');
    $this->assertStringContainsString('user/' . $new_user->id(), $render, 'If link to user is checked the link to the user should appear as well.');

    $view->field['name']->options['link_to_user'] = FALSE;
    $view->field['name']->options['type'] = 'string';
    $render = (string) $renderer->executeInRenderContext(new RenderContext(), function () use ($view, $new_user) {
      return $view->field['name']->advancedRender($view->result[$new_user->id()]);
    });
    $this->assertEquals($new_user->getDisplayName(), $render, 'If the user is not linked the username should be printed out for a normal user.');

  }

  /**
   * Tests that the field handler works when no additional fields are added.
   */
  public function testNoAdditionalFields(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_views_handler_field_user_name');
    $this->executeView($view);

    $username = $this->randomMachineName();
    $view->result[0]->_entity->setUsername($username);
    $view->result[0]->_entity->uid->value = 1;
    $render = (string) $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertStringContainsString($username, $render, 'If link to user is checked the username should be part of the output.');
  }

}
