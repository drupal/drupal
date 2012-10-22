<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FieldCustomTest.
 */

namespace Drupal\views\Tests\Handler;

/**
 * Tests the core Drupal\views\Plugin\views\field\Custom handler.
 */
class FieldCustomTest extends HandlerTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Field: Custom',
      'description' => 'Test the core Drupal\views\Plugin\views\field\Custom handler.',
      'group' => 'Views Handlers',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['field']['id'] = 'custom';
    return $data;
  }

  public function testFieldCustom() {
    $view = $this->getView();

    // Alter the text of the field to a random string.
    $random = $this->randomName();
    $view->displayHandlers['default']->overrideOption('fields', array(
      'name' => array(
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'alter' => array(
          'text' => $random,
        ),
      ),
    ));

    $this->executeView($view);

    $this->assertEqual($random, $view->style_plugin->get_field(0, 'name'));
  }

}
