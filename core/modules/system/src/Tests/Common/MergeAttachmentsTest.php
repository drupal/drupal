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
 * @see \Drupal::service('renderer')->mergeAttachments()
 *
 * @group Common
 */
class MergeAttachmentsTest extends KernelTestBase {

  /**
   * Tests library asset merging.
   */
  function testLibraryMerging() {
    $renderer = \Drupal::service('renderer');

    $a['#attached'] = array(
      'library' => array(
        'core/drupal',
        'core/drupalSettings',
      ),
      'drupalSettings' => [
        'foo' => ['d'],
      ],
    );
    $b['#attached'] = array(
      'library' => array(
        'core/jquery',
      ),
      'drupalSettings' => [
        'bar' => ['a', 'b', 'c'],
      ],
    );
    $expected['#attached'] = array(
      'library' => array(
        'core/drupal',
        'core/drupalSettings',
        'core/jquery',
      ),
      'drupalSettings' => [
        'foo' => ['d'],
        'bar' => ['a', 'b', 'c'],
      ],
    );
    $this->assertIdentical($expected['#attached'], $renderer->mergeAttachments($a['#attached'], $b['#attached']), 'Attachments merged correctly.');

    // Merging in the opposite direction yields the opposite library order.
    $expected['#attached'] = array(
      'library' => array(
        'core/jquery',
        'core/drupal',
        'core/drupalSettings',
      ),
      'drupalSettings' => [
        'bar' => ['a', 'b', 'c'],
        'foo' => ['d'],
      ],
    );
    $this->assertIdentical($expected['#attached'], $renderer->mergeAttachments($b['#attached'], $a['#attached']), 'Attachments merged correctly; opposite merging yields opposite order.');

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
      'drupalSettings' => [
        'foo' => ['d'],
        'bar' => ['a', 'b', 'c'],
      ],
    );
    $this->assertIdentical($expected['#attached'], $renderer->mergeAttachments($a['#attached'], $b['#attached']), 'Attachments merged correctly; duplicates are retained.');

    // Merging with duplicates (simple case).
    $b['#attached']['drupalSettings']['foo'] = ['a', 'b', 'c'];
    $expected['#attached'] = array(
      'library' => array(
        'core/drupal',
        'core/drupalSettings',
        'core/jquery',
        'core/drupalSettings',
      ),
      'drupalSettings' => [
        'foo' => ['a', 'b', 'c'],
        'bar' => ['a', 'b', 'c'],
      ],
    );
    $this->assertIdentical($expected['#attached'], $renderer->mergeAttachments($a['#attached'], $b['#attached']));

    // Merging with duplicates (simple case) in the opposite direction yields
    // the opposite JS setting asset order, but also opposite overriding order.
    $expected['#attached'] = array(
      'library' => array(
        'core/jquery',
        'core/drupalSettings',
        'core/drupal',
        'core/drupalSettings',
      ),
      'drupalSettings' => [
        'bar' => ['a', 'b', 'c'],
        'foo' => ['d', 'b', 'c'],
      ],
    );
    $this->assertIdentical($expected['#attached'], $renderer->mergeAttachments($b['#attached'], $a['#attached']));

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

    $merged = $renderer->mergeAttachments($build['a']['#attached'], $build['b']['#attached']);

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

  /**
   * Tests feed asset merging.
   */
  function testFeedMerging() {
    $renderer = \Drupal::service('renderer');

    $a['#attached'] = array(
      'feed' => array(
        array(
          'aggregator/rss',
          t('Feed title'),
        ),
      ),
    );
    $b['#attached'] = array(
      'feed' => array(
        array(
          'taxonomy/term/1/feed',
          'RSS - foo',
        ),
      ),
    );
    $expected['#attached'] = array(
      'feed' => array(
        array(
          'aggregator/rss',
          t('Feed title'),
        ),
        array(
          'taxonomy/term/1/feed',
          'RSS - foo',
        ),
      ),
    );
    $this->assertIdentical($expected['#attached'], $renderer->mergeAttachments($a['#attached'], $b['#attached']), 'Attachments merged correctly.');

    // Merging in the opposite direction yields the opposite library order.
    $expected['#attached'] = array(
      'feed' => array(
        array(
          'taxonomy/term/1/feed',
          'RSS - foo',
        ),
        array(
          'aggregator/rss',
          t('Feed title'),
        ),
      ),
    );
    $this->assertIdentical($expected['#attached'], $renderer->mergeAttachments($b['#attached'], $a['#attached']), 'Attachments merged correctly; opposite merging yields opposite order.');
  }

  /**
   * Tests html_head asset merging.
   */
  function testHtmlHeadMerging() {
    $renderer = \Drupal::service('renderer');

    $a['#attached'] = array(
      'html_head' => array(
        array(
          '#tag' => 'meta',
          '#attributes' => array(
            'charset' => 'utf-8',
          ),
          '#weight' => -1000,
        ),
        'system_meta_content_type',
      ),
    );
    $b['#attached'] = array(
      'html_head' => array(
        array(
          '#type' => 'html_tag',
          '#tag' => 'meta',
          '#attributes' => array(
            'name' => 'Generator',
            'content' => 'Kitten 1.0 (https://www.drupal.org/project/kitten)',
          ),
        ),
        'system_meta_generator',
      ),
    );
    $expected['#attached'] = array(
      'html_head' => array(
        array(
          '#tag' => 'meta',
          '#attributes' => array(
            'charset' => 'utf-8',
          ),
          '#weight' => -1000,
        ),
        'system_meta_content_type',
        array(
          '#type' => 'html_tag',
          '#tag' => 'meta',
          '#attributes' => array(
            'name' => 'Generator',
            'content' => 'Kitten 1.0 (https://www.drupal.org/project/kitten)',
          ),
        ),
        'system_meta_generator',
      ),
    );
    $this->assertIdentical($expected['#attached'], $renderer->mergeAttachments($a['#attached'], $b['#attached']), 'Attachments merged correctly.');

    // Merging in the opposite direction yields the opposite library order.
    $expected['#attached'] = array(
      'html_head' => array(
        array(
          '#type' => 'html_tag',
          '#tag' => 'meta',
          '#attributes' => array(
            'name' => 'Generator',
            'content' => 'Kitten 1.0 (https://www.drupal.org/project/kitten)',
          ),
        ),
        'system_meta_generator',
        array(
          '#tag' => 'meta',
          '#attributes' => array(
            'charset' => 'utf-8',
          ),
          '#weight' => -1000,
        ),
        'system_meta_content_type',
      ),
    );
    $this->assertIdentical($expected['#attached'], $renderer->mergeAttachments($b['#attached'], $a['#attached']), 'Attachments merged correctly; opposite merging yields opposite order.');
  }

  /**
   * Tests html_head_link asset merging.
   */
  function testHtmlHeadLinkMerging() {
    $renderer = \Drupal::service('renderer');

    $a['#attached'] = array(
      'html_head_link' => array(
        array(
          'rel' => 'rel',
          'href' => 'http://rel.example.com',
        ),
        TRUE,
      ),
    );
    $b['#attached'] = array(
      'html_head_link' => array(
        array(
          'rel' => 'shortlink',
          'href' => 'http://shortlink.example.com',
        ),
        FALSE,
      ),
    );
    $expected['#attached'] = array(
      'html_head_link' => array(
        array(
          'rel' => 'rel',
          'href' => 'http://rel.example.com',
        ),
        TRUE,
        array(
          'rel' => 'shortlink',
          'href' => 'http://shortlink.example.com',
        ),
        FALSE,
      ),
    );
    $this->assertIdentical($expected['#attached'], $renderer->mergeAttachments($a['#attached'], $b['#attached']), 'Attachments merged correctly.');

    // Merging in the opposite direction yields the opposite library order.
    $expected['#attached'] = array(
      'html_head_link' => array(
        array(
          'rel' => 'shortlink',
          'href' => 'http://shortlink.example.com',
        ),
        FALSE,
        array(
          'rel' => 'rel',
          'href' => 'http://rel.example.com',
        ),
        TRUE,
      ),
    );
    $this->assertIdentical($expected['#attached'], $renderer->mergeAttachments($b['#attached'], $a['#attached']), 'Attachments merged correctly; opposite merging yields opposite order.');
  }

  /**
   * Tests http_header asset merging.
   */
  function testHttpHeaderMerging() {
    $renderer = \Drupal::service('renderer');

    $a['#attached'] = array(
      'http_header' => array(
        array(
          'Content-Type',
          'application/rss+xml; charset=utf-8',
        ),
      ),
    );
    $b['#attached'] = array(
      'http_header' => array(
        array(
          'Expires',
          'Sun, 19 Nov 1978 05:00:00 GMT',
        ),
      ),
    );
    $expected['#attached'] = array(
      'http_header' => array(
        array(
          'Content-Type',
          'application/rss+xml; charset=utf-8',
        ),
        array(
          'Expires',
          'Sun, 19 Nov 1978 05:00:00 GMT',
        ),
      ),
    );
    $this->assertIdentical($expected['#attached'], $renderer->mergeAttachments($a['#attached'], $b['#attached']), 'Attachments merged correctly.');

    // Merging in the opposite direction yields the opposite library order.
    $expected['#attached'] = array(
      'http_header' => array(
        array(
          'Expires',
          'Sun, 19 Nov 1978 05:00:00 GMT',
        ),
        array(
          'Content-Type',
          'application/rss+xml; charset=utf-8',
        ),
      ),
    );
    $this->assertIdentical($expected['#attached'], $renderer->mergeAttachments($b['#attached'], $a['#attached']), 'Attachments merged correctly; opposite merging yields opposite order.');
  }

}
