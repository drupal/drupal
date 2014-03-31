<?php

/**
 * @file
 * Contains \Drupal\filter\Tests\FilterAPITest.
 */

namespace Drupal\filter\Tests;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\TypedData\AllowedValuesInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\filter\Plugin\DataType\FilterFormat;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\system\Tests\Entity\EntityUnitTestBase;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Tests the behavior of Filter's API.
 */
class FilterAPITest extends EntityUnitTestBase {

  public static $modules = array('system', 'filter', 'filter_test', 'user');

  public static function getInfo() {
    return array(
      'name' => 'API',
      'description' => 'Test the behavior of the API of the Filter module.',
      'group' => 'Filter',
    );
  }

  function setUp() {
    parent::setUp();

    $this->installConfig(array('system', 'filter'));

    // Create Filtered HTML format.
    $filtered_html_format = entity_create('filter_format', array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'filters' => array(
        // Note that the filter_html filter is of the type FilterInterface::TYPE_MARKUP_LANGUAGE.
        'filter_url' => array(
          'weight' => -1,
          'status' => 1,
        ),
        // Note that the filter_html filter is of the type FilterInterface::TYPE_HTML_RESTRICTOR.
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
   * Tests that the filter order is respected.
   */
  function testCheckMarkupFilterOrder() {
    // Create crazy HTML format.
    $crazy_format = entity_create('filter_format', array(
      'format' => 'crazy',
      'name' => 'Crazy',
      'weight' => 1,
      'filters' => array(
        'filter_html_escape' => array(
          'weight' => 10,
          'status' => 1,
        ),
        'filter_html' => array(
          'weight' => -10,
          'status' => 1,
          'settings' => array(
            'allowed_html' => '<p>',
          ),
        ),
      )
    ));
    $crazy_format->save();

    $text = "<p>Llamas are <not> awesome!</p>";
    $expected_filtered_text = "&lt;p&gt;Llamas are  awesome!&lt;/p&gt;";

    $this->assertIdentical(check_markup($text, 'crazy'), $expected_filtered_text, 'Filters applied in correct order.');
  }

  /**
   * Tests the ability to apply only a subset of filters.
   */
  function testCheckMarkupFilterSubset() {
    $text = "Text with <marquee>evil content and</marquee> a URL: http://drupal.org!";
    $expected_filtered_text = "Text with evil content and a URL: <a href=\"http://drupal.org\">http://drupal.org</a>!";
    $expected_filter_text_without_html_generators = "Text with evil content and a URL: http://drupal.org!";

    $this->assertIdentical(
      check_markup($text, 'filtered_html', '', FALSE, array()),
      $expected_filtered_text,
      'Expected filter result.'
    );
    $this->assertIdentical(
      check_markup($text, 'filtered_html', '', FALSE, array(FilterInterface::TYPE_MARKUP_LANGUAGE)),
      $expected_filter_text_without_html_generators,
      'Expected filter result when skipping FilterInterface::TYPE_MARKUP_LANGUAGE filters.'
    );
    // Related to @see FilterSecurityTest.php/testSkipSecurityFilters(), but
    // this check focuses on the ability to filter multiple filter types at once.
    // Drupal core only ships with these two types of filters, so this is the
    // most extensive test possible.
    $this->assertIdentical(
      check_markup($text, 'filtered_html', '', FALSE, array(FilterInterface::TYPE_HTML_RESTRICTOR, FilterInterface::TYPE_MARKUP_LANGUAGE)),
      $expected_filter_text_without_html_generators,
      'Expected filter result when skipping FilterInterface::TYPE_MARKUP_LANGUAGE filters, even when trying to disable filters of the FilterInterface::TYPE_HTML_RESTRICTOR type.'
    );
  }

  /**
   * Tests the following functions for a variety of formats:
   *   - \Drupal\filter\Entity\FilterFormatInterface::getHtmlRestrictions()
   *   - \Drupal\filter\Entity\FilterFormatInterface::getFilterTypes()
   */
  function testFilterFormatAPI() {
    // Test on filtered_html.
    $filtered_html_format = entity_load('filter_format', 'filtered_html');
    $this->assertIdentical(
      $filtered_html_format->getHtmlRestrictions(),
      array('allowed' => array('p' => TRUE, 'br' => TRUE, 'strong' => TRUE, 'a' => TRUE, '*' => array('style' => FALSE, 'on*' => FALSE))),
      'FilterFormatInterface::getHtmlRestrictions() works as expected for the filtered_html format.'
    );
    $this->assertIdentical(
      $filtered_html_format->getFilterTypes(),
      array(FilterInterface::TYPE_HTML_RESTRICTOR, FilterInterface::TYPE_MARKUP_LANGUAGE),
      'FilterFormatInterface::getFilterTypes() works as expected for the filtered_html format.'
    );

    // Test on full_html.
    $full_html_format = entity_load('filter_format', 'full_html');
    $this->assertIdentical(
      $full_html_format->getHtmlRestrictions(),
      FALSE, // Every tag is allowed.
      'FilterFormatInterface::getHtmlRestrictions() works as expected for the full_html format.'
    );
    $this->assertIdentical(
      $full_html_format->getFilterTypes(),
      array(),
      'FilterFormatInterface::getFilterTypes() works as expected for the full_html format.'
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
      $stupid_filtered_html_format->getHtmlRestrictions(),
      array('allowed' => array()), // No tag is allowed.
      'FilterFormatInterface::getHtmlRestrictions() works as expected for the stupid_filtered_html format.'
    );
    $this->assertIdentical(
      $stupid_filtered_html_format->getFilterTypes(),
      array(FilterInterface::TYPE_HTML_RESTRICTOR),
      'FilterFormatInterface::getFilterTypes() works as expected for the stupid_filtered_html format.'
    );

    // Test on very_restricted_html, where there's two different filters of the
    // FilterInterface::TYPE_HTML_RESTRICTOR type, each restricting in different ways.
    $very_restricted_html_format = entity_create('filter_format', array(
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
    $very_restricted_html_format->save();
    $this->assertIdentical(
      $very_restricted_html_format->getHtmlRestrictions(),
      array('allowed' => array('p' => TRUE, 'br' => FALSE, 'a' => array('href' => TRUE), '*' => array('style' => FALSE, 'on*' => FALSE))),
      'FilterFormatInterface::getHtmlRestrictions() works as expected for the very_restricted_html format.'
    );
    $this->assertIdentical(
      $very_restricted_html_format->getFilterTypes(),
      array(FilterInterface::TYPE_HTML_RESTRICTOR),
      'FilterFormatInterface::getFilterTypes() works as expected for the very_restricted_html format.'
    );
  }

  /**
   * Tests the function of the typed data type.
   */
  function testTypedDataAPI() {
    $definition = DataDefinition::create('filter_format');
    $data = \Drupal::typedDataManager()->create($definition);

    $this->assertTrue($data instanceof AllowedValuesInterface, 'Typed data object implements \Drupal\Core\TypedData\AllowedValuesInterface');

    $filtered_html_user = $this->createUser(array('uid' => 2), array(
      entity_load('filter_format', 'filtered_html')->getPermissionName(),
    ));

    // Test with anonymous user.
    $user = new AnonymousUserSession();
    \Drupal::currentUser()->setAccount($user);

    $expected_available_options = array(
      'filtered_html' => 'Filtered HTML',
      'full_html' => 'Full HTML',
      'filter_test' => 'Test format',
      'plain_text' => 'Plain text',
    );

    $available_values = $data->getPossibleValues();
    $this->assertEqual($available_values, array_keys($expected_available_options));
    $available_options = $data->getPossibleOptions();
    $this->assertEqual($available_options, $expected_available_options);

    $allowed_values = $data->getSettableValues($user);
    $this->assertEqual($allowed_values, array('plain_text'));
    $allowed_options = $data->getSettableOptions($user);
    $this->assertEqual($allowed_options, array('plain_text' => 'Plain text'));

    $data->setValue('foo');
    $violations = $data->validate();
    $this->assertFilterFormatViolation($violations, 'foo');

    // Make sure the information provided by a violation is correct.
    $violation = $violations[0];
    $this->assertEqual($violation->getRoot(), $data, 'Violation root is filter format.');
    $this->assertEqual($violation->getPropertyPath(), '', 'Violation property path is correct.');
    $this->assertEqual($violation->getInvalidValue(), 'foo', 'Violation contains invalid value.');

    $data->setValue('plain_text');
    $violations = $data->validate();
    $this->assertEqual(count($violations), 0, "No validation violation for format 'plain_text' found");

    // Anonymous doesn't have access to the 'filtered_html' format.
    $data->setValue('filtered_html');
    $violations = $data->validate();
    $this->assertFilterFormatViolation($violations, 'filtered_html');

    // Set user with access to 'filtered_html' format.
    \Drupal::currentUser()->setAccount($filtered_html_user);
    $violations = $data->validate();
    $this->assertEqual(count($violations), 0, "No validation violation for accessible format 'filtered_html' found.");

    $allowed_values = $data->getSettableValues($filtered_html_user);
    $this->assertEqual($allowed_values, array('filtered_html', 'plain_text'));
    $allowed_options = $data->getSettableOptions($filtered_html_user);
    $expected_allowed_options = array(
      'filtered_html' => 'Filtered HTML',
      'plain_text' => 'Plain text',
    );
    $this->assertEqual($allowed_options, $expected_allowed_options);
  }

  /**
   * Checks if an expected violation exists in the given violations.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
   *   The violations to assert.
   * @param mixed $invalid_value
   *   The expected invalid value.
   */
  public function assertFilterFormatViolation(ConstraintViolationListInterface $violations, $invalid_value) {
    $filter_format_violation_found = FALSE;
    foreach ($violations as $violation) {
      if ($violation->getRoot() instanceof FilterFormat && $violation->getInvalidValue() === $invalid_value) {
        $filter_format_violation_found = TRUE;
        break;
      }
    }
    $this->assertTrue($filter_format_violation_found, format_string('Validation violation for invalid value "%invalid_value" found', array('%invalid_value' => $invalid_value)));
  }
}
