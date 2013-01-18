<?php

/**
 * @file
 * Definition of Drupal\filter\Tests\FilterAPITest.
 */

namespace Drupal\filter\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the behavior of Filter's API.
 */
class FilterAPITest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'API',
      'description' => 'Test the behavior of the API of the Filter module.',
      'group' => 'Filter',
    );
  }

  function setUp() {
    parent::setUp();

    // Create Filtered HTML format.
    $filtered_html_format = entity_create('filter_format', array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'filters' => array(
        // Note that the filter_html filter is of the type FILTER_TYPE_MARKUP_LANGUAGE.
        'filter_url' => array(
          'weight' => -1,
          'status' => 1,
        ),
        // Note that the filter_html filter is of the type FILTER_TYPE_HTML_RESTRICTOR.
        'filter_html' => array(
          'status' => 1,
        ),
      )
    ));
    $filtered_html_format->save();

    // Create Full HTML format.
    $full_html_format = entity_create('filter_format', array(
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => array(
        'filter_htmlcorrector' => array(
          'weight' => 10,
          'status' => 1,
        ),
      ),
    ));
    $full_html_format->save();
  }

  /**
   * Tests the ability to apply only a subset of filters.
   */
  function testCheckMarkup() {
    $text = "Text with <marquee>evil content and</marquee> a URL: http://drupal.org!";
    $expected_filtered_text = "Text with evil content and a URL: <a href=\"http://drupal.org\">http://drupal.org</a>!";
    $expected_filter_text_without_html_generators = "Text with evil content and a URL: http://drupal.org!";

    $this->assertIdentical(
      check_markup($text, 'filtered_html', '', FALSE, array()),
      $expected_filtered_text,
      'Expected filter result.'
    );
    $this->assertIdentical(
      check_markup($text, 'filtered_html', '', FALSE, array(FILTER_TYPE_MARKUP_LANGUAGE)),
      $expected_filter_text_without_html_generators,
      'Expected filter result when skipping FILTER_TYPE_MARKUP_LANGUAGE filters.'
    );
    // Related to @see FilterSecurityTest.php/testSkipSecurityFilters(), but
    // this check focuses on the ability to filter multiple filter types at once.
    // Drupal core only ships with these two types of filters, so this is the
    // most extensive test possible.
    $this->assertIdentical(
      check_markup($text, 'filtered_html', '', FALSE, array(FILTER_TYPE_HTML_RESTRICTOR, FILTER_TYPE_MARKUP_LANGUAGE)),
      $expected_filter_text_without_html_generators,
      'Expected filter result when skipping FILTER_TYPE_MARKUP_LANGUAGE filters, even when trying to disable filters of the FILTER_TYPE_HTML_RESTRICTOR type.'
    );
  }

  /**
   * Tests the function filter_get_filter_types_by_format().
   */
  function testFilterFormatAPI() {
    // Test on filtered_html.
    $this->assertEqual(
      filter_get_filter_types_by_format('filtered_html'),
      array(FILTER_TYPE_HTML_RESTRICTOR, FILTER_TYPE_MARKUP_LANGUAGE),
      'filter_get_filter_types_by_format() works as expected for the filtered_html format.'
    );

    // Test on full_html.
    $this->assertEqual(
      filter_get_filter_types_by_format('full_html'),
      array(FILTER_TYPE_HTML_RESTRICTOR),
      'filter_get_filter_types_by_format() works as expected for the full_html format.'
    );
  }

}
