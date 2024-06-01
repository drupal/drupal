<?php

namespace Drupal\editor\EditorXssFilter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\filter\FilterFormatInterface;
use Drupal\editor\EditorXssFilterInterface;

/**
 * Defines the standard text editor XSS filter.
 */
class Standard extends Xss implements EditorXssFilterInterface {

  /**
   * {@inheritdoc}
   */
  public static function filterXss($html, FilterFormatInterface $format, ?FilterFormatInterface $original_format = NULL) {
    // Apply XSS filtering, but blacklist the <script>, <style>, <link>, <embed>
    // and <object> tags.
    // The <script> and <style> tags are blacklisted because their contents
    // can be malicious (and therefore they are inherently unsafe), whereas for
    // all other tags, only their attributes can make them malicious. Since
    // \Drupal\Component\Utility\Xss::filter() protects against malicious
    // attributes, we take no blacklisting action.
    // The exceptions to the above rule are <link>, <embed> and <object>:
    // - <link> because the href attribute allows the attacker to import CSS
    //   using the HTTP(S) protocols which Xss::filter() considers safe by
    //   default. The imported remote CSS is applied to the main document, thus
    //   allowing for the same XSS attacks as a regular <style> tag.
    // - <embed> and <object> because these tags allow non-HTML applications or
    //   content to be embedded using the src or data attributes, respectively.
    //   This is safe in the case of HTML documents, but not in the case of
    //   Flash objects for example, that may access/modify the main document
    //   directly.
    // <iframe> is considered safe because it only allows HTML content to be
    // embedded, hence ensuring the same origin policy always applies.
    $dangerous_tags = ['script', 'style', 'link', 'embed', 'object'];

    // Simply blacklisting these five dangerous tags would bring safety, but
    // also user frustration: what if a text format is configured to allow
    // <embed>, for example? Then we would strip that tag, even though it is
    // allowed, thereby causing data loss!
    // Therefore, we want to be smarter still. We want to take into account
    // which HTML tags are allowed by the text format we're filtering for, and
    // if we're switching from another text format, we want to take that
    // format's allowed tags into account as well.
    // In other words: we only expect markup allowed in both the original and
    // the new format to continue to exist.
    $format_restrictions = $format->getHtmlRestrictions();
    if ($original_format !== NULL) {
      $original_format_restrictions = $original_format->getHtmlRestrictions();
    }

    // Any tags that are explicitly whitelisted by the text format must be
    // removed from the list of default dangerous tags: if they're explicitly
    // allowed, then we must respect that configuration.
    // When switching from another format, we must use the intersection of
    // allowed tags: if either format is more restrictive, then the safety
    // expectations of *both* formats apply.
    $allowed_tags = self::getAllowedTags($format_restrictions);
    if ($original_format !== NULL) {
      $allowed_tags = array_intersect($allowed_tags, self::getAllowedTags($original_format_restrictions));
    }

    // Don't blacklist dangerous tags that are explicitly allowed in both text
    // formats.
    $blacklisted_tags = array_diff($dangerous_tags, $allowed_tags);

    $output = static::filter($html, $blacklisted_tags);

    // Since data-attributes can contain encoded HTML markup that could be
    // decoded and interpreted by editors, we need to apply XSS filtering to
    // their contents.
    return static::filterXssDataAttributes($output);
  }

  /**
   * Applies a very permissive XSS/HTML filter to data-attributes.
   *
   * @param string $html
   *   The string to apply the data-attributes filtering to.
   *
   * @return string
   *   The filtered string.
   */
  protected static function filterXssDataAttributes($html) {
    if (stristr($html, 'data-') !== FALSE) {
      $dom = Html::load($html);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//@*[starts-with(name(.), "data-")]') as $node) {
        // The data-attributes contain an HTML-encoded value, so we need to
        // decode the value, apply XSS filtering and then re-save as encoded
        // value. There is no need to explicitly decode $node->value, since the
        // DOMAttr::value getter returns the decoded value.
        $value = Xss::filterAdmin($node->value);
        $node->value = Html::escape($value);
      }
      $html = Html::serialize($dom);
    }

    return $html;
  }

  /**
   * Get all allowed tags from a restrictions data structure.
   *
   * @param array|false $restrictions
   *   Restrictions as returned by FilterInterface::getHTMLRestrictions().
   *
   * @return array
   *   An array of allowed HTML tags.
   *
   * @see \Drupal\filter\Plugin\Filter\FilterInterface::getHTMLRestrictions()
   */
  protected static function getAllowedTags($restrictions) {
    if ($restrictions === FALSE || !isset($restrictions['allowed'])) {
      return [];
    }

    $allowed_tags = array_keys($restrictions['allowed']);
    // Exclude the wildcard tag, which is used to set attribute restrictions on
    // all tags simultaneously.
    $allowed_tags = array_diff($allowed_tags, ['*']);

    return $allowed_tags;
  }

  /**
   * {@inheritdoc}
   */
  protected static function needsRemoval(array $html_tags, $elem) {
    // See static::filterXss() about how this class uses blacklisting instead
    // of the normal whitelisting.
    return !parent::needsRemoval($html_tags, $elem);
  }

}
