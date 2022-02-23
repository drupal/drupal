<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5;

use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Utility\DiffArray;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\Plugin\Filter\FilterHtml;
use Drupal\filter\Plugin\FilterInterface;
use Masterminds\HTML5\Elements;

/**
 * Represents a set of HTML restrictions.
 *
 * This is a value object to represent HTML restrictions as defined by
 * \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions(). It:
 * - accepts the array structure documented on that interface as its constructor
 *   argument
 * - provides convenience constructors for common sources of HTML restrictions
 * - can transform this into multiple representations: a single string
 *   representation historically used by Drupal, a list
 *   representation used by CKEditor 5 and a complex array structure used by
 *   CKEditor 5's General HTML Support plugin
 * - offers difference, intersection and union operations.
 *
 * This makes it significantly simpler to reason about different sets of HTML
 * restrictions and perform complex comparisons by performing these simple
 * operations.
 *
 * @see FilterInterface::getHTMLRestrictions()
 *
 * NOTE: Wildcard tags are not a concept of the Drupal filter system or HTML
 * filter; they are a CKEditor 5 concept. This allows CKEditor 5 plugins to
 * convey a whole range of elements which they support setting certain
 * attributes or attribute values on. For example: alignment.
 *
 * @see ::WILDCARD_ELEMENT_METHODS
 *
 * NOTE: Currently only supports the 'allowed' portion.
 * @todo Add support for "forbidden" tags in https://www.drupal.org/project/drupal/issues/3231334
 *
 * @internal
 */
final class HTMLRestrictions {

  /**
   * An array of allowed elements.
   *
   * @var array
   * @see \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions()
   */
  private $elements;

  /**
   * Wildcard types, and the methods that return tags the wildcard represents.
   *
   * @var string[]
   */
  private const WILDCARD_ELEMENT_METHODS = [
    '$block' => 'getBlockElementList',
  ];

  /**
   * Constructs a set of HTML restrictions.
   *
   * @param array $elements
   *   The allowed elements.
   *
   * @see \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions()
   */
  public function __construct(array $elements) {
    self::validateAllowedRestrictionsPhase1($elements);
    self::validateAllowedRestrictionsPhase2($elements);
    self::validateAllowedRestrictionsPhase3($elements);
    self::validateAllowedRestrictionsPhase4($elements);
    $this->elements = $elements;
  }

  /**
   * Validates allowed elements — phase 1: shape of keys.
   *
   * Confirms each of the top-level array keys:
   * - Is a string
   * - Does not contain leading or trailing whitespace
   * - Is a tag name, not a tag, e.g. `div` not `<div>`
   * - Is a valid HTML tag name.
   *
   * @param array $elements
   *   The allowed elements.
   *
   * @throws \InvalidArgumentException
   */
  private static function validateAllowedRestrictionsPhase1(array $elements): void {
    if (!is_array($elements) || !Inspector::assertAllStrings(array_keys($elements))) {
      throw new \InvalidArgumentException('An array of key-value pairs must be provided, with HTML tag names as keys.');
    }
    foreach (array_keys($elements) as $html_tag_name) {
      if (trim($html_tag_name) !== $html_tag_name) {
        throw new \InvalidArgumentException(sprintf('The "%s" HTML tag contains trailing or leading whitespace.', $html_tag_name));
      }
      if ($html_tag_name[0] === '<' || $html_tag_name[-1] === '>') {
        throw new \InvalidArgumentException(sprintf('"%s" is not a HTML tag name, it is an actual HTML tag. Omit the angular brackets.', $html_tag_name));
      }
      if (self::isWildcardTag($html_tag_name)) {
        continue;
      }
      // HTML elements must have a valid tag name.
      // @see https://html.spec.whatwg.org/multipage/syntax.html#syntax-tag-name
      // @see https://html.spec.whatwg.org/multipage/custom-elements.html#valid-custom-element-name
      if (!preg_match('/^[a-z][0-9a-z\-]*$/', strtolower($html_tag_name))) {
        throw new \InvalidArgumentException(sprintf('"%s" is not a valid HTML tag name.', $html_tag_name));
      }
    }
  }

  /**
   * Validates allowed elements — phase 2: shape of values.
   *
   * @param array $elements
   *   The allowed elements.
   *
   * @throws \InvalidArgumentException
   */
  private static function validateAllowedRestrictionsPhase2(array $elements): void {
    foreach ($elements as $html_tag_name => $html_tag_restrictions) {
      // The value must be either a boolean (FALSE means no attributes are
      // allowed, TRUE means all attributes are allowed), or an array of allowed
      // The value must be either:
      // - An array of allowed attribute names OR
      // - A boolean (where FALSE means no attributes are allowed, and TRUE
      //   means all attributes are allowed).
      if (is_bool($html_tag_restrictions)) {
        continue;
      }
      if (!is_array($html_tag_restrictions)) {
        throw new \InvalidArgumentException(sprintf('The value for the "%s" HTML tag is neither a boolean nor an array of attribute restrictions.', $html_tag_name));
      }
      if ($html_tag_restrictions === []) {
        throw new \InvalidArgumentException(sprintf('The value for the "%s" HTML tag is an empty array. This is not permitted, specify FALSE instead to indicate no attributes are allowed. Otherwise, list allowed attributes.', $html_tag_name));
      }
    }
  }

  /**
   * Validates allowed elements — phase 3: HTML tag attribute restriction keys.
   *
   * @param array $elements
   *   The allowed elements.
   *
   * @throws \InvalidArgumentException
   */
  private static function validateAllowedRestrictionsPhase3(array $elements): void {
    foreach ($elements as $html_tag_name => $html_tag_restrictions) {
      if (!is_array($html_tag_restrictions)) {
        continue;
      }
      if (!Inspector::assertAllStrings(array_keys($html_tag_restrictions))) {
        throw new \InvalidArgumentException(sprintf('The "%s" HTML tag has attribute restrictions, but it is not an array of key-value pairs, with HTML tag attribute names as keys.', $html_tag_name));
      }

      foreach ($html_tag_restrictions as $html_tag_attribute_name => $html_tag_attribute_restrictions) {
        if (trim($html_tag_attribute_name) !== $html_tag_attribute_name) {
          throw new \InvalidArgumentException(sprintf('The "%s" HTML tag has an attribute restriction "%s" which contains whitespace. Omit the whitespace.', $html_tag_name, $html_tag_attribute_name));
        }
      }
    }
  }

  /**
   * Validates allowed elements — phase 4: HTML tag attr restriction values.
   *
   * @param array $elements
   *   The allowed elements.
   *
   * @throws \InvalidArgumentException
   */
  private static function validateAllowedRestrictionsPhase4(array $elements): void {
    foreach ($elements as $html_tag_name => $html_tag_restrictions) {
      if (!is_array($html_tag_restrictions)) {
        continue;
      }

      foreach ($html_tag_restrictions as $html_tag_attribute_name => $html_tag_attribute_restrictions) {
        // The value must be either TRUE (meaning all values for this
        // are allowed), or an array of allowed attribute values.
        if ($html_tag_attribute_restrictions === TRUE) {
          continue;
        }
        if (!is_array($html_tag_attribute_restrictions)) {
          throw new \InvalidArgumentException(sprintf('The "%s" HTML tag has an attribute restriction "%s" which is neither TRUE nor an array of attribute value restrictions.', $html_tag_name, $html_tag_attribute_name));
        }
        if ($html_tag_attribute_restrictions === []) {
          throw new \InvalidArgumentException(sprintf('The "%s" HTML tag has an attribute restriction "%s" which is set to the empty array. This is not permitted, specify either TRUE to allow all attribute values, or list the attribute value restrictions.', $html_tag_name, $html_tag_attribute_name));
        }
        // @codingStandardsIgnoreLine
        if (!Inspector::assertAll(function ($v) { return $v === TRUE; }, $html_tag_attribute_restrictions)) {
          throw new \InvalidArgumentException(sprintf('The "%s" HTML tag has attribute restriction "%s", but it is not an array of key-value pairs, with HTML tag attribute values as keys and TRUE as values.', $html_tag_name, $html_tag_attribute_name));
        }
      }
    }
  }

  /**
   * Creates the empty set of HTML restrictions: nothing is allowed.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   */
  public static function emptySet(): HTMLRestrictions {
    return new self([]);
  }

  /**
   * Whether this is the empty set of HTML restrictions.
   *
   * @return bool
   *
   * @see ::emptySet()
   */
  public function isEmpty(): bool {
    return count($this->elements) === 0;
  }

  /**
   * Constructs a set of HTML restrictions matching the given text format.
   *
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   A filter plugin instance to construct a HTML restrictions object for.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   */
  public static function fromFilterPluginInstance(FilterInterface $filter): HTMLRestrictions {
    return self::fromObjectWithHtmlRestrictions($filter);
  }

  /**
   * Constructs a set of HTML restrictions matching the given text format.
   *
   * @param \Drupal\filter\FilterFormatInterface $text_format
   *   A text format to construct a HTML restrictions object for.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   */
  public static function fromTextFormat(FilterFormatInterface $text_format): HTMLRestrictions {
    return self::fromObjectWithHtmlRestrictions($text_format);
  }

  /**
   * Constructs a set of HTML restrictions matching the given object.
   *
   * Note: there is no interface for the ::getHTMLRestrictions() method that
   * both text filter plugins and the text format configuration entity type
   * implement. To avoid duplicating this logic, this private helper method
   * exists: to simplify the two public static methods that each accept one of
   * those two interfaces.
   *
   * @param \Drupal\filter\Plugin\FilterInterface|\Drupal\filter\FilterFormatInterface $object
   *   A text format or filter plugin instance to construct a HTML restrictions
   *   object for.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *
   * @see ::fromFilterPluginInstance()
   * @see ::fromTextFormat()
   */
  private static function fromObjectWithHtmlRestrictions(object $object): HTMLRestrictions {
    if (!method_exists($object, 'getHTMLRestrictions')) {
      throw new \InvalidArgumentException();
    }

    $restrictions = $object->getHTMLRestrictions();
    if (!isset($restrictions['allowed'])) {
      // @todo Handle HTML restrictor filters that only set forbidden_tags
      //   https://www.drupal.org/project/ckeditor5/issues/3231336.
      throw new \DomainException('text formats with only filters that forbid tags rather than allowing tags are not yet supported.');
    }

    $allowed = $restrictions['allowed'];
    // @todo Validate attributes allowed or forbidden on all elements
    //   https://www.drupal.org/project/ckeditor5/issues/3231334.
    if (isset($allowed['*'])) {
      unset($allowed['*']);
    }

    return new self($allowed);
  }

  /**
   * Parses a string of HTML restrictions into a HTMLRestrictions value object.
   *
   * @param string $elements_string
   *   A string representing a list of allowed HTML elements.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *
   * @see ::toFilterHtmlAllowedTagsString()
   * @see ::toCKEditor5ElementsArray()
   */
  public static function fromString(string $elements_string): HTMLRestrictions {
    // Preprocess wildcard tags: convert `<$block>` to
    // `<__preprocessed-wildcard-block__>`.
    // Note: unknown wildcard tags will trigger a validation error in
    // ::validateAllowedRestrictionsPhase1().
    $replaced_wildcard_tags = [];
    $elements_string = preg_replace_callback('/<(\$[a-z][0-9a-z\-]*)/', function ($matches) use (&$replaced_wildcard_tags) {
      $wildcard_tag_name = $matches[1];
      $replacement = sprintf("__preprocessed-wildcard-%s__", substr($wildcard_tag_name, 1));
      $replaced_wildcard_tags[$replacement] = $wildcard_tag_name;
      return "<$replacement";
    }, $elements_string);

    // Reuse the parsing logic from FilterHtml::getHTMLRestrictions().
    $configuration = ['settings' => ['allowed_html' => $elements_string]];
    $filter = new FilterHtml($configuration, 'filter_html', ['provider' => 'filter']);
    $allowed_elements = $filter->getHTMLRestrictions()['allowed'];
    // Omit the broad wildcard addition that FilterHtml::getHTMLRestrictions()
    // always sets; it is specific to how FilterHTML works and irrelevant here.
    unset($allowed_elements['*']);

    // Postprocess tag wildcards: convert `<__preprocessed-wildcard-block__>` to
    // `<$block>`.
    foreach ($replaced_wildcard_tags as $processed => $original) {
      if (isset($allowed_elements[$processed])) {
        $allowed_elements[$original] = $allowed_elements[$processed];
        unset($allowed_elements[$processed]);
      }
    }

    return new self($allowed_elements);
  }

  /**
   * Computes difference of two HTML restrictions, with wildcard support.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $other
   *   The HTML restrictions to compare to.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   Returns a new HTML restrictions value object with all the elements that
   *   are not allowed in $other.
   */
  public function diff(HTMLRestrictions $other): HTMLRestrictions {
    return self::applyOperation($this, $other, 'doDiff');
  }

  /**
   * Computes difference of two HTML restrictions, without wildcard support.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $other
   *   The HTML restrictions to compare to.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   Returns a new HTML restrictions value object with all the elements that
   *   are not allowed in $other.
   */
  private function doDiff(HTMLRestrictions $other): HTMLRestrictions {
    $diff_elements = array_filter(
      DiffArray::diffAssocRecursive($this->elements, $other->elements),
      // DiffArray::diffAssocRecursive() provides a good start, but additional
      // filtering is necessary due to the specific semantics of an HTML
      // restrictions array, where:
      // - A value of FALSE for a given tag/attribute disallows all
      //   attributes/ /attribute values for that tag/attribute.
      // - An array value for a given tag/attribute provides an array keyed by
      //   specific attributes/attribute values with boolean values determining
      //   if they are allowed or not.
      // - A value of TRUE for a given tag/attribute permits all attributes/attribute
      //   values for that tag/attribute.
      // @see \Drupal\filter\Entity\FilterFormat::getHtmlRestrictions()
      function ($value, string $tag) use ($other) {
        // If this HTML restrictions object contains a tag that the other did
        // not contain at all: keep the DiffArray result.
        if (!array_key_exists($tag, $other->elements)) {
          return TRUE;
        }

        // All subsequent checks can assume that $other contains an entry for
        // this tag.

        // If this HTML restrictions object does not allow any attributes for
        // this tag, then the other is at least equally restrictive: drop the
        // DiffArray result.
        if ($value === FALSE) {
          return FALSE;
        }
        // If this HTML restrictions object allows any attributes for this
        // tag, then the other is at most equally permissive: keep the
        // DiffArray result.
        if ($value === TRUE) {
          return TRUE;
        }
        // Otherwise, this HTML restrictions object allows specific attributes
        // only. DiffArray only knows to compare arrays. When the other object
        // has a non-array value for this tag, interpret those values correctly.
        assert(is_array($value));
        // The other object is more restrictive regarding allowed attributes
        // for this tag: keep the DiffArray result.
        if ($other->elements[$tag] === FALSE) {
          return TRUE;
        }
        // The other object is more permissive regarding allowed attributes
        // for this tag: drop the DiffArray result.
        if ($other->elements[$tag] === TRUE) {
          return FALSE;
        }
        // Both objects have lists of allowed attributes: keep the DiffArray
        // result.
        // @see ::validateAllowedRestrictionsPhase3()
        assert(is_array($other->elements[$tag]));
        return TRUE;
      },
      ARRAY_FILTER_USE_BOTH
    );

    return new self($diff_elements);
  }

  /**
   * Computes intersection of two HTML restrictions, with wildcard support.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $other
   *   The HTML restrictions to compare to.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   Returns a new HTML restrictions value object with all the elements that
   *   are also allowed in $other.
   */
  public function intersect(HTMLRestrictions $other): HTMLRestrictions {
    return self::applyOperation($this, $other, 'doIntersect');
  }

  /**
   * Computes intersection of two HTML restrictions, without wildcard support.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $other
   *   The HTML restrictions to compare to.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   Returns a new HTML restrictions value object with all the elements that
   *   are also allowed in $other.
   */
  public function doIntersect(HTMLRestrictions $other): HTMLRestrictions {
    $intersection_based_on_tags = array_intersect_key($this->elements, $other->elements);
    $intersection = [];
    // Additional filtering is necessary beyond the array_intersect_key that
    // computed $intersection_based_on_tags because tag configuration can have
    // boolean values that have different logic than array values.
    foreach (array_keys($intersection_based_on_tags) as $tag) {
      // If either does not allow attributes, neither does the intersection.
      if ($this->elements[$tag] === FALSE || $other->elements[$tag] === FALSE) {
        $intersection[$tag] = FALSE;
        continue;
      }
      // If both allow all attributes, so does the intersection.
      if ($this->elements[$tag] === TRUE && $other->elements[$tag] === TRUE) {
        $intersection[$tag] = TRUE;
        continue;
      }
      // If the first allows all attributes, return the second.
      if ($this->elements[$tag] === TRUE) {
        $intersection[$tag] = $other->elements[$tag];
        continue;
      }
      // And vice versa.
      if ($other->elements[$tag] === TRUE) {
        $intersection[$tag] = $this->elements[$tag];
        continue;
      }
      // In all other cases, we need to return the most restrictive
      // intersection of per-attribute restrictions.
      // @see ::validateAllowedRestrictionsPhase3()
      assert(is_array($this->elements[$tag]));
      assert(is_array($other->elements[$tag]));
      $intersection[$tag] = [];
      $attributes_intersection = array_intersect_key($this->elements[$tag], $other->elements[$tag]);
      foreach (array_keys($attributes_intersection) as $attr) {
        // If both allow all attribute values, so does the intersection.
        if ($this->elements[$tag][$attr] === TRUE && $other->elements[$tag][$attr] === TRUE) {
          $intersection[$tag][$attr] = TRUE;
          continue;
        }
        // If the first allows all attribute values, return the second.
        if ($this->elements[$tag][$attr] === TRUE) {
          $intersection[$tag][$attr] = $other->elements[$tag][$attr];
          continue;
        }
        // And vice versa.
        if ($other->elements[$tag][$attr] === TRUE) {
          $intersection[$tag][$attr] = $this->elements[$tag][$attr];
          continue;
        }
        assert(is_array($this->elements[$tag][$attr]));
        assert(is_array($other->elements[$tag][$attr]));
        $intersection[$tag][$attr] = array_intersect_key($this->elements[$tag][$attr], $other->elements[$tag][$attr]);
        // It is not permitted to specify an empty attribute value
        // restrictions array.
        if (empty($intersection[$tag][$attr])) {
          unset($intersection[$tag][$attr]);
        }
      }

      // HTML tags must not have an empty array of allowed attributes.
      if ($intersection[$tag] === []) {
        $intersection[$tag] = FALSE;
      }
    }

    return new self($intersection);
  }

  /**
   * Computes set union of two HTML restrictions, with wildcard support.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $other
   *   The HTML restrictions to compare to.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   Returns a new HTML restrictions value object with all the elements that
   *   are either allowed in $this or in $other.
   */
  public function merge(HTMLRestrictions $other): HTMLRestrictions {
    $union = array_merge_recursive($this->elements, $other->elements);
    // When recursively merging elements arrays, unkeyed boolean values can
    // appear in attribute config arrays. This removes them.
    foreach ($union as $tag => $tag_config) {
      if (is_array($tag_config)) {
        // If the HTML tag restrictions for both operands were both booleans,
        // then the result of array_merge_recursive() is an array containing two
        // booleans (because it is designed for arrays, not for also merging
        // booleans) under the first two numeric keys: 0 and 1. This does not
        // match the structure expected of HTML restrictions. Combine the two
        // booleans.
        if (array_key_exists(0, $tag_config) && array_key_exists(1, $tag_config) && is_bool($tag_config[0]) && is_bool($tag_config[1])) {
          // Twice FALSE.
          if ($tag_config === [FALSE, FALSE]) {
            $union[$tag] = FALSE;
          }
          // Once or twice TRUE.
          else {
            $union[$tag] = TRUE;
          }
          continue;
        }

        // If the HTML tag restrictions for only one of the two operands was a
        // boolean, then the result of array_merge_recursive() is an array
        // containing the complete contents of the non-boolean operand plus an
        // additional key-value pair with the first numeric key: 0.
        if (array_key_exists(0, $tag_config)) {
          // If the boolean was FALSE (meaning: "no attributes allowed"), then
          // the other operand's values should be used in an union: this yields
          // the most permissive result.
          if ($tag_config[0] === FALSE) {
            unset($union[$tag][0]);
          }
          // If the boolean was TRUE (meaning: "all attributes allowed"), then
          // the other operand's values should be ignored in an union: this
          // yields the most permissive result.
          elseif ($tag_config[0] === TRUE) {
            $union[$tag] = TRUE;
          }
          continue;
        }

        // If the HTML tag restrictions are arrays for both operands, similar
        // logic needs to be applied to the attribute-level restrictions.
        foreach ($tag_config as $html_tag_attribute_name => $html_tag_attribute_restrictions) {
          if ($html_tag_attribute_restrictions === TRUE) {
            continue;
          }

          if (array_key_exists(0, $html_tag_attribute_restrictions)) {
            // The "twice FALSE" case cannot occur for attributes, because
            // attribute restrictions either have "TRUE" (to indicate any value
            // is allowed for the attribute) or a list of allowed attribute
            // values. If there is a numeric key, then one of the two operands
            // must allow all attribute values (the "TRUE" case). Otherwise, an
            // array merge would have happened, and no numeric key would exist.
            // Therefore, this is always once or twice TRUE.
            // e.g.: <foo bar> and <foo bar>, or <foo bar> and <foo bar="baz">
            assert($html_tag_attribute_restrictions[0] === TRUE || $html_tag_attribute_restrictions[1] === TRUE);
            $union[$tag][$html_tag_attribute_name] = TRUE;
          }
          else {
            // Finally, when both operands list the same allowed attribute
            // values, then the result provided by array_merge_recursive() for
            // those allowed attribute values is an array containing two times
            // `TRUE` (because it is designed for arrays, not for also merging
            // booleans) under the first two numeric keys: 0 and 1.
            // e.g.: <foo bar="baz qux"> merged with <foo bar="baz quux">.
            foreach ($html_tag_attribute_restrictions as $allowed_attribute_value => $merged_result) {
              if ($merged_result === [0 => TRUE, 1 => TRUE]) {
                $union[$tag][$html_tag_attribute_name][$allowed_attribute_value] = TRUE;
              }
            }
          }
        }
      }
    }
    return new self($union);
  }

  /**
   * Applies an operation (difference/intersection/union) with wildcard support.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $a
   *   The first operand.
   * @param \Drupal\ckeditor5\HTMLRestrictions $b
   *   The second operand.
   * @param string $operation_method_name
   *   The name of the private method on this class to use as the operation.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   The result of the operation.
   */
  private static function applyOperation(HTMLRestrictions $a, HTMLRestrictions $b, string $operation_method_name): HTMLRestrictions {
    // 1. Operation applied to wildcard tags that exist in both operands.
    // For example: <$block id> in both operands.
    $a_wildcard = self::getWildcardSubset($a);
    $b_wildcard = self::getWildcardSubset($b);
    $wildcard_op_result = $a_wildcard->$operation_method_name($b_wildcard);

    // Early return if both operands contain only wildcard tags.
    if (count($a_wildcard->elements) === count($a->elements) && count($b_wildcard->elements) === count($b->elements)) {
      return $wildcard_op_result;
    }

    // 2. Operation applied with wildcard tags resolved into concrete tags.
    // For example: <p class="text-align-center"> in the first operand and
    // <$block class="text-align-center"> in the second operand.
    $a_concrete = self::resolveWildcards($a);
    $b_concrete = self::resolveWildcards($b);
    $concrete_op_result = $a_concrete->$operation_method_name($b_concrete);

    // Using the PHP array union operator is safe because the two operation
    // result arrays ensure there is no overlap between the array keys.
    // @codingStandardsIgnoreStart
    assert(Inspector::assertAll(function ($t) { return self::isWildcardTag($t); }, array_keys($wildcard_op_result->elements)));
    assert(Inspector::assertAll(function ($t) { return !self::isWildcardTag($t); }, array_keys($concrete_op_result->elements)));
    // @codingStandardsIgnoreEnd

    return new self($concrete_op_result->elements + $wildcard_op_result->elements);
  }

  /**
   * Gets the subset of allowed elements whose tags are wildcards.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $r
   *   A set of HTML restrictions.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   The subset of the given set of HTML restrictions.
   */
  private static function getWildcardSubset(HTMLRestrictions $r): HTMLRestrictions {
    return new self(array_filter($r->elements, [__CLASS__, 'isWildcardTag'], ARRAY_FILTER_USE_KEY));
  }

  /**
   * Checks whether given tag is a wildcard.
   *
   * @param string $tag_name
   *   A tag name.
   *
   * @return bool
   *   TRUE if it is a wildcard, otherwise FALSE.
   */
  private static function isWildcardTag(string $tag_name): bool {
    return substr($tag_name, 0, 1) === '$' && array_key_exists($tag_name, self::WILDCARD_ELEMENT_METHODS);
  }

  /**
   * Resolves the wildcard tags (this consumes the wildcard tags).
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $r
   *   A set of HTML restrictions.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   The concrete interpretation of the given set of HTML restrictions. All
   *   wildcard tag restrictions are resolved into restrictions on concrete
   *   elements, if concrete elements are allowed that correspond to the
   *   wildcard tags.
   *
   * @see ::getWildcardTags()
   */
  private static function resolveWildcards(HTMLRestrictions $r): HTMLRestrictions {
    // Start by resolving the wildcards in a naive, simple way: generate
    // tags, attributes and attribute values they support.
    $naively_resolved_wildcard_elements = [];
    foreach ($r->elements as $tag_name => $tag_config) {
      if (self::isWildcardTag($tag_name)) {
        $wildcard_tags = self::getWildcardTags($tag_name);
        // Do not resolve to all tags supported by the wildcard tag, but only
        // those which are explicitly supported. Because wildcard tags only
        // allow declaring support for additional attributes and attribute
        // values on already supported tags.
        foreach ($wildcard_tags as $wildcard_tag) {
          if (isset($r->elements[$wildcard_tag])) {
            $naively_resolved_wildcard_elements[$wildcard_tag] = $tag_config;
          }
        }
      }
    }
    $naive_resolution = new self($naively_resolved_wildcard_elements);

    // Now merge the naive resolution's elements with the original elements, to
    // let ::merge() pick the most permissive one.
    // This is necessary because resolving wildcards may result in concrete tags
    // becoming either more permissive:
    // - if $r is `<p> <$block class="foo">`
    // - then $naive will be `<p class="foo">`
    // - merging them yields `<p class="foo"> <$block class="foo">`
    // - diffing the wildcard subsets yields just `<p class="foo">`
    // Or it could result in concrete tags being unaffected by the resolved
    // wildcards:
    // - if $r is `<p class> <$block class="foo">`
    // - then $naive will be `<p class="foo">`
    // - merging them yields `<p class> <$block class="foo">` again
    // - diffing the wildcard subsets yields just `<p class>`
    return $r->merge($naive_resolution)->doDiff(self::getWildcardSubset($r));
  }

  /**
   * Gets allowed elements.
   *
   * @param bool $resolve_wildcards
   *   (optional) Whether to resolve wildcards. Defaults to TRUE. When set to
   *   FALSE, the raw allowed elements will be returned (with no processing
   *   applied hence no resolved wildcards).
   *
   * @return array
   *
   * @see \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions()
   */
  public function getAllowedElements(bool $resolve_wildcards = TRUE): array {
    if ($resolve_wildcards) {
      return self::resolveWildcards($this)->elements;
    }

    return $this->elements;
  }

  /**
   * Transforms into the CKEditor 5 package metadata "elements" representation.
   *
   * @return string[]
   *   A list of strings, with each string expressing an allowed element,
   *   structured in the way expected by the CKEditor 5 package metadata.
   *
   * @see https://ckeditor.com/docs/ckeditor5/latest/framework/guides/contributing/package-metadata.html
   */
  public function toCKEditor5ElementsArray(): array {
    $readable = [];
    foreach ($this->elements as $tag => $attributes) {
      $attribute_string = '';
      if (is_array($attributes)) {
        foreach ($attributes as $attribute_name => $attribute_values) {
          if (is_array($attribute_values)) {
            $attribute_values_string = implode(' ', array_keys($attribute_values));
            $attribute_string .= "$attribute_name=\"$attribute_values_string\" ";
          }
          else {
            $attribute_string .= "$attribute_name ";
          }
        }
      }
      $joined = '<' . $tag . (!empty($attribute_string) ? ' ' . trim($attribute_string) : '') . '>';
      array_push($readable, $joined);
    }
    assert(Inspector::assertAllStrings($readable));
    return $readable;
  }

  /**
   * Transforms into the Drupal HTML filter's "allowed_html" representation.
   *
   * @return string
   *   A string representing the list of allowed elements, structured in the
   *   manner expected by the "Limit allowed HTML tags and correct faulty HTML"
   *   filter plugin.
   *
   * @see \Drupal\filter\Plugin\Filter\FilterHtml
   */
  public function toFilterHtmlAllowedTagsString(): string {
    return implode(' ', $this->toCKEditor5ElementsArray());
  }

  /**
   * Transforms into the CKEditor 5 GHS configuration representation.
   *
   * @return string[]
   *   An array of allowed elements, structured in the manner expected by the
   *   CKEditor 5 htmlSupport plugin constructor.
   *
   * @see https://ckeditor5.github.io/docs/nightly/ckeditor5/latest/features/general-html-support.html#configuration
   */
  public function toGeneralHtmlSupportConfig(): array {
    $allowed = [];
    foreach ($this->elements as $tag => $attributes) {
      $to_allow = ['name' => $tag];
      assert($attributes === FALSE || is_array($attributes));
      if (is_array($attributes)) {
        foreach ($attributes as $name => $value) {
          // Convert the `'hreflang' => ['en' => TRUE, 'fr' => TRUE]` structure
          // that this class expects to the `['en', 'fr']` structure that the
          // GHS functionality in CKEditor 5 expects.
          if (is_array($value)) {
            $value = array_keys($value);
          }
          // Drupal never allows style attributes due to security concerns.
          // @see \Drupal\Component\Utility\Xss
          if ($name === 'style') {
            continue;
          }
          assert($value === TRUE || Inspector::assertAllStrings($value));
          if ($name === 'class') {
            $to_allow['classes'] = $value;
            continue;
          }
          // If a single attribute value is allowed, it must be TRUE (see the
          // assertion above). Otherwise, it must be an array of strings (see
          // the assertion above), which lists all allowed attribute values. To
          // be able to configure GHS to a range of values, we need to use a
          // regular expression.
          // @todo Expand to support partial wildcards in
          //   https://www.drupal.org/project/drupal/issues/3260853.
          $to_allow['attributes'][$name] = is_array($value)
            ? ['regexp' => ['pattern' => '/^(' . implode('|', $value) . ')$/']]
            : $value;
        }
      }
      $allowed[] = $to_allow;
    }

    return $allowed;
  }

  /**
   * Gets a list of block-level elements.
   *
   * @return string[]
   *   An array of block-level element tags.
   */
  private static function getBlockElementList(): array {
    return array_filter(array_keys(Elements::$html5), function (string $element): bool {
      return Elements::isA($element, Elements::BLOCK_TAG);
    });
  }

  /**
   * Computes the tags that match the provided wildcard.
   *
   * A wildcard tag in element config is a way of representing multiple tags
   * with a single item, such as `<$block>` to represent all block tags. Each
   * wildcard should have a corresponding callback method listed in
   * WILDCARD_ELEMENT_METHODS that returns the set of tags represented by the
   * wildcard.
   *
   * @param string $wildcard
   *   The wildcard that represents multiple tags.
   *
   * @return string[]
   *   An array of HTML tags.
   */
  private static function getWildcardTags(string $wildcard): array {
    $wildcard_element_method = self::WILDCARD_ELEMENT_METHODS[$wildcard];
    return call_user_func([self::class, $wildcard_element_method]);
  }

}
