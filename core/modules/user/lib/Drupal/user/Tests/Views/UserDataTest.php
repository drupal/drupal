<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\UserDataTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the user data service field handler.
 *
 * @see \Drupal\user\Plugin\views\field\UserData
 */
class UserDataTest extends UserTestBase {

  /**
   * Provides the user data service object.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_user_data');

  public static function getInfo() {
    return array(
      'name' => 'User data: Field',
      'description' => 'Tests the user data service field handler.',
      'group' => 'Views module integration',
    );
  }

  /**
   * Tests field handler.
   */
  public function testDataField() {
    // But some random values into the user data service.
    $this->userData = $this->container->get('user.data');
    $random_value = $this->randomName();
    $this->userData->set('views_test_config', $this->users[0]->id(), 'test_value_name', $random_value);

    $view = Views::getView('test_user_data');
    $this->executeView($view);

    $output = $view->field['data']->render($view->result[0]);
    $this->assertEqual($output, $random_value, 'A valid user data got rendered.');

    $view->field['data']->options['data_name'] = $this->randomName();
    $output = $view->field['data']->render($view->result[0]);
    $this->assertFalse($output, 'An invalid configuration does not return anything');

  }

}
