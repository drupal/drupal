<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\ArgumentStringTest.
 */

namespace Drupal\views\Tests\Handler;

/**
 * Tests the core Drupal\views\Plugin\views\argument\String handler.
 */
class ArgumentStringTest extends HandlerTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_glossary');

  public static function getInfo() {
    return array(
      'name' => 'Argument: String',
      'description' => 'Test the core Drupal\views\Plugin\views\argument\String handler.',
      'group' => 'Views Handlers',
    );
  }

  /**
   * Tests the glossary feature.
   */
  function testGlossary() {
    // Setup some nodes, one with a, two with b and three with c.
    $counter = 1;
    foreach (array('a', 'b', 'c') as $char) {
      for ($i = 0; $i < $counter; $i++) {
        $edit = array(
          'title' => $char . $this->randomName(),
        );
        $this->drupalCreateNode($edit);
      }
    }

    $view = views_get_view('test_glossary');
    $this->executeView($view);

    $count_field = 'nid';
    foreach ($view->result as &$row) {
      if (strpos($view->field['title']->get_value($row), 'a') === 0) {
        $this->assertEqual(1, $row->{$count_field});
      }
      if (strpos($view->field['title']->get_value($row), 'b') === 0) {
        $this->assertEqual(2, $row->{$count_field});
      }
      if (strpos($view->field['title']->get_value($row), 'c') === 0) {
        $this->assertEqual(3, $row->{$count_field});
      }
    }
  }

}
