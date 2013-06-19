<?php

/**
 * @file
 * Contains \Drupal\filter\Tests\FilterAPITest.
 */

namespace Drupal\filter\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the behavior of Filter's API.
 */
class FilterAPITest extends DrupalUnitTestBase {

  public static $modules = array('system', 'filter', 'filter_test');

  public static function getInfo() {
    return array(
      'name' => 'API',
      'description' => 'Test the behavior of the API of the Filter module.',
      'group' => 'Filter',
    );
  }

  function setUp() {
    parent::setUp();

    $this->installConfig(array('system'));

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
          'settings' => array(
            'allowed_html' => '<p> <br> <strong> <a>',
          ),
        ),
      )
    ));
    $filtered_html_format->save();

    // Create Full HTML format.
    $full_html_format = entity_create('filter_format', array(
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => array(),
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
   * Tests the following functions for a variety of formats:
   *   - filter_get_html_restrictions_by_format()
   *   - filter_get_filter_types_by_format()
   */
  function testFilterFormatAPI() {
    // Test on filtered_html.
    $this->assertIdentical(
      filter_get_html_restrictions_by_format('filtered_html'),
      array('allowed' => array('p' => TRUE, 'br' => TRUE, 'strong' => TRUE, 'a' => TRUE, '*' => array('style' => FALSE, 'on*' => FALSE))),
      'filter_get_html_restrictions_by_format() works as expected for the filtered_html format.'
    );
    $this->assertIdentical(
      filter_get_filter_types_by_format('filtered_html'),
      array(FILTER_TYPE_HTML_RESTRICTOR, FILTER_TYPE_MARKUP_LANGUAGE),
      'filter_get_filter_types_by_format() works as expected for the filtered_html format.'
    );

    // Test on full_html.
    $this->assertIdentical(
      filter_get_html_restrictions_by_format('full_html'),
      FALSE, // Every tag is allowed.
      'filter_get_html_restrictions_by_format() works as expected for the full_html format.'
    );
    $this->assertIdentical(
      filter_get_filter_types_by_format('full_html'),
      array(),
      'filter_get_filter_types_by_format() works as expected for the full_html format.'
    );

    // Test on stupid_filtered_html, where nothing is allowed.
    $stupid_filtered_html_format = entity_create('filter_format', array(
      'format' => 'stupid_filtered_html',
      'name' => 'Stupid Filtered HTML',
      'filters' => array(
        'filter_html' => array(
          'status' => 1,
          'settings' => array(
            'allowed_html' => '', // Nothing is allowed.
          ),
        ),
      ),
    ));
    $stupid_filtered_html_format->save();
    $this->assertIdentical(
      filter_get_html_restrictions_by_format('stupid_filtered_html'),
      array('allowed' => array()), // No tag is allowed.
      'filter_get_html_restrictions_by_format() works as expected for the stupid_filtered_html format.'
    );
    $this->assertIdentical(
      filter_get_filter_types_by_format('stupid_filtered_html'),
      array(FILTER_TYPE_HTML_RESTRICTOR),
      'filter_get_filter_types_by_format() works as expected for the stupid_filtered_html format.'
    );

    // Test on very_restricted_html, where there's two different filters of the
    // FILTER_TYPE_HTML_RESTRICTOR type, each restricting in different ways.
    $very_restricted_html = entity_create('filter_format', array(
      'format' => 'very_restricted_html',
      'name' => 'Very Restricted HTML',
      'filters' => array(
        'filter_html' => array(
          'status' => 1,
          'settings' => array(
            'allowed_html' => '<p> <br> <a> <strong>',
          ),
        ),
        'filter_test_restrict_tags_and_attributes' => array(
          'status' => 1,
          'settings' => array(
            'restrictions' => array(
              'allowed' => array(
                'p' => TRUE,
                'br' => FALSE,
                'a' => array('href' => TRUE),
                'em' => TRUE,
              ),
            )
          ),
        ),
      )
    ));
    $very_restricted_html->save();
    $this->assertIdentical(
      filter_get_html_restrictions_by_format('very_restricted_html'),
      array('allowed' => array('p' => TRUE, 'br' => FALSE, 'a' => array('href' => TRUE), '*' => array('style' => FALSE, 'on*' => FALSE))),
      'filter_get_html_restrictions_by_format() works as expected for the very_restricted_html format.'
    );
    $this->assertIdentical(
      filter_get_filter_types_by_format('very_restricted_html'),
      array(FILTER_TYPE_HTML_RESTRICTOR),
      'filter_get_filter_types_by_format() works as expected for the very_restricted_html format.'
    );
  }

}
