<?php

namespace Drupal\Tests\user\Kernel\Views;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the user data service field handler.
 *
 * @group user
 *
 * @see \Drupal\user\Plugin\views\field\UserData
 */
class UserDataTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_user_data'];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user_test_views'];

  /**
   * Tests field handler.
   */
  public function testDataField() {
    ViewTestData::createTestViews(get_class($this), ['user_test_views']);

    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);

    $user = User::create([
      // Set 'uid' because the 'test_user_data' view filters the user with an ID
      // equal to 2.
      'uid' => 2,
      'name' => $this->randomMachineName(),
    ]);
    $user->save();

    // Add some random value as user data.
    $user_data = $this->container->get('user.data');
    $random_value = $this->randomMachineName();
    $user_data->set('views_test_config', $user->id(), 'test_value_name', $random_value);

    $view = Views::getView('test_user_data');
    $this->executeView($view);

    $output = $view->field['data']->render($view->result[0]);
    // Assert that using a valid user data key renders the value.
    $this->assertEquals($random_value, $output);

    $view->field['data']->options['data_name'] = $this->randomMachineName();

    $output = $view->field['data']->render($view->result[0]);
    // An invalid configuration does not return anything.
    $this->assertNull($output);
  }

}
