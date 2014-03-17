<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Controller\SystemControllerTest.
 */

namespace Drupal\system\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Json;
use Drupal\Core\Template\Attribute;
use Drupal\system\Controller\SystemController;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the System controller's #post_render_cache callback for active links.
 *
 * @group Drupal
 * @group System
 *
 * @see \Drupal\system\Controller\SystemController::setLinkActiveClass()
 */
class SystemControllerTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'System controller set active link class test',
      'description' => 'Unit test of system controller #post_render_cache callback for marking active links.',
      'group' => 'System'
    );
  }

  /**
   * Provides test data for testSetLinkActiveClass().
   *
   * @see \Drupal\system\Controller\SystemController::setLinkActiveClass()
   */
  public function providerTestSetLinkActiveClass() {
    // Define all the variations that *don't* affect whether or not an "active"
    // class is set, but that should remain unchanged:
    // - surrounding HTML
    // - tags for which to test the setting of the "active" class
    // - content of said tags
    $edge_case_html5 = '<audio src="foo.ogg">
  <track kind="captions" src="foo.en.vtt" srclang="en" label="English">
  <track kind="captions" src="foo.sv.vtt" srclang="sv" label="Svenska">
</audio>';
    $html = array(
      // Simple HTML.
      0 => array('prefix' => '<div><p>', 'suffix' => '</p></div>'),
      // Tricky HTML5 example that's unsupported by PHP <=5.4's DOMDocument:
      // https://drupal.org/comment/7938201#comment-7938201.
      1 => array('prefix' => '<div><p>', 'suffix' => '</p>' . $edge_case_html5 . '</div>'),
      // Multi-byte content *before* the HTML that needs the "active" class.
      2 => array('prefix' => '<div><p>αβγδεζηθικλμνξοσὠ</p><p>', 'suffix' => '</p></div>'),
    );
    $tags = array(
      // Of course, it must work on anchors.
      'a',
      // Unfortunately, it must also work on list items.
      'li',
      // … and therefor, on *any* tag, really.
      'foo',
    );
    $contents = array(
      // Regular content.
      'test',
      // Mix of UTF-8 and HTML entities, both must be retained.
      '☆ 3 × 4 = €12 and 4 &times; 3 = &euro;12 &#9734',
      // Multi-byte content.
      'ΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΣὨ',
      // Text that closely approximates an important attribute, but should be
      // ignored.
      'data-drupal-link-system-path=&quot;&lt;front&gt;&quot;',
    );

    // Define all variations that *do* affect whether or not an "active" class
    // is set: all possible situations that can be encountered.
    $situations = array();

    // Situations with context: front page, English, no query.
    $context = array(
      'path' => 'myfrontpage',
      'front' => TRUE,
      'language' => 'en',
      'query' => array(),
    );
    // Nothing to do.
    $markup = '<foo>bar</foo>';
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => array());
    // Matching path, plus all matching variations.
    $attributes = array(
      'data-drupal-link-system-path' => 'myfrontpage',
    );
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes);
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes + array('hreflang' => 'en'));
    // Matching path, plus all non-matching variations.
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => '{"foo":"bar"}'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => TRUE));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en', 'data-drupal-link-query' => '{"foo":"bar"}'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en', 'data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en', 'data-drupal-link-query' => TRUE));
    // Special matching path, plus all variations.
    $attributes = array(
      'data-drupal-link-system-path' => '<front>',
    );
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes);
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes + array('hreflang' => 'en'));
    // Special matching path, plus all non-matching variations.
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => '{"foo":"bar"}'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => TRUE));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en', 'data-drupal-link-query' => '{"foo":"bar"}'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en', 'data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en', 'data-drupal-link-query' => TRUE));

    // Situations with context: non-front page, Dutch, no query.
    $context = array(
      'path' => 'llama',
      'front' => FALSE,
      'language' => 'nl',
      'query' => array(),
    );
    // Nothing to do.
    $markup = '<foo>bar</foo>';
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => array());
    // Matching path, plus all matching variations.
    $attributes = array(
      'data-drupal-link-system-path' => 'llama',
    );
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes);
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes + array('hreflang' => 'nl'));
    // Matching path, plus all non-matching variations.
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => '{"foo":"bar"}'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => TRUE));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => '{"foo":"bar"}'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => TRUE));
    // Special non-matching path, plus all variations.
    $attributes = array(
      'data-drupal-link-system-path' => '<front>',
    );
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes);
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => '{"foo":"bar"}'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => TRUE));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => '{"foo":"bar"}'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => TRUE));

    // Situations with context: non-front page, Dutch, with query.
    $context = array(
      'path' => 'llama',
      'front' => FALSE,
      'language' => 'nl',
      'query' => array('foo' => 'bar'),
    );
    // Nothing to do.
    $markup = '<foo>bar</foo>';
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => array());
    // Matching path, plus all matching variations.
    $attributes = array(
      'data-drupal-link-system-path' => 'llama',
      'data-drupal-link-query' => Json::encode(array('foo' => 'bar')),
    );
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes);
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes + array('hreflang' => 'nl'));
    // Matching path, plus all non-matching variations.
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en'));
    unset($attributes['data-drupal-link-query']);
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => TRUE));
    // Special non-matching path, plus all variations.
    $attributes = array(
      'data-drupal-link-system-path' => '<front>',
    );
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes);
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en'));
    unset($attributes['data-drupal-link-query']);
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => TRUE));

    // Situations with context: non-front page, Dutch, with query.
    $context = array(
      'path' => 'llama',
      'front' => FALSE,
      'language' => 'nl',
      'query' => array('foo' => 'bar'),
    );
    // Nothing to do.
    $markup = '<foo>bar</foo>';
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => array());
    // Matching path, plus all matching variations.
    $attributes = array(
      'data-drupal-link-system-path' => 'llama',
      'data-drupal-link-query' => Json::encode(array('foo' => 'bar')),
    );
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes);
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes + array('hreflang' => 'nl'));
    // Matching path, plus all non-matching variations.
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en'));
    unset($attributes['data-drupal-link-query']);
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => TRUE));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => TRUE));
    // Special non-matching path, plus all variations.
    $attributes = array(
      'data-drupal-link-system-path' => '<front>',
    );
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes);
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl'));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en'));
    unset($attributes['data-drupal-link-query']);
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => TRUE));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl', 'data-drupal-link-query' => TRUE));

    // Situations with context: front page, English, query.
    $context = array(
      'path' => 'myfrontpage',
      'front' => TRUE,
      'language' => 'en',
      'query' => array('foo' => 'bar'),
    );
    // Nothing to do.
    $markup = '<foo>bar</foo>';
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => array());
    // Matching path, plus all matching variations.
    $attributes = array(
      'data-drupal-link-system-path' => 'myfrontpage',
      'data-drupal-link-query' => Json::encode(array('foo' => 'bar')),
    );
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes);
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes + array('hreflang' => 'en'));
    // Matching path, plus all non-matching variations.
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl'));
    unset($attributes['data-drupal-link-query']);
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => TRUE));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en', 'data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en', 'data-drupal-link-query' => TRUE));
    // Special matching path, plus all variations.
    $attributes = array(
      'data-drupal-link-system-path' => '<front>',
      'data-drupal-link-query' => Json::encode(array('foo' => 'bar')),
    );
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes);
    $situations[] = array('context' => $context, 'is active' => TRUE, 'attributes' => $attributes + array('hreflang' => 'en'));
    // Special matching path, plus all non-matching variations.
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'nl'));
    unset($attributes['data-drupal-link-query']);
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('data-drupal-link-query' => TRUE));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en', 'data-drupal-link-query' => ""));
    $situations[] = array('context' => $context, 'is active' => FALSE, 'attributes' => $attributes + array('hreflang' => 'en', 'data-drupal-link-query' => TRUE));

    // Helper function to generate a stubbed renderable array.
    $create_element = function ($markup) {
      return array(
        '#markup' => $markup,
        '#attached' => array(),
      );
    };

    // Loop over the surrounding HTML variations.
    $data = array();
    for ($h = 0; $h < count($html); $h++) {
      $html_prefix = $html[$h]['prefix'];
      $html_suffix = $html[$h]['suffix'];
      // Loop over the tag variations.
      for ($t = 0; $t < count($tags); $t++) {
        $tag = $tags[$t];
        // Loop over the tag contents variations.
        for ($c = 0; $c < count($contents); $c++) {
          $tag_content = $contents[$c];

          $create_markup = function (Attribute $attributes) use ($html_prefix, $html_suffix, $tag, $tag_content) {
            return $html_prefix . '<' . $tag . $attributes . '>' . $tag_content . '</' . $tag . '>' . $html_suffix;
          };

          // Loop over the situations.
          for ($s = 0; $s < count($situations); $s++) {
            $situation = $situations[$s];

            // Build the source markup.
            $source_markup = $create_markup(new Attribute($situation['attributes']));

            // Build the target markup. If no "active" class should be set, the
            // resulting HTML should be identical. Otherwise, it should get an
            // "active" class, either by extending an existing "class" attribute
            // or by adding a "class" attribute.
            $target_markup = NULL;
            if (!$situation['is active']) {
              $target_markup = $source_markup;
            }
            else {
              $active_attributes = $situation['attributes'];
              if (!isset($active_attributes['class'])) {
                $active_attributes['class'] = array();
              }
              $active_attributes['class'][] = 'active';
              $target_markup = $create_markup(new Attribute($active_attributes));
            }

            $data[] = array($create_element($source_markup), $situation['context'], $create_element($target_markup));
          }
        }
      }
    }

    return $data;
  }

  /**
   * Tests setLinkActiveClass().
   *
   * @param array $element
   *  A renderable array with the following keys:
   *    - #markup
   *    - #attached
   * @param array $context
   *   The page context to simulate. An array with the following keys:
   *   - path: the system path of the currently active page
   *   - front: whether the current page is the front page (which implies the
   *     current path might also be <front>)
   *   - language: the language code of the currently active page
   *   - query: the query string for the currently active page
   * @param array $expected_element
   *   The returned renderable array.
   *
   * @dataProvider providerTestSetLinkActiveClass
   */
  public function testSetLinkActiveClass(array $element, array $context, $expected_element) {
    $this->assertSame($expected_element, SystemController::setLinkActiveClass($element, $context));
  }

}
