<?php

declare(strict_types=1);

namespace Drupal\link;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Template\Attribute;

/**
 * Defines a class for attribute XSS filtering.
 *
 * @internal This class was added for a security fix and will be folded into
 *   the \Drupal\Component\Utility\Xss class in a public issue.
 */
final class AttributeXss {

  /**
   * Filters attributes.
   *
   * @param string $attributes
   *   Rendered attribute string, e.g. 'class="foo bar"'.
   */
  private static function attributes(string $attributes): array {
    $attributes_array = [];
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
          if (preg_match('/^([-a-zA-Z][-a-zA-Z0-9]*)/', $attributes, $match)) {
            $attribute_name = strtolower($match[1]);
            $skip = (
              $attribute_name == 'style' ||
              str_starts_with($attribute_name, 'on') ||
              str_starts_with($attribute_name, '-') ||
              // Ignore long attributes to avoid unnecessary processing
              // overhead.
              strlen($attribute_name) > 96
            );

            // Values for attributes of type URI should be filtered for
            // potentially malicious protocols (for example, an href-attribute
            // starting with "javascript:"). However, for some non-URI
            // attributes performing this filtering causes valid and safe data
            // to be mangled. We prevent this by skipping protocol filtering on
            // such attributes.
            // @see \Drupal\Component\Utility\UrlHelper::filterBadProtocol()
            // @see http://www.w3.org/TR/html4/index/attributes.html
            $skip_protocol_filtering = str_starts_with($attribute_name, 'data-') || in_array($attribute_name, [
              'title',
              'alt',
              'rel',
              'property',
              'class',
              'datetime',
            ]);

            $working = $mode = 1;
            $attributes = preg_replace('/^[-a-zA-Z][-a-zA-Z0-9]*/', '', $attributes);
          }
          break;

        case 1:
          // Equals sign or valueless ("selected").
          if (preg_match('/^\s*=\s*/', $attributes)) {
            $working = 1;
            $mode = 2;
            $attributes = preg_replace('/^\s*=\s*/', '', $attributes);
            break;
          }

          if (preg_match('/^\s+/', $attributes)) {
            $working = 1;
            $mode = 0;
            if (!$skip) {
              $attributes_array[$attribute_name] = $attribute_name;
            }
            $attributes = preg_replace('/^\s+/', '', $attributes);
          }
          break;

        case 2:
          // Once we've finished processing the attribute value continue to look
          // for attributes.
          $mode = 0;
          $working = 1;
          // Attribute value, a URL after href= for instance.
          if (preg_match('/^"([^"]*)"(\s+|$)/', $attributes, $match)) {
            $value = $skip_protocol_filtering ? $match[1] : UrlHelper::filterBadProtocol($match[1]);

            if (!$skip) {
              $attributes_array[$attribute_name] = $value;
            }
            $attributes = preg_replace('/^"[^"]*"(\s+|$)/', '', $attributes);
            break;
          }

          if (preg_match("/^'([^']*)'(\s+|$)/", $attributes, $match)) {
            $value = $skip_protocol_filtering ? $match[1] : UrlHelper::filterBadProtocol($match[1]);

            if (!$skip) {
              $attributes_array[$attribute_name] = $value;
            }
            $attributes = preg_replace("/^'[^']*'(\s+|$)/", '', $attributes);
            break;
          }

          if (preg_match("%^([^\s\"']+)(\s+|$)%", $attributes, $match)) {
            $value = $skip_protocol_filtering ? $match[1] : UrlHelper::filterBadProtocol($match[1]);

            if (!$skip) {
              $attributes_array[$attribute_name] = $value;
            }
            $attributes = preg_replace("%^[^\s\"']+(\s+|$)%", '', $attributes);
          }
          break;
      }

      if ($working == 0) {
        // Not well-formed; remove and try again.
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
      $attributes_array[$attribute_name] = $attribute_name;
    }
    return $attributes_array;
  }

  /**
   * Sanitizes attributes.
   *
   * @param array $attributes
   *   Attribute values as key => value format. Value may be a string or in the
   *   case of the 'class' attribute, an array.
   *
   * @return array
   *   Sanitized attributes.
   */
  public static function sanitizeAttributes(array $attributes): array {
    $new_attributes = [];
    foreach ($attributes as $name => $value) {
      // The attribute name should be a single attribute, but there is the
      // possibility that the name is corrupt. Core's XSS::attributes can
      // cleanly handle sanitizing 'selected href="http://example.com" so we
      // provide an allowance for cases where the attribute array is malformed.
      // For example given a name of 'selected href' and a value of
      // http://example.com we split this into two separate attributes, with the
      // value assigned to the last attribute name.
      // Explode the attribute name if a space exists.
      $names = \array_filter(\explode(' ', $name));
      if (\count($names) === 0) {
        // Empty attribute names.
        continue;
      }
      // Valueless attributes set the name to the value when processed by the
      // Attributes object.
      $with_values = \array_combine($names, $names);
      // Create a new Attribute object with the value applied to the last
      // attribute name. If there is only one attribute this simply creates a
      // new attribute with a single key-value pair.
      $last_name = \end($names);
      $with_values[$last_name] = $value;
      $attribute_object = new Attribute($with_values);
      // Filter the attributes.
      $safe = AttributeXss::attributes((string) $attribute_object);
      $safe = \array_map([Html::class, 'decodeEntities'], $safe);
      if (\array_key_exists('class', $safe)) {
        // The class attribute is expected to be an array.
        $safe['class'] = \explode(' ', $safe['class']);
      }
      // Special case for boolean values which are unique to valueless
      // attributes.
      if (\array_key_exists($last_name, $safe) && \is_bool($value)) {
        $safe[$last_name] = $value;
      }
      // Add the safe attributes to the new list.
      $new_attributes += \array_intersect_key($safe, $with_values);
    }

    return $new_attributes;
  }

}
