<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Common\MergeAttachmentsTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the merging of attachments.
 *
 * @group Common
 */
class MergeAttachmentsTest extends DrupalUnitTestBase {

  /**
   * Tests justs library asset merging.
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
   * Tests justs CSS asset merging.
   */
  function testCssMerging() {
    $a['#attached'] = array(
      'css' => array(
        'foo.css' => array(),
        'bar.css' => array(),
      ),
    );
    $b['#attached'] = array(
      'css' => array(
        'baz.css' => array(),
      ),
    );
    $expected['#attached'] = array(
      'css' => array(
        'foo.css' => array(),
        'bar.css' => array(),
        'baz.css' => array(),
      ),
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($a['#attached'], $b['#attached']), 'Attachments merged correctly.');

    // Merging in the opposite direction yields the opposite CSS asset order.
    $expected['#attached'] = array(
      'css' => array(
        'baz.css' => array(),
        'foo.css' => array(),
        'bar.css' => array(),
      ),
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($b['#attached'], $a['#attached']), 'Attachments merged correctly; opposite merging yields opposite order.');

    // Merging with duplicates: duplicates are automatically removed because the
    // values have unique keys.
    $b['#attached']['css']['bar.css'] = array();
    $expected['#attached'] = array(
      'css' => array(
        'foo.css' => array(),
        'bar.css' => array(),
        'baz.css' => array(),
      ),
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($a['#attached'], $b['#attached']), 'Attachments merged correctly; CSS asset duplicates removed.');
  }

  /**
   * Tests justs JavaScript asset merging.
   */
  function testJsMerging() {
    $a['#attached'] = array(
      'js' => array(
        'foo.js' => array(),
        'bar.js' => array(),
      ),
    );
    $b['#attached'] = array(
      'js' => array(
        'baz.js' => array(),
      ),
    );
    $expected['#attached'] = array(
      'js' => array(
        'foo.js' => array(),
        'bar.js' => array(),
        'baz.js' => array(),
      ),
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($a['#attached'], $b['#attached']), 'Attachments merged correctly.');

    // Merging in the opposite direction yields the opposite JS asset order.
    $expected['#attached'] = array(
      'js' => array(
        'baz.js' => array(),
        'foo.js' => array(),
        'bar.js' => array(),
      ),
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($b['#attached'], $a['#attached']), 'Attachments merged correctly; opposite merging yields opposite order.');

    // Merging with duplicates: duplicates are automatically removed because the
    // values have unique keys.
    $b['#attached']['js']['bar.js'] = array();
    $expected['#attached'] = array(
      'js' => array(
        'foo.js' => array(),
        'bar.js' => array(),
        'baz.js' => array(),
      ),
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($a['#attached'], $b['#attached']), 'Attachments merged correctly; JS asset duplicates removed.');
  }

  /**
   * Tests justs JavaScript and JavaScript setting asset merging.
   */
  function testJsSettingMerging() {
    $a['#attached'] = array(
      'js' => array(
        'foo.js' => array(),
        array(
          'type' => 'setting',
          'data' => array('foo' => array('d')),
        ),
        'bar.js' => array(),
      ),
    );
    $b['#attached'] = array(
      'js' => array(
        83 => array(
          'type' => 'setting',
          'data' => array('bar' => array('a', 'b', 'c')),
        ),
        'baz.js' => array(),
      ),
    );
    $expected['#attached'] = array(
      'js' => array(
        'foo.js' => array(),
        0 => array(
          'type' => 'setting',
          'data' => array('foo' => array('d')),
        ),
        'bar.js' => array(),
        1 => array(
          'type' => 'setting',
          'data' => array('bar' => array('a', 'b', 'c')),
        ),
        'baz.js' => array(),
      ),
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($a['#attached'], $b['#attached']), 'Attachments merged correctly.');

    // Merging in the opposite direction yields the opposite JS setting asset
    // order.
    $expected['#attached'] = array(
      'js' => array(
        0 => array(
          'type' => 'setting',
          'data' => array('bar' => array('a', 'b', 'c')),
        ),
        'baz.js' => array(),
        'foo.js' => array(),
        1 => array(
          'type' => 'setting',
          'data' => array('foo' => array('d')),
        ),
        'bar.js' => array(),
      ),
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($b['#attached'], $a['#attached']), 'Attachments merged correctly; opposite merging yields opposite order.');

    // Merging with duplicates: JavaScript setting duplicates are simply
    // retained, it's up to the rest of the system (drupal_merge_js_settings())
    // to handle duplicates.
    $b['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('foo' => array('a', 'b', 'c')),
    );
    $expected['#attached'] = array(
      'js' => array(
        'foo.js' => array(),
        0 => array(
          'type' => 'setting',
          'data' => array('foo' => array('d')),
        ),
        'bar.js' => array(),
        1 => array(
          'type' => 'setting',
          'data' => array('bar' => array('a', 'b', 'c')),
        ),
        'baz.js' => array(),
        2 => array(
          'type' => 'setting',
          'data' => array('foo' => array('a', 'b', 'c')),
        ),
      ),
    );
    $this->assertIdentical($expected['#attached'], drupal_merge_attached($a['#attached'], $b['#attached']), 'Attachments merged correctly; JavaScript asset duplicates removed, JavaScript setting asset duplicates retained.');
  }

}
