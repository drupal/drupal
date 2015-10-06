<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\EventSubscriber\ActiveLinkResponseFilterTest.
 */

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Component\Serialization\Json;
use Drupal\Core\EventSubscriber\ActiveLinkResponseFilter;
use Drupal\Core\Template\Attribute;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\ActiveLinkResponseFilter
 * @group EventSubscriber
 */
class ActiveLinkResponseFilterTest extends UnitTestCase {

  /**
   * Provides test data for testSetLinkActiveClass().
   *
   * @see \Drupal\Core\EventSubscriber\ActiveLinkResponseFilter::setLinkActiveClass()
   */
  public function providerTestSetLinkActiveClass() {
    // Define all the variations that *don't* affect whether or not an
    // "is-active" class is set, but that should remain unchanged:
    // - surrounding HTML
    // - tags for which to test the setting of the "is-active" class
    // - content of said tags
    $edge_case_html5 = '<audio src="foo.ogg">
  <track kind="captions" src="foo.en.vtt" srclang="en" label="English">
  <track kind="captions" src="foo.sv.vtt" srclang="sv" label="Svenska">
</audio>';
    $html = array(
      // Simple HTML.
      0 => array('prefix' => '<div><p>', 'suffix' => '</p></div>'),
      // Tricky HTML5 example that's unsupported by PHP <=5.4's DOMDocument:
      // https://www.drupal.org/comment/7938201#comment-7938201.
      1 => array('prefix' => '<div><p>', 'suffix' => '</p>' . $edge_case_html5 . '</div>'),
      // Multi-byte content *before* the HTML that needs the "is-active" class.
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

    // Define all variations that *do* affect whether or not an "is-active"
    // class is set: all possible situations that can be encountered.
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

            // Build the target markup. If no "is-active" class should be set,
            // the resulting HTML should be identical. Otherwise, it should get
            // an "is-active" class, either by extending an existing "class"
            // attribute or by adding a "class" attribute.
            $target_markup = NULL;
            if (!$situation['is active']) {
              $target_markup = $source_markup;
            }
            else {
              $active_attributes = $situation['attributes'];
              if (!isset($active_attributes['class'])) {
                $active_attributes['class'] = array();
              }
              $active_attributes['class'][] = 'is-active';
              $target_markup = $create_markup(new Attribute($active_attributes));
            }

            $data[] = array($source_markup, $situation['context']['path'], $situation['context']['front'], $situation['context']['language'], $situation['context']['query'], $target_markup);
          }
        }
      }
    }

    // Test case to verify that the 'is-active' class is not added multiple
    // times.
    $data[] = [
      0 => '<a data-drupal-link-system-path="&lt;front&gt;">Once</a> <a data-drupal-link-system-path="&lt;front&gt;">Twice</a>',
      1 => '',
      2 => TRUE,
      3 => 'en',
      4 => [],
      5 => '<a data-drupal-link-system-path="&lt;front&gt;" class="is-active">Once</a> <a data-drupal-link-system-path="&lt;front&gt;" class="is-active">Twice</a>',
    ];

    // Test cases to verify that the 'is-active' class is added when on the
    // front page, and there are two different kinds of matching links on the
    // page:
    // - the matching path (the resolved front page path)
    // - the special matching path ('<front>')
    $front_special_link = '<a data-drupal-link-system-path="&lt;front&gt;">Front</a>';
    $front_special_link_active = '<a data-drupal-link-system-path="&lt;front&gt;" class="is-active">Front</a>';
    $front_path_link = '<a data-drupal-link-system-path="myfrontpage">Front Path</a>';
    $front_path_link_active = '<a data-drupal-link-system-path="myfrontpage" class="is-active">Front Path</a>';
    $data[] = [
      0 => $front_path_link . ' ' . $front_special_link,
      1 => 'myfrontpage',
      2 => TRUE,
      3 => 'en',
      4 => [],
      5 => $front_path_link_active . ' ' . $front_special_link_active,
    ];
    $data[] = [
      0 => $front_special_link . ' ' . $front_path_link,
      1 => 'myfrontpage',
      2 => TRUE,
      3 => 'en',
      4 => [],
      5 => $front_special_link_active . ' ' . $front_path_link_active,
    ];

    // Test cases to verify that links to the front page do not get the
    // 'is-active' class when not on the front page.
    $other_link = '<a data-drupal-link-system-path="otherpage">Other page</a>';
    $other_link_active = '<a data-drupal-link-system-path="otherpage" class="is-active">Other page</a>';
    $data['<front>-and-other-link-on-other-path'] = [
      0 => $front_special_link . ' ' . $other_link,
      1 => 'otherpage',
      2 => FALSE,
      3 => 'en',
      4 => [],
      5 => $front_special_link . ' ' . $other_link_active,
    ];
    $data['front-and-other-link-on-other-path'] = [
      0 => $front_path_link . ' ' . $other_link,
      1 => 'otherpage',
      2 => FALSE,
      3 => 'en',
      4 => [],
      5 => $front_path_link . ' ' . $other_link_active,
    ];
    $data['other-and-<front>-link-on-other-path'] = [
      0 => $other_link . ' ' . $front_special_link,
      1 => 'otherpage',
      2 => FALSE,
      3 => 'en',
      4 => [],
      5 => $other_link_active . ' ' . $front_special_link,
    ];
    $data['other-and-front-link-on-other-path'] = [
      0 => $other_link . ' ' . $front_path_link,
      1 => 'otherpage',
      2 => FALSE,
      3 => 'en',
      4 => [],
      5 => $other_link_active . ' ' . $front_path_link,
    ];
    return $data;
  }

  /**
   * Tests setLinkActiveClass().
   *
   * @param string $html_markup
   *   The original HTML markup.
   * @param string $current_path
   *   The system path of the currently active page.
   * @param bool $is_front
   *   Whether the current page is the front page (which implies the current
   *   path might also be <front>).
   * @param string $url_language
   *   The language code of the current URL.
   * @param array $query
   *   The query string for the current URL.
   * @param string $expected_html_markup
   *   The expected updated HTML markup.
   *
   * @dataProvider providerTestSetLinkActiveClass
   * @covers ::setLinkActiveClass
   */
  public function testSetLinkActiveClass($html_markup, $current_path, $is_front, $url_language, array $query, $expected_html_markup) {
    $this->assertSame($expected_html_markup, ActiveLinkResponseFilter::setLinkActiveClass($html_markup, $current_path, $is_front, $url_language, $query));
  }

}
