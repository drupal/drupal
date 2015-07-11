<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\Xss.
 */

namespace Drupal\Component\Utility;

/**
 * Provides helper to filter for cross-site scripting.
 *
 * @ingroup utility
 */
class Xss {

  /**
   * The list of html tags allowed by filterAdmin().
   *
   * @var array
   *
   * @see \Drupal\Component\Utility\Xss::filterAdmin()
   */
  protected static $adminTags = array('a', 'abbr', 'acronym', 'address', 'article', 'aside', 'b', 'bdi', 'bdo', 'big', 'blockquote', 'br', 'caption', 'cite', 'code', 'col', 'colgroup', 'command', 'dd', 'del', 'details', 'dfn', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr', 'i', 'img', 'ins', 'kbd', 'li', 'mark', 'menu', 'meter', 'nav', 'ol', 'output', 'p', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'section', 'small', 'span', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'time', 'tr', 'tt', 'u', 'ul', 'var', 'wbr');

  /**
   * Filters HTML to prevent cross-site-scripting (XSS) vulnerabilities.
   *
   * Based on kses by Ulf Harnhammar, see http://sourceforge.net/projects/kses.
   * For examples of various XSS attacks, see: http://ha.ckers.org/xss.html.
   *
   * This method is preferred to
   * \Drupal\Component\Utility\SafeMarkup::xssFilter() when the result is not
   * being used directly in the rendering system (for example, when its result
   * is being combined with other strings before rendering). This avoids
   * bloating the safe string list with partial strings if the whole result will
   * be marked safe.
   *
   * This code does four things:
   * - Removes characters and constructs that can trick browsers.
   * - Makes sure all HTML entities are well-formed.
   * - Makes sure all HTML tags and attributes are well-formed.
   * - Makes sure no HTML tags contain URLs with a disallowed protocol (e.g.
   *   javascript:).
   *
   * @param $string
   *   The string with raw HTML in it. It will be stripped of everything that
   *   can cause an XSS attack.
   * @param array $html_tags
   *   An array of HTML tags.
   *
   * @return string
   *   An XSS safe version of $string, or an empty string if $string is not
   *   valid UTF-8.
   *
   * @see \Drupal\Component\Utility\Unicode::validateUtf8()
   * @see \Drupal\Component\Utility\SafeMarkup::xssFilter()
   *
   * @ingroup sanitization
   */
  public static function filter($string, $html_tags = array('a', 'em', 'strong', 'cite', 'blockquote', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd')) {
    // Only operate on valid UTF-8 strings. This is necessary to prevent cross
    // site scripting issues on Internet Explorer 6.
    if (!Unicode::validateUtf8($string)) {
      return '';
    }
    // Remove NULL characters (ignored by some browsers).
    $string = str_replace(chr(0), '', $string);
    // Remove Netscape 4 JS entities.
    $string = preg_replace('%&\s*\{[^}]*(\}\s*;?|$)%', '', $string);

    // Defuse all HTML entities.
    $string = str_replace('&', '&amp;', $string);
    // Change back only well-formed entities in our whitelist:
    // Decimal numeric entities.
    $string = preg_replace('/&amp;#([0-9]+;)/', '&#\1', $string);
    // Hexadecimal numeric entities.
    $string = preg_replace('/&amp;#[Xx]0*((?:[0-9A-Fa-f]{2})+;)/', '&#x\1', $string);
    // Named entities.
    $string = preg_replace('/&amp;([A-Za-z][A-Za-z0-9]*;)/', '&\1', $string);
    $html_tags = array_flip($html_tags);
    // Late static binding does not work inside anonymous functions.
    $class = get_called_class();
    $splitter = function ($matches) use ($html_tags, $class) {
      return $class::split($matches[1], $html_tags, $class);
    };
    // Strip any tags that are not in the whitelist, then mark the text as safe
    // for output. All other known XSS vectors have been filtered out by this
    // point and any HTML tags remaining will have been deliberately allowed, so
    // it is acceptable to call SafeMarkup::set() on the resultant string.
    return preg_replace_callback('%
      (
      <(?=[^a-zA-Z!/])  # a lone <
      |                 # or
      <!--.*?-->        # a comment
      |                 # or
      <[^>]*(>|$)       # a string that starts with a <, up until the > or the end of the string
      |                 # or
      >                 # just a >
      )%x', $splitter, $string);
  }

  /**
   * Applies a very permissive XSS/HTML filter for admin-only use.
   *
   * Use only for fields where it is impractical to use the
   * whole filter system, but where some (mainly inline) mark-up
   * is desired (so \Drupal\Component\Utility\SafeMarkup::checkPlain() is
   * not acceptable).
   *
   * This method is preferred to
   * \Drupal\Component\Utility\SafeMarkup::xssFilter() when the result is
   * not being used directly in the rendering system (for example, when its
   * result is being combined with other strings before rendering). This avoids
   * bloating the safe string list with partial strings if the whole result will
   * be marked safe.
   *
   * Allows all tags that can be used inside an HTML body, save
   * for scripts and styles.
   *
   * @param string $string
   *   The string to apply the filter to.
   *
   * @return string
   *   The filtered string.
   *
   * @ingroup sanitization
   *
   * @see \Drupal\Component\Utility\SafeMarkup::xssFilter()
   * @see \Drupal\Component\Utility\Xss::getAdminTagList()
   *
   */
  public static function filterAdmin($string) {
    return static::filter($string, static::$adminTags);
  }

  /**
   * Processes an HTML tag.
   *
   * @param string $string
   *   The HTML tag to process.
   * @param array $html_tags
   *   An array where the keys are the allowed tags and the values are not
   *   used.
   * @param string $class
   *   The called class. This method is called from an anonymous function which
   *   breaks late static binding. See https://bugs.php.net/bug.php?id=66622 for
   *   more information.
   *
   * @return string
   *   If the element isn't allowed, an empty string. Otherwise, the cleaned up
   *   version of the HTML element.
   */
  protected static function split($string, $html_tags, $class) {
    if (substr($string, 0, 1) != '<') {
      // We matched a lone ">" character.
      return '&gt;';
    }
    elseif (strlen($string) == 1) {
      // We matched a lone "<" character.
      return '&lt;';
    }

    if (!preg_match('%^<\s*(/\s*)?([a-zA-Z0-9\-]+)\s*([^>]*)>?|(<!--.*?-->)$%', $string, $matches)) {
      // Seriously malformed.
      return '';
    }
    $slash = trim($matches[1]);
    $elem = &$matches[2];
    $attrlist = &$matches[3];
    $comment = &$matches[4];

    if ($comment) {
      $elem = '!--';
    }

    // When in whitelist mode, an element is disallowed when not listed.
    if ($class::needsRemoval($html_tags, $elem)) {
      return '';
    }

    if ($comment) {
      return $comment;
    }

    if ($slash != '') {
      return "</$elem>";
    }

    // Is there a closing XHTML slash at the end of the attributes?
    $attrlist = preg_replace('%(\s?)/\s*$%', '\1', $attrlist, -1, $count);
    $xhtml_slash = $count ? ' /' : '';

    // Clean up attributes.
    $attr2 = implode(' ', $class::attributes($attrlist));
    $attr2 = preg_replace('/[<>]/', '', $attr2);
    $attr2 = strlen($attr2) ? ' ' . $attr2 : '';

    return "<$elem$attr2$xhtml_slash>";
  }

  /**
   * Processes a string of HTML attributes.
   *
   * @param string $attributes
   *   The html attribute to process.
   *
   * @return string
   *   Cleaned up version of the HTML attributes.
   */
  protected static function attributes($attributes) {
    $attributes_array = array();
    $mode = 0;
    $attribute_name = '';
    $skip = FALSE;
    $skip_protocol_filtering = FALSE;

    while (strlen($attributes) != 0) {
      // Was the last operation successful?
      $working = 0;

      switch ($mode) {
        case 0:
          // Attribute name, href for instance.
          if (preg_match('/^([-a-zA-Z]+)/', $attributes, $match)) {
            $attribute_name = strtolower($match[1]);
            $skip = ($attribute_name == 'style' || substr($attribute_name, 0, 2) == 'on');

            // Values for attributes of type URI should be filtered for
            // potentially malicious protocols (for example, an href-attribute
            // starting with "javascript:"). However, for some non-URI
            // attributes performing this filtering causes valid and safe data
            // to be mangled. We prevent this by skipping protocol filtering on
            // such attributes.
            // @see \Drupal\Component\Utility\UrlHelper::filterBadProtocol()
            // @see http://www.w3.org/TR/html4/index/attributes.html
            $skip_protocol_filtering = substr($attribute_name, 0, 5) === 'data-' || in_array($attribute_name, array(
              'title',
              'alt',
            ));

            $working = $mode = 1;
            $attributes = preg_replace('/^[-a-zA-Z]+/', '', $attributes);
          }
          break;

        case 1:
          // Equals sign or valueless ("selected").
          if (preg_match('/^\s*=\s*/', $attributes)) {
            $working = 1; $mode = 2;
            $attributes = preg_replace('/^\s*=\s*/', '', $attributes);
            break;
          }

          if (preg_match('/^\s+/', $attributes)) {
            $working = 1; $mode = 0;
            if (!$skip) {
              $attributes_array[] = $attribute_name;
            }
            $attributes = preg_replace('/^\s+/', '', $attributes);
          }
          break;

        case 2:
          // Attribute value, a URL after href= for instance.
          if (preg_match('/^"([^"]*)"(\s+|$)/', $attributes, $match)) {
            $thisval = $skip_protocol_filtering ? $match[1] : UrlHelper::filterBadProtocol($match[1]);

            if (!$skip) {
              $attributes_array[] = "$attribute_name=\"$thisval\"";
            }
            $working = 1;
            $mode = 0;
            $attributes = preg_replace('/^"[^"]*"(\s+|$)/', '', $attributes);
            break;
          }

          if (preg_match("/^'([^']*)'(\s+|$)/", $attributes, $match)) {
            $thisval = $skip_protocol_filtering ? $match[1] : UrlHelper::filterBadProtocol($match[1]);

            if (!$skip) {
              $attributes_array[] = "$attribute_name='$thisval'";
            }
            $working = 1; $mode = 0;
            $attributes = preg_replace("/^'[^']*'(\s+|$)/", '', $attributes);
            break;
          }

          if (preg_match("%^([^\s\"']+)(\s+|$)%", $attributes, $match)) {
            $thisval = $skip_protocol_filtering ? $match[1] : UrlHelper::filterBadProtocol($match[1]);

            if (!$skip) {
              $attributes_array[] = "$attribute_name=\"$thisval\"";
            }
            $working = 1; $mode = 0;
            $attributes = preg_replace("%^[^\s\"']+(\s+|$)%", '', $attributes);
          }
          break;
      }

      if ($working == 0) {
        // Not well formed; remove and try again.
        $attributes = preg_replace('/
          ^
          (
          "[^"]*("|$)     # - a string that starts with a double quote, up until the next double quote or the end of the string
          |               # or
          \'[^\']*(\'|$)| # - a string that starts with a quote, up until the next quote or the end of the string
          |               # or
          \S              # - a non-whitespace character
          )*              # any number of the above three
          \s*             # any number of whitespaces
          /x', '', $attributes);
        $mode = 0;
      }
    }

    // The attribute list ends with a valueless attribute like "selected".
    if ($mode == 1 && !$skip) {
      $attributes_array[] = $attribute_name;
    }
    return $attributes_array;
  }

  /**
   * Whether this element needs to be removed altogether.
   *
   * @param $html_tags
   *   The list of HTML tags.
   * @param $elem
   *   The name of the HTML element.
   *
   * @return bool
   *   TRUE if this element needs to be removed.
   */
  protected static function needsRemoval($html_tags, $elem) {
    return !isset($html_tags[strtolower($elem)]);
  }

  /**
   * Gets the list of html tags allowed by Xss::filterAdmin().
   *
   * @return array
   *   The list of html tags allowed by filterAdmin().
   */
  public static function getAdminTagList() {
    return static::$adminTags;
  }

}
