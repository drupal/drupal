<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FieldCustomTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewsSqlTest;

/**
 * Tests the core Drupal\views\Plugin\views\field\Custom handler.
 */
class FieldCustomTest extends ViewsSqlTest {
  public static function getInfo() {
    return array(
      'name' => 'Field: Custom',
      'description' => 'Test the core Drupal\views\Plugin\views\field\Custom handler.',
      'group' => 'Views Handlers',
    );
  }

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test']['name']['field']['id'] = 'custom';
    return $data;
  }

  public function testFieldCustom() {
    $view = $this->getBasicView();

    // Alter the text of the field to a random string.
    $random = $this->randomName();
    $view->display['default']->handler->override_option('fields', array(
      'name' => array(
        'id' => 'name',
        'table' => 'views_test',
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
