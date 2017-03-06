<?php

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Views;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views_test_data\Plugin\views\row\RowTest;
use Drupal\views\Plugin\views\row\Fields;
use Drupal\views\ResultRow;
use Drupal\views_test_data\Plugin\views\style\StyleTest as StyleTestPlugin;

/**
 * Tests general style functionality.
 *
 * @group views
 * @see \Drupal\views_test_data\Plugin\views\style\StyleTest.
 */
class StyleTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Stores the SimpleXML representation of the output.
   *
   * @var \SimpleXMLElement
   */
  protected $elements;

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests the general rendering of styles.
   */
  public function testStyle() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    // This run use the test row plugin and render with it.
    $view = Views::getView('test_view');
    $view->setDisplay();
    $style = $view->display_handler->getOption('style');
    $style['type'] = 'test_style';
    $view->display_handler->setOption('style', $style);
    $row = $view->display_handler->getOption('row');
    $row['type'] = 'test_row';
    $view->display_handler->setOption('row', $row);
    $view->initDisplay();
    $view->initStyle();
    // Reinitialize the style as it supports row plugins now.
    $view->style_plugin->init($view, $view->display_handler);
    $this->assertTrue($view->rowPlugin instanceof RowTest, 'Make sure the right row plugin class is loaded.');

    $random_text = $this->randomMachineName();
    $view->rowPlugin->setOutput($random_text);

    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    $this->assertTrue(strpos($output, $random_text) !== FALSE, 'Make sure that the rendering of the row plugin appears in the output of the view.');

    // Test without row plugin support.
    $view = Views::getView('test_view');
    $view->setDisplay();
    $style = $view->display_handler->getOption('style');
    $style['type'] = 'test_style';
    $view->display_handler->setOption('style', $style);
    $view->initDisplay();
    $view->initStyle();
    $view->style_plugin->setUsesRowPlugin(FALSE);
    $this->assertTrue($view->style_plugin instanceof StyleTestPlugin, 'Make sure the right style plugin class is loaded.');
    $this->assertTrue($view->rowPlugin instanceof Fields, 'Make sure that rowPlugin is now a fields instance.');

    $random_text = $this->randomMachineName();
    // Set some custom text to the output and make sure that this value is
    // rendered.
    $view->style_plugin->setOutput($random_text);
    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    $this->assertTrue(strpos($output, $random_text) !== FALSE, 'Make sure that the rendering of the style plugin appears in the output of the view.');
  }

  public function testGrouping() {
    $this->_testGrouping(FALSE);
    $this->_testGrouping(TRUE);
  }

  /**
   * Tests the grouping features of styles.
   */
  public function _testGrouping($stripped = FALSE) {
    $view = Views::getView('test_view');
    $view->setDisplay();
    // Setup grouping by the job and the age field.
    $view->initStyle();
    $view->style_plugin->options['grouping'] = [
      ['field' => 'job'],
      ['field' => 'age'],
    ];

    // Reduce the amount of items to make the test a bit easier.
    // Set up the pager.
    $view->displayHandlers->get('default')->overrideOption('pager', [
      'type' => 'some',
      'options' => ['items_per_page' => 3],
    ]);

    // Add the job and age field.
    $fields = [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'label' => 'Name',
      ],
      'job' => [
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
        'label' => 'Job',
      ],
      'age' => [
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
        'label' => 'Age',
      ],
    ];
    $view->displayHandlers->get('default')->overrideOption('fields', $fields);

    // Now run the query and groupby the result.
    $this->executeView($view);

    $expected = [];
    $expected['Job: Singer'] = [];
    $expected['Job: Singer']['group'] = 'Job: Singer';
    $expected['Job: Singer']['level'] = 0;
    $expected['Job: Singer']['rows']['Age: 25'] = [];
    $expected['Job: Singer']['rows']['Age: 25']['group'] = 'Age: 25';
    $expected['Job: Singer']['rows']['Age: 25']['level'] = 1;
    $expected['Job: Singer']['rows']['Age: 25']['rows'][0] = new ResultRow(['index' => 0]);
    $expected['Job: Singer']['rows']['Age: 25']['rows'][0]->views_test_data_name = 'John';
    $expected['Job: Singer']['rows']['Age: 25']['rows'][0]->views_test_data_job = 'Singer';
    $expected['Job: Singer']['rows']['Age: 25']['rows'][0]->views_test_data_age = '25';
    $expected['Job: Singer']['rows']['Age: 25']['rows'][0]->views_test_data_id = '1';
    $expected['Job: Singer']['rows']['Age: 27'] = [];
    $expected['Job: Singer']['rows']['Age: 27']['group'] = 'Age: 27';
    $expected['Job: Singer']['rows']['Age: 27']['level'] = 1;
    $expected['Job: Singer']['rows']['Age: 27']['rows'][1] = new ResultRow(['index' => 1]);
    $expected['Job: Singer']['rows']['Age: 27']['rows'][1]->views_test_data_name = 'George';
    $expected['Job: Singer']['rows']['Age: 27']['rows'][1]->views_test_data_job = 'Singer';
    $expected['Job: Singer']['rows']['Age: 27']['rows'][1]->views_test_data_age = '27';
    $expected['Job: Singer']['rows']['Age: 27']['rows'][1]->views_test_data_id = '2';
    $expected['Job: Drummer'] = [];
    $expected['Job: Drummer']['group'] = 'Job: Drummer';
    $expected['Job: Drummer']['level'] = 0;
    $expected['Job: Drummer']['rows']['Age: 28'] = [];
    $expected['Job: Drummer']['rows']['Age: 28']['group'] = 'Age: 28';
    $expected['Job: Drummer']['rows']['Age: 28']['level'] = 1;
    $expected['Job: Drummer']['rows']['Age: 28']['rows'][2] = new ResultRow(['index' => 2]);
    $expected['Job: Drummer']['rows']['Age: 28']['rows'][2]->views_test_data_name = 'Ringo';
    $expected['Job: Drummer']['rows']['Age: 28']['rows'][2]->views_test_data_job = 'Drummer';
    $expected['Job: Drummer']['rows']['Age: 28']['rows'][2]->views_test_data_age = '28';
    $expected['Job: Drummer']['rows']['Age: 28']['rows'][2]->views_test_data_id = '3';


    // Alter the results to support the stripped case.
    if ($stripped) {

      // Add some html to the result and expected value.
      $rand1 = '<a data="' . $this->randomMachineName() . '" />';
      $view->result[0]->views_test_data_job .= $rand1;
      $expected['Job: Singer']['rows']['Age: 25']['rows'][0]->views_test_data_job = 'Singer' . $rand1;
      $expected['Job: Singer']['group'] = 'Job: Singer';
      $rand2 = '<a data="' . $this->randomMachineName() . '" />';
      $view->result[1]->views_test_data_job .= $rand2;
      $expected['Job: Singer']['rows']['Age: 27']['rows'][1]->views_test_data_job = 'Singer' . $rand2;
      $rand3 = '<a data="' . $this->randomMachineName() . '" />';
      $view->result[2]->views_test_data_job .= $rand3;
      $expected['Job: Drummer']['rows']['Age: 28']['rows'][2]->views_test_data_job = 'Drummer' . $rand3;
      $expected['Job: Drummer']['group'] = 'Job: Drummer';

      $view->style_plugin->options['grouping'][0] = ['field' => 'job', 'rendered' => TRUE, 'rendered_strip' => TRUE];
      $view->style_plugin->options['grouping'][1] = ['field' => 'age', 'rendered' => TRUE, 'rendered_strip' => TRUE];
    }


    // The newer api passes the value of the grouping as well.
    $sets_new_rendered = $view->style_plugin->renderGrouping($view->result, $view->style_plugin->options['grouping'], TRUE);

    $this->assertEqual($sets_new_rendered, $expected, 'The style plugins should properly group the results with grouping by the rendered output.');

    // Don't test stripped case, because the actual value is not stripped.
    if (!$stripped) {
      $sets_new_value = $view->style_plugin->renderGrouping($view->result, $view->style_plugin->options['grouping'], FALSE);

      // Reorder the group structure to grouping by value.
      $new_expected = $expected;
      $new_expected['Singer'] = $expected['Job: Singer'];
      $new_expected['Singer']['rows']['25'] = $expected['Job: Singer']['rows']['Age: 25'];
      $new_expected['Singer']['rows']['27'] = $expected['Job: Singer']['rows']['Age: 27'];
      $new_expected['Drummer'] = $expected['Job: Drummer'];
      $new_expected['Drummer']['rows']['28'] = $expected['Job: Drummer']['rows']['Age: 28'];
      unset($new_expected['Job: Singer']);
      unset($new_expected['Singer']['rows']['Age: 25']);
      unset($new_expected['Singer']['rows']['Age: 27']);
      unset($new_expected['Job: Drummer']);
      unset($new_expected['Drummer']['rows']['Age: 28']);

      $this->assertEqual($sets_new_value, $new_expected, 'The style plugins should proper group the results with grouping by the value.');
    }

    // Test that grouping works on fields having no label.
    $fields['job']['label'] = '';
    $view->destroy();
    $view->setDisplay();
    $view->initStyle();
    $view->displayHandlers->get('default')->overrideOption('fields', $fields);
    $view->style_plugin->options['grouping'] = [
      ['field' => 'job'],
      ['field' => 'age'],
    ];

    $this->executeView($view);

    if ($stripped) {
      $view->result[0]->views_test_data_job .= $rand1;
      $view->result[1]->views_test_data_job .= $rand2;
      $view->result[2]->views_test_data_job .= $rand3;
      $view->style_plugin->options['grouping'][0] = ['field' => 'job', 'rendered' => TRUE, 'rendered_strip' => TRUE];
      $view->style_plugin->options['grouping'][1] = ['field' => 'age', 'rendered' => TRUE, 'rendered_strip' => TRUE];
    }

    $sets_new_rendered = $view->style_plugin->renderGrouping($view->result, $view->style_plugin->options['grouping'], TRUE);

    // Remove labels from expected results.
    foreach ($expected as $job => $data) {
      unset($expected[$job]);
      $job = str_replace('Job: ', '', $job);
      $data['group'] = $job;
      $expected[$job] = $data;
    }
    $this->assertEqual($expected, $sets_new_rendered);
  }

  /**
   * Tests custom css classes.
   */
  public function testCustomRowClasses() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Setup some random css class.
    $view->initStyle();
    $random_name = $this->randomMachineName();
    $view->style_plugin->options['row_class'] = $random_name . " test-token-{{ name }}";

    $output = $view->preview();
    $this->storeViewPreview(\Drupal::service('renderer')->renderRoot($output));

    $rows = $this->elements->body->div->div->div;
    $count = 0;
    foreach ($rows as $row) {
      $attributes = $row->attributes();
      $class = (string) $attributes['class'][0];
      $this->assertTrue(strpos($class, $random_name) !== FALSE, 'Make sure that a custom css class is added to the output.');

      // Check token replacement.
      $name = $view->field['name']->getValue($view->result[$count]);
      $this->assertTrue(strpos($class, "test-token-$name") !== FALSE, 'Make sure that a token in custom css class is replaced.');

      $count++;
    }
  }

  /**
   * Stores a view output in the elements.
   */
  protected function storeViewPreview($output) {
    $htmlDom = new \DOMDocument();
    @$htmlDom->loadHTML($output);
    if ($htmlDom) {
      // It's much easier to work with simplexml than DOM, luckily enough
      // we can just simply import our DOM tree.
      $this->elements = simplexml_import_dom($htmlDom);
    }
  }

}
