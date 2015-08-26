<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Mail\HtmlToTextTest.
 */

namespace Drupal\system\Tests\Mail;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Site\Settings;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for \Drupal\Core\Mail\MailFormatHelper::htmlToText().
 *
 * @group Mail
 */
class HtmlToTextTest extends WebTestBase {
  /**
   * Converts a string to its PHP source equivalent for display in test messages.
   *
   * @param $text
   *   The text string to convert.
   *
   * @return
   *   An HTML representation of the text string that, when displayed in a
   *   browser, represents the PHP source code equivalent of $text.
   */
  protected function stringToHtml($text) {
    return '"' .
      str_replace(
        array("\n", ' '),
        array('\n', '&nbsp;'),
        Html::escape($text)
      ) . '"';
  }

  /**
   * Helper function to test \Drupal\Core\Mail\MailFormatHelper::htmlToText().
   *
   * @param $html
   *   The source HTML string to be converted.
   * @param $text
   *   The expected result of converting $html to text.
   * @param $message
   *   A text message to display in the assertion message.
   * @param $allowed_tags
   *   (optional) An array of allowed tags, or NULL to default to the full
   *   set of tags supported by
   *   \Drupal\Core\Mail\MailFormatHelper::htmlToText().
   */
  protected function assertHtmlToText($html, $text, $message, $allowed_tags = NULL) {
    preg_match_all('/<([a-z0-6]+)/', Unicode::strtolower($html), $matches);
    $tested_tags = implode(', ', array_unique($matches[1]));
    $message .= ' (' . $tested_tags . ')';
    $result = MailFormatHelper::htmlToText($html, $allowed_tags);
    $pass = $this->assertEqual($result, $text, Html::escape($message));
    $verbose = 'html = <pre>' . $this->stringToHtml($html)
      . '</pre><br />' . 'result = <pre>' . $this->stringToHtml($result)
      . '</pre><br />' . 'expected = <pre>' . $this->stringToHtml($text)
      . '</pre>';
    $this->verbose($verbose);
    if (!$pass) {
      $this->pass("Previous test verbose info:<br />$verbose");
    }
  }

  /**
   * Test supported tags of \Drupal\Core\Mail\MailFormatHelper::htmlToText().
   */
  public function testTags() {
    global $base_path, $base_url;
    $tests = array(
      // @todo Trailing linefeeds should be trimmed.
      '<a href = "https://www.drupal.org">Drupal.org</a>' => "Drupal.org [1]\n\n[1] https://www.drupal.org\n",
      // @todo Footer URLs should be absolute.
      "<a href = \"$base_path\">Homepage</a>" => "Homepage [1]\n\n[1] $base_url/\n",
      '<address>Drupal</address>' => "Drupal\n",
      // @todo The <address> tag is currently not supported.
      '<address>Drupal</address><address>Drupal</address>' => "DrupalDrupal\n",
      '<b>Drupal</b>' => "*Drupal*\n",
      // @todo There should be a space between the '>' and the text.
      '<blockquote>Drupal</blockquote>' => ">Drupal\n",
      '<blockquote>Drupal</blockquote><blockquote>Drupal</blockquote>' => ">Drupal\n>Drupal\n",
      '<br />Drupal<br />Drupal<br /><br />Drupal' => "Drupal\nDrupal\nDrupal\n",
      '<br/>Drupal<br/>Drupal<br/><br/>Drupal' => "Drupal\nDrupal\nDrupal\n",
      // @todo There should be two line breaks before the paragraph.
      '<br/>Drupal<br/>Drupal<br/><br/>Drupal<p>Drupal</p>' => "Drupal\nDrupal\nDrupal\nDrupal\n\n",
      '<div>Drupal</div>' => "Drupal\n",
      // @todo The <div> tag is currently not supported.
      '<div>Drupal</div><div>Drupal</div>' => "DrupalDrupal\n",
      '<em>Drupal</em>' => "/Drupal/\n",
      '<h1>Drupal</h1>' => "======== DRUPAL ==============================================================\n\n",
      '<h1>Drupal</h1><p>Drupal</p>' => "======== DRUPAL ==============================================================\n\nDrupal\n\n",
      '<h2>Drupal</h2>' => "-------- DRUPAL --------------------------------------------------------------\n\n",
      '<h2>Drupal</h2><p>Drupal</p>' => "-------- DRUPAL --------------------------------------------------------------\n\nDrupal\n\n",
      '<h3>Drupal</h3>' => ".... Drupal\n\n",
      '<h3>Drupal</h3><p>Drupal</p>' => ".... Drupal\n\nDrupal\n\n",
      '<h4>Drupal</h4>' => ".. Drupal\n\n",
      '<h4>Drupal</h4><p>Drupal</p>' => ".. Drupal\n\nDrupal\n\n",
      '<h5>Drupal</h5>' => "Drupal\n\n",
      '<h5>Drupal</h5><p>Drupal</p>' => "Drupal\n\nDrupal\n\n",
      '<h6>Drupal</h6>' => "Drupal\n\n",
      '<h6>Drupal</h6><p>Drupal</p>' => "Drupal\n\nDrupal\n\n",
      '<hr />Drupal<hr />' => "------------------------------------------------------------------------------\nDrupal\n------------------------------------------------------------------------------\n",
      '<hr/>Drupal<hr/>' => "------------------------------------------------------------------------------\nDrupal\n------------------------------------------------------------------------------\n",
      '<hr/>Drupal<hr/><p>Drupal</p>' => "------------------------------------------------------------------------------\nDrupal\n------------------------------------------------------------------------------\nDrupal\n\n",
      '<i>Drupal</i>' => "/Drupal/\n",
      '<p>Drupal</p>' => "Drupal\n\n",
      '<p>Drupal</p><p>Drupal</p>' => "Drupal\n\nDrupal\n\n",
      '<strong>Drupal</strong>' => "*Drupal*\n",
      // @todo Tables are currently not supported.
      '<table><tr><td>Drupal</td><td>Drupal</td></tr><tr><td>Drupal</td><td>Drupal</td></tr></table>' => "DrupalDrupalDrupalDrupal\n",
      '<table><tr><td>Drupal</td></tr></table><p>Drupal</p>' => "Drupal\nDrupal\n\n",
      // @todo The <u> tag is currently not supported.
      '<u>Drupal</u>' => "Drupal\n",
      '<ul><li>Drupal</li></ul>' => " * Drupal\n\n",
      '<ul><li>Drupal <em>Drupal</em> Drupal</li></ul>' => " * Drupal /Drupal/ Drupal\n\n",
      // @todo Lines containing nothing but spaces should be trimmed.
      '<ul><li>Drupal</li><li><ol><li>Drupal</li><li>Drupal</li></ol></li></ul>' => " * Drupal\n *  1) Drupal\n    2) Drupal\n   \n\n",
      '<ul><li>Drupal</li><li><ol><li>Drupal</li></ol></li><li>Drupal</li></ul>' => " * Drupal\n *  1) Drupal\n   \n * Drupal\n\n",
      '<ul><li>Drupal</li><li>Drupal</li></ul>' => " * Drupal\n * Drupal\n\n",
      '<ul><li>Drupal</li></ul><p>Drupal</p>' => " * Drupal\n\nDrupal\n\n",
      '<ol><li>Drupal</li></ol>' => " 1) Drupal\n\n",
      '<ol><li>Drupal</li><li><ul><li>Drupal</li><li>Drupal</li></ul></li></ol>' => " 1) Drupal\n 2)  * Drupal\n     * Drupal\n    \n\n",
      '<ol><li>Drupal</li><li>Drupal</li></ol>' => " 1) Drupal\n 2) Drupal\n\n",
      '<ol>Drupal</ol>' => "Drupal\n\n",
      '<ol><li>Drupal</li></ol><p>Drupal</p>' => " 1) Drupal\n\nDrupal\n\n",
      '<dl><dt>Drupal</dt></dl>' => "Drupal\n\n",
      '<dl><dt>Drupal</dt><dd>Drupal</dd></dl>' => "Drupal\n    Drupal\n\n",
      '<dl><dt>Drupal</dt><dd>Drupal</dd><dt>Drupal</dt><dd>Drupal</dd></dl>' => "Drupal\n    Drupal\nDrupal\n    Drupal\n\n",
      '<dl><dt>Drupal</dt><dd>Drupal</dd></dl><p>Drupal</p>' => "Drupal\n    Drupal\n\nDrupal\n\n",
      '<dl><dt>Drupal<dd>Drupal</dl>' => "Drupal\n    Drupal\n\n",
      '<dl><dt>Drupal</dt></dl><p>Drupal</p>' => "Drupal\n\nDrupal\n\n",
      // @todo Again, lines containing only spaces should be trimmed.
      '<ul><li>Drupal</li><li><dl><dt>Drupal</dt><dd>Drupal</dd><dt>Drupal</dt><dd>Drupal</dd></dl></li><li>Drupal</li></ul>' => " * Drupal\n * Drupal\n       Drupal\n   Drupal\n       Drupal\n   \n * Drupal\n\n",
      // Tests malformed HTML tags.
      '<br>Drupal<br>Drupal' => "Drupal\nDrupal\n",
      '<hr>Drupal<hr>Drupal' => "------------------------------------------------------------------------------\nDrupal\n------------------------------------------------------------------------------\nDrupal\n",
      '<ol><li>Drupal<li>Drupal</ol>' => " 1) Drupal\n 2) Drupal\n\n",
      '<ul><li>Drupal <em>Drupal</em> Drupal</ul></ul>' => " * Drupal /Drupal/ Drupal\n\n",
      '<ul><li>Drupal<li>Drupal</ol>' => " * Drupal\n * Drupal\n\n",
      '<ul><li>Drupal<li>Drupal</ul>' => " * Drupal\n * Drupal\n\n",
      '<ul>Drupal</ul>' => "Drupal\n\n",
      'Drupal</ul></ol></dl><li>Drupal' => "Drupal\n * Drupal\n",
      '<dl>Drupal</dl>' => "Drupal\n\n",
      '<dl>Drupal</dl><p>Drupal</p>' => "Drupal\n\nDrupal\n\n",
      '<dt>Drupal</dt>' => "Drupal\n",
      // Tests some unsupported HTML tags.
      '<html>Drupal</html>' => "Drupal\n",
      // @todo Perhaps the contents of <script> tags should be dropped.
      '<script type="text/javascript">Drupal</script>' => "Drupal\n",
      // A couple of tests for Unicode characters.
      '<q>I <em>will</em> be back…</q>' => "I /will/ be back…\n",
      'FrançAIS is ÜBER-åwesome' => "FrançAIS is ÜBER-åwesome\n",
    );

    foreach ($tests as $html => $text) {
      $this->assertHtmlToText($html, $text, 'Supported tags');
    }
  }

  /**
   * Tests allowing tags in \Drupal\Core\Mail\MailFormatHelper::htmlToText().
   */
  public function testDrupalHtmlToTextArgs() {
    // The second parameter of \Drupal\Core\Mail\MailFormatHelper::htmlToText()
    // overrules the allowed tags.
    $this->assertHtmlToText(
      'Drupal <b>Drupal</b> Drupal',
      "Drupal *Drupal* Drupal\n",
      'Allowed <b> tag found',
      array('b')
    );
    $this->assertHtmlToText(
      'Drupal <h1>Drupal</h1> Drupal',
      "Drupal Drupal Drupal\n",
      'Disallowed <h1> tag not found',
      array('b')
    );

    $this->assertHtmlToText(
      'Drupal <p><em><b>Drupal</b></em><p> Drupal',
      "Drupal Drupal Drupal\n",
      'Disallowed <p>, <em>, and <b> tags not found',
      array('a', 'br', 'h1')
    );

    $this->assertHtmlToText(
      '<html><body>Drupal</body></html>',
      "Drupal\n",
      'Unsupported <html> and <body> tags not found',
      array('html', 'body')
    );
  }

  /**
   * Test that whitespace is collapsed.
   */
  public function testDrupalHtmltoTextCollapsesWhitespace() {
    $input = "<p>Drupal  Drupal\n\nDrupal<pre>Drupal  Drupal\n\nDrupal</pre>Drupal  Drupal\n\nDrupal</p>";
    // @todo The whitespace should be collapsed.
    $collapsed = "Drupal  Drupal\n\nDrupalDrupal  Drupal\n\nDrupalDrupal  Drupal\n\nDrupal\n\n";
    $this->assertHtmlToText(
      $input,
      $collapsed,
      'Whitespace is collapsed',
      array('p')
    );
  }

  /**
   * Test that text separated by block-level tags in HTML get separated by
   * (at least) a newline in the plaintext version.
   */
  public function testDrupalHtmlToTextBlockTagToNewline() {
    $input = '[text]'
      . '<blockquote>[blockquote]</blockquote>'
      . '<br />[br]'
      . '<dl><dt>[dl-dt]</dt>'
      . '<dt>[dt]</dt>'
      . '<dd>[dd]</dd>'
      . '<dd>[dd-dl]</dd></dl>'
      . '<h1>[h1]</h1>'
      . '<h2>[h2]</h2>'
      . '<h3>[h3]</h3>'
      . '<h4>[h4]</h4>'
      . '<h5>[h5]</h5>'
      . '<h6>[h6]</h6>'
      . '<hr />[hr]'
      . '<ol><li>[ol-li]</li>'
      . '<li>[li]</li>'
      . '<li>[li-ol]</li></ol>'
      . '<p>[p]</p>'
      . '<ul><li>[ul-li]</li>'
      . '<li>[li-ul]</li></ul>'
      . '[text]';
    $output = MailFormatHelper::htmlToText($input);
    $pass = $this->assertFalse(
      preg_match('/\][^\n]*\[/s', $output),
      'Block-level HTML tags should force newlines'
    );
    if (!$pass) {
      $this->verbose($this->stringToHtml($output));
    }
    $output_upper = Unicode::strtoupper($output);
    $upper_input = Unicode::strtoupper($input);
    $upper_output = MailFormatHelper::htmlToText($upper_input);
    $pass = $this->assertEqual(
      $upper_output,
      $output_upper,
      'Tag recognition should be case-insensitive'
    );
    if (!$pass) {
      $this->verbose(
        $upper_output
        . '<br />should  be equal to <br />'
        . $output_upper
      );
    }
  }

  /**
   * Test that headers are properly separated from surrounding text.
   */
  public function testHeaderSeparation() {
    $html = 'Drupal<h1>Drupal</h1>Drupal';
    // @todo There should be more space above the header than below it.
    $text = "Drupal\n======== DRUPAL ==============================================================\n\nDrupal\n";
    $this->assertHtmlToText($html, $text,
      'Text before and after <h1> tag');
    $html = '<p>Drupal</p><h1>Drupal</h1>Drupal';
    // @todo There should be more space above the header than below it.
    $text = "Drupal\n\n======== DRUPAL ==============================================================\n\nDrupal\n";
    $this->assertHtmlToText($html, $text,
      'Paragraph before and text after <h1> tag');
    $html = 'Drupal<h1>Drupal</h1><p>Drupal</p>';
    // @todo There should be more space above the header than below it.
    $text = "Drupal\n======== DRUPAL ==============================================================\n\nDrupal\n\n";
    $this->assertHtmlToText($html, $text,
      'Text before and paragraph after <h1> tag');
    $html = '<p>Drupal</p><h1>Drupal</h1><p>Drupal</p>';
    $text = "Drupal\n\n======== DRUPAL ==============================================================\n\nDrupal\n\n";
    $this->assertHtmlToText($html, $text,
      'Paragraph before and after <h1> tag');
  }

  /**
   * Test that footnote references are properly generated.
   */
  public function testFootnoteReferences() {
    global $base_path, $base_url;
    $source = '<a href="http://www.example.com/node/1">Host and path</a>'
      . '<br /><a href="http://www.example.com">Host, no path</a>'
      . '<br /><a href="' . $base_path . 'node/1">Path, no host</a>'
      . '<br /><a href="node/1">Relative path</a>';
    // @todo Footnote URLs should be absolute.
    $tt = "Host and path [1]"
      . "\nHost, no path [2]"
      // @todo The following two references should be combined.
      . "\nPath, no host [3]"
      . "\nRelative path [4]"
      . "\n"
      . "\n[1] http://www.example.com/node/1"
      . "\n[2] http://www.example.com"
      // @todo The following two references should be combined.
      . "\n[3] $base_url/node/1"
      . "\n[4] node/1\n";
    $this->assertHtmlToText($source, $tt, 'Footnotes');
  }

  /**
   * Test that combinations of paragraph breaks, line breaks, linefeeds,
   * and spaces are properly handled.
   */
  public function testDrupalHtmlToTextParagraphs() {
    $tests = array();
    $tests[] = array(
        'html' => "<p>line 1<br />\nline 2<br />line 3\n<br />line 4</p><p>paragraph</p>",
        // @todo Trailing line breaks should be trimmed.
        'text' => "line 1\nline 2\nline 3\nline 4\n\nparagraph\n\n",
    );
    $tests[] = array(
      'html' => "<p>line 1<br /> line 2</p> <p>line 4<br /> line 5</p> <p>0</p>",
      // @todo Trailing line breaks should be trimmed.
      'text' => "line 1\nline 2\n\nline 4\nline 5\n\n0\n\n",
    );
    foreach ($tests as $test) {
      $this->assertHtmlToText($test['html'], $test['text'], 'Paragraph breaks');
    }
  }

  /**
   * Tests \Drupal\Core\Mail\MailFormatHelper::htmlToText() wrapping.
   *
   * RFC 3676 says, "The Text/Plain media type is the lowest common
   * denominator of Internet email, with lines of no more than 998 characters."
   *
   * RFC 2046 says, "SMTP [RFC-821] allows a maximum of 998 octets before the
   * next CRLF sequence."
   *
   * RFC 821 says, "The maximum total length of a text line including the
   * <CRLF> is 1000 characters."
   */
  public function testVeryLongLineWrap() {
    $input = 'Drupal<br /><p>' . str_repeat('x', 2100) . '</p><br />Drupal';
    $output = MailFormatHelper::htmlToText($input);
    $eol = Settings::get('mail_line_endings', PHP_EOL);

    $maximum_line_length = 0;
    foreach (explode($eol, $output) as $line) {
      // We must use strlen() rather than Unicode::strlen() in order to count
      // octets rather than characters.
      $maximum_line_length = max($maximum_line_length, strlen($line . $eol));
    }
    $verbose = 'Maximum line length found was ' . $maximum_line_length . ' octets.';
    $this->assertTrue($maximum_line_length <= 1000, $verbose);
  }

  /**
   * Tests that trailing whitespace is removed before newlines.
   *
   * @see \Drupal\Core\Mail\MailFormatHelper::wrapMail()
   */
  public function testRemoveTrailingWhitespace() {
    $text = "Hi there! \nHerp Derp";
    $mail_lines = explode("\n", MailFormatHelper::wrapMail($text));
    $this->assertNotEqual(" ", substr($mail_lines[0], -1), 'Trailing whitespace removed.');
  }

  /**
   * Tests that trailing whitespace from Usenet style signatures is not removed.
   *
   * RFC 3676 says, "This is a special case; an (optionally quoted or quoted and
   * stuffed) line consisting of DASH DASH SP is neither fixed nor flowed."
   *
   * @see \Drupal\Core\Mail\MailFormatHelper::wrapMail()
   */
  public function testUsenetSignature() {
    $text = "Hi there!\n-- \nHerp Derp";
    $mail_lines = explode("\n", MailFormatHelper::wrapMail($text));
    $this->assertEqual("-- ", $mail_lines[1], 'Trailing whitespace not removed for dash-dash-space signatures.');

    $text = "Hi there!\n--  \nHerp Derp";
    $mail_lines = explode("\n", MailFormatHelper::wrapMail($text));
    $this->assertEqual("--", $mail_lines[1], 'Trailing whitespace removed for incorrect dash-dash-space signatures.');
  }
}
