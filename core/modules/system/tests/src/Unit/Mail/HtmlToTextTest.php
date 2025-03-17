<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Unit\Mail;

use Drupal\Component\Utility\Html;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for \Drupal\Core\Mail\MailFormatHelper::htmlToText().
 *
 * @group Mail
 */
class HtmlToTextTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $GLOBALS['base_path'] = '/';
    $GLOBALS['base_url'] = 'http://localhost';
  }

  /**
   * Converts a string to its PHP source equivalent for display in test messages.
   *
   * @param string $text
   *   The text string to convert.
   *
   * @return string
   *   An HTML representation of the text string that, when displayed in a
   *   browser, represents the PHP source code equivalent of $text.
   */
  protected function stringToHtml($text): string {
    return '"' .
      str_replace(
        ["\n", ' '],
        ['\n', '&nbsp;'],
        Html::escape($text)
      ) . '"';
  }

  /**
   * Helper function to test \Drupal\Core\Mail\MailFormatHelper::htmlToText().
   *
   * @param string $html
   *   The source HTML string to be converted.
   * @param string $text
   *   The expected result of converting $html to text.
   * @param string $message
   *   A text message to display in the assertion message.
   * @param array|null $allowed_tags
   *   (optional) An array of allowed tags, or NULL to default to the full
   *   set of tags supported by
   *   \Drupal\Core\Mail\MailFormatHelper::htmlToText().
   *
   * @internal
   */
  protected function assertHtmlToText(string $html, string $text, string $message, ?array $allowed_tags = NULL): void {
    preg_match_all('/<([a-z0-6]+)/', mb_strtolower($html), $matches);
    $tested_tags = implode(', ', array_unique($matches[1]));
    $message .= ' (' . $tested_tags . ')';
    $result = MailFormatHelper::htmlToText($html, $allowed_tags);
    $this->assertEquals($text, $result, Html::escape($message));
  }

  /**
   * Tests supported tags of \Drupal\Core\Mail\MailFormatHelper::htmlToText().
   */
  public function testTags(): void {
    global $base_path, $base_url;
    $tests = [
      // Tests tag inside <a>.
      '<a href = "https://www.drupal.org"><b>Drupal.org</b> our site</a>' => "*Drupal.org* our site [1]\n\n[1] https://www.drupal.org\n",
      // Tests newlines are stripped from anchor text.
      '<a href = "https://www.drupal.org">Drupal' . "\n.org</a>" => "Drupal.org [1]\n\n[1] https://www.drupal.org\n",
      // Tests newlines and carriage returns are stripped from anchor text.
      '<a href = "https://www.drupal.org">Drupal' . "\r\n.org</a>" => "Drupal.org [1]\n\n[1] https://www.drupal.org\n",
      // @todo Trailing newlines should be trimmed.
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
      '<h1>Drupal</h1>' => "======== Drupal ==============================================================\n\n",
      '<h1>Drupal</h1><p>Drupal</p>' => "======== Drupal ==============================================================\n\nDrupal\n\n",
      '<h2>Drupal</h2>' => "-------- Drupal --------------------------------------------------------------\n\n",
      '<h2>Drupal</h2><p>Drupal</p>' => "-------- Drupal --------------------------------------------------------------\n\nDrupal\n\n",
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
      // cspell:disable-next-line
      'FrançAIS is ÜBER-åwesome' => "FrançAIS is ÜBER-åwesome\n",
    ];

    foreach ($tests as $html => $text) {
      $this->assertHtmlToText($html, $text, 'Supported tags');
    }
  }

  /**
   * Tests allowing tags in \Drupal\Core\Mail\MailFormatHelper::htmlToText().
   */
  public function testDrupalHtmlToTextArgs(): void {
    // The second parameter of \Drupal\Core\Mail\MailFormatHelper::htmlToText()
    // overrules the allowed tags.
    $this->assertHtmlToText(
      'Drupal <b>Drupal</b> Drupal',
      "Drupal *Drupal* Drupal\n",
      'Allowed <b> tag found',
      ['b']
    );
    $this->assertHtmlToText(
      'Drupal <h1>Drupal</h1> Drupal',
      "Drupal Drupal Drupal\n",
      'Disallowed <h1> tag not found',
      ['b']
    );

    $this->assertHtmlToText(
      'Drupal <p><em><b>Drupal</b></em><p> Drupal',
      "Drupal Drupal Drupal\n",
      'Disallowed <p>, <em>, and <b> tags not found',
      ['a', 'br', 'h1']
    );

    $this->assertHtmlToText(
      '<html><body>Drupal</body></html>',
      "Drupal\n",
      'Unsupported <html> and <body> tags not found',
      ['html', 'body']
    );
  }

  /**
   * Tests that whitespace is collapsed.
   */
  public function testDrupalHtmlToTextCollapsesWhitespace(): void {
    $input = "<p>Drupal  Drupal\n\nDrupal<pre>Drupal  Drupal\n\nDrupal</pre>Drupal  Drupal\n\nDrupal</p>";
    // @todo The whitespace should be collapsed.
    $collapsed = "Drupal  Drupal\n\nDrupalDrupal  Drupal\n\nDrupalDrupal  Drupal\n\nDrupal\n\n";
    $this->assertHtmlToText(
      $input,
      $collapsed,
      'Whitespace is collapsed',
      ['p']
    );
  }

  /**
   * Tests the conversion of block-level HTML tags to plaintext with newlines.
   */
  public function testDrupalHtmlToTextBlockTagToNewline(): void {
    $input = <<<'EOT'
[text]
<blockquote>[blockquote]</blockquote>
<br />[br]
<dl><dt>[dl-dt]</dt>
<dt>[dt]</dt>
<dd>[dd]</dd>
<dd>[dd-dl]</dd></dl>
<h1>[h1]</h1>
<h2>[h2]</h2>
<h3>[h3]</h3>
<h4>[h4]</h4>
<h5>[h5]</h5>
<h6>[h6]</h6>
<hr />[hr]
<ol><li>[ol-li]</li>
<li>[li]</li>
<li>[li-ol]</li></ol>
<p>[p]</p>
<ul><li>[ul-li]</li>
<li>[li-ul]</li></ul>
[text]
EOT;
    $input = str_replace(["\r", "\n"], '', $input);
    $output = MailFormatHelper::htmlToText($input);
    $this->assertDoesNotMatchRegularExpression('/\][^\n]*\[/s', $output, 'Block-level HTML tags should force newlines');
    $output_upper = mb_strtoupper($output);
    $upper_input = mb_strtoupper($input);
    $upper_output = MailFormatHelper::htmlToText($upper_input);
    $this->assertEquals($output_upper, $upper_output, 'Tag recognition should be case-insensitive');
  }

  /**
   * Tests that headers are properly separated from surrounding text.
   */
  public function testHeaderSeparation(): void {
    $html = 'Drupal<h1>Drupal</h1>Drupal';
    // @todo There should be more space above the header than below it.
    $text = "Drupal\n======== Drupal ==============================================================\n\nDrupal\n";
    $this->assertHtmlToText($html, $text,
      'Text before and after <h1> tag');
    $html = '<p>Drupal</p><h1>Drupal</h1>Drupal';
    // @todo There should be more space above the header than below it.
    $text = "Drupal\n\n======== Drupal ==============================================================\n\nDrupal\n";
    $this->assertHtmlToText($html, $text,
      'Paragraph before and text after <h1> tag');
    $html = 'Drupal<h1>Drupal</h1><p>Drupal</p>';
    // @todo There should be more space above the header than below it.
    $text = "Drupal\n======== Drupal ==============================================================\n\nDrupal\n\n";
    $this->assertHtmlToText($html, $text,
      'Text before and paragraph after <h1> tag');
    $html = '<p>Drupal</p><h1>Drupal</h1><p>Drupal</p>';
    $text = "Drupal\n\n======== Drupal ==============================================================\n\nDrupal\n\n";
    $this->assertHtmlToText($html, $text,
      'Paragraph before and after <h1> tag');
  }

  /**
   * Tests that footnote references are properly generated.
   */
  public function testFootnoteReferences(): void {
    global $base_path, $base_url;
    $source = <<<EOT
<a href="http://www.example.com/node/1">Host and path</a>
<br /><a href="http://www.example.com">Host, no path</a>
<br /><a href="{$base_path}node/1">Path, no host</a>
<br /><a href="node/1">Relative path</a>
EOT;
    $source = str_replace(["\r", "\n"], '', $source);
    // @todo Footnote URLs should be absolute.
    // @todo The last two references should be combined.
    $text = <<<EOT
Host and path [1]
Host, no path [2]
Path, no host [3]
Relative path [4]

[1] http://www.example.com/node/1
[2] http://www.example.com
[3] $base_url/node/1
[4] node/1

EOT;
    $this->assertHtmlToText($source, $text, 'Footnotes');
  }

  /**
   * Tests the plaintext conversion of different whitespace combinations.
   */
  public function testDrupalHtmlToTextParagraphs(): void {
    $tests = [];
    $tests[] = [
      'html' => "<p>line 1<br />\nline 2<br />line 3\n<br />line 4</p><p>paragraph</p>",
        // @todo Trailing line breaks should be trimmed.
      'text' => "line 1\nline 2\nline 3\nline 4\n\nparagraph\n\n",
    ];
    $tests[] = [
      'html' => "<p>line 1<br /> line 2</p> <p>line 4<br /> line 5</p> <p>0</p>",
      // @todo Trailing line breaks should be trimmed.
      'text' => "line 1\nline 2\n\nline 4\nline 5\n\n0\n\n",
    ];
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
  public function testVeryLongLineWrap(): void {
    $input = 'Drupal<br /><p>' . str_repeat('x', 2100) . '</p><br />Drupal';
    $output = MailFormatHelper::htmlToText($input);
    $eol = Settings::get('mail_line_endings', PHP_EOL);

    $maximum_line_length = 0;
    foreach (explode($eol, $output) as $line) {
      // We must use strlen() rather than mb_strlen() in order to count octets
      // rather than characters.
      $maximum_line_length = max($maximum_line_length, strlen($line . $eol));
    }
    // Verify that the maximum line length found was less than or equal to 1000
    // characters as per RFC 821.
    $this->assertLessThanOrEqual(1000, $maximum_line_length);
  }

  /**
   * Tests that trailing whitespace is removed before newlines.
   *
   * @see \Drupal\Core\Mail\MailFormatHelper::wrapMail()
   */
  public function testRemoveTrailingWhitespace(): void {
    $text = "Hi there! \nEarth";
    $mail_lines = explode("\n", MailFormatHelper::wrapMail($text));
    $this->assertNotEquals(" ", substr($mail_lines[0], -1), 'Trailing whitespace removed.');
  }

  /**
   * Tests that trailing whitespace from Usenet style signatures is not removed.
   *
   * RFC 3676 says, "This is a special case; an (optionally quoted or quoted and
   * stuffed) line consisting of DASH DASH SP is neither fixed nor flowed."
   *
   * @see \Drupal\Core\Mail\MailFormatHelper::wrapMail()
   */
  public function testUsenetSignature(): void {
    $text = "Hi there!\n-- \nEarth";
    $mail_lines = explode("\n", MailFormatHelper::wrapMail($text));
    $this->assertEquals("-- ", $mail_lines[1], 'Trailing whitespace not removed for dash-dash-space signatures.');

    $text = "Hi there!\n--  \nEarth";
    $mail_lines = explode("\n", MailFormatHelper::wrapMail($text));
    $this->assertEquals("--", $mail_lines[1], 'Trailing whitespace removed for incorrect dash-dash-space signatures.');
  }

}
