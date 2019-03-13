<?php

namespace Drupal\twig_extension_test\TwigExtension;

use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * A test Twig extension that adds a custom function and a custom filter.
 */
class TestExtension extends \Twig_Extension {

  /**
   * Generates a list of all Twig functions that this extension defines.
   *
   * @return array
   *   A key/value array that defines custom Twig functions. The key denotes the
   *   function name used in the tag, e.g.:
   *   @code
   *   {{ testfunc() }}
   *   @endcode
   *
   *   The value is a standard PHP callback that defines what the function does.
   */
  public function getFunctions() {
    return [
      'testfunc' => new TwigFunction('testfunc', ['Drupal\twig_extension_test\TwigExtension\TestExtension', 'testFunction']),
    ];
  }

  /**
   * Generates a list of all Twig filters that this extension defines.
   *
   * @return array
   *   A key/value array that defines custom Twig filters. The key denotes the
   *   filter name used in the tag, e.g.:
   *   @code
   *   {{ foo|testfilter }}
   *   @endcode
   *
   *   The value is a standard PHP callback that defines what the filter does.
   */
  public function getFilters() {
    return [
      'testfilter' => new TwigFilter('testfilter', ['Drupal\twig_extension_test\TwigExtension\TestExtension', 'testFilter']),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   A unique identifier for this Twig extension.
   */
  public function getName() {
    return 'twig_extension_test.test_extension';
  }

  /**
   * Outputs either an uppercase or lowercase test phrase.
   *
   * The function generates either an uppercase or lowercase version of the
   * phrase "The quick brown fox jumps over the lazy dog 123.", depending on
   * whether or not the $upperCase parameter evaluates to TRUE. If $upperCase
   * evaluates to TRUE, the result will be uppercase, and if it evaluates to
   * FALSE, the result will be lowercase.
   *
   * @param bool $upperCase
   *   (optional) Whether the result is uppercase (true) or lowercase (false).
   *
   * @return string
   *   The generated string.
   *
   * @see \Drupal\system\Tests\Theme\TwigExtensionTest::testTwigExtensionFunction()
   */
  public static function testFunction($upperCase = FALSE) {
    $string = "The quick brown box jumps over the lazy dog 123.";
    if ($upperCase == TRUE) {
      return strtoupper($string);
    }
    else {
      return strtolower($string);
    }
  }

  /**
   * Replaces all instances of "animal" in a string with "plant".
   *
   * @param string $string
   *   The string to be filtered.
   *
   * @return string
   *   The filtered string.
   *
   * @see \Drupal\system\Tests\Theme\TwigExtensionTest::testTwigExtensionFilter()
   */
  public static function testFilter($string) {
    return str_replace(['animal'], ['plant'], $string);
  }

}
