<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Common\MergeAttachmentsTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests the merging of attachments.
 *
 * @see drupal_merge_attached()
 *
 * @group Common
 */
class MergeAttachmentsTest extends KernelTestBase {

  /**
   * Tests library asset merging.
   */
  function testLibraryMerging() {
    $a['#attached'] = array(
      'library' => array(
        'core/drupal',
        'core/drupalSettings',
      ),
    );
    $b['#attached'] = array(
      'library' => array(
        'core/jquery',
      ),
    );
    $expected['#attached'] = array(
      'library' => array(
        'core/drupal',
        'core/drupalSettings',
        'core/jquery',
      ),
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($a['#attached'], $b['#attached']), 'Attachments merged correctly.');

    // Merging in the opposite direction yields the opposite library order.
    $expected['#attached'] = array(
      'library' => array(
        'core/jquery',
        'core/drupal',
        'core/drupalSettings',
      ),
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($b['#attached'], $a['#attached']), 'Attachments merged correctly; opposite merging yields opposite order.');

    // Merging with duplicates: duplicates are simply retained, it's up to the
    // rest of the system to handle duplicates.
    $b['#attached']['library'][] = 'core/drupalSettings';
    $expected['#attached'] = array(
      'library' => array(
        'core/drupal',
        'core/drupalSettings',
        'core/jquery',
        'core/drupalSettings',
      ),
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($a['#attached'], $b['#attached']), 'Attachments merged correctly; duplicates are retained.');
  }

  /**
   * Tests JavaScript and JavaScript setting asset merging.
   */
  function testJsSettingMerging() {
    $a['#attached'] = array(
      'js' => array(
        'foo.js' => array(),
        'bar.js' => array(),
      ),
      'drupalSettings' => [
        'foo' => ['d'],
      ],
    );
    $b['#attached'] = array(
      'js' => array(
        'baz.js' => array(),
      ),
      'drupalSettings' => [
        'bar' => ['a', 'b', 'c'],
      ],
    );
    $expected['#attached'] = array(
      'js' => array(
        'foo.js' => array(),
        'bar.js' => array(),
        'baz.js' => array(),
      ),
      'drupalSettings' => [
        'foo' => ['d'],
        'bar' => ['a', 'b', 'c'],
      ],
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($a['#attached'], $b['#attached']), 'Attachments merged correctly.');

    // Merging in the opposite direction yields the opposite JS setting asset
    // order.
    $expected['#attached'] = array(
      'js' => array(
        'baz.js' => array(),
        'foo.js' => array(),
        'bar.js' => array(),
      ),
      'drupalSettings' => [
        'bar' => ['a', 'b', 'c'],
        'foo' => ['d'],
      ],
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($b['#attached'], $a['#attached']), 'Attachments merged correctly; opposite merging yields opposite order.');

    // Merging with duplicates (simple case).
    $b['#attached']['drupalSettings']['foo'] = ['a', 'b', 'c'];
    $expected['#attached'] = array(
      'js' => array(
        'foo.js' => array(),
        'bar.js' => array(),
        'baz.js' => array(),
      ),
      'drupalSettings' => [
        'foo' => ['a', 'b', 'c'],
        'bar' => ['a', 'b', 'c'],
      ],
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($a['#attached'], $b['#attached']));

    // Merging with duplicates (simple case) in the opposite direction yields
    // the opposite JS setting asset order, but also opposite overriding order.
    $expected['#attached'] = array(
      'js' => array(
        'baz.js' => array(),
        'foo.js' => array(),
        'bar.js' => array(),
      ),
      'drupalSettings' => [
        'bar' => ['a', 'b', 'c'],
        'foo' => ['d', 'b', 'c'],
      ],
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($b['#attached'], $a['#attached']));

    // Merging with duplicates: complex case.
    // Only the second of these two entries should appear in drupalSettings.
    $build = array();
    $build['a']['#attached']['drupalSettings']['commonTest'] = 'firstValue';
    $build['b']['#attached']['drupalSettings']['commonTest'] = 'secondValue';
    // Only the second of these entries should appear in drupalSettings.
    $build['a']['#attached']['drupalSettings']['commonTestJsArrayLiteral'] = ['firstValue'];
    $build['b']['#attached']['drupalSettings']['commonTestJsArrayLiteral'] = ['secondValue'];
    // Only the second of these two entries should appear in drupalSettings.
    $build['a']['#attached']['drupalSettings']['commonTestJsObjectLiteral'] = ['key' => 'firstValue'];
    $build['b']['#attached']['drupalSettings']['commonTestJsObjectLiteral'] = ['key' => 'secondValue'];
    // Real world test case: multiple elements in a render array are adding the
    // same (or nearly the same) JavaScript settings. When merged, they should
    // contain all settings and not duplicate some settings.
    $settings_one = array('moduleName' => array('ui' => array('button A', 'button B'), 'magical flag' => 3.14159265359));
    $build['a']['#attached']['drupalSettings']['commonTestRealWorldIdentical'] = $settings_one;
    $build['b']['#attached']['drupalSettings']['commonTestRealWorldIdentical'] = $settings_one;
    $settings_two_a = array('moduleName' => array('ui' => array('button A', 'button B', 'button C'), 'magical flag' => 3.14159265359, 'thingiesOnPage' => array('id1' => array())));
    $build['a']['#attached']['drupalSettings']['commonTestRealWorldAlmostIdentical'] = $settings_two_a;
    $settings_two_b = array('moduleName' => array('ui' => array('button D', 'button E'), 'magical flag' => 3.14, 'thingiesOnPage' => array('id2' => array())));
    $build['b']['#attached']['drupalSettings']['commonTestRealWorldAlmostIdentical'] = $settings_two_b;

    $merged = drupal_merge_attached($build['a']['#attached'], $build['b']['#attached']);

    // Test whether #attached can be used to override a previous setting.
    $this->assertIdentical('secondValue', $merged['drupalSettings']['commonTest']);

    // Test whether #attached can be used to add and override a JavaScript
    // array literal (an indexed PHP array) values.
    $this->assertIdentical('secondValue', $merged['drupalSettings']['commonTestJsArrayLiteral'][0]);

    // Test whether #attached can be used to add and override a JavaScript
    // object literal (an associate PHP array) values.
    $this->assertIdentical('secondValue', $merged['drupalSettings']['commonTestJsObjectLiteral']['key']);

    // Test whether the two real world cases are handled correctly: the first
    // adds the exact same settings twice and hence tests idempotency, the
    // second adds *almost* the same settings twice: the second time, some
    // values are altered, and some key-value pairs are added.
    $settings_two['moduleName']['thingiesOnPage']['id1'] = array();
    $this->assertIdentical($settings_one, $merged['drupalSettings']['commonTestRealWorldIdentical']);
    $expected_settings_two = $settings_two_a;
    $expected_settings_two['moduleName']['ui'][0] = 'button D';
    $expected_settings_two['moduleName']['ui'][1] = 'button E';
    $expected_settings_two['moduleName']['ui'][2] = 'button C';
    $expected_settings_two['moduleName']['magical flag'] = 3.14;
    $expected_settings_two['moduleName']['thingiesOnPage']['id2'] = [];
    $this->assertIdentical($expected_settings_two, $merged['drupalSettings']['commonTestRealWorldAlmostIdentical']);
  }

}
