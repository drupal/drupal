<?php

/**
 * @file
 * Definition of Drupal\views\Tests\WizardSortingTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests the ability of the views wizard to create views with sorts.
 */
class WizardSortingTest extends WizardTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Views UI wizard sorting functionality',
      'description' => 'Test the ability of the views wizard to create views with sorts.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests the sorting functionality.
   */
  function testSorting() {
    // Create nodes, each with a different creation time so that we can do a
    // meaningful sort.
    $node1 = $this->drupalCreateNode(array('created' => REQUEST_TIME));
    $node2 = $this->drupalCreateNode(array('created' => REQUEST_TIME + 1));
    $node3 = $this->drupalCreateNode(array('created' => REQUEST_TIME + 2));

    // Create a view that sorts oldest first.
    $view1 = array();
    $view1['human_name'] = $this->randomName(16);
    $view1['name'] = strtolower($this->randomName(16));
    $view1['description'] = $this->randomName(16);
    $view1['show[sort]'] = 'created:ASC';
    $view1['page[create]'] = 1;
    $view1['page[title]'] = $this->randomName(16);
    $view1['page[path]'] = $this->randomName(16);
    $this->drupalPost('admin/structure/views/add', $view1, t('Save & exit'));

    // Make sure the view shows the nodes in the expected order.
    $this->assertUrl($view1['page[path]']);
    $this->assertText($view1['page[title]']);
    $content = $this->drupalGetContent();
    $this->assertText($node1->title);
    $this->assertText($node2->title);
    $this->assertText($node3->title);
    $pos1 = strpos($content, $node1->title);
    $pos2 = strpos($content, $node2->title);
    $pos3 = strpos($content, $node3->title);
    $this->assertTrue($pos1 < $pos2 && $pos2 < $pos3, t('The nodes appear in the expected order in a view that sorts by oldest first.'));

    // Create a view that sorts newest first.
    $view2 = array();
    $view2['human_name'] = $this->randomName(16);
    $view2['name'] = strtolower($this->randomName(16));
    $view2['description'] = $this->randomName(16);
    $view2['show[sort]'] = 'created:DESC';
    $view2['page[create]'] = 1;
    $view2['page[title]'] = $this->randomName(16);
    $view2['page[path]'] = $this->randomName(16);
    $this->drupalPost('admin/structure/views/add', $view2, t('Save & exit'));

    // Make sure the view shows the nodes in the expected order.
    $this->assertUrl($view2['page[path]']);
    $this->assertText($view2['page[title]']);
    $content = $this->drupalGetContent();
    $this->assertText($node3->title);
    $this->assertText($node2->title);
    $this->assertText($node1->title);
    $pos3 = strpos($content, $node3->title);
    $pos2 = strpos($content, $node2->title);
    $pos1 = strpos($content, $node1->title);
    $this->assertTrue($pos3 < $pos2 && $pos2 < $pos1, t('The nodes appear in the expected order in a view that sorts by newest first.'));
  }
}

