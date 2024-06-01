<?php

namespace Drupal\Core\Test;

/**
 * Converts JUnit XML to Drupal's {simpletest} schema.
 *
 * This is mainly for converting PHPUnit test results.
 *
 * This class is @internal and not considered to be API.
 */
class JUnitConverter {

  /**
   * Converts PHPUnit's JUnit XML output file to {simpletest} schema.
   *
   * @param int $test_id
   *   The current test ID.
   * @param string $phpunit_xml_file
   *   Path to the PHPUnit XML file.
   *
   * @return array[]
   *   The results as array of rows in a format that can be inserted into the
   *   {simpletest} table of the results database.
   *
   * @internal
   */
  public static function xmlToRows($test_id, $phpunit_xml_file) {
    $contents = @file_get_contents($phpunit_xml_file);
    if (!$contents) {
      return [];
    }
    return static::xmlElementToRows($test_id, new \SimpleXMLElement($contents));
  }

  /**
   * Parse test cases from XML to {simpletest} schema.
   *
   * @param int $test_id
   *   The current test ID.
   * @param \SimpleXMLElement $element
   *   The XML data from the JUnit file.
   *
   * @return array[]
   *   The results as array of rows in a format that can be inserted into the
   *   {simpletest} table of the results database.
   *
   * @internal
   */
  public static function xmlElementToRows($test_id, \SimpleXMLElement $element) {
    $records = [];
    $test_cases = static::findTestCases($element);
    foreach ($test_cases as $test_case) {
      $records[] = static::convertTestCaseToSimpletestRow($test_id, $test_case);
    }
    return $records;
  }

  /**
   * Finds all test cases recursively from a test suite list.
   *
   * @param \SimpleXMLElement $element
   *   The PHPUnit xml to search for test cases.
   * @param \SimpleXMLElement $parent
   *   (Optional) The parent of the current element. Defaults to NULL.
   *
   * @return array
   *   A list of all test cases.
   *
   * @internal
   */
  public static function findTestCases(\SimpleXMLElement $element, ?\SimpleXMLElement $parent = NULL) {
    if (!isset($parent)) {
      $parent = $element;
    }

    if ($element->getName() === 'testcase' && (int) $parent->attributes()->tests > 0) {
      // Add the class attribute if the test case does not have one. This is the
      // case for tests using a data provider. The name of the parent testsuite
      // will be in the format class::method.
      if (!$element->attributes()->class) {
        $name = explode('::', $parent->attributes()->name, 2);
        $element->addAttribute('class', $name[0]);
      }
      return [$element];
    }
    $test_cases = [];
    foreach ($element as $child) {
      $file = (string) $parent->attributes()->file;
      if ($file && !$child->attributes()->file) {
        $child->addAttribute('file', $file);
      }
      $test_cases[] = static::findTestCases($child, $element);
    }
    return array_merge(...$test_cases);
  }

  /**
   * Converts a PHPUnit test case result to a {simpletest} result row.
   *
   * @param int $test_id
   *   The current test ID.
   * @param \SimpleXMLElement $test_case
   *   The PHPUnit test case represented as XML element.
   *
   * @return array
   *   An array containing the {simpletest} result row.
   *
   * @internal
   */
  public static function convertTestCaseToSimpletestRow($test_id, \SimpleXMLElement $test_case) {
    $message = '';
    $pass = TRUE;
    if ($test_case->failure) {
      $lines = explode("\n", $test_case->failure);
      $message = $lines[2];
      $pass = FALSE;
    }
    if ($test_case->error) {
      $message = $test_case->error;
      $pass = FALSE;
    }

    $attributes = $test_case->attributes();

    $record = [
      'test_id' => $test_id,
      'test_class' => (string) $attributes->class,
      'status' => $pass ? 'pass' : 'fail',
      'message' => $message,
      'message_group' => 'Other',
      'function' => $attributes->class . '->' . $attributes->name . '()',
      'line' => (int) $attributes->line ?: 0,
      'file' => (string) $attributes->file,
    ];
    return $record;
  }

}
