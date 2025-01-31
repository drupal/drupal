<?php

namespace Drupal\Component\Utility;

use Masterminds\HTML5;
use Masterminds\HTML5\Serializer\Traverser;

/**
 * Provides DOMDocument helpers for parsing and serializing HTML strings.
 *
 * @ingroup utility
 */
class Html {

  /**
   * An array of previously cleaned HTML classes.
   *
   * @var array
   */
  protected static $classes = [];

  /**
   * An array of the initial IDs used in one request.
   *
   * @var array
   */
  protected static $seenIdsInit;

  /**
   * An array of IDs, including incremented versions when an ID is duplicated.
   *
   * @var array
   */
  protected static $seenIds;

  /**
   * Stores whether the current request was sent via AJAX.
   *
   * @var bool
   */
  protected static $isAjax = FALSE;

  /**
   * All attributes that may contain URIs.
   *
   * - The attributes 'code' and 'codebase' are omitted, because they only exist
   *   for the <applet> tag. The time of Java applets has passed.
   * - The attribute 'icon' is omitted, because no browser implements the
   *   <command> tag anymore.
   *  See https://developer.mozilla.org/en-US/docs/Web/HTML/Element/command.
   * - The 'manifest' attribute is omitted because it only exists for the <html>
   *   tag. That tag only makes sense in an HTML-served-as-HTML context, in
   *   which case relative URLs are guaranteed to work.
   *
   * @var string[]
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes
   * @see https://stackoverflow.com/questions/2725156/complete-list-of-html-tag-attributes-which-have-a-url-value
   */
  protected static $uriAttributes = ['href', 'poster', 'src', 'cite', 'data', 'action', 'formaction', 'srcset', 'about'];

  /**
   * Prepares a string for use as a valid class name.
   *
   * Do not pass one string containing multiple classes as they will be
   * incorrectly concatenated with dashes, i.e. "one two" will become "one-two".
   *
   * @param mixed $class
   *   The class name to clean. It can be a string or anything that can be cast
   *   to string.
   *
   * @return string
   *   The cleaned class name.
   */
  public static function getClass($class) {
    $class = (string) $class;
    if (!isset(static::$classes[$class])) {
      static::$classes[$class] = static::cleanCssIdentifier(mb_strtolower($class));
    }
    return static::$classes[$class];
  }

  /**
   * Prepares a string for use as a CSS identifier (element, class, or ID name).
   *
   * Link below shows the syntax for valid CSS identifiers (including element
   * names, classes, and IDs in selectors).
   *
   * @see https://www.w3.org/TR/CSS21/syndata.html#characters
   *
   * @param string $identifier
   *   The identifier to clean.
   * @param array $filter
   *   An array of string replacements to use on the identifier.
   *
   * @return string
   *   The cleaned identifier.
   */
  public static function cleanCssIdentifier(
    $identifier,
    array $filter = [
      ' ' => '-',
      '_' => '-',
      '/' => '-',
      '[' => '-',
      ']' => '',
    ],
  ) {
    // We could also use strtr() here but its much slower than str_replace(). In
    // order to keep '__' to stay '__' we first replace it with a different
    // placeholder after checking that it is not defined as a filter.
    $double_underscore_replacements = 0;
    if (!isset($filter['__'])) {
      $identifier = str_replace('__', '##', $identifier, $double_underscore_replacements);
    }
    $identifier = str_replace(array_keys($filter), array_values($filter), $identifier);
    // Replace temporary placeholder '##' with '__' only if the original
    // $identifier contained '__'.
    if ($double_underscore_replacements > 0) {
      $identifier = str_replace('##', '__', $identifier);
    }

    // Valid characters in a CSS identifier are:
    // - the hyphen (U+002D)
    // - a-z (U+0030 - U+0039)
    // - A-Z (U+0041 - U+005A)
    // - the underscore (U+005F)
    // - 0-9 (U+0061 - U+007A)
    // - ISO 10646 characters U+00A1 and higher
    // We strip out any character not in the above list.
    $identifier = preg_replace('/[^\x{002D}\x{0030}-\x{0039}\x{0041}-\x{005A}\x{005F}\x{0061}-\x{007A}\x{00A1}-\x{FFFF}]/u', '', $identifier);
    // Identifiers cannot start with a digit, two hyphens, or a hyphen followed by a digit.
    $identifier = preg_replace([
      '/^[0-9]/',
      '/^(-[0-9])|^(--)/',
    ], ['_', '__'], $identifier);
    return $identifier;
  }

  /**
   * Sets if this request is an Ajax request.
   *
   * @param bool $is_ajax
   *   TRUE if this request is an Ajax request, FALSE otherwise.
   */
  public static function setIsAjax($is_ajax) {
    static::$isAjax = $is_ajax;
  }

  /**
   * Prepares a string for use as a valid HTML ID and guarantees uniqueness.
   *
   * This function ensures that each passed HTML ID value only exists once on
   * the page. By tracking the already returned ids, this function enables
   * forms, blocks, and other content to be output multiple times on the same
   * page, without breaking HTML validation.
   *
   * For already existing IDs, a counter is appended to the ID string.
   * Therefore, JavaScript and CSS code should not rely on any value that was
   * generated by this function and instead should rely on manually added CSS
   * classes or similarly reliable constructs.
   *
   * Two consecutive hyphens separate the counter from the original ID. To
   * manage uniqueness across multiple Ajax requests on the same page, Ajax
   * requests POST an array of all IDs currently present on the page, which are
   * used to prime this function's cache upon first invocation.
   *
   * To allow reverse-parsing of IDs submitted via Ajax, any multiple
   * consecutive hyphens in the originally passed $id are replaced with a
   * single hyphen.
   *
   * @param string $id
   *   The ID to clean.
   *
   * @return string
   *   The cleaned ID.
   */
  public static function getUniqueId($id) {
    // If this is an Ajax request, then content returned by this page request
    // will be merged with content already on the base page. The HTML IDs must
    // be unique for the fully merged content. Therefore use unique IDs.
    if (static::$isAjax) {
      return static::getId($id) . '--' . Crypt::randomBytesBase64(8);
    }

    // @todo Remove all that code once we switch over to random IDs only,
    // see https://www.drupal.org/node/1090592.
    if (!isset(static::$seenIdsInit)) {
      static::$seenIdsInit = [];
    }
    if (!isset(static::$seenIds)) {
      static::$seenIds = static::$seenIdsInit;
    }

    $id = static::getId($id);

    // Ensure IDs are unique by appending a counter after the first occurrence.
    // The counter needs to be appended with a delimiter that does not exist in
    // the base ID. Requiring a unique delimiter helps ensure that we really do
    // return unique IDs and also helps us re-create the $seen_ids array during
    // Ajax requests.
    if (isset(static::$seenIds[$id])) {
      $id = $id . '--' . ++static::$seenIds[$id];
    }
    else {
      static::$seenIds[$id] = 1;
    }
    return $id;
  }

  /**
   * Prepares a string for use as a valid HTML ID.
   *
   * Only use this function when you want to intentionally skip the uniqueness
   * guarantee of self::getUniqueId().
   *
   * @param string $id
   *   The ID to clean.
   *
   * @return string
   *   The cleaned ID.
   *
   * @see self::getUniqueId()
   */
  public static function getId($id) {
    $id = str_replace([' ', '_', '[', ']'], ['-', '-', '-', ''], mb_strtolower($id));

    // As defined in https://www.w3.org/TR/html4/types.html#type-name, HTML IDs can
    // only contain letters, digits ([0-9]), hyphens ("-"), underscores ("_"),
    // colons (":"), and periods ("."). We strip out any character not in that
    // list. Note that the CSS spec doesn't allow colons or periods in identifiers
    // (https://www.w3.org/TR/CSS21/syndata.html#characters), so we strip those two
    // characters as well.
    $id = preg_replace('/[^A-Za-z0-9\-_]/', '', $id);

    // Removing multiple consecutive hyphens.
    $id = preg_replace('/\-+/', '-', $id);
    return $id;
  }

  /**
   * Resets the list of seen IDs.
   */
  public static function resetSeenIds() {
    static::$seenIds = NULL;
  }

  /**
   * Normalizes an HTML snippet.
   *
   * This function is essentially \DOMDocument::normalizeDocument(), but
   * operates on an HTML string instead of a \DOMDocument.
   *
   * @param string $html
   *   The HTML string to normalize.
   *
   * @return string
   *   The normalized HTML string.
   */
  public static function normalize($html) {
    $document = static::load($html);
    return static::serialize($document);
  }

  /**
   * Parses an HTML snippet and returns it as a DOM object.
   *
   * This function loads the body part of a partial HTML document and returns a
   * full \DOMDocument object that represents this document.
   *
   * Use \Drupal\Component\Utility\Html::serialize() to serialize this
   * \DOMDocument back to a string.
   *
   * @param string $html
   *   The partial HTML snippet to load. Invalid markup will be corrected on
   *   import.
   *
   * @return \DOMDocument
   *   A \DOMDocument that represents the loaded HTML snippet.
   */
  public static function load($html) {
    // Instantiate the HTML5 parser, but without the HTML5 namespace being
    // added to the DOM document.
    $html5 = new HTML5(['disable_html_ns' => TRUE, 'encoding' => 'UTF-8']);

    // Attach the provided HTML inside the body. Rely on the HTML5 parser to
    // close the body tag.
    return $html5->loadHTML('<body>' . $html);
  }

  /**
   * Converts the body of a \DOMDocument back to an HTML snippet.
   *
   * The function serializes the body part of a \DOMDocument back to an HTML
   * snippet. The resulting HTML snippet will be properly formatted to be
   * compatible with HTML user agents.
   *
   * @param \DOMDocument $document
   *   A \DOMDocument object to serialize, only the tags below the first <body>
   *   node will be converted.
   *
   * @return string
   *   A valid HTML snippet, as a string.
   */
  public static function serialize(\DOMDocument $document) {
    $body_node = $document->getElementsByTagName('body')->item(0);
    $html = '';

    if ($body_node !== NULL) {
      foreach ($body_node->getElementsByTagName('script') as $node) {
        static::escapeCdataElement($node);
      }
      foreach ($body_node->getElementsByTagName('style') as $node) {
        static::escapeCdataElement($node, '/*', '*/');
      }

      // Serialize the body using our custom set of rules.
      // @see \Masterminds\HTML5::saveHTML()
      $stream = fopen('php://temp', 'wb');
      $rules = new HtmlSerializerRules($stream);
      foreach ($body_node->childNodes as $node) {
        $traverser = new Traverser($node, $stream, $rules);
        $traverser->walk();
      }
      $rules->unsetTraverser();
      $html = stream_get_contents($stream, -1, 0);
      fclose($stream);
    }

    // Normalize all newlines.
    $html = str_replace(["\r\n", "\r"], "\n", $html);

    return $html;
  }

  /**
   * Adds comments around a <!CDATA section in a \DOMNode.
   *
   * \DOMDocument::loadHTML() in \Drupal\Component\Utility\Html::load() makes
   * CDATA sections from the contents of inline script and style tags. This can
   * cause HTML4 browsers to throw exceptions.
   *
   * This function attempts to solve the problem by creating a
   * \DOMDocumentFragment to comment the CDATA tag.
   *
   * @param \DOMNode $node
   *   The element potentially containing a CDATA node.
   * @param string $comment_start
   *   (optional) A string to use as a comment start marker to escape the CDATA
   *   declaration. Defaults to '//'.
   * @param string $comment_end
   *   (optional) A string to use as a comment end marker to escape the CDATA
   *   declaration. Defaults to an empty string.
   */
  public static function escapeCdataElement(\DOMNode $node, $comment_start = '//', $comment_end = '') {
    foreach ($node->childNodes as $child_node) {
      if ($child_node instanceof \DOMCdataSection) {
        $data = $child_node->data;
        if (!str_contains($child_node->data, 'CDATA')) {
          $embed_prefix = "\n{$comment_start}<![CDATA[{$comment_end}\n";
          $embed_suffix = "\n{$comment_start}]]>{$comment_end}\n";

          $data = $embed_prefix . $data . $embed_suffix;
        }

        $fragment = $node->ownerDocument->createDocumentFragment();
        $fragment->appendXML($data);
        $node->appendChild($fragment);
        $node->removeChild($child_node);
      }
    }
  }

  /**
   * Decodes all HTML entities including numerical ones to regular UTF-8 bytes.
   *
   * Double-escaped entities will only be decoded once ("&amp;lt;" becomes
   * "&lt;", not "<"). Be careful when using this function, as it will revert
   * previous sanitization efforts (&lt;script&gt; will become <script>).
   *
   * This method is not the opposite of Html::escape(). For example, this method
   * will convert "&eacute;" to "é", whereas Html::escape() will not convert "é"
   * to "&eacute;".
   *
   * @param string $text
   *   The text to decode entities in.
   *
   * @return string
   *   The input $text, with all HTML entities decoded once.
   *
   * @see html_entity_decode()
   * @see \Drupal\Component\Utility\Html::escape()
   */
  public static function decodeEntities(string $text): string {
    return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
  }

  /**
   * Escapes text by converting special characters to HTML entities.
   *
   * This method escapes HTML for sanitization purposes by replacing the
   * following special characters with their HTML entity equivalents:
   * - & (ampersand) becomes &amp;
   * - " (double quote) becomes &quot;
   * - ' (single quote) becomes &#039;
   * - < (less than) becomes &lt;
   * - > (greater than) becomes &gt;
   * Special characters that have already been escaped will be double-escaped
   * (for example, "&lt;" becomes "&amp;lt;"), and invalid UTF-8 encoding
   * will be converted to the Unicode replacement character ("�").
   *
   * This method is not the opposite of Html::decodeEntities(). For example,
   * this method will not encode "é" to "&eacute;", whereas
   * Html::decodeEntities() will convert all HTML entities to UTF-8 bytes,
   * including "&eacute;" and "&lt;" to "é" and "<".
   *
   * When constructing @link theme_render render arrays @endlink passing the
   * output of Html::escape() to '#markup' is not recommended. Use the
   * '#plain_text' key instead and the renderer will autoescape the text.
   *
   * @param string $text
   *   The input text.
   *
   * @return string
   *   The text with all HTML special characters converted.
   *
   * @see htmlspecialchars()
   * @see \Drupal\Component\Utility\Html::decodeEntities()
   *
   * @ingroup sanitization
   */
  public static function escape(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }

  /**
   * Converts all root-relative URLs to absolute URLs.
   *
   * Does not change any existing protocol-relative or absolute URLs. Does not
   * change other relative URLs because they would result in different absolute
   * URLs depending on the current path. For example: when the same content
   * containing such a relative URL (for example 'image.png'), is served from
   * its canonical URL (for example 'http://example.com/some-article') or from
   * a listing or feed (for example 'http://example.com/all-articles') their
   * "current path" differs, resulting in different absolute URLs:
   * 'http://example.com/some-article/image.png' versus
   * 'http://example.com/all-articles/image.png'. Only one can be correct.
   * Therefore relative URLs that are not root-relative cannot be safely
   * transformed and should generally be avoided.
   *
   * Necessary for HTML that is served outside of a website, for example, RSS
   * and email.
   *
   * @param string $html
   *   The partial HTML snippet to load. Invalid markup will be corrected on
   *   import.
   * @param string $scheme_and_host
   *   The root URL, which has a URI scheme, host and optional port.
   *
   * @return string
   *   The updated HTML snippet.
   */
  public static function transformRootRelativeUrlsToAbsolute($html, $scheme_and_host) {
    assert(empty(array_diff(array_keys(parse_url($scheme_and_host)), ["scheme", "host", "port"])), '$scheme_and_host contains scheme, host and port at most.');
    assert(isset(parse_url($scheme_and_host)["scheme"]), '$scheme_and_host is absolute and hence has a scheme.');
    assert(isset(parse_url($scheme_and_host)["host"]), '$base_url is absolute and hence has a host.');

    $html_dom = Html::load($html);
    $xpath = new \DOMXPath($html_dom);

    // Update all root-relative URLs to absolute URLs in the given HTML.
    // Perform on attributes that may contain a single URI.
    foreach (static::$uriAttributes as $attr) {
      foreach ($xpath->query("//*[starts-with(@$attr, '/') and not(starts-with(@$attr, '//'))]") as $node) {
        $node->setAttribute($attr, $scheme_and_host . $node->getAttribute($attr));
      }
    }
    // Perform on each URI within "srcset" attributes.
    foreach ($xpath->query("//*[@srcset]") as $node) {
      // @see https://html.spec.whatwg.org/multipage/embedded-content.html#attr-img-srcset
      // @see https://html.spec.whatwg.org/multipage/embedded-content.html#image-candidate-string
      $image_candidate_strings = explode(',', $node->getAttribute('srcset'));
      $image_candidate_strings = array_filter(array_map('trim', $image_candidate_strings));
      foreach ($image_candidate_strings as $key => $image_candidate_string) {
        if ($image_candidate_string[0] === '/' && $image_candidate_string[1] !== '/') {
          $image_candidate_strings[$key] = $scheme_and_host . $image_candidate_string;
        }
      }
      $node->setAttribute('srcset', implode(', ', $image_candidate_strings));
    }
    return Html::serialize($html_dom);
  }

}
