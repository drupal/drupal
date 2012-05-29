<?php

/**
 * @file
 * Definition of Drupal\filter\Tests\FilterUnitTest.
 */

namespace Drupal\filter\Tests;

use Drupal\simpletest\UnitTestBase;
use stdClass;

/**
 * Unit tests for core filters.
 */
class FilterUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Filter module filters',
      'description' => 'Tests Filter module filters individually.',
      'group' => 'Filter',
    );
  }

  /**
   * Test the line break filter.
   */
  function testLineBreakFilter() {
    // Setup dummy filter object.
    $filter = new stdClass();
    $filter->callback = '_filter_autop';

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
    $source = $this->randomName($limit);
    $result = _filter_autop($source);
    $success = $this->assertEqual($result, '<p>' . $source . "</p>\n", t('Line break filter can process very long strings.'));
    if (!$success) {
      $this->verbose("\n" . $source . "\n<hr />\n" . $result);
    }
  }

  /**
   * Tests limiting allowed tags and XSS prevention.
   *
   * XSS tests assume that script is disallowed by default and src is allowed
   * by default, but on* and style attributes are disallowed.
   *
   * Script injection vectors mostly adopted from http://ha.ckers.org/xss.html.
   *
   * Relevant CVEs:
   * - CVE-2002-1806, ~CVE-2005-0682, ~CVE-2005-2106, CVE-2005-3973,
   *   CVE-2006-1226 (= rev. 1.112?), CVE-2008-0273, CVE-2008-3740.
   */
  function testFilterXSS() {
    // Tag stripping, different ways to work around removal of HTML tags.
    $f = filter_xss('<script>alert(0)</script>');
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping -- simple script without special characters.'));

    $f = filter_xss('<script src="http://www.example.com" />');
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping -- empty script with source.'));

    $f = filter_xss('<ScRipt sRc=http://www.example.com/>');
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- varying case.'));

    $f = filter_xss("<script\nsrc\n=\nhttp://www.example.com/\n>");
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- multiline tag.'));

    $f = filter_xss('<script/a src=http://www.example.com/a.js></script>');
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- non whitespace character after tag name.'));

    $f = filter_xss('<script/src=http://www.example.com/a.js></script>');
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- no space between tag and attribute.'));

    // Null between < and tag name works at least with IE6.
    $f = filter_xss("<\0scr\0ipt>alert(0)</script>");
    $this->assertNoNormalized($f, 'ipt', t('HTML tag stripping evasion -- breaking HTML with nulls.'));

    $f = filter_xss("<scrscriptipt src=http://www.example.com/a.js>");
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- filter just removing "script".'));

    $f = filter_xss('<<script>alert(0);//<</script>');
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- double opening brackets.'));

    $f = filter_xss('<script src=http://www.example.com/a.js?<b>');
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- no closing tag.'));

    // DRUPAL-SA-2008-047: This doesn't seem exploitable, but the filter should
    // work consistently.
    $f = filter_xss('<script>>');
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- double closing tag.'));

    $f = filter_xss('<script src=//www.example.com/.a>');
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- no scheme or ending slash.'));

    $f = filter_xss('<script src=http://www.example.com/.a');
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- no closing bracket.'));

    $f = filter_xss('<script src=http://www.example.com/ <');
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- opening instead of closing bracket.'));

    $f = filter_xss('<nosuchtag attribute="newScriptInjectionVector">');
    $this->assertNoNormalized($f, 'nosuchtag', t('HTML tag stripping evasion -- unknown tag.'));

    $f = filter_xss('<?xml:namespace ns="urn:schemas-microsoft-com:time">');
    $this->assertTrue(stripos($f, '<?xml') === FALSE, t('HTML tag stripping evasion -- starting with a question sign (processing instructions).'));

    $f = filter_xss('<t:set attributeName="innerHTML" to="&lt;script defer&gt;alert(0)&lt;/script&gt;">');
    $this->assertNoNormalized($f, 't:set', t('HTML tag stripping evasion -- colon in the tag name (namespaces\' tricks).'));

    $f = filter_xss('<img """><script>alert(0)</script>', array('img'));
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- a malformed image tag.'));

    $f = filter_xss('<blockquote><script>alert(0)</script></blockquote>', array('blockquote'));
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- script in a blockqoute.'));

    $f = filter_xss("<!--[if true]><script>alert(0)</script><![endif]-->");
    $this->assertNoNormalized($f, 'script', t('HTML tag stripping evasion -- script within a comment.'));

    // Dangerous attributes removal.
    $f = filter_xss('<p onmouseover="http://www.example.com/">', array('p'));
    $this->assertNoNormalized($f, 'onmouseover', t('HTML filter attributes removal -- events, no evasion.'));

    $f = filter_xss('<li style="list-style-image: url(javascript:alert(0))">', array('li'));
    $this->assertNoNormalized($f, 'style', t('HTML filter attributes removal -- style, no evasion.'));

    $f = filter_xss('<img onerror   =alert(0)>', array('img'));
    $this->assertNoNormalized($f, 'onerror', t('HTML filter attributes removal evasion -- spaces before equals sign.'));

    $f = filter_xss('<img onabort!#$%&()*~+-_.,:;?@[/|\]^`=alert(0)>', array('img'));
    $this->assertNoNormalized($f, 'onabort', t('HTML filter attributes removal evasion -- non alphanumeric characters before equals sign.'));

    $f = filter_xss('<img oNmediAError=alert(0)>', array('img'));
    $this->assertNoNormalized($f, 'onmediaerror', t('HTML filter attributes removal evasion -- varying case.'));

    // Works at least with IE6.
    $f = filter_xss("<img o\0nfocus\0=alert(0)>", array('img'));
    $this->assertNoNormalized($f, 'focus', t('HTML filter attributes removal evasion -- breaking with nulls.'));

    // Only whitelisted scheme names allowed in attributes.
    $f = filter_xss('<img src="javascript:alert(0)">', array('img'));
    $this->assertNoNormalized($f, 'javascript', t('HTML scheme clearing -- no evasion.'));

    $f = filter_xss('<img src=javascript:alert(0)>', array('img'));
    $this->assertNoNormalized($f, 'javascript', t('HTML scheme clearing evasion -- no quotes.'));

    // A bit like CVE-2006-0070.
    $f = filter_xss('<img src="javascript:confirm(0)">', array('img'));
    $this->assertNoNormalized($f, 'javascript', t('HTML scheme clearing evasion -- no alert ;)'));

    $f = filter_xss('<img src=`javascript:alert(0)`>', array('img'));
    $this->assertNoNormalized($f, 'javascript', t('HTML scheme clearing evasion -- grave accents.'));

    $f = filter_xss('<img dynsrc="javascript:alert(0)">', array('img'));
    $this->assertNoNormalized($f, 'javascript', t('HTML scheme clearing -- rare attribute.'));

    $f = filter_xss('<table background="javascript:alert(0)">', array('table'));
    $this->assertNoNormalized($f, 'javascript', t('HTML scheme clearing -- another tag.'));

    $f = filter_xss('<base href="javascript:alert(0);//">', array('base'));
    $this->assertNoNormalized($f, 'javascript', t('HTML scheme clearing -- one more attribute and tag.'));

    $f = filter_xss('<img src="jaVaSCriPt:alert(0)">', array('img'));
    $this->assertNoNormalized($f, 'javascript', t('HTML scheme clearing evasion -- varying case.'));

    $f = filter_xss('<img src=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#48;&#41;>', array('img'));
    $this->assertNoNormalized($f, 'javascript', t('HTML scheme clearing evasion -- UTF-8 decimal encoding.'));

    $f = filter_xss('<img src=&#00000106&#0000097&#00000118&#0000097&#00000115&#0000099&#00000114&#00000105&#00000112&#00000116&#0000058&#0000097&#00000108&#00000101&#00000114&#00000116&#0000040&#0000048&#0000041>', array('img'));
    $this->assertNoNormalized($f, 'javascript', t('HTML scheme clearing evasion -- long UTF-8 encoding.'));

    $f = filter_xss('<img src=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x30&#x29>', array('img'));
    $this->assertNoNormalized($f, 'javascript', t('HTML scheme clearing evasion -- UTF-8 hex encoding.'));

    $f = filter_xss("<img src=\"jav\tascript:alert(0)\">", array('img'));
    $this->assertNoNormalized($f, 'script', t('HTML scheme clearing evasion -- an embedded tab.'));

    $f = filter_xss('<img src="jav&#x09;ascript:alert(0)">', array('img'));
    $this->assertNoNormalized($f, 'script', t('HTML scheme clearing evasion -- an encoded, embedded tab.'));

    $f = filter_xss('<img src="jav&#x000000A;ascript:alert(0)">', array('img'));
    $this->assertNoNormalized($f, 'script', t('HTML scheme clearing evasion -- an encoded, embedded newline.'));

    // With &#xD; this test would fail, but the entity gets turned into
    // &amp;#xD;, so it's OK.
    $f = filter_xss('<img src="jav&#x0D;ascript:alert(0)">', array('img'));
    $this->assertNoNormalized($f, 'script', t('HTML scheme clearing evasion -- an encoded, embedded carriage return.'));

    $f = filter_xss("<img src=\"\n\n\nj\na\nva\ns\ncript:alert(0)\">", array('img'));
    $this->assertNoNormalized($f, 'cript', t('HTML scheme clearing evasion -- broken into many lines.'));

    $f = filter_xss("<img src=\"jav\0a\0\0cript:alert(0)\">", array('img'));
    $this->assertNoNormalized($f, 'cript', t('HTML scheme clearing evasion -- embedded nulls.'));

    $f = filter_xss('<img src=" &#14;  javascript:alert(0)">', array('img'));
    $this->assertNoNormalized($f, 'javascript', t('HTML scheme clearing evasion -- spaces and metacharacters before scheme.'));

    $f = filter_xss('<img src="vbscript:msgbox(0)">', array('img'));
    $this->assertNoNormalized($f, 'vbscript', t('HTML scheme clearing evasion -- another scheme.'));

    $f = filter_xss('<img src="nosuchscheme:notice(0)">', array('img'));
    $this->assertNoNormalized($f, 'nosuchscheme', t('HTML scheme clearing evasion -- unknown scheme.'));

    // Netscape 4.x javascript entities.
    $f = filter_xss('<br size="&{alert(0)}">', array('br'));
    $this->assertNoNormalized($f, 'alert', t('Netscape 4.x javascript entities.'));

    // DRUPAL-SA-2008-006: Invalid UTF-8, these only work as reflected XSS with
    // Internet Explorer 6.
    $f = filter_xss("<p arg=\"\xe0\">\" style=\"background-image: url(javascript:alert(0));\"\xe0<p>", array('p'));
    $this->assertNoNormalized($f, 'style', t('HTML filter -- invalid UTF-8.'));

    $f = filter_xss("\xc0aaa");
    $this->assertEqual($f, '', t('HTML filter -- overlong UTF-8 sequences.'));

    $f = filter_xss("Who&#039;s Online");
    $this->assertNormalized($f, "who's online", t('HTML filter -- html entity number'));

    $f = filter_xss("Who&amp;#039;s Online");
    $this->assertNormalized($f, "who&#039;s online", t('HTML filter -- encoded html entity number'));

    $f = filter_xss("Who&amp;amp;#039; Online");
    $this->assertNormalized($f, "who&amp;#039; online", t('HTML filter -- double encoded html entity number'));
  }

  /**
   * Test filter settings, defaults, access restrictions and similar.
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
    // Setup dummy filter object.
    $filter = new stdClass();
    $filter->settings = array(
      'allowed_html' => '<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>',
      'filter_html_help' => 1,
      'filter_html_nofollow' => 0,
    );

    // HTML filter is not able to secure some tags, these should never be
    // allowed.
    $f = _filter_html('<script />', $filter);
    $this->assertNoNormalized($f, 'script', t('HTML filter should always remove script tags.'));

    $f = _filter_html('<iframe />', $filter);
    $this->assertNoNormalized($f, 'iframe', t('HTML filter should always remove iframe tags.'));

    $f = _filter_html('<object />', $filter);
    $this->assertNoNormalized($f, 'object', t('HTML filter should always remove object tags.'));

    $f = _filter_html('<style />', $filter);
    $this->assertNoNormalized($f, 'style', t('HTML filter should always remove style tags.'));

    // Some tags make CSRF attacks easier, let the user take the risk herself.
    $f = _filter_html('<img />', $filter);
    $this->assertNoNormalized($f, 'img', t('HTML filter should remove img tags on default.'));

    $f = _filter_html('<input />', $filter);
    $this->assertNoNormalized($f, 'img', t('HTML filter should remove input tags on default.'));

    // Filtering content of some attributes is infeasible, these shouldn't be
    // allowed too.
    $f = _filter_html('<p style="display: none;" />', $filter);
    $this->assertNoNormalized($f, 'style', t('HTML filter should remove style attribute on default.'));

    $f = _filter_html('<p onerror="alert(0);" />', $filter);
    $this->assertNoNormalized($f, 'onerror', t('HTML filter should remove on* attributes on default.'));

    $f = _filter_html('<code onerror>&nbsp;</code>', $filter);
    $this->assertNoNormalized($f, 'onerror', t('HTML filter should remove empty on* attributes on default.'));
  }

  /**
   * Test the spam deterrent.
   */
  function testNoFollowFilter() {
    // Setup dummy filter object.
    $filter = new stdClass();
    $filter->settings = array(
      'allowed_html' => '<a>',
      'filter_html_help' => 1,
      'filter_html_nofollow' => 1,
    );

    // Test if the rel="nofollow" attribute is added, even if we try to prevent
    // it.
    $f = _filter_html('<a href="http://www.example.com/">text</a>', $filter);
    $this->assertNormalized($f, 'rel="nofollow"', t('Spam deterrent -- no evasion.'));

    $f = _filter_html('<A href="http://www.example.com/">text</a>', $filter);
    $this->assertNormalized($f, 'rel="nofollow"', t('Spam deterrent evasion -- capital A.'));

    $f = _filter_html("<a/href=\"http://www.example.com/\">text</a>", $filter);
    $this->assertNormalized($f, 'rel="nofollow"', t('Spam deterrent evasion -- non whitespace character after tag name.'));

    $f = _filter_html("<\0a\0 href=\"http://www.example.com/\">text</a>", $filter);
    $this->assertNormalized($f, 'rel="nofollow"', t('Spam deterrent evasion -- some nulls.'));

    $f = _filter_html('<a href="http://www.example.com/" rel="follow">text</a>', $filter);
    $this->assertNoNormalized($f, 'rel="follow"', t('Spam deterrent evasion -- with rel set - rel="follow" removed.'));
    $this->assertNormalized($f, 'rel="nofollow"', t('Spam deterrent evasion -- with rel set - rel="nofollow" added.'));
  }

  /**
   * Test the loose, admin HTML filter.
   */
  function testFilterXSSAdmin() {
    // DRUPAL-SA-2008-044
    $f = filter_xss_admin('<object />');
    $this->assertNoNormalized($f, 'object', t('Admin HTML filter -- should not allow object tag.'));

    $f = filter_xss_admin('<script />');
    $this->assertNoNormalized($f, 'script', t('Admin HTML filter -- should not allow script tag.'));

    $f = filter_xss_admin('<style /><iframe /><frame /><frameset /><meta /><link /><embed /><applet /><param /><layer />');
    $this->assertEqual($f, '', t('Admin HTML filter -- should never allow some tags.'));
  }

  /**
   * Tests the HTML escaping filter.
   *
   * check_plain() is not tested here.
   */
  function testHtmlEscapeFilter() {
    // Setup dummy filter object.
    $filter = new stdClass();
    $filter->callback = '_filter_html_escape';

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
    // Setup dummy filter object.
    $filter = new stdClass();
    $filter->callback = '_filter_url';
    $filter->settings = array(
      'filter_url_length' => 496,
    );
    // @todo Possible categories:
    // - absolute, mail, partial
    // - characters/encoding, surrounding markup, security

    // Create a e-mail that is too long.
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
ftp://user:pass@ftp.example.com/~home/dir1
sftp://user@nonstandardport:222/dir
ssh://192.168.0.100/srv/git/drupal.git
' => array(
        '<a href="http://trailingslash.com/">http://trailingslash.com/</a>' => TRUE,
        '<a href="http://www.trailingslash.com/">www.trailingslash.com/</a>' => TRUE,
        '<a href="http://host.com/some/path?query=foo&amp;bar[baz]=beer#fragment">http://host.com/some/path?query=foo&amp;bar[baz]=beer#fragment</a>' => TRUE,
        '<a href="http://www.host.com/some/path?query=foo&amp;bar[baz]=beer#fragment">www.host.com/some/path?query=foo&amp;bar[baz]=beer#fragment</a>' => TRUE,
        '<a href="http://twitter.com/#!/example/status/22376963142324226">http://twitter.com/#!/example/status/22376963142324226</a>' => TRUE,
        '<a href="ftp://user:pass@ftp.example.com/~home/dir1">ftp://user:pass@ftp.example.com/~home/dir1</a>' => TRUE,
        '<a href="sftp://user@nonstandardport:222/dir">sftp://user@nonstandardport:222/dir</a>' => TRUE,
        '<a href="ssh://192.168.0.100/srv/git/drupal.git">ssh://192.168.0.100/srv/git/drupal.git</a>' => TRUE,
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
      // $protocols = variable_get('filter_allowed_protocols'... (approx line 1325).
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
E-mail with trailing comma person@example.com,
Absolute URL with trailing question http://www.absolute.com?
Query string with trailing exclamation www.query.com/index.php?a=!
Partial URL with 3 trailing www.partial.periods...
E-mail with 3 trailing exclamations@example.com!!!
Absolute URL and query string with 2 different punctuation characters (http://www.example.com/q=abc).
' => array(
        'period <a href="http://www.partial.com">www.partial.com</a>.' => TRUE,
        'comma <a href="mailto:person@example.com">person@example.com</a>,' => TRUE,
        'question <a href="http://www.absolute.com">http://www.absolute.com</a>?' => TRUE,
        'exclamation <a href="http://www.query.com/index.php?a=">www.query.com/index.php?a=</a>!' => TRUE,
        'trailing <a href="http://www.partial.periods">www.partial.periods</a>...' => TRUE,
        'trailing <a href="mailto:exclamations@example.com">exclamations@example.com</a>!!!' => TRUE,
        'characters (<a href="http://www.example.com/q=abc">http://www.example.com/q=abc</a>).' => TRUE,
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
    $filter->settings['filter_url_length'] = 20;
    $tests = array(
      'www.trimmed.com/d/ff.ext?a=1&b=2#a1' => array(
        '<a href="http://www.trimmed.com/d/ff.ext?a=1&amp;b=2#a1">www.trimmed.com/d/ff...</a>' => TRUE,
      ),
    );
    $this->assertFilteredString($filter, $tests);
  }

  /**
   * Asserts multiple filter output expectations for multiple input strings.
   *
   * @param $filter
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
      $function = $filter->callback;
      $result = $function($source, $filter);
      foreach ($tasks as $value => $is_expected) {
        // Not using assertIdentical, since combination with strpos() is hard to grok.
        if ($is_expected) {
          $success = $this->assertTrue(strpos($result, $value) !== FALSE, t('@source: @value found.', array(
            '@source' => var_export($source, TRUE),
            '@value' => var_export($value, TRUE),
          )));
        }
        else {
          $success = $this->assertTrue(strpos($result, $value) === FALSE, t('@source: @value not found.', array(
            '@source' => var_export($source, TRUE),
            '@value' => var_export($value, TRUE),
          )));
        }
        if (!$success) {
          $this->verbose('Source:<pre>' . check_plain(var_export($source, TRUE)) . '</pre>'
            . '<hr />' . 'Result:<pre>' . check_plain(var_export($result, TRUE)) . '</pre>'
            . '<hr />' . ($is_expected ? 'Expected:' : 'Not expected:')
            . '<pre>' . check_plain(var_export($value, TRUE)) . '</pre>'
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
   * - Mix of absolute and partial URLs, and e-mail addresses in one content.
   */
  function testUrlFilterContent() {
    // Setup dummy filter object.
    $filter = new stdClass();
    $filter->settings = array(
      'filter_url_length' => 496,
    );
    $path = drupal_get_path('module', 'filter') . '/tests';

    $input = file_get_contents($path . '/filter.url-input.txt');
    $expected = file_get_contents($path . '/filter.url-output.txt');
    $result = _filter_url($input, $filter);
    $this->assertIdentical($result, $expected, 'Complex HTML document was correctly processed.');
  }

  /**
   * Test the HTML corrector filter.
   *
   * @todo This test could really use some validity checking function.
   */
  function testHtmlCorrectorFilter() {
    // Tag closing.
    $f = _filter_htmlcorrector('<p>text');
    $this->assertEqual($f, '<p>text</p>', t('HTML corrector -- tag closing at the end of input.'));

    $f = _filter_htmlcorrector('<p>text<p><p>text');
    $this->assertEqual($f, '<p>text</p><p></p><p>text</p>', t('HTML corrector -- tag closing.'));

    $f = _filter_htmlcorrector("<ul><li>e1<li>e2");
    $this->assertEqual($f, "<ul><li>e1</li><li>e2</li></ul>", t('HTML corrector -- unclosed list tags.'));

    $f = _filter_htmlcorrector('<div id="d">content');
    $this->assertEqual($f, '<div id="d">content</div>', t('HTML corrector -- unclosed tag with attribute.'));

    // XHTML slash for empty elements.
    $f = _filter_htmlcorrector('<hr><br>');
    $this->assertEqual($f, '<hr /><br />', t('HTML corrector -- XHTML closing slash.'));

    $f = _filter_htmlcorrector('<P>test</P>');
    $this->assertEqual($f, '<p>test</p>', t('HTML corrector -- Convert uppercased tags to proper lowercased ones.'));

    $f = _filter_htmlcorrector('<P>test</p>');
    $this->assertEqual($f, '<p>test</p>', t('HTML corrector -- Convert uppercased tags to proper lowercased ones.'));

    $f = _filter_htmlcorrector('test<hr />');
    $this->assertEqual($f, 'test<hr />', t('HTML corrector -- Let proper XHTML pass through.'));

    $f = _filter_htmlcorrector('test<hr/>');
    $this->assertEqual($f, 'test<hr />', t('HTML corrector -- Let proper XHTML pass through, but ensure there is a single space before the closing slash.'));

    $f = _filter_htmlcorrector('test<hr    />');
    $this->assertEqual($f, 'test<hr />', t('HTML corrector -- Let proper XHTML pass through, but ensure there are not too many spaces before the closing slash.'));

    $f = _filter_htmlcorrector('<span class="test" />');
    $this->assertEqual($f, '<span class="test"></span>', t('HTML corrector -- Convert XHTML that is properly formed but that would not be compatible with typical HTML user agents.'));

    $f = _filter_htmlcorrector('test1<br class="test">test2');
    $this->assertEqual($f, 'test1<br class="test" />test2', t('HTML corrector -- Automatically close single tags.'));

    $f = _filter_htmlcorrector('line1<hr>line2');
    $this->assertEqual($f, 'line1<hr />line2', t('HTML corrector -- Automatically close single tags.'));

    $f = _filter_htmlcorrector('line1<HR>line2');
    $this->assertEqual($f, 'line1<hr />line2', t('HTML corrector -- Automatically close single tags.'));

    $f = _filter_htmlcorrector('<img src="http://example.com/test.jpg">test</img>');
    $this->assertEqual($f, '<img src="http://example.com/test.jpg" />test', t('HTML corrector -- Automatically close single tags.'));

    $f = _filter_htmlcorrector('<br></br>');
    $this->assertEqual($f, '<br />', t("HTML corrector -- Transform empty tags to a single closed tag if the tag's content model is EMPTY."));

    $f = _filter_htmlcorrector('<div></div>');
    $this->assertEqual($f, '<div></div>', t("HTML corrector -- Do not transform empty tags to a single closed tag if the tag's content model is not EMPTY."));

    $f = _filter_htmlcorrector('<p>line1<br/><hr/>line2</p>');
    $this->assertEqual($f, '<p>line1<br /></p><hr />line2', t('HTML corrector -- Move non-inline elements outside of inline containers.'));

    $f = _filter_htmlcorrector('<p>line1<div>line2</div></p>');
    $this->assertEqual($f, '<p>line1</p><div>line2</div>', t('HTML corrector -- Move non-inline elements outside of inline containers.'));

    $f = _filter_htmlcorrector('<p>test<p>test</p>\n');
    $this->assertEqual($f, '<p>test</p><p>test</p>\n', t('HTML corrector -- Auto-close improperly nested tags.'));

    $f = _filter_htmlcorrector('<p>Line1<br><STRONG>bold stuff</b>');
    $this->assertEqual($f, '<p>Line1<br /><strong>bold stuff</strong></p>', t('HTML corrector -- Properly close unclosed tags, and remove useless closing tags.'));

    $f = _filter_htmlcorrector('test <!-- this is a comment -->');
    $this->assertEqual($f, 'test <!-- this is a comment -->', t('HTML corrector -- Do not touch HTML comments.'));

    $f = _filter_htmlcorrector('test <!--this is a comment-->');
    $this->assertEqual($f, 'test <!--this is a comment-->', t('HTML corrector -- Do not touch HTML comments.'));

    $f = _filter_htmlcorrector('test <!-- comment <p>another
    <strong>multiple</strong> line
    comment</p> -->');
    $this->assertEqual($f, 'test <!-- comment <p>another
    <strong>multiple</strong> line
    comment</p> -->', t('HTML corrector -- Do not touch HTML comments.'));

    $f = _filter_htmlcorrector('test <!-- comment <p>another comment</p> -->');
    $this->assertEqual($f, 'test <!-- comment <p>another comment</p> -->', t('HTML corrector -- Do not touch HTML comments.'));

    $f = _filter_htmlcorrector('test <!--break-->');
    $this->assertEqual($f, 'test <!--break-->', t('HTML corrector -- Do not touch HTML comments.'));

    $f = _filter_htmlcorrector('<p>test\n</p>\n');
    $this->assertEqual($f, '<p>test\n</p>\n', t('HTML corrector -- New-lines are accepted and kept as-is.'));

    $f = _filter_htmlcorrector('<p>دروبال');
    $this->assertEqual($f, '<p>دروبال</p>', t('HTML corrector -- Encoding is correctly kept.'));

    $f = _filter_htmlcorrector('<script type="text/javascript">alert("test")</script>');
    $this->assertEqual($f, '<script type="text/javascript">
<!--//--><![CDATA[// ><!--
alert("test")
//--><!]]>
</script>', t('HTML corrector -- CDATA added to script element'));

    $f = _filter_htmlcorrector('<p><script type="text/javascript">alert("test")</script></p>');
    $this->assertEqual($f, '<p><script type="text/javascript">
<!--//--><![CDATA[// ><!--
alert("test")
//--><!]]>
</script></p>', t('HTML corrector -- CDATA added to a nested script element'));

    $f = _filter_htmlcorrector('<p><style> /* Styling */ body {color:red}</style></p>');
    $this->assertEqual($f, '<p><style>
<!--/*--><![CDATA[/* ><!--*/
 /* Styling */ body {color:red}
/*--><!]]>*/
</style></p>', t('HTML corrector -- CDATA added to a style element.'));

    $filtered_data = _filter_htmlcorrector('<p><style>
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
      t('HTML corrector -- Existing cdata section @pattern_name properly escaped', array('@pattern_name' => '/*<![CDATA[*/'))
    );

    $filtered_data = _filter_htmlcorrector('<p><style>
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
      t('HTML corrector -- Existing cdata section @pattern_name properly escaped', array('@pattern_name' => '<!--/*--><![CDATA[/* ><!--*/'))
    );

    $filtered_data = _filter_htmlcorrector('<p><script type="text/javascript">
<!--//--><![CDATA[// ><!--
  alert("test");
//--><!]]>
</script></p>');
    $this->assertEqual($filtered_data, '<p><script type="text/javascript">
<!--//--><![CDATA[// ><!--

<!--//--><![CDATA[// ><!--
  alert("test");
//--><!]]]]><![CDATA[>

//--><!]]>
</script></p>',
      t('HTML corrector -- Existing cdata section @pattern_name properly escaped', array('@pattern_name' => '<!--//--><![CDATA[// ><!--'))
    );

    $filtered_data = _filter_htmlcorrector('<p><script type="text/javascript">
// <![CDATA[
  alert("test");
// ]]>
</script></p>');
    $this->assertEqual($filtered_data, '<p><script type="text/javascript">
<!--//--><![CDATA[// ><!--

// <![CDATA[
  alert("test");
// ]]]]><![CDATA[>

//--><!]]>
</script></p>',
      t('HTML corrector -- Existing cdata section @pattern_name properly escaped', array('@pattern_name' => '// <![CDATA['))
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
   *   Message to display if failed.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNormalized($haystack, $needle, $message = '', $group = 'Other') {
    return $this->assertTrue(strpos(strtolower(decode_entities($haystack)), $needle) !== FALSE, $message, $group);
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
   *   Message to display if failed.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNoNormalized($haystack, $needle, $message = '', $group = 'Other') {
    return $this->assertTrue(strpos(strtolower(decode_entities($haystack)), $needle) === FALSE, $message, $group);
  }
}
