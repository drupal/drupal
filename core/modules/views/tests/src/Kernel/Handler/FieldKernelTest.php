<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Render\RenderContext;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Views;

/**
 * Tests the generic field handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\field\FieldPluginBase
 */
class FieldKernelTest extends ViewsKernelTestBase {

  protected static $modules = ['user'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view', 'test_field_tokens', 'test_field_argument_tokens', 'test_field_output'];

  /**
   * Map column names.
   *
   * @var array
   */
  protected $columnMap = [
    'views_test_data_name' => 'name',
  ];

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['job']['field']['id'] = 'test_field';
    $data['views_test_data']['job']['field']['click sortable'] = FALSE;
    $data['views_test_data']['id']['field']['click sortable'] = TRUE;
    return $data;
  }

  /**
   * Tests that the render function is called.
   */
  public function testRender() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_field_tokens');
    $this->executeView($view);

    $random_text = $this->randomMachineName();
    $view->field['job']->setTestValue($random_text);
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['job']->theme($view->result[0]);
    });
    $this->assertEqual($random_text, $output, 'Make sure the render method rendered the manual set value.');
  }

  /**
   * Tests all things related to the query.
   */
  public function testQuery() {
    // Tests adding additional fields to the query.
    $view = Views::getView('test_view');
    $view->initHandlers();

    $id_field = $view->field['id'];
    $id_field->additional_fields['job'] = 'job';
    // Choose also a field alias key which doesn't match to the table field.
    $id_field->additional_fields['created_test'] = ['table' => 'views_test_data', 'field' => 'created'];
    $view->build();

    // Make sure the field aliases have the expected value.
    $this->assertEqual('views_test_data_job', $id_field->aliases['job']);
    $this->assertEqual('views_test_data_created', $id_field->aliases['created_test']);

    $this->executeView($view);
    // Tests the getValue method with and without a field aliases.
    foreach ($this->dataSet() as $key => $row) {
      $id = $key + 1;
      $result = $view->result[$key];
      $this->assertEqual($id, $id_field->getValue($result));
      $this->assertEqual($row['job'], $id_field->getValue($result, 'job'));
      $this->assertEqual($row['created'], $id_field->getValue($result, 'created_test'));
    }
  }

  /**
   * Asserts that a string is part of another string.
   *
   * @param string $haystack
   *   The value to search in.
   * @param string $needle
   *   The value to search for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   */
  protected function assertSubString($haystack, $needle, $message = '', $group = 'Other') {
    $this->assertStringContainsString($needle, $haystack, $message);
  }

  /**
   * Asserts that a string is not part of another string.
   *
   * @param string $haystack
   *   The value to search in.
   * @param string $needle
   *   The value to search for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   */
  protected function assertNotSubString($haystack, $needle, $message = '', $group = 'Other') {
    $this->assertStringNotContainsString($needle, $haystack, $message);
  }

  /**
   * Tests general rewriting of the output.
   */
  public function testRewrite() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_view');
    $view->initHandlers();
    $this->executeView($view);
    $row = $view->result[0];
    $id_field = $view->field['id'];

    // Don't check the rewrite checkbox, so the text shouldn't appear.
    $id_field->options['alter']['text'] = $random_text = $this->randomMachineName();
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($id_field, $row) {
      return $id_field->theme($row);
    });
    $this->assertNotSubString($output, $random_text);

    $id_field->options['alter']['alter_text'] = TRUE;
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($id_field, $row) {
      return $id_field->theme($row);
    });
    $this->assertSubString($output, $random_text);
  }

  /**
   * Tests rewriting of the output with HTML.
   */
  public function testRewriteHtmlWithTokens() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_view');
    $view->initHandlers();
    $this->executeView($view);
    $row = $view->result[0];
    $id_field = $view->field['id'];

    $id_field->options['alter']['text'] = '<p>{{ id }}</p>';
    $id_field->options['alter']['alter_text'] = TRUE;
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($id_field, $row) {
      return $id_field->theme($row);
    });
    $this->assertSubString($output, '<p>1</p>');

    // Add a non-safe HTML tag and make sure this gets removed.
    $id_field->options['alter']['text'] = '<p>{{ id }} <script>alert("Script removed")</script></p>';
    $id_field->options['alter']['alter_text'] = TRUE;
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($id_field, $row) {
      return $id_field->theme($row);
    });
    $this->assertSubString($output, '<p>1 alert("Script removed")</p>');
  }

  /**
   * Tests rewriting of the output with HTML and aggregation.
   */
  public function testRewriteHtmlWithTokensAndAggregation() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_view');
    $view->setDisplay();
    $view->displayHandlers->get('default')->options['fields']['id']['group_type'] = 'sum';
    $view->displayHandlers->get('default')->setOption('group_by', TRUE);
    $view->initHandlers();
    $this->executeView($view);
    $row = $view->result[0];
    $id_field = $view->field['id'];

    $id_field->options['alter']['text'] = '<p>{{ id }}</p>';
    $id_field->options['alter']['alter_text'] = TRUE;
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($id_field, $row) {
      return $id_field->theme($row);
    });
    $this->assertSubString($output, '<p>1</p>');

    // Add a non-safe HTML tag and make sure this gets removed.
    $id_field->options['alter']['text'] = '<p>{{ id }} <script>alert("Script removed")</script></p>';
    $id_field->options['alter']['alter_text'] = TRUE;
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($id_field, $row) {
      return $id_field->theme($row);
    });
    $this->assertSubString($output, '<p>1 alert("Script removed")</p>');
  }

  /**
   * Tests the arguments tokens on field level.
   */
  public function testArgumentTokens() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_field_argument_tokens');
    $this->executeView($view, ['{{ { "#pre_render": ["\Drupal\views_test_data\Controller\ViewsTestDataController::preRender"]} }}']);

    $name_field_0 = $view->field['name'];

    // Test the old style tokens.
    $name_field_0->options['alter']['alter_text'] = TRUE;
    $name_field_0->options['alter']['text'] = '%1 !1';

    $row = $view->result[0];
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($name_field_0, $row) {
      return $name_field_0->advancedRender($row);
    });

    $this->assertStringNotContainsString('\Drupal\views_test_data\Controller\ViewsTestDataController::preRender executed', (string) $output, 'Ensure that the pre_render function was not executed');
    $this->assertEqual('%1 !1', (string) $output, "Ensure that old style placeholders aren't replaced");

    // This time use new style tokens but ensure that we still don't allow
    // arbitrary code execution.
    $name_field_0->options['alter']['alter_text'] = TRUE;
    $name_field_0->options['alter']['text'] = '{{ arguments.null }} {{ raw_arguments.null }}';

    $row = $view->result[0];
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($name_field_0, $row) {
      return $name_field_0->advancedRender($row);
    });

    $this->assertStringNotContainsString('\Drupal\views_test_data\Controller\ViewsTestDataController::preRender executed', (string) $output, 'Ensure that the pre_render function was not executed');
    $this->assertEqual('{{ { &quot;#pre_render&quot;: [&quot;\Drupal\views_test_data\Controller\ViewsTestDataController::preRender&quot;]} }} {{ { &quot;#pre_render&quot;: [&quot;\Drupal\views_test_data\Controller\ViewsTestDataController::preRender&quot;]} }}', (string) $output, 'Ensure that new style placeholders are replaced');
  }

  /**
   * Tests the field tokens, row level and field level.
   */
  public function testFieldTokens() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_field_tokens');
    $this->executeView($view);
    $name_field_0 = $view->field['name'];
    $name_field_1 = $view->field['name_1'];
    $name_field_2 = $view->field['name_2'];
    $row = $view->result[0];

    $name_field_0->options['alter']['alter_text'] = TRUE;
    $name_field_0->options['alter']['text'] = '{{ name }}';

    $name_field_1->options['alter']['alter_text'] = TRUE;
    $name_field_1->options['alter']['text'] = '{{ name_1 }} {{ name }}';

    $name_field_2->options['alter']['alter_text'] = TRUE;
    $name_field_2->options['alter']['text'] = '{% if name_2|length > 3 %}{{ name_2 }} {{ name_1 }}{% endif %}';

    foreach ($view->result as $row) {
      $expected_output_0 = $row->views_test_data_name;
      $expected_output_1 = "$row->views_test_data_name $row->views_test_data_name";
      $expected_output_2 = "$row->views_test_data_name $row->views_test_data_name $row->views_test_data_name";

      $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($name_field_0, $row) {
        return $name_field_0->advancedRender($row);
      });
      $this->assertEqual($expected_output_0, $output, new FormattableMarkup('Test token replacement: "@token" gave "@output"', ['@token' => $name_field_0->options['alter']['text'], '@output' => $output]));

      $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($name_field_1, $row) {
        return $name_field_1->advancedRender($row);
      });
      $this->assertEqual($expected_output_1, $output, new FormattableMarkup('Test token replacement: "@token" gave "@output"', ['@token' => $name_field_1->options['alter']['text'], '@output' => $output]));

      $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($name_field_2, $row) {
        return $name_field_2->advancedRender($row);
      });
      $this->assertEqual($expected_output_2, $output, new FormattableMarkup('Test token replacement: "@token" gave "@output"', ['@token' => $name_field_2->options['alter']['text'], '@output' => $output]));
    }

    $job_field = $view->field['job'];
    $job_field->options['alter']['alter_text'] = TRUE;
    $job_field->options['alter']['text'] = '{{ job }}';

    $random_text = $this->randomMachineName();
    $job_field->setTestValue($random_text);
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($job_field, $row) {
      return $job_field->advancedRender($row);
    });
    $this->assertSubString($output, $random_text, new FormattableMarkup('Make sure the self token (@token => @value) appears in the output (@output)', [
      '@value' => $random_text,
      '@output' => $output,
      '@token' => $job_field->options['alter']['text'],
    ]));

    // Verify the token format used in D7 and earlier does not get substituted.
    $old_token = '[job]';
    $job_field->options['alter']['text'] = $old_token;
    $random_text = $this->randomMachineName();
    $job_field->setTestValue($random_text);
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($job_field, $row) {
      return $job_field->advancedRender($row);
    });
    $this->assertEqual($old_token, $output, new FormattableMarkup('Make sure the old token style (@token => @value) is not changed in the output (@output)', ['@value' => $random_text, '@output' => $output, '@token' => $job_field->options['alter']['text']]));

    // Verify HTML tags are allowed in rewrite templates while token
    // replacements are escaped.
    $job_field->options['alter']['text'] = '<h1>{{ job }}</h1>';
    $random_text = $this->randomMachineName();
    $job_field->setTestValue('<span>' . $random_text . '</span>');
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($job_field, $row) {
      return $job_field->advancedRender($row);
    });
    $this->assertEqual('<h1>&lt;span&gt;' . $random_text . '&lt;/span&gt;</h1>', $output, 'Valid tags are allowed in rewrite templates and token replacements.');

    // Verify <script> tags are correctly removed from rewritten text.
    $rewrite_template = '<script>alert("malicious");</script>';
    $job_field->options['alter']['text'] = $rewrite_template;
    $random_text = $this->randomMachineName();
    $job_field->setTestValue($random_text);
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($job_field, $row) {
      return $job_field->advancedRender($row);
    });
    $this->assertNotSubString($output, '<script>', 'Ensure a script tag in the rewrite template is removed.');

    $rewrite_template = '<script>{{ job }}</script>';
    $job_field->options['alter']['text'] = $rewrite_template;
    $random_text = $this->randomMachineName();
    $job_field->setTestValue($random_text);
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($job_field, $row) {
      return $job_field->advancedRender($row);
    });
    $this->assertEqual($random_text, $output, new FormattableMarkup('Make sure a script tag in the template (@template) is removed, leaving only the replaced token in the output (@output)', ['@output' => $output, '@template' => $rewrite_template]));
  }

  /**
   * Tests the exclude setting.
   */
  public function testExclude() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_field_output');
    $view->initHandlers();
    // Hide the field and see whether it's rendered.
    $view->field['name']->options['exclude'] = TRUE;

    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    foreach ($this->dataSet() as $entry) {
      $this->assertNotSubString($output, $entry['name']);
    }

    // Show and check the field.
    $view->field['name']->options['exclude'] = FALSE;

    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    foreach ($this->dataSet() as $entry) {
      $this->assertSubString($output, $entry['name']);
    }
  }

  /**
   * Tests everything related to empty output of a field.
   */
  public function testEmpty() {
    $this->_testHideIfEmpty();
    $this->_testEmptyText();
  }

  /**
   * Tests the hide if empty functionality.
   *
   * This tests alters the result to get easier and less coupled results. It is
   * important that assertSame() is used in this test since in PHP 0 == ''.
   */
  public function _testHideIfEmpty() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_view');
    $view->initDisplay();
    $this->executeView($view);

    $column_map_reversed = array_flip($this->columnMap);
    $view->row_index = 0;
    $random_name = $this->randomMachineName();
    $random_value = $this->randomMachineName();

    // Test when results are not rewritten and empty values are not hidden.
    $view->field['name']->options['hide_alter_empty'] = FALSE;
    $view->field['name']->options['hide_empty'] = FALSE;
    $view->field['name']->options['empty_zero'] = FALSE;

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_name;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($random_name, (string) $render, 'By default, a string should not be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("", $render, 'By default, "" should not be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame('0', (string) $render, 'By default, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("0", (string) $render, 'By default, "0" should not be treated as empty.');

    // Test when results are not rewritten and non-zero empty values are hidden.
    $view->field['name']->options['hide_alter_empty'] = TRUE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = FALSE;

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_name;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($random_name, (string) $render, 'If hide_empty is checked, a string should not be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("", $render, 'If hide_empty is checked, "" should be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame('0', (string) $render, 'If hide_empty is checked, but not empty_zero, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("0", (string) $render, 'If hide_empty is checked, but not empty_zero, "0" should not be treated as empty.');

    // Test when results are not rewritten and all empty values are hidden.
    $view->field['name']->options['hide_alter_empty'] = TRUE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = TRUE;

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("", $render, 'If hide_empty and empty_zero are checked, 0 should be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("", $render, 'If hide_empty and empty_zero are checked, "0" should be treated as empty.');

    // Test when results are rewritten to a valid string and non-zero empty
    // results are hidden.
    $view->field['name']->options['hide_alter_empty'] = FALSE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = FALSE;
    $view->field['name']->options['alter']['alter_text'] = TRUE;
    $view->field['name']->options['alter']['text'] = $random_name;

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_value;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($random_name, (string) $render, 'If the rewritten string is not empty, it should not be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($random_name, (string) $render, 'If the rewritten string is not empty, "" should not be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($random_name, (string) $render, 'If the rewritten string is not empty, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($random_name, (string) $render, 'If the rewritten string is not empty, "0" should not be treated as empty.');

    // Test when results are rewritten to an empty string and non-zero empty results are hidden.
    $view->field['name']->options['hide_alter_empty'] = TRUE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = FALSE;
    $view->field['name']->options['alter']['alter_text'] = TRUE;
    $view->field['name']->options['alter']['text'] = "";

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_name;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($random_name, (string) $render, 'If the rewritten string is empty, it should not be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("", $render, 'If the rewritten string is empty, "" should be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame('0', (string) $render, 'If the rewritten string is empty, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("0", (string) $render, 'If the rewritten string is empty, "0" should not be treated as empty.');

    // Test when results are rewritten to zero as a string and non-zero empty
    // results are hidden.
    $view->field['name']->options['hide_alter_empty'] = FALSE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = FALSE;
    $view->field['name']->options['alter']['alter_text'] = TRUE;
    $view->field['name']->options['alter']['text'] = "0";

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_name;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("0", (string) $render, 'If the rewritten string is zero and empty_zero is not checked, the string rewritten as 0 should not be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("0", (string) $render, 'If the rewritten string is zero and empty_zero is not checked, "" rewritten as 0 should not be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("0", (string) $render, 'If the rewritten string is zero and empty_zero is not checked, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("0", (string) $render, 'If the rewritten string is zero and empty_zero is not checked, "0" should not be treated as empty.');

    // Test when results are rewritten to a valid string and non-zero empty
    // results are hidden.
    $view->field['name']->options['hide_alter_empty'] = TRUE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = FALSE;
    $view->field['name']->options['alter']['alter_text'] = TRUE;
    $view->field['name']->options['alter']['text'] = $random_value;

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_name;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($random_value, (string) $render, 'If the original and rewritten strings are valid, it should not be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("", $render, 'If either the original or rewritten string is invalid, "" should be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($random_value, (string) $render, 'If the original and rewritten strings are valid, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($random_value, (string) $render, 'If the original and rewritten strings are valid, "0" should not be treated as empty.');

    // Test when results are rewritten to zero as a string and all empty
    // original values and results are hidden.
    $view->field['name']->options['hide_alter_empty'] = TRUE;
    $view->field['name']->options['hide_empty'] = TRUE;
    $view->field['name']->options['empty_zero'] = TRUE;
    $view->field['name']->options['alter']['alter_text'] = TRUE;
    $view->field['name']->options['alter']['text'] = "0";

    // Test a valid string.
    $view->result[0]->{$column_map_reversed['name']} = $random_name;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("", (string) $render, 'If the rewritten string is zero, it should be treated as empty.');

    // Test an empty string.
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("", $render, 'If the rewritten string is zero, "" should be treated as empty.');

    // Test zero as an integer.
    $view->result[0]->{$column_map_reversed['name']} = 0;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("", $render, 'If the rewritten string is zero, 0 should not be treated as empty.');

    // Test zero as a string.
    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("", $render, 'If the rewritten string is zero, "0" should not be treated as empty.');
  }

  /**
   * Tests the usage of the empty text.
   */
  public function _testEmptyText() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_view');
    $view->initDisplay();
    $this->executeView($view);

    $column_map_reversed = array_flip($this->columnMap);
    $view->row_index = 0;

    $empty_text = $view->field['name']->options['empty'] = $this->randomMachineName();
    $view->result[0]->{$column_map_reversed['name']} = "";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($empty_text, (string) $render, 'If a field is empty, the empty text should be used for the output.');

    $view->result[0]->{$column_map_reversed['name']} = "0";
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame("0", (string) $render, 'If a field is 0 and empty_zero is not checked, the empty text should not be used for the output.');

    $view->result[0]->{$column_map_reversed['name']} = "0";
    $view->field['name']->options['empty_zero'] = TRUE;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($empty_text, (string) $render, 'If a field is 0 and empty_zero is checked, the empty text should be used for the output.');

    $view->result[0]->{$column_map_reversed['name']} = "";
    $view->field['name']->options['alter']['alter_text'] = TRUE;
    $alter_text = $view->field['name']->options['alter']['text'] = $this->randomMachineName();
    $view->field['name']->options['hide_alter_empty'] = FALSE;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($alter_text, (string) $render, 'If a field is empty, some rewrite text exists, but hide_alter_empty is not checked, render the rewrite text.');

    $view->field['name']->options['hide_alter_empty'] = TRUE;
    $render = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $this->assertSame($empty_text, (string) $render, 'If a field is empty, some rewrite text exists, and hide_alter_empty is checked, use the empty text.');
  }

  /**
   * Tests views_handler_field::isValueEmpty().
   */
  public function testIsValueEmpty() {
    $view = Views::getView('test_view');
    $view->initHandlers();
    $field = $view->field['name'];

    $this->assertFalse($field->isValueEmpty("not empty", TRUE), 'A normal string is not empty.');
    $this->assertTrue($field->isValueEmpty("not empty", TRUE, FALSE), 'A normal string which skips empty() can be seen as empty.');

    $this->assertTrue($field->isValueEmpty("", TRUE), '"" is considered as empty.');

    $this->assertTrue($field->isValueEmpty('0', TRUE), '"0" is considered as empty if empty_zero is TRUE.');
    $this->assertTrue($field->isValueEmpty(0, TRUE), '0 is considered as empty if empty_zero is TRUE.');
    $this->assertFalse($field->isValueEmpty('0', FALSE), '"0" is considered not as empty if empty_zero is FALSE.');
    $this->assertFalse($field->isValueEmpty(0, FALSE), '0 is considered not as empty if empty_zero is FALSE.');

    $this->assertTrue($field->isValueEmpty(NULL, TRUE, TRUE), 'Null should be always seen as empty, regardless of no_skip_empty.');
    $this->assertTrue($field->isValueEmpty(NULL, TRUE, FALSE), 'Null should be always seen as empty, regardless of no_skip_empty.');
  }

  /**
   * Tests whether the filters are click sortable as expected.
   */
  public function testClickSortable() {
    // Test that clickSortable is TRUE by default.
    $item = [
      'table' => 'views_test_data',
      'field' => 'name',
    ];
    $plugin = $this->container->get('plugin.manager.views.field')->getHandler($item);
    $this->assertTrue($plugin->clickSortable(), 'TRUE as a default value is correct.');

    // Test that clickSortable is TRUE by when set TRUE in the data.
    $item['field'] = 'id';
    $plugin = $this->container->get('plugin.manager.views.field')->getHandler($item);
    $this->assertTrue($plugin->clickSortable(), 'TRUE as a views data value is correct.');

    // Test that clickSortable is FALSE by when set FALSE in the data.
    $item['field'] = 'job';
    $plugin = $this->container->get('plugin.manager.views.field')->getHandler($item);
    $this->assertFalse($plugin->clickSortable(), 'FALSE as a views data value is correct.');
  }

  /**
   * Tests the trimText method.
   */
  public function testTrimText() {
    // Test unicode. See https://www.drupal.org/node/513396#comment-2839416.
    // cSpell:disable
    $text = [
      'Tuy nhiên, những hi vọng',
      'Giả sử chúng tôi có 3 Apple',
      'siêu nhỏ này là bộ xử lý',
      'Di động của nhà sản xuất Phần Lan',
      'khoảng cách từ đại lí đến',
      'của hãng bao gồm ba dòng',
      'сд асд асд ас',
      'асд асд асд ас',
    ];
    // Just test maxlength without word boundary.
    $alter = [
      'max_length' => 10,
    ];
    $expect = [
      'Tuy nhiên,',
      'Giả sử chú',
      'siêu nhỏ n',
      'Di động củ',
      'khoảng các',
      'của hãng b',
      'сд асд асд',
      'асд асд ас',
    ];

    foreach ($text as $key => $line) {
      $result_text = FieldPluginBase::trimText($alter, $line);
      $this->assertEqual($expect[$key], $result_text);
    }

    // Test also word_boundary
    $alter['word_boundary'] = TRUE;
    $expect = [
      'Tuy nhiên',
      'Giả sử',
      'siêu nhỏ',
      'Di động',
      'khoảng',
      'của hãng',
      'сд асд',
      'асд асд',
    ];

    foreach ($text as $key => $line) {
      $result_text = FieldPluginBase::trimText($alter, $line);
      $this->assertEqual($expect[$key], $result_text);
    }
    // cSpell:enable
  }

}
