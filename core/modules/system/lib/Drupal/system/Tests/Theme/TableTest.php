<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\TableTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Unit tests for theme_table().
 */
class TableTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Theme Table',
      'description' => 'Tests built-in theme functions.',
      'group' => 'Theme',
    );
  }

  /**
   * Tableheader.js provides 'sticky' table headers, and is included by default.
   */
  function testThemeTableStickyHeaders() {
    $header = array('one', 'two', 'three');
    $rows = array(array(1,2,3), array(4,5,6), array(7,8,9));
    $this->content = theme('table', array('header' => $header, 'rows' => $rows));
    $js = drupal_add_js();
    $this->assertTrue(isset($js['core/misc/tableheader.js']), t('tableheader.js was included when $sticky = TRUE.'));
    $this->assertRaw('sticky-enabled',  t('Table has a class of sticky-enabled when $sticky = TRUE.'));
    drupal_static_reset('drupal_add_js');
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
    $this->content = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => $attributes, 'caption' => $caption, 'colgroups' => $colgroups, 'sticky' => FALSE));
    $js = drupal_add_js();
    $this->assertFalse(isset($js['core/misc/tableheader.js']), t('tableheader.js was not included because $sticky = FALSE.'));
    $this->assertNoRaw('sticky-enabled',  t('Table does not have a class of sticky-enabled because $sticky = FALSE.'));
    drupal_static_reset('drupal_add_js');
  }

  /**
   * Tests that the table header is printed correctly even if there are no rows,
   * and that the empty text is displayed correctly.
   */
  function testThemeTableWithEmptyMessage() {
    $header = array(
      t('Header 1'),
      array(
        'data' => t('Header 2'),
        'colspan' => 2,
      ),
    );
    $this->content = theme('table', array('header' => $header, 'rows' => array(), 'empty' => t('No strings available.')));
    $this->assertRaw('<tr class="odd"><td colspan="3" class="empty message">No strings available.</td>', t('Correct colspan was set on empty message.'));
    $this->assertRaw('<thead><tr><th>Header 1</th>', t('Table header was printed.'));
  }
}
