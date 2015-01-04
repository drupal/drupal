<?php

/**
 * @file
 * Contains \Drupal\filter\Tests\FilterFormTest.
 */

namespace Drupal\filter\Tests;

use Drupal\Component\Utility\String;
use Drupal\simpletest\WebTestBase;

/**
 * Tests form elements with associated text formats.
 *
 * @group filter
 */
class FilterFormTest extends WebTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var array
   */
  protected static $modules = array('filter', 'filter_test');

  /**
   * An administrative user account that can administer text formats.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * An basic user account that can only access basic HTML text format.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\filter\FilterFormatInterface $filter_test_format */
    $filter_test_format = entity_load('filter_format', 'filter_test');
    /** @var \Drupal\filter\FilterFormatInterface $filtered_html_format */
    $filtered_html_format = entity_load('filter_format', 'filtered_html');
    /** @var \Drupal\filter\FilterFormatInterface $full_html_format */
    $full_html_format = entity_load('filter_format', 'full_html');

    // Create users.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer filters',
      $filtered_html_format->getPermissionName(),
      $full_html_format->getPermissionName(),
      $filter_test_format->getPermissionName(),
    ));

    $this->webUser = $this->drupalCreateUser(array(
      $filtered_html_format->getPermissionName(),
      $filter_test_format->getPermissionName(),
    ));
  }

  /**
   * Tests various different configurations of the 'text_format' element.
   */
  public function testFilterForm() {
    $this->doFilterFormTestAsAdmin();
    $this->doFilterFormTestAsNonAdmin();
    // Ensure that enabling modules which provide filter plugins behaves
    // correctly.
    // @see https://www.drupal.org/node/2387983
    \Drupal::service('module_installer')->install(['filter_test_plugin']);
    // Force rebuild module data.
    _system_rebuild_module_data();
  }

  /**
   * Tests the behavior of the 'text_format' element as an administrator.
   */
  protected function doFilterFormTestAsAdmin() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('filter-test/text-format');

    // Test a text format element with all formats.
    $formats = array('filtered_html', 'full_html', 'filter_test');
    $this->assertEnabledTextarea('edit-all-formats-no-default-value');
    // If no default is given, the format with the lowest weight becomes the
    // default.
    $this->assertOptions('edit-all-formats-no-default-format--2', $formats, 'filtered_html');
    $this->assertEnabledTextarea('edit-all-formats-default-value');
    // \Drupal\filter_test\Form\FilterTestFormatForm::buildForm() uses
    // 'filter_test' as the default value in this case.
    $this->assertOptions('edit-all-formats-default-format--2', $formats, 'filter_test');
    $this->assertEnabledTextarea('edit-all-formats-default-missing-value');
    // If a missing format is set as the default, administrators must select a
    // valid replacement format.
    $this->assertRequiredSelectAndOptions('edit-all-formats-default-missing-format--2', $formats);

    // Test a text format element with a predefined list of formats.
    $formats = array('full_html', 'filter_test');
    $this->assertEnabledTextarea('edit-restricted-formats-no-default-value');
    $this->assertOptions('edit-restricted-formats-no-default-format--2', $formats, 'full_html');
    $this->assertEnabledTextarea('edit-restricted-formats-default-value');
    $this->assertOptions('edit-restricted-formats-default-format--2', $formats, 'full_html');
    $this->assertEnabledTextarea('edit-restricted-formats-default-missing-value');
    $this->assertRequiredSelectAndOptions('edit-restricted-formats-default-missing-format--2', $formats);
    $this->assertEnabledTextarea('edit-restricted-formats-default-disallowed-value');
    $this->assertRequiredSelectAndOptions('edit-restricted-formats-default-disallowed-format--2', $formats);

    // Test a text format element with a fixed format.
    $formats = array('filter_test');
    // When there is only a single option there is no point in choosing.
    $this->assertEnabledTextarea('edit-single-format-no-default-value');
    $this->assertNoSelect('edit-single-format-no-default-format--2');
    $this->assertEnabledTextarea('edit-single-format-default-value');
    $this->assertNoSelect('edit-single-format-default-format--2');
    // If the select has a missing or disallowed format, administrators must
    // explicitly choose the format.
    $this->assertEnabledTextarea('edit-single-format-default-missing-value');
    $this->assertRequiredSelectAndOptions('edit-single-format-default-missing-format--2', $formats);
    $this->assertEnabledTextarea('edit-single-format-default-disallowed-value');
    $this->assertRequiredSelectAndOptions('edit-single-format-default-disallowed-format--2', $formats);
  }

  /**
   * Tests the behavior of the 'text_format' element as a normal user.
   */
  protected function doFilterFormTestAsNonAdmin() {
    $this->drupalLogin($this->webUser);
    $this->drupalGet('filter-test/text-format');

    // Test a text format element with all formats. Only formats the user has
    // access to are shown.
    $formats = array('filtered_html', 'filter_test');
    $this->assertEnabledTextarea('edit-all-formats-no-default-value');
    // If no default is given, the format with the lowest weight becomes the
    // default. This happens to be 'filtered_html'.
    $this->assertOptions('edit-all-formats-no-default-format--2', $formats, 'filtered_html');
    $this->assertEnabledTextarea('edit-all-formats-default-value');
    // \Drupal\filter_test\Form\FilterTestFormatForm::buildForm() uses
    // 'filter_test' as the default value in this case.
    $this->assertOptions('edit-all-formats-default-format--2', $formats, 'filter_test');
    // If a missing format is given as default, non-admin users are presented
    // with a disabled textarea.
    $this->assertDisabledTextarea('edit-all-formats-default-missing-value');

    // Test a text format element with a predefined list of formats.
    $this->assertEnabledTextarea('edit-restricted-formats-no-default-value');
    // The user only has access to the 'filter_test' format, so when no default
    // is given that is preselected and the text format select is hidden.
    $this->assertNoSelect('edit-restricted-formats-no-default-format--2');
    // When the format that the user does not have access to is preselected, the
    // textarea should be disabled.
    $this->assertDisabledTextarea('edit-restricted-formats-default-value');
    $this->assertDisabledTextarea('edit-restricted-formats-default-missing-value');
    $this->assertDisabledTextarea('edit-restricted-formats-default-disallowed-value');

    // Test a text format element with a fixed format.
    // When there is only a single option there is no point in choosing.
    $this->assertEnabledTextarea('edit-single-format-no-default-value');
    $this->assertNoSelect('edit-single-format-no-default-format--2');
    $this->assertEnabledTextarea('edit-single-format-default-value');
    $this->assertNoSelect('edit-single-format-default-format--2');
    // If the select has a missing or disallowed format make sure the textarea
    // is disabled.
    $this->assertDisabledTextarea('edit-single-format-default-missing-value');
    $this->assertDisabledTextarea('edit-single-format-default-disallowed-value');
  }

  /**
   * Makes sure that no select element with the given ID exists on the page.
   *
   * @param string $id
   *   The HTML ID of the select element.
   *
   * @return bool
   *   TRUE if the assertion passed; FALSE otherwise.
   */
  protected function assertNoSelect($id) {
    $select = $this->xpath('//select[@id=:id]', array(':id' => $id));
    return $this->assertFalse($select, String::format('Field @id does not exist.', array(
      '@id' => $id,
    )));
  }

  /**
   * Asserts that a select element has the correct options.
   *
   * @param string $id
   *   The HTML ID of the select element.
   * @param array $expected_options
   *   An array of option values.
   * @param string $selected
   *   The value of the selected option.
   *
   * @return bool
   *   TRUE if the assertion passed; FALSE otherwise.
   */
  protected function assertOptions($id, array $expected_options, $selected) {
    $select = $this->xpath('//select[@id=:id]', array(':id' => $id));
    $select = reset($select);
    $passed = $this->assertTrue($select instanceof \SimpleXMLElement, String::format('Field @id exists.', array(
      '@id' => $id,
    )));

    $found_options = $this->getAllOptions($select);
    foreach ($found_options as $found_key => $found_option) {
      $expected_key = array_search($found_option->attributes()->value, $expected_options);
      if ($expected_key !== FALSE) {
        $this->pass(String::format('Option @option for field @id exists.', array(
          '@option' => $expected_options[$expected_key],
          '@id' => $id,
        )));
        unset($found_options[$found_key]);
        unset($expected_options[$expected_key]);
      }
    }

    // Make sure that all expected options were found and that there are no
    // unexpected options.
    foreach ($expected_options as $expected_option) {
      $this->fail(String::format('Option @option for field @id exists.', array(
        '@option' => $expected_option,
        '@id' => $id,
      )));
      $passed = FALSE;
    }
    foreach ($found_options as $found_option) {
      $this->fail(String::format('Option @option for field @id does not exist.', array(
        '@option' => $found_option->attributes()->value,
        '@id' => $id,
      )));
      $passed = FALSE;
    }

    return $passed && $this->assertOptionSelected($id, $selected);
  }

  /**
   * Asserts that there is a select element with the given ID that is required.
   *
   * @param string $id
   *   The HTML ID of the select element.
   * @param array $options
   *   An array of option values that are contained in the select element
   *   besides the "- Select -" option.
   *
   * @return bool
   *   TRUE if the assertion passed; FALSE otherwise.
   */
  protected function assertRequiredSelectAndOptions($id, array $options) {
    $select = $this->xpath('//select[@id=:id and contains(@required, "required")]', array(
      ':id' => $id,
    ));
    $select = reset($select);
    $passed = $this->assertTrue($select instanceof \SimpleXMLElement, String::format('Required field @id exists.', array(
      '@id' => $id,
    )));
    // A required select element has a "- Select -" option whose key is an empty
    // string.
    $options[] = '';
    return $passed && $this->assertOptions($id, $options, '');
  }

  /**
   * Asserts that a textarea with a given ID exists and is not disabled.
   *
   * @param string $id
   *   The HTML ID of the textarea.
   *
   * @return bool
   *   TRUE if the assertion passed; FALSE otherwise.
   */
  protected function assertEnabledTextarea($id) {
    $textarea = $this->xpath('//textarea[@id=:id and not(contains(@disabled, "disabled"))]', array(
      ':id' => $id,
    ));
    $textarea = reset($textarea);
    return $this->assertTrue($textarea instanceof \SimpleXMLElement, String::format('Enabled field @id exists.', array(
      '@id' => $id,
    )));
  }

  /**
   * Asserts that a textarea with a given ID has been disabled from editing.
   *
   * @param string $id
   *   The HTML ID of the textarea.
   *
   * @return bool
   *   TRUE if the assertion passed; FALSE otherwise.
   */
  protected function assertDisabledTextarea($id) {
    $textarea = $this->xpath('//textarea[@id=:id and contains(@disabled, "disabled")]', array(
      ':id' => $id,
    ));
    $textarea = reset($textarea);
    $passed = $this->assertTrue($textarea instanceof \SimpleXMLElement, String::format('Disabled field @id exists.', array(
      '@id' => $id,
    )));
    $expected = 'This field has been disabled because you do not have sufficient permissions to edit it.';
    $passed = $passed && $this->assertEqual((string) $textarea, $expected, String::format('Disabled textarea @id hides text in an inaccessible text format.', array(
      '@id' => $id,
    )));
    // Make sure the text format select is not shown.
    $select_id = str_replace('value', 'format--2', $id);
    return $passed && $this->assertNoSelect($select_id);
  }

}
