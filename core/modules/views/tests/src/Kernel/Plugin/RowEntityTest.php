<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Core\Form\FormState;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\user\Entity\User;
use Drupal\views\Views;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;

/**
 * Tests the generic entity row plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\row\EntityRow
 */
class RowEntityTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'entity_test',
    'field',
    'system',
    'user',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_entity_row'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
  }

  /**
   * Tests the entity row handler.
   */
  public function testEntityRow(): void {
    $user = User::create([
      'name' => 'test user',
    ]);
    $user->save();

    $entity_test = EntityTest::create([
      'user_id' => $user->id(),
      'name' => 'test entity test',
    ]);
    $entity_test->save();

    // Ensure entities have different ids.
    if ($entity_test->id() == $user->id()) {
      $entity_test->delete();
      $entity_test = EntityTest::create([
        'user_id' => $user->id(),
        'name' => 'test entity test',
      ]);
      $entity_test->save();
    }

    $view = Views::getView('test_entity_row');
    $build = $view->preview();
    $this->render($build);

    $this->assertText('test entity test');
    $this->assertNoText('Member for');

    // Change the view to use a relationship to render the row.
    $view = Views::getView('test_entity_row');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['row']['type'] = 'entity:user';
    $display['display_options']['row']['options']['relationship'] = 'user_id';
    $view->setDisplay('default');
    $build = $view->preview();
    $this->render($build);

    $this->assertNoText('test entity test');
    $this->assertText('Member for');

    // Tests the available view mode options.
    $form = [];
    $form_state = new FormState();
    $form_state->set('view', $view->storage);
    $view->rowPlugin->buildOptionsForm($form, $form_state);

    $this->assertTrue(isset($form['view_mode']['#options']['default']), 'Ensure that the default view mode is available');
  }

}
