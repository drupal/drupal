<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestForm.
 */

namespace Drupal\form_test\Form;

/**
 * Temporary form controller for form_test module.
 */
class FormTestForm {

  /**
   * Wraps form_test_alter_form().
   *
   * @todo Remove form_test_alter_form().
   */
  public function alterForm() {
    return drupal_get_form('form_test_alter_form');
  }

  /**
   * Wraps form_test_validate_form().
   *
   * @todo Remove form_test_validate_form().
   */
  public function validateForm() {
    return drupal_get_form('form_test_validate_form');
  }

  /**
   * Wraps form_test_validate_required_form().
   *
   * @todo Remove form_test_validate_required_form().
   */
  public function validateRequiredForm() {
    return drupal_get_form('form_test_validate_required_form');
  }

  /**
   * Wraps form_test_validate_required_form_no_title().
   *
   * @todo Remove form_test_validate_required_form_no_title().
   */
  public function validateRequiredFormNoTitle() {
    return drupal_get_form('form_test_validate_required_form_no_title');
  }

  /**
   * Wraps form_test_limit_validation_errors_form().
   *
   * @todo Remove form_test_limit_validation_errors_form().
   */
  public function validateFormWithErrorSuppression() {
    return drupal_get_form('form_test_limit_validation_errors_form');
  }

  /**
   * Wraps form_test_pattern_form().
   *
   * @todo Remove form_test_pattern_form().
   */
  public function validatePattern() {
    return drupal_get_form('form_test_pattern_form');
  }

  /**
   * Wraps _form_test_tableselect_multiple_true_form().
   *
   * @todo Remove _form_test_tableselect_multiple_true_form().
   */
  public function testTableSelectCheckboxes() {
    return drupal_get_form('_form_test_tableselect_multiple_true_form');
  }

  /**
   * Wraps _form_test_tableselect_multiple_false_form().
   *
   * @todo Remove _form_test_tableselect_multiple_false_form().
   */
  public function testTableSelectRadios() {
    return drupal_get_form('_form_test_tableselect_multiple_false_form');
  }

  /**
   * Wraps _form_test_tableselect_colspan_form().
   *
   * @todo Remove _form_test_tableselect_colspan_form().
   */
  public function testTableSelectColspan() {
    return drupal_get_form('_form_test_tableselect_colspan_form');
  }

  /**
   * Wraps _form_test_tableselect_empty_form().
   *
   * @todo Remove _form_test_tableselect_empty_form().
   */
  public function testTableSelectEmptyText() {
    return drupal_get_form('_form_test_tableselect_empty_form');
  }

  /**
   * Wraps _form_test_tableselect_js_select_form().
   *
   * @todo Remove _form_test_tableselect_js_select_form().
   */
  public function testTableSelectJS($test_action) {
    return drupal_get_form('_form_test_tableselect_js_select_form', $test_action);
  }

  /**
   * Wraps _form_test_vertical_tabs_form().
   *
   * @todo Remove _form_test_vertical_tabs_form().
   */
  public function testVerticalTabs() {
    return drupal_get_form('_form_test_vertical_tabs_form');
  }

  /**
   * Wraps form_test_storage_form().
   *
   * @todo Remove form_test_storage_form().
   */
  public function testStorage() {
    return drupal_get_form('form_test_storage_form');
  }

  /**
   * Wraps form_test_form_state_values_clean_form().
   *
   * @todo Remove form_test_form_state_values_clean_form().
   */
  public function testFormStateClean() {
    return drupal_get_form('form_test_form_state_values_clean_form');
  }

  /**
   * Wraps form_test_form_state_values_clean_advanced_form().
   *
   * @todo Remove form_test_form_state_values_clean_advanced_form().
   */
  public function testFormStateCleanAdvanced() {
    return drupal_get_form('form_test_form_state_values_clean_advanced_form');
  }

  /**
   * Wraps _form_test_checkbox().
   *
   * @todo Remove _form_test_checkbox().
   */
  public function testCheckbox() {
    return drupal_get_form('_form_test_checkbox');
  }

  /**
   * Wraps form_test_select().
   *
   * @todo Remove form_test_select().
   */
  public function testSelect() {
    return drupal_get_form('form_test_select');
  }

  /**
   * Wraps form_test_empty_select().
   *
   * @todo Remove form_test_empty_select().
   */
  public function testEmptySelect() {
    return drupal_get_form('form_test_empty_select');
  }

  /**
   * Wraps form_test_language_select().
   *
   * @todo Remove form_test_language_select().
   */
  public function testLanguageSelect() {
    return drupal_get_form('form_test_language_select');
  }

  /**
   * Wraps form_test_placeholder_test().
   *
   * @todo Remove form_test_placeholder_test().
   */
  public function testPlaceholder() {
    return drupal_get_form('form_test_placeholder_test');
  }

  /**
   * Wraps form_test_number().
   *
   * @todo Remove form_test_number().
   */
  public function testNumber() {
    return drupal_get_form('form_test_number');
  }

  /**
   * Wraps form_test_number().
   *
   * @todo Remove form_test_number().
   */
  public function testNumberRange() {
    return drupal_get_form('form_test_number', 'range');
  }

  /**
   * Wraps form_test_range().
   *
   * @todo Remove form_test_range().
   */
  public function testRange() {
    return drupal_get_form('form_test_range');
  }

  /**
   * Wraps form_test_range_invalid().
   *
   * @todo Remove form_test_range_invalid().
   */
  public function testRangeInvalid() {
    return drupal_get_form('form_test_range_invalid');
  }

  /**
   * Wraps form_test_color().
   *
   * @todo Remove form_test_color().
   */
  public function testColor() {
    return drupal_get_form('form_test_color');
  }

  /**
   * Wraps form_test_checkboxes_radios().
   *
   * @todo Remove form_test_checkboxes_radios().
   */
  public function testCheckboxesRadios($customize) {
    return drupal_get_form('form_test_checkboxes_radios', $customize);
  }

  /**
   * Wraps form_test_email().
   *
   * @todo Remove form_test_email().
   */
  public function testEmail() {
    return drupal_get_form('form_test_email');
  }

  /**
   * Wraps form_test_url().
   *
   * @todo Remove form_test_url().
   */
  public function testUrl() {
    return drupal_get_form('form_test_url');
  }

  /**
   * Wraps _form_test_disabled_elements().
   *
   * @todo Remove _form_test_disabled_elements().
   */
  public function testDisabledElements() {
    return drupal_get_form('_form_test_disabled_elements');
  }

  /**
   * Wraps _form_test_input_forgery().
   *
   * @todo Remove _form_test_input_forgery().
   */
  public function testInputForgery() {
    return drupal_get_form('_form_test_input_forgery');
  }

  /**
   * Wraps form_test_form_rebuild_preserve_values_form().
   *
   * @todo Remove form_test_form_rebuild_preserve_values_form().
   */
  public function testRebuildPreservation() {
    return drupal_get_form('form_test_form_rebuild_preserve_values_form');
  }

  /**
   * Wraps form_test_redirect().
   *
   * @todo Remove form_test_redirect().
   */
  public function testRedirect() {
    return drupal_get_form('form_test_redirect');
  }

  /**
   * Wraps form_label_test_form().
   *
   * @todo Remove form_label_test_form().
   */
  public function testLabel() {
    return drupal_get_form('form_label_test_form');
  }

  /**
   * Wraps form_test_state_persist().
   *
   * @todo Remove form_test_state_persist().
   */
  public function testStatePersistence() {
    return drupal_get_form('form_test_state_persist');
  }

  /**
   * Wraps form_test_clicked_button().
   *
   * @todo Remove form_test_clicked_button().
   */
  public function testClickedButton($first, $second, $third) {
    return drupal_get_form('form_test_clicked_button', $first, $second, $third);
  }

  /**
   * Wraps form_test_checkboxes_zero().
   *
   * @todo Remove form_test_checkboxes_zero().
   */
  public function testCheckboxesZero($json) {
    return drupal_get_form('form_test_checkboxes_zero', $json);
  }

  /**
   * Wraps form_test_required_attribute().
   *
   * @todo Remove form_test_required_attribute().
   */
  public function testRequired() {
    return drupal_get_form('form_test_required_attribute');
  }

  /**
   * Wraps form_test_button_class().
   *
   * @todo Remove form_test_button_class().
   */
  public function testButtonClass() {
    return drupal_get_form('form_test_button_class');
  }

  /**
   * Wraps form_test_group_details().
   *
   * @todo Remove form_test_group_details().
   */
  public function testGroupDetails() {
    return drupal_get_form('form_test_group_details');
  }

  /**
   * Wraps form_test_group_container().
   *
   * @todo Remove form_test_group_container().
   */
  public function testGroupContainer() {
    return drupal_get_form('form_test_group_container');
  }

  /**
   * Wraps form_test_group_fieldset().
   *
   * @todo Remove form_test_group_fieldset().
   */
  public function testGroupFieldset() {
    return drupal_get_form('form_test_group_fieldset');
  }

  /**
   * Wraps form_test_group_vertical_tabs().
   *
   * @todo Remove form_test_group_vertical_tabs().
   */
  public function testGroupVerticalTabs() {
    return drupal_get_form('form_test_group_vertical_tabs');
  }

  /**
   * Wraps form_test_form_state_database().
   *
   * @todo Remove form_test_form_state_database().
   */
  public function testFormStateDatabase() {
    return drupal_get_form('form_test_form_state_database');
  }

}
