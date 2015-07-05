<?php

/**
 * @file
 * Contains \Drupal\filter\Tests\FilterUnitTest.
 */

namespace Drupal\filter\Tests;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Render\RenderContext;
use Drupal\editor\EditorXssFilter\Standard;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterPluginCollection;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests Filter module filters individually.
 *
 * @group filter
 */
class FilterUnitTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'filter');

  /**
   * @var \Drupal\filter\Plugin\FilterInterface[]
   */
  protected $filters;

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('system'));

    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, array());
    $this->filters = $bag->getAll();
  }

  /**
   * Tests the align filter.
   */
  function testAlignFilter() {
    $filter = $this->filters['filter_align'];

    $test = function($input) use ($filter) {
      return $filter->process($input, 'und');
    };

    // No data-align attribute.
    $input = '<img src="llama.jpg" />';
    $expected = $input;
    $this->assertIdentical($expected, $test($input)->getProcessedText());

    // Data-align attribute: all 3 allowed values.
    $input = '<img src="llama.jpg" data-align="left" />';
    $expected = '<img src="llama.jpg" class="align-left" />';
    $this->assertIdentical($expected, $test($input)->getProcessedText());
    $input = '<img src="llama.jpg" data-align="center" />';
    $expected = '<img src="llama.jpg" class="align-center" />';
    $this->assertIdentical($expected, $test($input)->getProcessedText());
    $input = '<img src="llama.jpg" data-align="right" />';
    $expected = '<img src="llama.jpg" class="align-right" />';
    $this->assertIdentical($expected, $test($input)->getProcessedText());

    // Data-align attribute: a disallowed value.
    $input = '<img src="llama.jpg" data-align="left foobar" />';
    $expected = '<img src="llama.jpg" />';
    $this->assertIdentical($expected, $test($input)->getProcessedText());

    // Empty data-align attribute.
    $input = '<img src="llama.jpg" data-align="" />';
    $expected = '<img src="llama.jpg" />';
    $this->assertIdentical($expected, $test($input)->getProcessedText());

    // Ensure the filter also works with uncommon yet valid attribute quoting.
    $input = '<img src=llama.jpg data-align=right />';
    $expected = '<img src="llama.jpg" class="align-right" />';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());

    // Security test: attempt to inject an additional class.
    $input = '<img src="llama.jpg" data-align="center another-class-here" />';
    $expected = '<img src="llama.jpg" />';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());

    // Security test: attempt an XSS.
    $input = '<img src="llama.jpg" data-align="center \'onclick=\'alert(foo);" />';
    $expected = '<img src="llama.jpg" />';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());
  }

  /**
   * Tests the caption filter.
   */
  function testCaptionFilter() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $filter = $this->filters['filter_caption'];

    $test = function($input) use ($filter, $renderer) {
      return $renderer->executeInRenderContext(new RenderContext(), function () use ($input, $filter) {
        return $filter->process($input, 'und');
      });
    };

    $attached_library = array(
      'library' => array(
        'filter/caption',
      ),
    );

    // No data-caption attribute.
    $input = '<img src="llama.jpg" />';
    $expected = $input;
    $this->assertIdentical($expected, $test($input)->getProcessedText());

    // Data-caption attribute.
    $input = '<img src="llama.jpg" data-caption="Loquacious llama!" />';
    $expected = '<figure><img src="llama.jpg" /><figcaption>Loquacious llama!</figcaption></figure>';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());
    $this->assertIdentical($attached_library, $output->getAttachments());

    // Empty data-caption attribute.
    $input = '<img src="llama.jpg" data-caption="" />';
    $expected = '<img src="llama.jpg" />';
    $this->assertIdentical($expected, $test($input)->getProcessedText());

    // HTML entities in the caption.
    $input = '<img src="llama.jpg" data-caption="&ldquo;Loquacious llama!&rdquo;" />';
    $expected = '<figure><img src="llama.jpg" /><figcaption>“Loquacious llama!”</figcaption></figure>';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());
    $this->assertIdentical($attached_library, $output->getAttachments());

    // HTML encoded as HTML entities in data-caption attribute.
    $input = '<img src="llama.jpg" data-caption="&lt;em&gt;Loquacious llama!&lt;/em&gt;" />';
    $expected = '<figure><img src="llama.jpg" /><figcaption><em>Loquacious llama!</em></figcaption></figure>';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());
    $this->assertIdentical($attached_library, $output->getAttachments());

    // HTML (not encoded as HTML entities) in data-caption attribute, which is
    // not allowed by the HTML spec, but may happen when people manually write
    // HTML, so we explicitly support it.
    $input = '<img src="llama.jpg" data-caption="<em>Loquacious llama!</em>" />';
    $expected = '<figure><img src="llama.jpg" /><figcaption><em>Loquacious llama!</em></figcaption></figure>';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());
    $this->assertIdentical($attached_library, $output->getAttachments());

    // Security test: attempt an XSS.
    $input = '<img src="llama.jpg" data-caption="<script>alert(\'Loquacious llama!\')</script>" />';
    $expected = '<figure><img src="llama.jpg" /><figcaption>alert(\'Loquacious llama!\')</figcaption></figure>';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());
    $this->assertIdentical($attached_library, $output->getAttachments());

    // Ensure the filter also works with uncommon yet valid attribute quoting.
    $input = '<img src=llama.jpg data-caption=\'Loquacious llama!\' />';
    $expected = '<figure><img src="llama.jpg" /><figcaption>Loquacious llama!</figcaption></figure>';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());
    $this->assertIdentical($attached_library, $output->getAttachments());

    // Finally, ensure that this also works on any other tag.
    $input = '<video src="llama.jpg" data-caption="Loquacious llama!" />';
    $expected = '<figure><video src="llama.jpg"></video><figcaption>Loquacious llama!</figcaption></figure>';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());
    $this->assertIdentical($attached_library, $output->getAttachments());
    $input = '<foobar data-caption="Loquacious llama!">baz</foobar>';
    $expected = '<figure><foobar>baz</foobar><figcaption>Loquacious llama!</figcaption></figure>';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());
    $this->assertIdentical($attached_library, $output->getAttachments());

    // So far we've tested that the caption filter works correctly. But we also
    // want to make sure that it works well in tandem with the "Limit allowed
    // HTML tags" filter, which it is typically used with.
    $html_filter = $this->filters['filter_html'];
    $html_filter->setConfiguration(array(
      'settings' => array(
        'allowed_html' => '<img>',
        'filter_html_help' => 1,
        'filter_html_nofollow' => 0,
      )
    ));
    $test_with_html_filter = function ($input) use ($filter, $html_filter, $renderer) {
      return $renderer->executeInRenderContext(new RenderContext(), function () use ($input, $filter, $html_filter) {
        // 1. Apply HTML filter's processing step.
        $output = $html_filter->process($input, 'und');
        // 2. Apply caption filter's processing step.
        $output = $filter->process($output, 'und');
        return $output->getProcessedText();
      });
    };
    // Editor XSS filter.
    $test_editor_xss_filter = function ($input) {
      $dummy_filter_format = FilterFormat::create();
      return Standard::filterXss($input, $dummy_filter_format);
    };

    // All the tricky cases encountered at https://www.drupal.org/node/2105841.
    // A plain URL preceded by text.
    $input = '<img data-caption="See https://www.drupal.org" src="llama.jpg" />';
    $expected = '<figure><img src="llama.jpg" /><figcaption>See https://www.drupal.org</figcaption></figure>';
    $this->assertIdentical($expected, $test_with_html_filter($input));
    $this->assertIdentical($input, $test_editor_xss_filter($input));

    // An anchor.
    $input = '<img data-caption="This is a &lt;a href=&quot;https://www.drupal.org&quot;&gt;quick&lt;/a&gt; test…" src="llama.jpg" />';
    $expected = '<figure><img src="llama.jpg" /><figcaption>This is a <a href="https://www.drupal.org">quick</a> test…</figcaption></figure>';
    $this->assertIdentical($expected, $test_with_html_filter($input));
    $this->assertIdentical($input, $test_editor_xss_filter($input));

    // A plain URL surrounded by parentheses.
    $input = '<img data-caption="(https://www.drupal.org)" src="llama.jpg" />';
    $expected = '<figure><img src="llama.jpg" /><figcaption>(https://www.drupal.org)</figcaption></figure>';
    $this->assertIdentical($expected, $test_with_html_filter($input));
    $this->assertIdentical($input, $test_editor_xss_filter($input));

    // A source being credited.
    $input = '<img data-caption="Source: Wikipedia" src="llama.jpg" />';
    $expected = '<figure><img src="llama.jpg" /><figcaption>Source: Wikipedia</figcaption></figure>';
    $this->assertIdentical($expected, $test_with_html_filter($input));
    $this->assertIdentical($input, $test_editor_xss_filter($input));

    // A source being credited, without a space after the colon.
    $input = '<img data-caption="Source:Wikipedia" src="llama.jpg" />';
    $expected = '<figure><img src="llama.jpg" /><figcaption>Source:Wikipedia</figcaption></figure>';
    $this->assertIdentical($expected, $test_with_html_filter($input));
    $this->assertIdentical($input, $test_editor_xss_filter($input));

    // A pretty crazy edge case where we have two colons.
    $input = '<img data-caption="Interesting (Scope resolution operator ::)" src="llama.jpg" />';
    $expected = '<figure><img src="llama.jpg" /><figcaption>Interesting (Scope resolution operator ::)</figcaption></figure>';
    $this->assertIdentical($expected, $test_with_html_filter($input));
    $this->assertIdentical($input, $test_editor_xss_filter($input));

    // An evil anchor (to ensure XSS filtering is applied to the caption also).
    $input = '<img data-caption="This is an &lt;a href=&quot;javascript:alert();&quot;&gt;evil&lt;/a&gt; test…" src="llama.jpg" />';
    $expected = '<figure><img src="llama.jpg" /><figcaption>This is an <a href="alert();">evil</a> test…</figcaption></figure>';
    $this->assertIdentical($expected, $test_with_html_filter($input));
    $expected_xss_filtered = '<img data-caption="This is an &lt;a href=&quot;alert();&quot;&gt;evil&lt;/a&gt; test…" src="llama.jpg" />';
    $this->assertIdentical($expected_xss_filtered, $test_editor_xss_filter($input));
  }

  /**
   * Tests the combination of the align and caption filters.
   */
  function testAlignAndCaptionFilters() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $align_filter = $this->filters['filter_align'];
    $caption_filter = $this->filters['filter_caption'];

    $test = function($input) use ($align_filter, $caption_filter, $renderer) {
      return $renderer->executeInRenderContext(new RenderContext(), function () use ($input, $align_filter, $caption_filter) {
        return $caption_filter->process($align_filter->process($input, 'und'), 'und');
      });
    };

    $attached_library = array(
      'library' => array(
        'filter/caption',
      ),
    );

    // Both data-caption and data-align attributes: all 3 allowed values for the
    // data-align attribute.
    $input = '<img src="llama.jpg" data-caption="Loquacious llama!" data-align="left" />';
    $expected = '<figure class="align-left"><img src="llama.jpg" /><figcaption>Loquacious llama!</figcaption></figure>';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());
    $this->assertIdentical($attached_library, $output->getAttachments());
    $input = '<img src="llama.jpg" data-caption="Loquacious llama!" data-align="center" />';
    $expected = '<figure class="align-center"><img src="llama.jpg" /><figcaption>Loquacious llama!</figcaption></figure>';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());
    $this->assertIdentical($attached_library, $output->getAttachments());
    $input = '<img src="llama.jpg" data-caption="Loquacious llama!" data-align="right" />';
    $expected = '<figure class="align-right"><img src="llama.jpg" /><figcaption>Loquacious llama!</figcaption></figure>';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());
    $this->assertIdentical($attached_library, $output->getAttachments());

    // Both data-caption and data-align attributes, but a disallowed data-align
    // attribute value.
    $input = '<img src="llama.jpg" data-caption="Loquacious llama!" data-align="left foobar" />';
    $expected = '<figure><img src="llama.jpg" /><figcaption>Loquacious llama!</figcaption></figure>';
    $output = $test($input);
    $this->assertIdentical($expected, $output->getProcessedText());
    $this->assertIdentical($attached_library, $output->getAttachments());
  }

  /**
   * Tests the line break filter.
   */
  function testLineBreakFilter() {
    // Get FilterAutoP object.
    $filter = $this->filters['filter_autop'];

    // Since the line break filter naturally needs plenty of newlines in test
    // strings and expectations, we're using "\n" instead of regular newlines
    // here.
    $tests = array(
      // Single line breaks should be changed to <br /> tags, while paragraphs
      // separated with double line breaks should be enclosed with <p></p> tags.
      "aaa\nbbb\n\nccc" => array(
        "<p>aaa<br />\nbbb</p>\n<p>ccc</p>" => TRUE,
      ),
      // Skip contents of certain block tags entirely.
      "<script>aaa\nbbb\n\nccc</script>
<style>aaa\nbbb\n\nccc</style>
<pre>aaa\nbbb\n\nccc</pre>
<object>aaa\nbbb\n\nccc</object>
<iframe>aaa\nbbb\n\nccc</iframe>
" => array(
        "<script>aaa\nbbb\n\nccc</script>" => TRUE,
        "<style>aaa\nbbb\n\nccc</style>" => TRUE,
        "<pre>aaa\nbbb\n\nccc</pre>" => TRUE,
        "<object>aaa\nbbb\n\nccc</object>" => TRUE,
        "<iframe>aaa\nbbb\n\nccc</iframe>" => TRUE,
      ),
      // Skip comments entirely.
      "One. <!-- comment --> Two.\n<!--\nThree.\n-->\n" => array(
        '<!-- comment -->' => TRUE,
        "<!--\nThree.\n-->" => TRUE,
      ),
      // Resulting HTML should produce matching paragraph tags.
      '<p><div>  </div></p>' => array(
        "<p>\n<div>  </div>\n</p>" => TRUE,
      ),
      '<div><p>  </p></div>' => array(
        "<div>\n</div>" => TRUE,
      ),
      '<blockquote><pre>aaa</pre></blockquote>' => array(
        "<blockquote><pre>aaa</pre></blockquote>" => TRUE,
      ),
      "<pre>aaa\nbbb\nccc</pre>\nddd\neee" => array(
        "<pre>aaa\nbbb\nccc</pre>" => TRUE,
        "<p>ddd<br />\neee</p>" => TRUE,
      ),
      // Comments remain unchanged and subsequent lines/paragraphs are
      // transformed normally.
      "aaa<!--comment-->\n\nbbb\n\nccc\n\nddd<!--comment\nwith linebreak-->\n\neee\n\nfff" => array(
        "<p>aaa</p>\n<!--comment--><p>\nbbb</p>\n<p>ccc</p>\n<p>ddd</p>" => TRUE,
        "<!--comment\nwith linebreak--><p>\neee</p>\n<p>fff</p>" => TRUE,
      ),
      // Check that a comment in a PRE will result that the text after
      // the comment, but still in PRE, is not transformed.
      "<pre>aaa\nbbb<!-- comment -->\n\nccc</pre>\nddd" => array(
        "<pre>aaa\nbbb<!-- comment -->\n\nccc</pre>" => TRUE,
      ),
      // Bug 810824, paragraphs were appearing around iframe tags.
      "<iframe>aaa</iframe>\n\n" => array(
        "<p><iframe>aaa</iframe></p>" => FALSE,
      ),
    );
    $this->assertFilteredString($filter, $tests);

    // Very long string hitting PCRE limits.
    $limit = max(ini_get('pcre.backtrack_limit'), ini_get('pcre.recursion_limit'));
    $source = $this->randomMachineName($limit);
    $result = _filter_autop($source);
    $success = $this->assertEqual($result, '<p>' . $source . "</p>\n", 'Line break filter can process very long strings.');
    if (!$success) {
      $this->verbose("\n" . $source . "\n<hr />\n" . $result);
    }
  }


  /**
   * Tests filter settings, defaults, access restrictions and similar.
   *
   * @todo This is for functions like filter_filter and check_markup, whose
   *   functionality is not completely focused on filtering. Some ideas:
   *   restricting formats according to user permissions, proper cache
   *   handling, defaults -- allowed tags/attributes/protocols.
   *
   * @todo It is possible to add script, iframe etc. to allowed tags, but this
   *   makes HTML filter completely ineffective.
   *
   * @todo Class, id, name and xmlns should be added to disallowed attributes,
   *   or better a whitelist approach should be used for that too.
   */
  function testHtmlFilter() {
    // Get FilterHtml object.
    $filter = $this->filters['filter_html'];
    $filter->setConfiguration(array(
      'settings' => array(
        'allowed_html' => '<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd> <br>',
        'filter_html_help' => 1,
        'filter_html_nofollow' => 0,
      )
    ));

    // HTML filter is not able to secure some tags, these should never be
    // allowed.
    $f = _filter_html('<script />', $filter);
    $this->assertNoNormalized($f, 'script', 'HTML filter should always remove script tags.');

    $f = _filter_html('<iframe />', $filter);
    $this->assertNoNormalized($f, 'iframe', 'HTML filter should always remove iframe tags.');

    $f = _filter_html('<object />', $filter);
    $this->assertNoNormalized($f, 'object', 'HTML filter should always remove object tags.');

    $f = _filter_html('<style />', $filter);
    $this->assertNoNormalized($f, 'style', 'HTML filter should always remove style tags.');

    // Some tags make CSRF attacks easier, let the user take the risk herself.
    $f = _filter_html('<img />', $filter);
    $this->assertNoNormalized($f, 'img', 'HTML filter should remove img tags on default.');

    $f = _filter_html('<input />', $filter);
    $this->assertNoNormalized($f, 'img', 'HTML filter should remove input tags on default.');

    // Filtering content of some attributes is infeasible, these shouldn't be
    // allowed too.
    $f = _filter_html('<p style="display: none;" />', $filter);
    $this->assertNoNormalized($f, 'style', 'HTML filter should remove style attribute on default.');

    $f = _filter_html('<p onerror="alert(0);" />', $filter);
    $this->assertNoNormalized($f, 'onerror', 'HTML filter should remove on* attributes on default.');

    $f = _filter_html('<code onerror>&nbsp;</code>', $filter);
    $this->assertNoNormalized($f, 'onerror', 'HTML filter should remove empty on* attributes on default.');

    $f = _filter_html('<br>', $filter);
    $this->assertNormalized($f, '<br>', 'HTML filter should allow line breaks.');

    $f = _filter_html('<br />', $filter);
    $this->assertNormalized($f, '<br />', 'HTML filter should allow self-closing line breaks.');
  }

  /**
   * Tests the spam deterrent.
   */
  function testNoFollowFilter() {
    // Get FilterHtml object.
    $filter = $this->filters['filter_html'];
    $filter->setConfiguration(array(
      'settings' => array(
        'allowed_html' => '<a>',
        'filter_html_help' => 1,
        'filter_html_nofollow' => 1,
      )
    ));

    // Test if the rel="nofollow" attribute is added, even if we try to prevent
    // it.
    $f = _filter_html('<a href="http://www.example.com/">text</a>', $filter);
    $this->assertNormalized($f, 'rel="nofollow"', 'Spam deterrent -- no evasion.');

    $f = _filter_html('<A href="http://www.example.com/">text</a>', $filter);
    $this->assertNormalized($f, 'rel="nofollow"', 'Spam deterrent evasion -- capital A.');

    $f = _filter_html("<a/href=\"http://www.example.com/\">text</a>", $filter);
    $this->assertNormalized($f, 'rel="nofollow"', 'Spam deterrent evasion -- non whitespace character after tag name.');

    $f = _filter_html("<\0a\0 href=\"http://www.example.com/\">text</a>", $filter);
    $this->assertNormalized($f, 'rel="nofollow"', 'Spam deterrent evasion -- some nulls.');

    $f = _filter_html('<a href="http://www.example.com/" rel="follow">text</a>', $filter);
    $this->assertNoNormalized($f, 'rel="follow"', 'Spam deterrent evasion -- with rel set - rel="follow" removed.');
    $this->assertNormalized($f, 'rel="nofollow"', 'Spam deterrent evasion -- with rel set - rel="nofollow" added.');
  }

  /**
   * Tests the HTML escaping filter.
   *
   * \Drupal\Component\Utility\SafeMarkup::checkPlain() is not tested here.
   */
  function testHtmlEscapeFilter() {
    // Get FilterHtmlEscape object.
    $filter = $this->filters['filter_html_escape'];

    $tests = array(
      "   One. <!-- \"comment\" --> Two'.\n<p>Three.</p>\n    " => array(
        "One. &lt;!-- &quot;comment&quot; --&gt; Two&#039;.\n&lt;p&gt;Three.&lt;/p&gt;" => TRUE,
        '   One.' => FALSE,
        "</p>\n    " => FALSE,
      ),
    );
    $this->assertFilteredString($filter, $tests);
  }

  /**
   * Tests the URL filter.
   */
  function testUrlFilter() {
    // Get FilterUrl object.
    $filter = $this->filters['filter_url'];
    $filter->setConfiguration(array(
      'settings' => array(
        'filter_url_length' => 496,
      )
    ));

    // @todo Possible categories:
    // - absolute, mail, partial
    // - characters/encoding, surrounding markup, security

    // Create a email that is too long.
    $long_email = str_repeat('a', 254) . '@example.com';
    $too_long_email = str_repeat('b', 255) . '@example.com';


    // Filter selection/pattern matching.
    $tests = array(
      // HTTP URLs.
      '
http://example.com or www.example.com
' => array(
        '<a href="http://example.com">http://example.com</a>' => TRUE,
        '<a href="http://www.example.com">www.example.com</a>' => TRUE,
      ),
      // MAILTO URLs.
      '
person@example.com or mailto:person2@example.com or ' . $long_email . ' but not ' . $too_long_email . '
' => array(
        '<a href="mailto:person@example.com">person@example.com</a>' => TRUE,
        '<a href="mailto:person2@example.com">mailto:person2@example.com</a>' => TRUE,
        '<a href="mailto:' . $long_email . '">' . $long_email . '</a>' => TRUE,
        '<a href="mailto:' . $too_long_email . '">' . $too_long_email . '</a>' => FALSE,
      ),
      // URI parts and special characters.
      '
http://trailingslash.com/ or www.trailingslash.com/
http://host.com/some/path?query=foo&bar[baz]=beer#fragment or www.host.com/some/path?query=foo&bar[baz]=beer#fragment
http://twitter.com/#!/example/status/22376963142324226
http://example.com/@user/
ftp://user:pass@ftp.example.com/~home/dir1
sftp://user@nonstandardport:222/dir
ssh://192.168.0.100/srv/git/drupal.git
' => array(
        '<a href="http://trailingslash.com/">http://trailingslash.com/</a>' => TRUE,
        '<a href="http://www.trailingslash.com/">www.trailingslash.com/</a>' => TRUE,
        '<a href="http://host.com/some/path?query=foo&amp;bar[baz]=beer#fragment">http://host.com/some/path?query=foo&amp;bar[baz]=beer#fragment</a>' => TRUE,
        '<a href="http://www.host.com/some/path?query=foo&amp;bar[baz]=beer#fragment">www.host.com/some/path?query=foo&amp;bar[baz]=beer#fragment</a>' => TRUE,
        '<a href="http://twitter.com/#!/example/status/22376963142324226">http://twitter.com/#!/example/status/22376963142324226</a>' => TRUE,
        '<a href="http://example.com/@user/">http://example.com/@user/</a>' => TRUE,
        '<a href="ftp://user:pass@ftp.example.com/~home/dir1">ftp://user:pass@ftp.example.com/~home/dir1</a>' => TRUE,
        '<a href="sftp://user@nonstandardport:222/dir">sftp://user@nonstandardport:222/dir</a>' => TRUE,
        '<a href="ssh://192.168.0.100/srv/git/drupal.git">ssh://192.168.0.100/srv/git/drupal.git</a>' => TRUE,
      ),
      // International Unicode characters.
      '
http://пример.испытание/
http://مثال.إختبار/
http://例子.測試/
http://12345.中国/
http://例え.テスト/
http://dréißig-bücher.de/
http://méxico-mañana.es/
' => array(
        '<a href="http://пример.испытание/">http://пример.испытание/</a>' => TRUE,
        '<a href="http://مثال.إختبار/">http://مثال.إختبار/</a>' => TRUE,
        '<a href="http://例子.測試/">http://例子.測試/</a>' => TRUE,
        '<a href="http://12345.中国/">http://12345.中国/</a>' => TRUE,
        '<a href="http://例え.テスト/">http://例え.テスト/</a>' => TRUE,
        '<a href="http://dréißig-bücher.de/">http://dréißig-bücher.de/</a>' => TRUE,
        '<a href="http://méxico-mañana.es/">http://méxico-mañana.es/</a>' => TRUE,
      ),
      // Encoding.
      '
http://ampersand.com/?a=1&b=2
http://encoded.com/?a=1&amp;b=2
' => array(
        '<a href="http://ampersand.com/?a=1&amp;b=2">http://ampersand.com/?a=1&amp;b=2</a>' => TRUE,
        '<a href="http://encoded.com/?a=1&amp;b=2">http://encoded.com/?a=1&amp;b=2</a>' => TRUE,
      ),
      // Domain name length.
      '
www.ex.ex or www.example.example or www.toolongdomainexampledomainexampledomainexampledomainexampledomain or
me@me.tv
' => array(
        '<a href="http://www.ex.ex">www.ex.ex</a>' => TRUE,
        '<a href="http://www.example.example">www.example.example</a>' => TRUE,
        'http://www.toolong' => FALSE,
        '<a href="mailto:me@me.tv">me@me.tv</a>' => TRUE,
      ),
      // Absolute URL protocols.
      // The list to test is found in the beginning of _filter_url() at
      // $protocols = $this->config('system.filter')->get('protocols').
      '
https://example.com,
ftp://ftp.example.com,
news://example.net,
telnet://example,
irc://example.host,
ssh://odd.geek,
sftp://secure.host?,
webcal://calendar,
rtsp://127.0.0.1,
not foo://disallowed.com.
' => array(
        'href="https://example.com"' => TRUE,
        'href="ftp://ftp.example.com"' => TRUE,
        'href="news://example.net"' => TRUE,
        'href="telnet://example"' => TRUE,
        'href="irc://example.host"' => TRUE,
        'href="ssh://odd.geek"' => TRUE,
        'href="sftp://secure.host"' => TRUE,
        'href="webcal://calendar"' => TRUE,
        'href="rtsp://127.0.0.1"' => TRUE,
        'href="foo://disallowed.com"' => FALSE,
        'not foo://disallowed.com.' => TRUE,
      ),
    );
    $this->assertFilteredString($filter, $tests);

    // Surrounding text/punctuation.
    $tests = array(
      '
Partial URL with trailing period www.partial.com.
Email with trailing comma person@example.com,
Absolute URL with trailing question http://www.absolute.com?
Query string with trailing exclamation www.query.com/index.php?a=!
Partial URL with 3 trailing www.partial.periods...
Email with 3 trailing exclamations@example.com!!!
Absolute URL and query string with 2 different punctuation characters (http://www.example.com/q=abc).
Partial URL with brackets in the URL as well as surrounded brackets (www.foo.com/more_(than)_one_(parens)).
Absolute URL with square brackets in the URL as well as surrounded brackets [https://www.drupal.org/?class[]=1]
Absolute URL with quotes "https://www.drupal.org/sample"

' => array(
        'period <a href="http://www.partial.com">www.partial.com</a>.' => TRUE,
        'comma <a href="mailto:person@example.com">person@example.com</a>,' => TRUE,
        'question <a href="http://www.absolute.com">http://www.absolute.com</a>?' => TRUE,
        'exclamation <a href="http://www.query.com/index.php?a=">www.query.com/index.php?a=</a>!' => TRUE,
        'trailing <a href="http://www.partial.periods">www.partial.periods</a>...' => TRUE,
        'trailing <a href="mailto:exclamations@example.com">exclamations@example.com</a>!!!' => TRUE,
        'characters (<a href="http://www.example.com/q=abc">http://www.example.com/q=abc</a>).' => TRUE,
        'brackets (<a href="http://www.foo.com/more_(than)_one_(parens)">www.foo.com/more_(than)_one_(parens)</a>).' => TRUE,
        'brackets [<a href="https://www.drupal.org/?class[]=1">https://www.drupal.org/?class[]=1</a>]' => TRUE,
        'quotes "<a href="https://www.drupal.org/sample">https://www.drupal.org/sample</a>"' => TRUE,
      ),
      '
(www.parenthesis.com/dir?a=1&b=2#a)
' => array(
        '(<a href="http://www.parenthesis.com/dir?a=1&amp;b=2#a">www.parenthesis.com/dir?a=1&amp;b=2#a</a>)' => TRUE,
      ),
    );
    $this->assertFilteredString($filter, $tests);

    // Surrounding markup.
    $tests = array(
      '
<p xmlns="www.namespace.com" />
<p xmlns="http://namespace.com">
An <a href="http://example.com" title="Read more at www.example.info...">anchor</a>.
</p>
' => array(
        '<p xmlns="www.namespace.com" />' => TRUE,
        '<p xmlns="http://namespace.com">' => TRUE,
        'href="http://www.namespace.com"' => FALSE,
        'href="http://namespace.com"' => FALSE,
        'An <a href="http://example.com" title="Read more at www.example.info...">anchor</a>.' => TRUE,
      ),
      '
Not <a href="foo">www.relative.com</a> or <a href="http://absolute.com">www.absolute.com</a>
but <strong>http://www.strong.net</strong> or <em>www.emphasis.info</em>
' => array(
        '<a href="foo">www.relative.com</a>' => TRUE,
        'href="http://www.relative.com"' => FALSE,
        '<a href="http://absolute.com">www.absolute.com</a>' => TRUE,
        '<strong><a href="http://www.strong.net">http://www.strong.net</a></strong>' => TRUE,
        '<em><a href="http://www.emphasis.info">www.emphasis.info</a></em>' => TRUE,
      ),
      '
Test <code>using www.example.com the code tag</code>.
' => array(
        'href' => FALSE,
        'http' => FALSE,
      ),
      '
Intro.
<blockquote>
Quoted text linking to www.example.com, written by person@example.com, originating from http://origin.example.com. <code>@see www.usage.example.com or <em>www.example.info</em> bla bla</code>.
</blockquote>

Outro.
' => array(
        'href="http://www.example.com"' => TRUE,
        'href="mailto:person@example.com"' => TRUE,
        'href="http://origin.example.com"' => TRUE,
        'http://www.usage.example.com' => FALSE,
        'http://www.example.info' => FALSE,
        'Intro.' => TRUE,
        'Outro.' => TRUE,
      ),
      '
Unknown tag <x>containing x and www.example.com</x>? And a tag <pooh>beginning with p and containing www.example.pooh with p?</pooh>
' => array(
        'href="http://www.example.com"' => TRUE,
        'href="http://www.example.pooh"' => TRUE,
      ),
      '
<p>Test &lt;br/&gt;: This is a www.example17.com example <strong>with</strong> various http://www.example18.com tags. *<br/>
 It is important www.example19.com to *<br/>test different URLs and http://www.example20.com in the same paragraph. *<br>
HTML www.example21.com soup by person@example22.com can litererally http://www.example23.com contain *img*<img> anything. Just a www.example24.com with http://www.example25.com thrown in. www.example26.com from person@example27.com with extra http://www.example28.com.
' => array(
        'href="http://www.example17.com"' => TRUE,
        'href="http://www.example18.com"' => TRUE,
        'href="http://www.example19.com"' => TRUE,
        'href="http://www.example20.com"' => TRUE,
        'href="http://www.example21.com"' => TRUE,
        'href="mailto:person@example22.com"' => TRUE,
        'href="http://www.example23.com"' => TRUE,
        'href="http://www.example24.com"' => TRUE,
        'href="http://www.example25.com"' => TRUE,
        'href="http://www.example26.com"' => TRUE,
        'href="mailto:person@example27.com"' => TRUE,
        'href="http://www.example28.com"' => TRUE,
      ),
      '
<script>
<!--
  // @see www.example.com
  var exampleurl = "http://example.net";
-->
<!--//--><![CDATA[//><!--
  // @see www.example.com
  var exampleurl = "http://example.net";
//--><!]]>
</script>
' => array(
        'href="http://www.example.com"' => FALSE,
        'href="http://example.net"' => FALSE,
      ),
      '
<style>body {
  background: url(http://example.com/pixel.gif);
}</style>
' => array(
        'href' => FALSE,
      ),
      '
<!-- Skip any URLs like www.example.com in comments -->
' => array(
        'href' => FALSE,
      ),
      '
<!-- Skip any URLs like
www.example.com with a newline in comments -->
' => array(
        'href' => FALSE,
      ),
      '
<!-- Skip any URLs like www.comment.com in comments. <p>Also ignore http://commented.out/markup.</p> -->
' => array(
        'href' => FALSE,
      ),
      '
<dl>
<dt>www.example.com</dt>
<dd>http://example.com</dd>
<dd>person@example.com</dd>
<dt>Check www.example.net</dt>
<dd>Some text around http://www.example.info by person@example.info?</dd>
</dl>
' => array(
        'href="http://www.example.com"' => TRUE,
        'href="http://example.com"' => TRUE,
        'href="mailto:person@example.com"' => TRUE,
        'href="http://www.example.net"' => TRUE,
        'href="http://www.example.info"' => TRUE,
        'href="mailto:person@example.info"' => TRUE,
      ),
      '
<div>www.div.com</div>
<ul>
<li>http://listitem.com</li>
<li class="odd">www.class.listitem.com</li>
</ul>
' => array(
        '<div><a href="http://www.div.com">www.div.com</a></div>' => TRUE,
        '<li><a href="http://listitem.com">http://listitem.com</a></li>' => TRUE,
        '<li class="odd"><a href="http://www.class.listitem.com">www.class.listitem.com</a></li>' => TRUE,
      ),
    );
    $this->assertFilteredString($filter, $tests);

    // URL trimming.
    $filter->setConfiguration(array(
      'settings' => array(
        'filter_url_length' => 20,
      )
    ));
    $tests = array(
      'www.trimmed.com/d/ff.ext?a=1&b=2#a1' => array(
        '<a href="http://www.trimmed.com/d/ff.ext?a=1&amp;b=2#a1">www.trimmed.com/d/f…</a>' => TRUE,
      ),
    );
    $this->assertFilteredString($filter, $tests);
  }

  /**
   * Asserts multiple filter output expectations for multiple input strings.
   *
   * @param FilterInterface $filter
   *   A input filter object.
   * @param $tests
   *   An associative array, whereas each key is an arbitrary input string and
   *   each value is again an associative array whose keys are filter output
   *   strings and whose values are Booleans indicating whether the output is
   *   expected or not.
   *
   * For example:
   * @code
   * $tests = array(
   *   'Input string' => array(
   *     '<p>Input string</p>' => TRUE,
   *     'Input string<br' => FALSE,
   *   ),
   * );
   * @endcode
   */
  function assertFilteredString($filter, $tests) {
    foreach ($tests as $source => $tasks) {
      $result = $filter->process($source, $filter)->getProcessedText();
      foreach ($tasks as $value => $is_expected) {
        // Not using assertIdentical, since combination with strpos() is hard to grok.
        if ($is_expected) {
          $success = $this->assertTrue(strpos($result, $value) !== FALSE, format_string('@source: @value found. Filtered result: @result.', array(
            '@source' => var_export($source, TRUE),
            '@value' => var_export($value, TRUE),
            '@result' => var_export($result, TRUE),
          )));
        }
        else {
          $success = $this->assertTrue(strpos($result, $value) === FALSE, format_string('@source: @value not found. Filtered result: @result.', array(
            '@source' => var_export($source, TRUE),
            '@value' => var_export($value, TRUE),
            '@result' => var_export($result, TRUE),
          )));
        }
        if (!$success) {
          $this->verbose('Source:<pre>' . SafeMarkup::checkPlain(var_export($source, TRUE)) . '</pre>'
            . '<hr />' . 'Result:<pre>' . SafeMarkup::checkPlain(var_export($result, TRUE)) . '</pre>'
            . '<hr />' . ($is_expected ? 'Expected:' : 'Not expected:')
            . '<pre>' . SafeMarkup::checkPlain(var_export($value, TRUE)) . '</pre>'
          );
        }
      }
    }
  }

  /**
   * Tests URL filter on longer content.
   *
   * Filters based on regular expressions should also be tested with a more
   * complex content than just isolated test lines.
   * The most common errors are:
   * - accidental '*' (greedy) match instead of '*?' (minimal) match.
   * - only matching first occurrence instead of all.
   * - newlines not matching '.*'.
   *
   * This test covers:
   * - Document with multiple newlines and paragraphs (two newlines).
   * - Mix of several HTML tags, invalid non-HTML tags, tags to ignore and HTML
   *   comments.
   * - Empty HTML tags (BR, IMG).
   * - Mix of absolute and partial URLs, and email addresses in one content.
   */
  function testUrlFilterContent() {
    // Get FilterUrl object.
    $filter = $this->filters['filter_url'];
    $filter->setConfiguration(array(
      'settings' => array(
        'filter_url_length' => 496,
      )
    ));
    $path = drupal_get_path('module', 'filter') . '/tests';

    $input = file_get_contents($path . '/filter.url-input.txt');
    $expected = file_get_contents($path . '/filter.url-output.txt');
    $result = _filter_url($input, $filter);
    $this->assertIdentical($result, $expected, 'Complex HTML document was correctly processed.');
  }

  /**
   * Tests the HTML corrector filter.
   *
   * @todo This test could really use some validity checking function.
   */
  function testHtmlCorrectorFilter() {
    // Tag closing.
    $f = Html::normalize('<p>text');
    $this->assertEqual($f, '<p>text</p>', 'HTML corrector -- tag closing at the end of input.');

    $f = Html::normalize('<p>text<p><p>text');
    $this->assertEqual($f, '<p>text</p><p></p><p>text</p>', 'HTML corrector -- tag closing.');

    $f = Html::normalize("<ul><li>e1<li>e2");
    $this->assertEqual($f, "<ul><li>e1</li><li>e2</li></ul>", 'HTML corrector -- unclosed list tags.');

    $f = Html::normalize('<div id="d">content');
    $this->assertEqual($f, '<div id="d">content</div>', 'HTML corrector -- unclosed tag with attribute.');

    // XHTML slash for empty elements.
    $f = Html::normalize('<hr><br>');
    $this->assertEqual($f, '<hr /><br />', 'HTML corrector -- XHTML closing slash.');

    $f = Html::normalize('<P>test</P>');
    $this->assertEqual($f, '<p>test</p>', 'HTML corrector -- Convert uppercased tags to proper lowercased ones.');

    $f = Html::normalize('<P>test</p>');
    $this->assertEqual($f, '<p>test</p>', 'HTML corrector -- Convert uppercased tags to proper lowercased ones.');

    $f = Html::normalize('test<hr />');
    $this->assertEqual($f, 'test<hr />', 'HTML corrector -- Let proper XHTML pass through.');

    $f = Html::normalize('test<hr/>');
    $this->assertEqual($f, 'test<hr />', 'HTML corrector -- Let proper XHTML pass through, but ensure there is a single space before the closing slash.');

    $f = Html::normalize('test<hr    />');
    $this->assertEqual($f, 'test<hr />', 'HTML corrector -- Let proper XHTML pass through, but ensure there are not too many spaces before the closing slash.');

    $f = Html::normalize('<span class="test" />');
    $this->assertEqual($f, '<span class="test"></span>', 'HTML corrector -- Convert XHTML that is properly formed but that would not be compatible with typical HTML user agents.');

    $f = Html::normalize('test1<br class="test">test2');
    $this->assertEqual($f, 'test1<br class="test" />test2', 'HTML corrector -- Automatically close single tags.');

    $f = Html::normalize('line1<hr>line2');
    $this->assertEqual($f, 'line1<hr />line2', 'HTML corrector -- Automatically close single tags.');

    $f = Html::normalize('line1<HR>line2');
    $this->assertEqual($f, 'line1<hr />line2', 'HTML corrector -- Automatically close single tags.');

    $f = Html::normalize('<img src="http://example.com/test.jpg">test</img>');
    $this->assertEqual($f, '<img src="http://example.com/test.jpg" />test', 'HTML corrector -- Automatically close single tags.');

    $f = Html::normalize('<br></br>');
    $this->assertEqual($f, '<br />', "HTML corrector -- Transform empty tags to a single closed tag if the tag's content model is EMPTY.");

    $f = Html::normalize('<div></div>');
    $this->assertEqual($f, '<div></div>', "HTML corrector -- Do not transform empty tags to a single closed tag if the tag's content model is not EMPTY.");

    $f = Html::normalize('<p>line1<br/><hr/>line2</p>');
    $this->assertEqual($f, '<p>line1<br /></p><hr />line2', 'HTML corrector -- Move non-inline elements outside of inline containers.');

    $f = Html::normalize('<p>line1<div>line2</div></p>');
    $this->assertEqual($f, '<p>line1</p><div>line2</div>', 'HTML corrector -- Move non-inline elements outside of inline containers.');

    $f = Html::normalize('<p>test<p>test</p>\n');
    $this->assertEqual($f, '<p>test</p><p>test</p>\n', 'HTML corrector -- Auto-close improperly nested tags.');

    $f = Html::normalize('<p>Line1<br><STRONG>bold stuff</b>');
    $this->assertEqual($f, '<p>Line1<br /><strong>bold stuff</strong></p>', 'HTML corrector -- Properly close unclosed tags, and remove useless closing tags.');

    $f = Html::normalize('test <!-- this is a comment -->');
    $this->assertEqual($f, 'test <!-- this is a comment -->', 'HTML corrector -- Do not touch HTML comments.');

    $f = Html::normalize('test <!--this is a comment-->');
    $this->assertEqual($f, 'test <!--this is a comment-->', 'HTML corrector -- Do not touch HTML comments.');

    $f = Html::normalize('test <!-- comment <p>another
    <strong>multiple</strong> line
    comment</p> -->');
    $this->assertEqual($f, 'test <!-- comment <p>another
    <strong>multiple</strong> line
    comment</p> -->', 'HTML corrector -- Do not touch HTML comments.');

    $f = Html::normalize('test <!-- comment <p>another comment</p> -->');
    $this->assertEqual($f, 'test <!-- comment <p>another comment</p> -->', 'HTML corrector -- Do not touch HTML comments.');

    $f = Html::normalize('test <!--break-->');
    $this->assertEqual($f, 'test <!--break-->', 'HTML corrector -- Do not touch HTML comments.');

    $f = Html::normalize('<p>test\n</p>\n');
    $this->assertEqual($f, '<p>test\n</p>\n', 'HTML corrector -- New-lines are accepted and kept as-is.');

    $f = Html::normalize('<p>دروبال');
    $this->assertEqual($f, '<p>دروبال</p>', 'HTML corrector -- Encoding is correctly kept.');

    $f = Html::normalize('<script>alert("test")</script>');
    $this->assertEqual($f, '<script>
<!--//--><![CDATA[// ><!--
alert("test")
//--><!]]>
</script>', 'HTML corrector -- CDATA added to script element');

    $f = Html::normalize('<p><script>alert("test")</script></p>');
    $this->assertEqual($f, '<p><script>
<!--//--><![CDATA[// ><!--
alert("test")
//--><!]]>
</script></p>', 'HTML corrector -- CDATA added to a nested script element');

    $f = Html::normalize('<p><style> /* Styling */ body {color:red}</style></p>');
    $this->assertEqual($f, '<p><style>
<!--/*--><![CDATA[/* ><!--*/
 /* Styling */ body {color:red}
/*--><!]]>*/
</style></p>', 'HTML corrector -- CDATA added to a style element.');

    $filtered_data = Html::normalize('<p><style>
/*<![CDATA[*/
/* Styling */
body {color:red}
/*]]>*/
</style></p>');
    $this->assertEqual($filtered_data, '<p><style>
<!--/*--><![CDATA[/* ><!--*/

/*<![CDATA[*/
/* Styling */
body {color:red}
/*]]]]><![CDATA[>*/

/*--><!]]>*/
</style></p>',
      format_string('HTML corrector -- Existing cdata section @pattern_name properly escaped', array('@pattern_name' => '/*<![CDATA[*/'))
    );

    $filtered_data = Html::normalize('<p><style>
  <!--/*--><![CDATA[/* ><!--*/
  /* Styling */
  body {color:red}
  /*--><!]]>*/
</style></p>');
    $this->assertEqual($filtered_data, '<p><style>
<!--/*--><![CDATA[/* ><!--*/

  <!--/*--><![CDATA[/* ><!--*/
  /* Styling */
  body {color:red}
  /*--><!]]]]><![CDATA[>*/

/*--><!]]>*/
</style></p>',
      format_string('HTML corrector -- Existing cdata section @pattern_name properly escaped', array('@pattern_name' => '<!--/*--><![CDATA[/* ><!--*/'))
    );

    $filtered_data = Html::normalize('<p><script>
<!--//--><![CDATA[// ><!--
  alert("test");
//--><!]]>
</script></p>');
    $this->assertEqual($filtered_data, '<p><script>
<!--//--><![CDATA[// ><!--

<!--//--><![CDATA[// ><!--
  alert("test");
//--><!]]]]><![CDATA[>

//--><!]]>
</script></p>',
      format_string('HTML corrector -- Existing cdata section @pattern_name properly escaped', array('@pattern_name' => '<!--//--><![CDATA[// ><!--'))
    );

    $filtered_data = Html::normalize('<p><script>
// <![CDATA[
  alert("test");
// ]]>
</script></p>');
    $this->assertEqual($filtered_data, '<p><script>
<!--//--><![CDATA[// ><!--

// <![CDATA[
  alert("test");
// ]]]]><![CDATA[>

//--><!]]>
</script></p>',
      format_string('HTML corrector -- Existing cdata section @pattern_name properly escaped', array('@pattern_name' => '// <![CDATA['))
    );

  }

  /**
   * Asserts that a text transformed to lowercase with HTML entities decoded does contains a given string.
   *
   * Otherwise fails the test with a given message, similar to all the
   * SimpleTest assert* functions.
   *
   * Note that this does not remove nulls, new lines and other characters that
   * could be used to obscure a tag or an attribute name.
   *
   * @param $haystack
   *   Text to look in.
   * @param $needle
   *   Lowercase, plain text to look for.
   * @param $message
   *   (optional) Message to display if failed. Defaults to an empty string.
   * @param $group
   *   (optional) The group this message belongs to. Defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNormalized($haystack, $needle, $message = '', $group = 'Other') {
    return $this->assertTrue(strpos(strtolower(Html::decodeEntities($haystack)), $needle) !== FALSE, $message, $group);
  }

  /**
   * Asserts that text transformed to lowercase with HTML entities decoded does not contain a given string.
   *
   * Otherwise fails the test with a given message, similar to all the
   * SimpleTest assert* functions.
   *
   * Note that this does not remove nulls, new lines, and other character that
   * could be used to obscure a tag or an attribute name.
   *
   * @param $haystack
   *   Text to look in.
   * @param $needle
   *   Lowercase, plain text to look for.
   * @param $message
   *   (optional) Message to display if failed. Defaults to an empty string.
   * @param $group
   *   (optional) The group this message belongs to. Defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNoNormalized($haystack, $needle, $message = '', $group = 'Other') {
    return $this->assertTrue(strpos(strtolower(Html::decodeEntities($haystack)), $needle) === FALSE, $message, $group);
  }
}
