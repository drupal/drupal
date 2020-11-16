<?php

namespace Drupal\Tests\field_ui\Functional;

use Drupal\Core\Url;
use Behat\Mink\Element\NodeElement;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the Field UI "Manage display" and "Manage form display" screens.
 *
 * @group field_ui
 */
class ManageDisplayTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field_ui',
    'taxonomy',
    'search',
    'field_test',
    'field_third_party_test',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');

    // Create a test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'administer taxonomy',
      'administer taxonomy_term fields',
      'administer taxonomy_term display',
      'administer users',
      'administer account settings',
      'administer user display',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->type = $type->id();

    // Create a default vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => mb_strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'help' => '',
      'nodes' => ['article' => 'article'],
      'weight' => mt_rand(0, 10),
    ]);
    $vocabulary->save();
    $this->vocabulary = $vocabulary->id();
  }

  /**
   * Tests switching view modes to use custom or 'default' settings'.
   */
  public function testViewModeCustom() {
    // Create a field, and a node with some data for the field.
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $this->type, 'test', 'Test field');
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    // For this test, use a formatter setting value that is an integer unlikely
    // to appear in a rendered node other than as part of the field being tested
    // (for example, unlikely to be part of the "Submitted by ... on ..." line).
    $value = 12345;
    $settings = [
      'type' => $this->type,
      'field_test' => [['value' => $value]],
    ];
    $node = $this->drupalCreateNode($settings);

    // Gather expected output values with the various formatters.
    $formatter_plugin_manager = \Drupal::service('plugin.manager.field.formatter');
    $field_test_default_settings = $formatter_plugin_manager->getDefaultSettings('field_test_default');
    $field_test_with_prepare_view_settings = $formatter_plugin_manager->getDefaultSettings('field_test_with_prepare_view');
    $output = [
      'field_test_default' => $field_test_default_settings['test_formatter_setting'] . '|' . $value,
      'field_test_with_prepare_view' => $field_test_with_prepare_view_settings['test_formatter_setting_additional'] . '|' . $value . '|' . ($value + 1),
    ];

    // Check that the field is displayed with the default formatter in 'rss'
    // mode (uses 'default'), and hidden in 'teaser' mode (uses custom settings).
    $this->assertNodeViewText($node, 'rss', $output['field_test_default'], "The field is displayed as expected in view modes that use 'default' settings.");
    $this->assertNodeViewNoText($node, 'teaser', $value, "The field is hidden in view modes that use custom settings.");

    // Change formatter for 'default' mode, check that the field is displayed
    // accordingly in 'rss' mode.
    $edit = [
      'fields[field_test][type]' => 'field_test_with_prepare_view',
      'fields[field_test][region]' => 'content',
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/display', $edit, 'Save');
    $this->assertNodeViewText($node, 'rss', $output['field_test_with_prepare_view'], "The field is displayed as expected in view modes that use 'default' settings.");

    // Specialize the 'rss' mode, check that the field is displayed the same.
    $edit = [
      "display_modes_custom[rss]" => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/display', $edit, 'Save');
    $this->assertNodeViewText($node, 'rss', $output['field_test_with_prepare_view'], "The field is displayed as expected in newly specialized 'rss' mode.");

    // Set the field to 'hidden' in the view mode, check that the field is
    // hidden.
    $edit = [
      'fields[field_test][region]' => 'hidden',
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/display/rss', $edit, 'Save');
    $this->assertNodeViewNoText($node, 'rss', $value, "The field is hidden in 'rss' mode.");

    // Set the view mode back to 'default', check that the field is displayed
    // accordingly.
    $edit = [
      "display_modes_custom[rss]" => FALSE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/display', $edit, 'Save');
    $this->assertNodeViewText($node, 'rss', $output['field_test_with_prepare_view'], "The field is displayed as expected when 'rss' mode is set back to 'default' settings.");

    // Specialize the view mode again.
    $edit = [
      "display_modes_custom[rss]" => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/display', $edit, 'Save');
    // Check that the previous settings for the view mode have been kept.
    $this->assertNodeViewNoText($node, 'rss', $value, "The previous settings are kept when 'rss' mode is specialized again.");
  }

  /**
   * Tests the local tasks are displayed correctly for view modes.
   */
  public function testViewModeLocalTasks() {
    $manage_display = 'admin/structure/types/manage/' . $this->type . '/display';
    $this->drupalGet($manage_display);
    $this->assertSession()->linkNotExists('Full content');
    $this->assertSession()->linkExists('Teaser');

    $this->drupalGet($manage_display . '/teaser');
    $this->assertSession()->linkNotExists('Full content');
    $this->assertSession()->linkExists('Default');
  }

  /**
   * Tests that fields with no explicit display settings do not break.
   */
  public function testNonInitializedFields() {
    // Create a test field.
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $this->type, 'test', 'Test');

    // Check that the field appears as 'hidden' on the 'Manage display' page
    // for the 'teaser' mode.
    $this->drupalGet('admin/structure/types/manage/' . $this->type . '/display/teaser');
    $this->assertSession()->fieldValueEquals('fields[field_test][region]', 'hidden');
  }

  /**
   * Tests hiding the view modes fieldset when there's only one available.
   */
  public function testSingleViewMode() {
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary . '/display');
    $this->assertNoText('Use custom display settings for the following view modes', 'Custom display settings fieldset found.');

    // This may not trigger a notice when 'view_modes_custom' isn't available.
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $this->vocabulary . '/overview/display', [], 'Save');
  }

  /**
   * Tests that a message is shown when there are no fields.
   */
  public function testNoFieldsDisplayOverview() {
    // Create a fresh content type without any fields.
    NodeType::create([
      'type' => 'no_fields',
      'name' => 'No fields',
    ])->save();

    $this->drupalGet('admin/structure/types/manage/no_fields/display');
    $this->assertRaw(t('There are no fields yet added. You can add new fields on the <a href=":link">Manage fields</a> page.', [':link' => Url::fromRoute('entity.node.field_ui_fields', ['node_type' => 'no_fields'])->toString()]));
  }

  /**
   * Asserts that a string is found in the rendered node in a view mode.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
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
  public function assertNodeViewText(EntityInterface $node, $view_mode, $text, $message) {
    return $this->assertNodeViewTextHelper($node, $view_mode, $text, $message, FALSE);
  }

  /**
   * Asserts that a string is not found in the rendered node in a view mode.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
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
  public function assertNodeViewNoText(EntityInterface $node, $view_mode, $text, $message) {
    return $this->assertNodeViewTextHelper($node, $view_mode, $text, $message, TRUE);
  }

  /**
   * Asserts that a string is (not) found in the rendered node in a view mode.
   *
   * This helper function is used by assertNodeViewText() and
   * assertNodeViewNoText().
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node.
   * @param $view_mode
   *   The view mode in which the node should be displayed.
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @param $not_exists
   *   TRUE if this text should not exist, FALSE if it should.
   */
  public function assertNodeViewTextHelper(EntityInterface $node, $view_mode, $text, $message, $not_exists) {
    // Make sure caches on the tester side are refreshed after changes
    // submitted on the tested side.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Render a cloned node, so that we do not alter the original.
    $clone = clone $node;
    $element = \Drupal::entityTypeManager()
      ->getViewBuilder('node')
      ->view($clone, $view_mode);
    $output = (string) \Drupal::service('renderer')->renderRoot($element);
    $this->verbose(t('Rendered node - view mode: @view_mode', ['@view_mode' => $view_mode]) . '<hr />' . $output);

    if ($not_exists) {
      $this->assertStringNotContainsString((string) $text, $output, $message);
    }
    else {
      $this->assertStringContainsString((string) $text, $output, $message);
    }
  }

  /**
   * Checks if a select element contains the specified options.
   *
   * @param string $name
   *   The field name.
   * @param array $expected_options
   *   An array of expected options.
   */
  protected function assertFieldSelectOptions($name, array $expected_options) {
    $xpath = $this->assertSession()->buildXPathQuery('//select[@name=:name]', [':name' => $name]);
    $fields = $this->xpath($xpath);
    if ($fields) {
      $field = $fields[0];
      $options = $this->getAllOptionsList($field);

      sort($options);
      sort($expected_options);

      $this->assertIdentical($options, $expected_options);
    }
    else {
      $this->fail('Unable to find field ' . $name);
    }
  }

  /**
   * Extracts all options from a select element.
   *
   * @param Behat\Mink\Element\NodeElement $element
   *   The select element field information.
   *
   * @return array
   *   An array of option values as strings.
   */
  protected function getAllOptionsList(NodeElement $element) {
    $options = [];
    // Add all options items.
    foreach ($element->option as $option) {
      $options[] = $option->getValue();
    }

    // Loops trough all the option groups
    foreach ($element->optgroup as $optgroup) {
      $options = array_merge($this->getAllOptionsList($optgroup), $options);
    }

    return $options;
  }

}
