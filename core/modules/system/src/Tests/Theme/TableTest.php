<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\TableTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests built-in table theme functions.
 *
 * @group Theme
 */
class TableTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'router');
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tableheader.js provides 'sticky' table headers, and is included by default.
   */
  function testThemeTableStickyHeaders() {
    $header = array('one', 'two', 'three');
    $rows = array(array(1,2,3), array(4,5,6), array(7,8,9));
    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
    );
    $this->render($table);
    $js = _drupal_add_js();
    $this->assertTrue(isset($js['core/misc/tableheader.js']), 'tableheader.js found.');
    $this->assertRaw('sticky-enabled');
    drupal_static_reset('_drupal_add_js');
  }

  /**
   * If $sticky is FALSE, no tableheader.js should be included.
   */
  function testThemeTableNoStickyHeaders() {
    $header = array('one', 'two', 'three');
    $rows = array(array(1,2,3), array(4,5,6), array(7,8,9));
    $attributes = array();
    $caption = NULL;
    $colgroups = array();
    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => $attributes,
      '#caption' => $caption,
      '#colgroups' => $colgroups,
      '#sticky' => FALSE,
    );
    $this->render($table);
    $js = _drupal_add_js();
    $this->assertFalse(isset($js['core/misc/tableheader.js']), 'tableheader.js not found.');
    $this->assertNoRaw('sticky-enabled');
    drupal_static_reset('_drupal_add_js');
  }

  /**
   * Tests that the table header is printed correctly even if there are no rows,
   * and that the empty text is displayed correctly.
   */
  function testThemeTableWithEmptyMessage() {
    $header = array(
      'Header 1',
      array(
        'data' => 'Header 2',
        'colspan' => 2,
      ),
    );
    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => array(),
      '#empty' => 'Empty row.',
    );
    $this->render($table);
    $this->removeWhiteSpace();
    $this->assertRaw('<thead><tr><th>Header 1</th><th colspan="2">Header 2</th></tr>', 'Table header found.');
    $this->assertRaw('<tr class="odd"><td colspan="3" class="empty message">Empty row.</td>', 'Colspan on #empty row found.');
  }

  /**
   * Tests that the 'no_striping' option works correctly.
   */
  function testThemeTableWithNoStriping() {
    $rows = array(
      array(
        'data' => array(1),
        'no_striping' => TRUE,
      ),
    );
    $table = array(
      '#type' => 'table',
      '#rows' => $rows,
    );
    $this->render($table);
    $this->assertNoRaw('class="odd"', 'Odd/even classes were not added because $no_striping = TRUE.');
    $this->assertNoRaw('no_striping', 'No invalid no_striping HTML attribute was printed.');
  }

  /**
   * Test that the 'footer' option works correctly.
   */
  function testThemeTableFooter() {
    $footer = array(
      array(
        'data' => array(1),
      ),
      array('Foo'),
    );

    $table = array(
      '#type' => 'table',
      '#rows' => array(),
      '#footer' => $footer,
    );

    $this->render($table);
    $this->removeWhiteSpace();
    $this->assertRaw('<tfoot><tr><td>1</td></tr><tr><td>Foo</td></tr></tfoot>', 'Table footer found.');
  }

  /**
   * Tests that the 'header' option in cells works correctly.
   */
  function testThemeTableHeaderCellOption() {
    $rows = array(
      array(
        array('data' => 1, 'header' => TRUE),
        array('data' => 1, 'header' => FALSE),
        array('data' => 1),
      ),
    );
    $table = array(
      '#type' => 'table',
      '#rows' => $rows,
    );
    $this->render($table);
    $this->removeWhiteSpace();
    $this->assertRaw('<th>1</th><td>1</td><td>1</td>', 'The th and td tags was printed correctly.');
  }

  /**
   * Tests that the 'responsive-table' class is applied correctly.
   */
  public function testThemeTableResponsive() {
    $header = array('one', 'two', 'three');
    $rows = array(array(1,2,3), array(4,5,6), array(7,8,9));
    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#responsive' => TRUE,
    );
    $this->render($table);
    $this->assertRaw('responsive-enabled', 'The responsive-enabled class was printed correctly.');
  }

  /**
   * Tests that the 'responsive-table' class is not applied without headers.
   */
  public function testThemeTableNotResponsiveHeaders() {
    $rows = array(array(1,2,3), array(4,5,6), array(7,8,9));
    $table = array(
      '#type' => 'table',
      '#rows' => $rows,
      '#responsive' => TRUE,
    );
    $this->render($table);
    $this->assertNoRaw('responsive-enabled', 'The responsive-enabled class is not applied without table headers.');
  }

  /**
   * Tests that 'responsive-table' class only applied when responsive is TRUE.
   */
  public function testThemeTableNotResponsiveProperty() {
    $header = array('one', 'two', 'three');
    $rows = array(array(1,2,3), array(4,5,6), array(7,8,9));
    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#responsive' => FALSE,
    );
    $this->render($table);
    $this->assertNoRaw('responsive-enabled', 'The responsive-enabled class is not applied without the "responsive" property set to TRUE.');
  }

  /**
   * Tests 'priority-medium' and 'priority-low' classes.
   */
  public function testThemeTableResponsivePriority() {
    $header = array(
      // Test associative header indices.
      'associative_key' => array('data' => 1, 'class' => array(RESPONSIVE_PRIORITY_MEDIUM)),
      // Test non-associative header indices.
      array('data' => 2, 'class' => array(RESPONSIVE_PRIORITY_LOW)),
      // Test no responsive priorities.
      array('data' => 3),
    );
    $rows = array(array(4, 5, 6));
    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#responsive' => TRUE,
    );
    $this->render($table);
    $this->assertRaw('<th class="priority-medium">1</th>', 'Header 1: the priority-medium class was applied correctly.');
    $this->assertRaw('<th class="priority-low">2</th>', 'Header 2: the priority-low class was applied correctly.');
    $this->assertRaw('<th>3</th>', 'Header 3: no priority classes were applied.');
    $this->assertRaw('<td class="priority-medium">4</td>', 'Cell 1: the priority-medium class was applied correctly.');
    $this->assertRaw('<td class="priority-low">5</td>', 'Cell 2: the priority-low class was applied correctly.');
    $this->assertRaw('<td>6</td>', 'Cell 3: no priority classes were applied.');
  }

}
