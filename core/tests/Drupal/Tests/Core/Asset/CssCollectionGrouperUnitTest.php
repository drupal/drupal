<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Asset\CssCollectionGrouperUnitTest.
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
        'media' => 'all',
        'preprocess' => TRUE,
        'data' => 'core/misc/ui/themes/base/jquery.ui.core.css',
        'browsers' => array('IE' => TRUE, '!IE' => TRUE),
        'basename' => 'jquery.ui.core.css',
      ),
      'field.css' => array(
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
        'group' => 0,
        'type' => 'external',
        'weight' => 0.009,
        'media' => 'all',
        'preprocess' => TRUE,
        'data' => 'http://example.com/external.css',
        'browsers' => array('IE' => TRUE, '!IE' => TRUE),
        'basename' => 'external.css',
      ),
      'elements.css' => array(
        'group' => 100,
        'media' => 'all',
        'type' => 'file',
        'weight' => 0.001,
        'preprocess' => TRUE,
        'data' => 'core/themes/bartik/css/base/elements.css',
        'browsers' => array('IE' => TRUE, '!IE' => TRUE),
        'basename' => 'elements.css',
      ),
      'print.css' => array(
        'group' => 100,
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

    $this->assertSame(count($groups), 5, "5 groups created.");

    // Check group 1.
    $group = $groups[0];
    $this->assertSame($group['group'], -100);
    $this->assertSame($group['type'], 'file');
    $this->assertSame($group['media'], 'all');
    $this->assertSame($group['preprocess'], TRUE);
    $this->assertSame(count($group['items']), 3);
    $this->assertContains($css_assets['system.base.css'], $group['items']);
    $this->assertContains($css_assets['system.theme.css'], $group['items']);

    // Check group 2.
    $group = $groups[1];
    $this->assertSame($group['group'], 0);
    $this->assertSame($group['type'], 'file');
    $this->assertSame($group['media'], 'all');
    $this->assertSame($group['preprocess'], TRUE);
    $this->assertSame(count($group['items']), 1);
    $this->assertContains($css_assets['field.css'], $group['items']);

    // Check group 3.
    $group = $groups[2];
    $this->assertSame($group['group'], 0);
    $this->assertSame($group['type'], 'external');
    $this->assertSame($group['media'], 'all');
    $this->assertSame($group['preprocess'], TRUE);
    $this->assertSame(count($group['items']), 1);
    $this->assertContains($css_assets['external.css'], $group['items']);

    // Check group 4.
    $group = $groups[3];
    $this->assertSame($group['group'], 100);
    $this->assertSame($group['type'], 'file');
    $this->assertSame($group['media'], 'all');
    $this->assertSame($group['preprocess'], TRUE);
    $this->assertSame(count($group['items']), 1);
    $this->assertContains($css_assets['elements.css'], $group['items']);

    // Check group 5.
    $group = $groups[4];
    $this->assertSame($group['group'], 100);
    $this->assertSame($group['type'], 'file');
    $this->assertSame($group['media'], 'print');
    $this->assertSame($group['preprocess'], TRUE);
    $this->assertSame(count($group['items']), 1);
    $this->assertContains($css_assets['print.css'], $group['items']);
  }

}
