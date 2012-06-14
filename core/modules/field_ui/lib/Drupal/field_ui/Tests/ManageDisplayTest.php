<?php

/**
 * @file
 * Definition of Drupal\field_ui\Tests\ManageDisplayTest.
 */

namespace Drupal\field_ui\Tests;

use Drupal\node\Node;

/**
 * Tests the functionality of the 'Manage display' screens.
 */
class ManageDisplayTest extends FieldUiTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Manage display',
      'description' => 'Test the Field UI "Manage display" screens.',
      'group' => 'Field UI',
    );
  }

  function setUp() {
    parent::setUp(array('search'));
  }

  /**
   * Tests formatter settings.
   */
  function testFormatterUI() {
    $manage_fields = 'admin/structure/types/manage/' . $this->type;
    $manage_display = $manage_fields . '/display';

    // Create a field, and a node with some data for the field.
    $edit = array(
      'fields[_add_new_field][label]' => 'Test field',
      'fields[_add_new_field][field_name]' => 'test',
    );
    $this->fieldUIAddNewField($manage_fields, $edit);

    // Clear the test-side cache and get the saved field instance.
    field_info_cache_clear();
    $instance = field_info_instance('node', 'field_test', $this->type);
    $format = $instance['display']['default']['type'];
    $default_settings = field_info_formatter_settings($format);
    $setting_name = key($default_settings);
    $setting_value = $instance['display']['default']['settings'][$setting_name];

    // Display the "Manage display" screen and check that the expected formatter is
    // selected.
    $this->drupalGet($manage_display);
    $this->assertFieldByName('fields[field_test][type]', $format, t('The expected formatter is selected.'));
    $this->assertText("$setting_name: $setting_value", t('The expected summary is displayed.'));

    // Change the formatter and check that the summary is updated.
    $edit = array('fields[field_test][type]' => 'field_test_multiple', 'refresh_rows' => 'field_test');
    $this->drupalPostAJAX(NULL, $edit, array('op' => t('Refresh')));
    $format = 'field_test_multiple';
    $default_settings = field_info_formatter_settings($format);
    $setting_name = key($default_settings);
    $setting_value = $default_settings[$setting_name];
    $this->assertFieldByName('fields[field_test][type]', $format, t('The expected formatter is selected.'));
    $this->assertText("$setting_name: $setting_value", t('The expected summary is displayed.'));

    // Submit the form and check that the instance is updated.
    $this->drupalPost(NULL, array(), t('Save'));
    field_info_cache_clear();
    $instance = field_info_instance('node', 'field_test', $this->type);
    $current_format = $instance['display']['default']['type'];
    $current_setting_value = $instance['display']['default']['settings'][$setting_name];
    $this->assertEqual($current_format, $format, t('The formatter was updated.'));
    $this->assertEqual($current_setting_value, $setting_value, t('The setting was updated.'));
  }

  /**
   * Tests switching view modes to use custom or 'default' settings'.
   */
  function testViewModeCustom() {
    // Create a field, and a node with some data for the field.
    $edit = array(
      'fields[_add_new_field][label]' => 'Test field',
      'fields[_add_new_field][field_name]' => 'test',
    );
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $this->type, $edit);
    // For this test, use a formatter setting value that is an integer unlikely
    // to appear in a rendered node other than as part of the field being tested
    // (for example, unlikely to be part of the "Submitted by ... on ..." line).
    $value = 12345;
    $settings = array(
      'type' => $this->type,
      'field_test' => array(LANGUAGE_NOT_SPECIFIED => array(array('value' => $value))),
    );
    $node = $this->drupalCreateNode($settings);

    // Gather expected output values with the various formatters.
    $formatters = field_info_formatter_types();
    $output = array(
      'field_test_default' => $formatters['field_test_default']['settings']['test_formatter_setting'] . '|' . $value,
      'field_test_with_prepare_view' => $formatters['field_test_with_prepare_view']['settings']['test_formatter_setting_additional'] . '|' . $value. '|' . ($value + 1),
    );

    // Check that the field is displayed with the default formatter in 'rss'
    // mode (uses 'default'), and hidden in 'teaser' mode (uses custom settings).
    $this->assertNodeViewText($node, 'rss', $output['field_test_default'], t("The field is displayed as expected in view modes that use 'default' settings."));
    $this->assertNodeViewNoText($node, 'teaser', $value, t("The field is hidden in view modes that use custom settings."));

    // Change fomatter for 'default' mode, check that the field is displayed
    // accordingly in 'rss' mode.
    $edit = array(
      'fields[field_test][type]' => 'field_test_with_prepare_view',
    );
    $this->drupalPost('admin/structure/types/manage/' . $this->type . '/display', $edit, t('Save'));
    $this->assertNodeViewText($node, 'rss', $output['field_test_with_prepare_view'], t("The field is displayed as expected in view modes that use 'default' settings."));

    // Specialize the 'rss' mode, check that the field is displayed the same.
    $edit = array(
      "view_modes_custom[rss]" => TRUE,
    );
    $this->drupalPost('admin/structure/types/manage/' . $this->type . '/display', $edit, t('Save'));
    $this->assertNodeViewText($node, 'rss', $output['field_test_with_prepare_view'], t("The field is displayed as expected in newly specialized 'rss' mode."));

    // Set the field to 'hidden' in the view mode, check that the field is
    // hidden.
    $edit = array(
      'fields[field_test][type]' => 'hidden',
    );
    $this->drupalPost('admin/structure/types/manage/' . $this->type . '/display/rss', $edit, t('Save'));
    $this->assertNodeViewNoText($node, 'rss', $value, t("The field is hidden in 'rss' mode."));

    // Set the view mode back to 'default', check that the field is displayed
    // accordingly.
    $edit = array(
      "view_modes_custom[rss]" => FALSE,
    );
    $this->drupalPost('admin/structure/types/manage/' . $this->type . '/display', $edit, t('Save'));
    $this->assertNodeViewText($node, 'rss', $output['field_test_with_prepare_view'], t("The field is displayed as expected when 'rss' mode is set back to 'default' settings."));

    // Specialize the view mode again.
    $edit = array(
      "view_modes_custom[rss]" => TRUE,
    );
    $this->drupalPost('admin/structure/types/manage/' . $this->type . '/display', $edit, t('Save'));
    // Check that the previous settings for the view mode have been kept.
    $this->assertNodeViewNoText($node, 'rss', $value, t("The previous settings are kept when 'rss' mode is specialized again."));
  }

  /**
   * Asserts that a string is found in the rendered node in a view mode.
   *
   * @param Node $node
   *   The node.
   * @param $view_mode
   *   The view mode in which the node should be displayed.
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   *
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNodeViewText(Node $node, $view_mode, $text, $message) {
    return $this->assertNodeViewTextHelper($node, $view_mode, $text, $message, FALSE);
  }

  /**
   * Asserts that a string is not found in the rendered node in a view mode.
   *
   * @param Node $node
   *   The node.
   * @param $view_mode
   *   The view mode in which the node should be displayed.
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNodeViewNoText(Node $node, $view_mode, $text, $message) {
    return $this->assertNodeViewTextHelper($node, $view_mode, $text, $message, TRUE);
  }

  /**
   * Asserts that a string is (not) found in the rendered nodein a view mode.
   *
   * This helper function is used by assertNodeViewText() and
   * assertNodeViewNoText().
   *
   * @param Node $node
   *   The node.
   * @param $view_mode
   *   The view mode in which the node should be displayed.
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @param $not_exists
   *   TRUE if this text should not exist, FALSE if it should.
   *
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNodeViewTextHelper(Node $node, $view_mode, $text, $message, $not_exists) {
    // Make sure caches on the tester side are refreshed after changes
    // submitted on the tested side.
    field_info_cache_clear();

    // Save current content so that we can restore it when we're done.
    $old_content = $this->drupalGetContent();

    // Render a cloned node, so that we do not alter the original.
    $clone = clone $node;
    $element = node_view($clone, $view_mode);
    $output = drupal_render($element);
    $this->verbose(t('Rendered node - view mode: @view_mode', array('@view_mode' => $view_mode)) . '<hr />'. $output);

    // Assign content so that WebTestBase functions can be used.
    $this->drupalSetContent($output);
    $method = ($not_exists ? 'assertNoText' : 'assertText');
    $return = $this->{$method}((string) $text, $message);

    // Restore previous content.
    $this->drupalSetContent($old_content);

    return $return;
  }
}
