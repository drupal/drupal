<?php

/**
 * @file
 * Definition of Drupal\views\Tests\GlossaryTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests glossary view ( summary of arguments ).
 */
class GlossaryTest extends ViewTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Glossary tests',
      'description' => 'Tests glossary functionality of views.',
      'group' => 'Views',
    );
  }

  /**
   * Tests the default glossary view.
   */
  public function testGlossaryView() {
    // create a contentype and add some nodes, with a non random title.
    $type = $this->drupalCreateContentType();
    $nodes_per_char = array(
      'd' => 1,
      'r' => 4,
      'u' => 10,
      'p' => 2,
      'a' => 3,
      'l' => 6,
    );
    foreach ($nodes_per_char as $char => $count) {
      $setting = array(
        'type' => $type->type
      );
      for ($i = 0; $i < $count; $i++) {
        $node = $setting;
        $node['title'] = $char . $this->randomString(3);
        $this->drupalCreateNode($node);
      }
    }

    // Execute glossary view
    $view = views_get_view('glossary');
    $view->setDisplay('attachment_1');
    $view->executeDisplay('attachment_1');

    // Check that the amount of nodes per char.
    $result_nodes_per_char = array();
    foreach ($view->result as $item) {
      $this->assertEqual($nodes_per_char[$item->title_truncated], $item->num_records);
    }
  }

}
