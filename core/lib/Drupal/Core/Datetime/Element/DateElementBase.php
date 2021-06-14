<?php

namespace Drupal\Core\Datetime\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a base class for date elements.
 */
abstract class DateElementBase extends FormElement {

  /**
   * Specifies the start and end year to use as a date range.
   *
   * Handles a string like -3:+3 or 2001:2010 to describe a dynamic range of
   * minimum and maximum years to use in a date selector.
   *
   * Centers the range around the current year, if any, but expands it far enough
   * so it will pick up the year value in the field in case the value in the field
   * is outside the initial range.
   *
   * @param string $string
   *   A min and max year string like '-3:+1' or '2000:2010' or '2000:+3'.
   * @param object $date
   *   (optional) A date object to test as a default value. Defaults to NULL.
   *
   * @return array
   *   A numerically indexed array, containing the minimum and maximum year
   *   described by this pattern.
   */
  protected static function datetimeRangeYears($string, $date = NULL) {
    $datetime = new DrupalDateTime();
    $this_year = $datetime->format('Y');
    list($min_year, $max_year) = explode(':', $string);

    // Valid patterns would be -5:+5, 0:+1, 2008:2010.
    $plus_pattern = '@[\+|\-][0-9]{1,4}@';
    $year_pattern = '@^[0-9]{4}@';
    if (!preg_match($year_pattern, $min_year, $matches)) {
      if (preg_match($plus_pattern, $min_year, $matches)) {
        $min_year = $this_year + $matches[0];
      }
      else {
        $min_year = $this_year;
      }
    }
    if (!preg_match($year_pattern, $max_year, $matches)) {
      if (preg_match($plus_pattern, $max_year, $matches)) {
        $max_year = $this_year + $matches[0];
      }
      else {
        $max_year = $this_year;
      }
    }
    // We expect the $min year to be less than the $max year. Some custom values
    // for -99:+99 might not obey that.
    if ($min_year > $max_year) {
      $temp = $max_year;
      $max_year = $min_year;
      $min_year = $temp;
    }
    // If there is a current value, stretch the range to include it.
    $value_year = $date instanceof DrupalDateTime ? $date->format('Y') : '';
    if (!empty($value_year)) {
      $min_year = min($value_year, $min_year);
      $max_year = max($value_year, $max_year);
    }
    return [$min_year, $max_year];
  }

  /**
   * Returns the most relevant title of a datetime element.
   *
   * Since datetime form elements often consist of combined date and time fields
   * the element title might not be located on the element itself but on the
   * parent container element.
   *
   * @param array $element
   *   The element being processed.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return string
   *   The title.
   */
  protected static function getElementTitle($element, $complete_form) {
    $title = '';
    if (!empty($element['#title'])) {
      $title = $element['#title'];
    }
    else {
      $parents = $element['#array_parents'];
      array_pop($parents);
      $parent_element = NestedArray::getValue($complete_form, $parents);
      if (!empty($parent_element['#title'])) {
        $title = $parent_element['#title'];
      }
    }

    return $title;
  }

}
