<?php

namespace Drupal\Core\Mail;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Site\Settings;

/**
 * Defines a class containing utility methods for formatting mail messages.
 */
class MailFormatHelper {

  /**
   * Internal array of urls replaced with tokens.
   *
   * @var array
   */
  protected static $urls = [];

  /**
   * Quoted regex expression based on base path.
   *
   * @var string
   */
  protected static $regexp;

  /**
   * Array of tags supported.
   *
   * @var array
   */
  protected static $supportedTags = [];

  /**
   * Performs format=flowed soft wrapping for mail (RFC 3676).
   *
   * We use delsp=yes wrapping, but only break non-spaced languages when
   * absolutely necessary to avoid compatibility issues.
   *
   * We deliberately use LF rather than CRLF, see MailManagerInterface::mail().
   *
   * @param string $text
   *   The plain text to process.
   * @param string $indent
   *   (optional) A string to indent the text with. Only '>' characters are
   *   repeated on subsequent wrapped lines. Others are replaced by spaces.
   *
   * @return string
   *   The content of the email as a string with formatting applied.
   */
  public static function wrapMail($text, $indent = '') {
    // Convert CRLF into LF.
    $text = str_replace("\r", '', $text);
    // See if soft-wrapping is allowed.
    $clean_indent = static::htmlToTextClean($indent);
    $soft = strpos($clean_indent, ' ') === FALSE;
    // Check if the string has line breaks.
    if (strpos($text, "\n") !== FALSE) {
      // Remove trailing spaces to make existing breaks hard, but leave
      // signature marker untouched (RFC 3676, Section 4.3).
      $text = preg_replace('/(?(?<!^--) +\n|  +\n)/m', "\n", $text);
      // Wrap each line at the needed width.
      $lines = explode("\n", $text);
      array_walk($lines, '\Drupal\Core\Mail\MailFormatHelper::wrapMailLine', ['soft' => $soft, 'length' => strlen($indent)]);
      $text = implode("\n", $lines);
    }
    else {
      // Wrap this line.
      static::wrapMailLine($text, 0, ['soft' => $soft, 'length' => strlen($indent)]);
    }
    // Empty lines with nothing but spaces.
    $text = preg_replace('/^ +\n/m', "\n", $text);
    // Space-stuff special lines.
    $text = preg_replace('/^(>| |From)/m', ' $1', $text);
    // Apply indentation. We only include non-'>' indentation on the first line.
    $text = $indent . substr(preg_replace('/^/m', $clean_indent, $text), strlen($indent));

    return $text;
  }

  /**
   * Transforms an HTML string into plain text, preserving its structure.
   *
   * The output will be suitable for use as 'format=flowed; delsp=yes' text
   * (RFC 3676) and can be passed directly to MailManagerInterface::mail() for sending.
   *
   * We deliberately use LF rather than CRLF, see MailManagerInterface::mail().
   *
   * This function provides suitable alternatives for the following tags:
   * <a> <em> <i> <strong> <b> <br> <p> <blockquote> <ul> <ol> <li> <dl> <dt>
   * <dd> <h1> <h2> <h3> <h4> <h5> <h6> <hr>
   *
   * @param string $string
   *   The string to be transformed.
   * @param array $allowed_tags
   *   (optional) If supplied, a list of tags that will be transformed. If
   *   omitted, all supported tags are transformed.
   *
   * @return string
   *   The transformed string.
   */
  public static function htmlToText($string, $allowed_tags = NULL) {
    // Cache list of supported tags.
    if (empty(static::$supportedTags)) {
      static::$supportedTags = ['a', 'em', 'i', 'strong', 'b', 'br', 'p',
        'blockquote', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'h1', 'h2', 'h3',
        'h4', 'h5', 'h6', 'hr',
      ];
    }

    // Make sure only supported tags are kept.
    $allowed_tags = isset($allowed_tags) ? array_intersect(static::$supportedTags, $allowed_tags) : static::$supportedTags;

    // Make sure tags, entities and attributes are well-formed and properly
    // nested.
    $string = Html::normalize(Xss::filter($string, $allowed_tags));

    // Apply inline styles.
    $string = preg_replace('!</?(em|i)((?> +)[^>]*)?>!i', '/', $string);
    $string = preg_replace('!</?(strong|b)((?> +)[^>]*)?>!i', '*', $string);

    // Replace inline <a> tags with the text of link and a footnote.
    // 'See <a href="https://www.drupal.org">the Drupal site</a>' becomes
    // 'See the Drupal site [1]' with the URL included as a footnote.
    static::htmlToMailUrls(NULL, TRUE);
    $pattern = '@(<a[^>]+?href="([^"]*)"[^>]*?>(.+?)</a>)@i';
    $string = preg_replace_callback($pattern, 'static::htmlToMailUrls', $string);
    $urls = static::htmlToMailUrls();
    $footnotes = '';
    if (count($urls)) {
      $footnotes .= "\n";
      for ($i = 0, $max = count($urls); $i < $max; $i++) {
        $footnotes .= '[' . ($i + 1) . '] ' . $urls[$i] . "\n";
      }
    }

    // Split tags from text.
    $split = preg_split('/<([^>]+?)>/', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
    // Note: PHP ensures the array consists of alternating delimiters and
    // literals and begins and ends with a literal (inserting $null as
    // required).
    // Odd/even counter (tag or no tag).
    $tag = FALSE;
    $output = '';
    // All current indentation string chunks.
    $indent = [];
    // Array of counters for opened lists.
    $lists = [];
    foreach ($split as $value) {
      // Holds a string ready to be formatted and output.
      $chunk = NULL;

      // Process HTML tags (but don't output any literally).
      if ($tag) {
        [$tagname] = explode(' ', strtolower($value), 2);
        switch ($tagname) {
          // List counters.
          case 'ul':
            array_unshift($lists, '*');
            break;

          case 'ol':
            array_unshift($lists, 1);
            break;

          case '/ul':
          case '/ol':
            array_shift($lists);
            // Ensure blank new-line.
            $chunk = '';
            break;

          // Quotation/list markers, non-fancy headers.
          case 'blockquote':
            // Format=flowed indentation cannot be mixed with lists.
            $indent[] = count($lists) ? ' "' : '>';
            break;

          case 'li':
            $indent[] = isset($lists[0]) && is_numeric($lists[0]) ? ' ' . $lists[0]++ . ') ' : ' * ';
            break;

          case 'dd':
            $indent[] = '    ';
            break;

          case 'h3':
            $indent[] = '.... ';
            break;

          case 'h4':
            $indent[] = '.. ';
            break;

          case '/blockquote':
            if (count($lists)) {
              // Append closing quote for inline quotes (immediately).
              $output = rtrim($output, "> \n") . "\"\n";
              // Ensure blank new-line.
              $chunk = '';
            }
            // Intentional fall-through to the processing for '/li' and '/dd'.
          case '/li':
          case '/dd':
            array_pop($indent);
            break;

          case '/h3':
          case '/h4':
            array_pop($indent);
            // Intentional fall-through to the processing for '/h5' and '/h6'.
          case '/h5':
          case '/h6':
            // Ensure blank new-line.
            $chunk = '';
            break;

          // Fancy headers.
          case 'h1':
            $indent[] = '======== ';
            break;

          case 'h2':
            $indent[] = '-------- ';
            break;

          case '/h1':
          case '/h2':
            // Pad the line with dashes.
            $output = static::htmlToTextPad($output, ($tagname == '/h1') ? '=' : '-', ' ');
            array_pop($indent);
            // Ensure blank new-line.
            $chunk = '';
            break;

          // Horizontal rulers.
          case 'hr':
            // Insert immediately.
            $output .= static::wrapMail('', implode('', $indent)) . "\n";
            $output = static::htmlToTextPad($output, '-');
            break;

          // Paragraphs and definition lists.
          case '/p':
          case '/dl':
            // Ensure blank new-line.
            $chunk = '';
            break;
        }
      }
      // Process blocks of text.
      else {
        // Convert inline HTML text to plain text; not removing line-breaks or
        // white-space, since that breaks newlines when sanitizing plain-text.
        $value = trim(Html::decodeEntities($value));
        if (mb_strlen($value)) {
          $chunk = $value;
        }
      }

      // See if there is something waiting to be output.
      if (isset($chunk)) {
        $line_endings = Settings::get('mail_line_endings', PHP_EOL);
        // Format it and apply the current indentation.
        $output .= static::wrapMail($chunk, implode('', $indent)) . $line_endings;
        // Remove non-quotation markers from indentation.
        $indent = array_map('\Drupal\Core\Mail\MailFormatHelper::htmlToTextClean', $indent);
      }

      $tag = !$tag;
    }

    return $output . $footnotes;
  }

  /**
   * Wraps words on a single line.
   *
   * Callback for array_walk() within
   * \Drupal\Core\Mail\MailFormatHelper::wrapMail().
   *
   * Note that we are skipping MIME content header lines, because attached
   * files, especially applications, could have long MIME types or long
   * filenames which result in line length longer than the 77 characters limit
   * and wrapping that line will break the email format. For instance, the
   * attached file hello_drupal.docx will produce the following Content-Type:
   * @code
   * Content-Type:
   * application/vnd.openxmlformats-officedocument.wordprocessingml.document;
   * name="hello_drupal.docx"
   * @endcode
   */
  protected static function wrapMailLine(&$line, $key, $values) {
    $line_is_mime_header = FALSE;
    $mime_headers = [
      'Content-Type',
      'Content-Transfer-Encoding',
      'Content-Disposition',
      'Content-Description',
    ];

    // Do not break MIME headers which could be longer than 77 characters.
    foreach ($mime_headers as $header) {
      if (strpos($line, $header . ': ') === 0) {
        $line_is_mime_header = TRUE;
        break;
      }
    }
    if (!$line_is_mime_header) {
      // Use soft-breaks only for purely quoted or unindented text.
      $line = wordwrap($line, 77 - $values['length'], $values['soft'] ? " \n" : "\n");
    }
    // Break really long words at the maximum width allowed.
    $line = wordwrap($line, 996 - $values['length'], $values['soft'] ? " \n" : "\n", TRUE);
  }

  /**
   * Keeps track of URLs and replaces them with placeholder tokens.
   *
   * Callback for preg_replace_callback() within
   * \Drupal\Core\Mail\MailFormatHelper::htmlToText().
   */
  protected static function htmlToMailUrls($match = NULL, $reset = FALSE) {
    // @todo Use request context instead.
    global $base_url, $base_path;

    if ($reset) {
      // Reset internal URL list.
      static::$urls = [];
    }
    else {
      if (empty(static::$regexp)) {
        static::$regexp = '@^' . preg_quote($base_path, '@') . '@';
      }
      if ($match) {
        [, , $url, $label] = $match;
        // Ensure all URLs are absolute.
        static::$urls[] = strpos($url, '://') ? $url : preg_replace(static::$regexp, $base_url . '/', $url);
        return $label . ' [' . count(static::$urls) . ']';
      }
    }
    return static::$urls;
  }

  /**
   * Replaces non-quotation markers from a piece of indentation with spaces.
   *
   * Callback for array_map() within
   * \Drupal\Core\Mail\MailFormatHelper::htmlToText().
   */
  protected static function htmlToTextClean($indent) {
    return preg_replace('/[^>]/', ' ', $indent);
  }

  /**
   * Pads the last line with the given character.
   *
   * @param string $text
   *   The text to pad.
   * @param string $pad
   *   The character to pad the end of the string with.
   * @param string $prefix
   *   (optional) Prefix to add to the string.
   *
   * @return string
   *   The padded string.
   *
   * @see \Drupal\Core\Mail\MailFormatHelper::htmlToText()
   */
  protected static function htmlToTextPad($text, $pad, $prefix = '') {
    // Remove last line break.
    $text = substr($text, 0, -1);
    // Calculate needed padding space and add it.
    if (($p = strrpos($text, "\n")) === FALSE) {
      $p = -1;
    }
    $n = max(0, 79 - (strlen($text) - $p) - strlen($prefix));
    // Add prefix and padding, and restore linebreak.
    return $text . $prefix . str_repeat($pad, $n) . "\n";
  }

}
