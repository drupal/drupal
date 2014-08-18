<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Asset\CssGrouperUnitTest.
 */


namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\CssCollectionGrouper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CSS asset collection grouper.
 *
 * @group Asset
 */
class CssCollectionGrouperUnitTest extends UnitTestCase {

  /**
   * A CSS asset grouper.
   *
   * @var \Drupal\Core\Asset\CssCollectionGrouper object.
   */
  protected $grouper;

  protected function setUp() {
    parent::setUp();

    $this->grouper = new CssCollectionGrouper();
  }

  /**
   * Tests \Drupal\Core\Asset\CssCollectionGrouper.
   */
  function testGrouper() {
    $css_assets = array(
      'system.base.css' => array(
        'group' => -100,
        'every_page' => TRUE,
        'type' => 'file',
        'weight' => 0.012,
        'media' => 'all',
        'preprocess' => TRUE,
        'data' => 'core/modules/system/system.base.css',
        'browsers' => array('IE' => TRUE, '!IE' => TRUE),
        'basename' => 'system.base.css',
      ),
      'system.theme.css' => array(
        'group' => -100,
        'every_page' => TRUE,
        'type' => 'file',
        'weight' => 0.013,
        'media' => 'all',
        'preprocess' => TRUE,
        'data' => 'core/modules/system/system.theme.css',
        'browsers' => array('IE' => TRUE, '!IE' => TRUE),
        'basename' => 'system.theme.css',
      ),
      'jquery.ui.core.css' => array(
        'group' => -100,
        'type' => 'file',
        'weight' => 0.004,
        'every_page' => FALSE,
        'media' => 'all',
        'preprocess' => TRUE,
        'data' => 'core/misc/ui/themes/base/jquery.ui.core.css',
        'browsers' => array('IE' => TRUE, '!IE' => TRUE),
        'basename' => 'jquery.ui.core.css',
      ),
      0 => array(
        'type' => 'inline',
        'group' => 0,
        'weight' => 0.007,
        'every_page' => FALSE,
        'media' => 'all',
        'preprocess' => TRUE,
        'data' => 'body { padding: 0px; }',
        'browsers' => array('IE' => TRUE, '!IE' => TRUE),
      ),
      1 => array(
        'type' => 'inline',
        'group' => 0,
        'weight' => 0.007,
        'every_page' => FALSE,
        'media' => 'all',
        'preprocess' => FALSE,
        'data' => 'body { margin: 0px; }',
        'browsers' => array('IE' => TRUE, '!IE' => TRUE),
      ),
      'field.css' => array(
        'every_page' => TRUE,
        'group' => 0,
        'type' => 'file',
        'weight' => 0.011,
        'media' => 'all',
        'preprocess' => TRUE,
        'data' => 'core/modules/field/theme/field.css',
        'browsers' => array('IE' => TRUE, '!IE' => TRUE),
        'basename' => 'field.css',
      ),
      'external.css' => array(
        'every_page' => FALSE,
        'group' => 0,
        'type' => 'external',
        'weight' => 0.009,
        'media' => 'all',
        'preprocess' => TRUE,
        'data' => 'http://example.com/external.css',
        'browsers' => array('IE' => TRUE, '!IE' => TRUE),
        'basename' => 'external.css',
      ),
      'style.css' => array(
        'group' => 100,
        'every_page' => TRUE,
        'media' => 'all',
        'type' => 'file',
        'weight' => 0.001,
        'preprocess' => TRUE,
        'data' => 'core/themes/bartik/css/style.css',
        'browsers' => array('IE' => TRUE, '!IE' => TRUE),
        'basename' => 'style.css',
      ),
      'print.css' => array(
        'group' => 100,
        'every_page' => TRUE,
        'media' => 'print',
        'type' => 'file',
        'weight' => 0.003,
        'preprocess' => TRUE,
        'data' => 'core/themes/bartik/css/print.css',
        'browsers' => array('IE' => TRUE, '!IE' => TRUE),
        'basename' => 'print.css',
      ),
    );

    $groups = $this->grouper->group($css_assets);

    $this->assertSame(count($groups), 7, "7 groups created.");

    // Check group 1.
    $this->assertSame($groups[0]['group'], -100);
    $this->assertSame($groups[0]['every_page'], TRUE);
    $this->assertSame($groups[0]['type'], 'file');
    $this->assertSame($groups[0]['media'], 'all');
    $this->assertSame($groups[0]['preprocess'], TRUE);
    $this->assertSame(count($groups[0]['items']), 2);
    $this->assertContains($css_assets['system.base.css'], $groups[0]['items']);
    $this->assertContains($css_assets['system.theme.css'], $groups[0]['items']);

    // Check group 2.
    $this->assertSame($groups[1]['group'], -100);
    $this->assertSame($groups[1]['every_page'], FALSE);
    $this->assertSame($groups[1]['type'], 'file');
    $this->assertSame($groups[1]['media'], 'all');
    $this->assertSame($groups[1]['preprocess'], TRUE);
    $this->assertSame(count($groups[1]['items']), 1);
    $this->assertContains($css_assets['jquery.ui.core.css'], $groups[1]['items']);

    // Check group 3.
    $this->assertSame($groups[2]['group'], 0);
    $this->assertSame($groups[2]['every_page'], FALSE);
    $this->assertSame($groups[2]['type'], 'inline');
    $this->assertSame($groups[2]['media'], 'all');
    $this->assertSame($groups[2]['preprocess'], TRUE);
    $this->assertSame(count($groups[2]['items']), 2);
    $this->assertContains($css_assets[0], $groups[2]['items']);
    $this->assertContains($css_assets[1], $groups[2]['items']);

    // Check group 4.
    $this->assertSame($groups[3]['group'], 0);
    $this->assertSame($groups[3]['every_page'], TRUE);
    $this->assertSame($groups[3]['type'], 'file');
    $this->assertSame($groups[3]['media'], 'all');
    $this->assertSame($groups[3]['preprocess'], TRUE);
    $this->assertSame(count($groups[3]['items']), 1);
    $this->assertContains($css_assets['field.css'], $groups[3]['items']);

    // Check group 5.
    $this->assertSame($groups[4]['group'], 0);
    $this->assertSame($groups[4]['every_page'], FALSE);
    $this->assertSame($groups[4]['type'], 'external');
    $this->assertSame($groups[4]['media'], 'all');
    $this->assertSame($groups[4]['preprocess'], TRUE);
    $this->assertSame(count($groups[4]['items']), 1);
    $this->assertContains($css_assets['external.css'], $groups[4]['items']);

    // Check group 6.
    $this->assertSame($groups[5]['group'], 100);
    $this->assertSame($groups[5]['every_page'], TRUE);
    $this->assertSame($groups[5]['type'], 'file');
    $this->assertSame($groups[5]['media'], 'all');
    $this->assertSame($groups[5]['preprocess'], TRUE);
    $this->assertSame(count($groups[5]['items']), 1);
    $this->assertContains($css_assets['style.css'], $groups[5]['items']);

    // Check group 7.
    $this->assertSame($groups[6]['group'], 100);
    $this->assertSame($groups[6]['every_page'], TRUE);
    $this->assertSame($groups[6]['type'], 'file');
    $this->assertSame($groups[6]['media'], 'print');
    $this->assertSame($groups[6]['preprocess'], TRUE);
    $this->assertSame(count($groups[6]['items']), 1);
    $this->assertContains($css_assets['print.css'], $groups[6]['items']);
  }

}
