<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\FieldWebTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\views\Views;

/**
 * Tests fields from within a UI.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\field\FieldPluginBase
 */
class FieldWebTest extends HandlerTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view', 'test_field_classes', 'test_field_output', 'test_click_sort');

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

  /**
   * Maps between the key in the expected result and the query result.
   *
   * @var array
   */
  protected $columnMap = array(
    'views_test_data_name' => 'name',
  );

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Overrides \Drupal\views\Tests\ViewTestBase::viewsData().
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['job']['field']['id'] = 'test_field';
    return $data;
  }

  /**
   * Tests the click sorting functionality.
   */
  public function testClickSorting() {
    $this->drupalGet('test_click_sort');
    $this->assertResponse(200);

    // Only the id and name should be click sortable, but not the name.
    $this->assertLinkByHref(\Drupal::url('view.test_click_sort.page_1', [], ['query' => ['order' => 'id', 'sort' => 'asc']]));
    $this->assertLinkByHref(\Drupal::url('view.test_click_sort.page_1', [], ['query' => ['order' => 'name', 'sort' => 'desc']]));
    $this->assertNoLinkByHref(\Drupal::url('view.test_click_sort.page_1', [], ['query' => ['order' => 'created']]));

    // Check that the view returns the click sorting cache contexts.
    $expected_contexts = [
      'languages:language_interface',
      'theme',
      'url.query_args.pagers:0',
      'url.query_args:order',
      'url.query_args:sort',
    ];
    $this->assertCacheContexts($expected_contexts);

    // Clicking a click sort should change the order.
    $this->clickLink(t('ID'));
    $this->assertLinkByHref(\Drupal::url('view.test_click_sort.page_1', [], ['query' => ['order' => 'id', 'sort' => 'desc']]));
    // Check that the output has the expected order (asc).
    $ids = $this->clickSortLoadIdsFromOutput();
    $this->assertEqual($ids, range(1, 5));

    $this->clickLink(t('ID'));
    // Check that the output has the expected order (desc).
    $ids = $this->clickSortLoadIdsFromOutput();
    $this->assertEqual($ids, range(5, 1, -1));
  }

  /**
   * Small helper function to get all ids in the output.
   *
   * @return array
   *   A list of beatle ids.
   */
  protected function clickSortLoadIdsFromOutput() {
    $fields = $this->xpath("//td[contains(@class, 'views-field-id')]");
    $ids = array();
    foreach ($fields as $field) {
      $ids[] = (int) $field[0];
    }
    return $ids;
  }

  /**
   * Assertion helper which checks whether a string is part of another string.
   *
   * @param string $haystack
   *   The value to search in.
   * @param string $needle
   *   The value to search for.
   * @param string $message
   *   The message to display along with the assertion.
   * @param string $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertSubString($haystack, $needle, $message = '', $group = 'Other') {
    return $this->assertTrue(strpos($haystack, $needle) !== FALSE, $message, $group);
  }

  /**
   * Assertion helper which checks whether a string is not part of another string.
   *
   * @param string $haystack
   *   The value to search in.
   * @param string $needle
   *   The value to search for.
   * @param string $message
   *   The message to display along with the assertion.
   * @param string $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNotSubString($haystack, $needle, $message = '', $group = 'Other') {
    return $this->assertTrue(strpos($haystack, $needle) === FALSE, $message, $group);
  }

  /**
   * Parse a content and return the html element.
   *
   * @param string $content
   *   The html to parse.
   *
   * @return array
   *   An array containing simplexml objects.
   */
  protected function parseContent($content) {
    $htmlDom = new \DOMDocument();
    @$htmlDom->loadHTML('<?xml encoding="UTF-8">' . $content);
    $elements = simplexml_import_dom($htmlDom);

    return $elements;
  }

  /**
   * Performs an xpath search on a certain content.
   *
   * The search is relative to the root element of the $content variable.
   *
   * @param string $content
   *   The html to parse.
   * @param string $xpath
   *   The xpath string to use in the search.
   * @param array $arguments
   *   Some arguments for the xpath.
   *
   * @return array|FALSE
   *   The return value of the xpath search. For details on the xpath string
   *   format and return values see the SimpleXML documentation,
   *   http://php.net/manual/function.simplexml-element-xpath.php.
   */
  protected function xpathContent($content, $xpath, array $arguments = array()) {
    if ($elements = $this->parseContent($content)) {
      $xpath = $this->buildXPathQuery($xpath, $arguments);
      $result = $elements->xpath($xpath);
      // Some combinations of PHP / libxml versions return an empty array
      // instead of the documented FALSE. Forcefully convert any falsish values
      // to an empty array to allow foreach(...) constructions.
      return $result ? $result : array();
    }
    else {
      return FALSE;
    }
  }

  /**
   * Tests rewriting the output to a link.
   */
  public function testAlterUrl() {
    $view = Views::getView('test_view');
    $view->setDisplay();
    $view->initHandlers();
    $this->executeView($view);
    $row = $view->result[0];
    $id_field = $view->field['id'];

    // Setup the general settings required to build a link.
    $id_field->options['alter']['make_link'] = TRUE;
    $id_field->options['alter']['path'] = $path = $this->randomMachineName();

    // Tests that the suffix/prefix appears on the output.
    $id_field->options['alter']['prefix'] = $prefix = $this->randomMachineName();
    $id_field->options['alter']['suffix'] = $suffix = $this->randomMachineName();
    $output = $id_field->theme($row);
    $this->assertSubString($output, $prefix);
    $this->assertSubString($output, $suffix);
    unset($id_field->options['alter']['prefix']);
    unset($id_field->options['alter']['suffix']);

    $output = $id_field->theme($row);
    $this->assertSubString($output, $path, 'Make sure that the path is part of the output');

    // Some generic test code adapted from the UrlTest class, which tests
    // mostly the different options for the path.
    foreach (array(FALSE, TRUE) as $absolute) {
      $alter = &$id_field->options['alter'];
      $alter['path'] = 'node/123';

      $expected_result = \Drupal::url('entity.node.canonical', ['node' => '123'], ['absolute' => $absolute]);
      $alter['absolute'] = $absolute;
      $result = $id_field->theme($row);
      $this->assertSubString($result, $expected_result);

      $expected_result = \Drupal::url('entity.node.canonical', ['node' => '123'], ['fragment' => 'foo', 'absolute' => $absolute]);
      $alter['path'] = 'node/123#foo';
      $result = $id_field->theme($row);
      $this->assertSubString($result, $expected_result);

      $expected_result = \Drupal::url('entity.node.canonical', ['node' => '123'], ['query' => ['foo' => NULL], 'absolute' => $absolute]);
      $alter['path'] = 'node/123?foo';
      $result = $id_field->theme($row);
      $this->assertSubString($result, $expected_result);

      $expected_result = \Drupal::url('entity.node.canonical', ['node' => '123'], ['query' => ['foo' => 'bar', 'bar' => 'baz'], 'absolute' => $absolute]);
      $alter['path'] = 'node/123?foo=bar&bar=baz';
      $result = $id_field->theme($row);
      $this->assertSubString(Html::decodeEntities($result), Html::decodeEntities($expected_result));

      // @todo The route-based URL generator strips out NULL attributes.
      // $expected_result = \Drupal::url('entity.node.canonical', ['node' => '123'], ['query' => ['foo' => NULL], 'fragment' => 'bar', 'absolute' => $absolute]);
      $expected_result = \Drupal::urlGenerator()->generateFromPath('node/123', array('query' => array('foo' => NULL), 'fragment' => 'bar', 'absolute' => $absolute));
      $alter['path'] = 'node/123?foo#bar';
      $result = $id_field->theme($row);
      $this->assertSubString(Html::decodeEntities($result), Html::decodeEntities($expected_result));

      $expected_result = \Drupal::url('<front>', [], ['absolute' => $absolute]);
      $alter['path'] = '<front>';
      $result = $id_field->theme($row);
      $this->assertSubString($result, $expected_result);
    }

    // Tests the replace spaces with dashes feature.
    $id_field->options['alter']['replace_spaces'] = TRUE;
    $id_field->options['alter']['path'] = $path = $this->randomMachineName() . ' ' . $this->randomMachineName();
    $output = $id_field->theme($row);
    $this->assertSubString($output, str_replace(' ', '-', $path));
    $id_field->options['alter']['replace_spaces'] = FALSE;
    $output = $id_field->theme($row);
    // The url has a space in it, so to check we have to decode the url output.
    $this->assertSubString(urldecode($output), $path);

    // Tests the external flag.
    // Switch on the external flag should output an external url as well.
    $id_field->options['alter']['external'] = TRUE;
    $id_field->options['alter']['path'] = $path = 'www.drupal.org';
    $output = $id_field->theme($row);
    $this->assertSubString($output, 'http://www.drupal.org');

    // Setup a not external url, which shouldn't lead to an external url.
    $id_field->options['alter']['external'] = FALSE;
    $id_field->options['alter']['path'] = $path = 'www.drupal.org';
    $output = $id_field->theme($row);
    $this->assertNotSubString($output, 'http://www.drupal.org');

    // Tests the transforming of the case setting.
    $id_field->options['alter']['path'] = $path = $this->randomMachineName();
    $id_field->options['alter']['path_case'] = 'none';
    $output = $id_field->theme($row);
    $this->assertSubString($output, $path);

    // Switch to uppercase and lowercase.
    $id_field->options['alter']['path_case'] = 'upper';
    $output = $id_field->theme($row);
    $this->assertSubString($output, strtoupper($path));
    $id_field->options['alter']['path_case'] = 'lower';
    $output = $id_field->theme($row);
    $this->assertSubString($output, strtolower($path));

    // Switch to ucfirst and ucwords.
    $id_field->options['alter']['path_case'] = 'ucfirst';
    $id_field->options['alter']['path'] = 'drupal has a great community';
    $output = $id_field->theme($row);
    $this->assertSubString($output, UrlHelper::encodePath('Drupal has a great community'));

    $id_field->options['alter']['path_case'] = 'ucwords';
    $output = $id_field->theme($row);
    $this->assertSubString($output, UrlHelper::encodePath('Drupal Has A Great Community'));
    unset($id_field->options['alter']['path_case']);

    // Tests the linkclass setting and see whether it actually exists in the
    // output.
    $id_field->options['alter']['link_class'] = $class = $this->randomMachineName();
    $output = $id_field->theme($row);
    $elements = $this->xpathContent($output, '//a[contains(@class, :class)]', array(':class' => $class));
    $this->assertTrue($elements);
    // @fixme link_class, alt, rel cannot be unset, which should be fixed.
    $id_field->options['alter']['link_class'] = '';

    // Tests the alt setting.
    $id_field->options['alter']['alt'] = $rel = $this->randomMachineName();
    $output = $id_field->theme($row);
    $elements = $this->xpathContent($output, '//a[contains(@title, :alt)]', array(':alt' => $rel));
    $this->assertTrue($elements);
    $id_field->options['alter']['alt'] = '';

    // Tests the rel setting.
    $id_field->options['alter']['rel'] = $rel = $this->randomMachineName();
    $output = $id_field->theme($row);
    $elements = $this->xpathContent($output, '//a[contains(@rel, :rel)]', array(':rel' => $rel));
    $this->assertTrue($elements);
    $id_field->options['alter']['rel'] = '';

    // Tests the target setting.
    $id_field->options['alter']['target'] = $target = $this->randomMachineName();
    $output = $id_field->theme($row);
    $elements = $this->xpathContent($output, '//a[contains(@target, :target)]', array(':target' => $target));
    $this->assertTrue($elements);
    unset($id_field->options['alter']['target']);
  }

  /**
   * Tests the field/label/wrapper classes.
   */
  public function testFieldClasses() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_field_classes');
    $view->initHandlers();

    // Tests whether the default field classes are added.
    $id_field = $view->field['id'];

    $id_field->options['element_default_classes'] = FALSE;
    // Setup some kind of label by default.
    $id_field->options['label'] = $this->randomMachineName();
    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    $this->assertFalse($this->xpathContent($output, '//div[contains(@class, :class)]', array(':class' => 'field-content')));
    $this->assertFalse($this->xpathContent($output, '//div[contains(@class, :class)]', array(':class' => 'field-label')));

    $id_field->options['element_default_classes'] = TRUE;
    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    // Per default the label and the element of the field are spans.
    $this->assertTrue($this->xpathContent($output, '//span[contains(@class, :class)]', array(':class' => 'field-content')));
    $this->assertTrue($this->xpathContent($output, '//span[contains(@class, :class)]', array(':class' => 'views-label')));
    $this->assertTrue($this->xpathContent($output, '//div[contains(@class, :class)]', array(':class' => 'views-field')));

    // Tests the element wrapper classes/element.
    $random_class = $this->randomMachineName();

    // Set some common wrapper element types and see whether they appear with and without a custom class set.
    foreach (array('h1', 'span', 'p', 'div') as $element_type) {
      $id_field->options['element_wrapper_type'] = $element_type;

      // Set a custom wrapper element css class.
      $id_field->options['element_wrapper_class'] = $random_class;
      $output = $view->preview();
      $output = $renderer->renderRoot($output);
      $this->assertTrue($this->xpathContent($output, "//{$element_type}[contains(@class, :class)]", array(':class' => $random_class)));

      // Set no custom css class.
      $id_field->options['element_wrapper_class'] = '';
      $output = $view->preview();
      $output = $renderer->renderRoot($output);
      $this->assertFalse($this->xpathContent($output, "//{$element_type}[contains(@class, :class)]", array(':class' => $random_class)));
      $this->assertTrue($this->xpathContent($output, "//li[contains(@class, views-row)]/{$element_type}"));
    }

    // Tests the label class/element.

    // Set some common label element types and see whether they appear with and without a custom class set.
    foreach (array('h1', 'span', 'p', 'div') as $element_type) {
      $id_field->options['element_label_type'] = $element_type;

      // Set a custom label element css class.
      $id_field->options['element_label_class'] = $random_class;
      $output = $view->preview();
      $output = $renderer->renderRoot($output);
      $this->assertTrue($this->xpathContent($output, "//li[contains(@class, views-row)]//{$element_type}[contains(@class, :class)]", array(':class' => $random_class)));

      // Set no custom css class.
      $id_field->options['element_label_class'] = '';
      $output = $view->preview();
      $output = $renderer->renderRoot($output);
      $this->assertFalse($this->xpathContent($output, "//li[contains(@class, views-row)]//{$element_type}[contains(@class, :class)]", array(':class' => $random_class)));
      $this->assertTrue($this->xpathContent($output, "//li[contains(@class, views-row)]//{$element_type}"));
    }

    // Tests the element classes/element.

    // Set some common element element types and see whether they appear with and without a custom class set.
    foreach (array('h1', 'span', 'p', 'div') as $element_type) {
      $id_field->options['element_type'] = $element_type;

      // Set a custom label element css class.
      $id_field->options['element_class'] = $random_class;
      $output = $view->preview();
      $output = $renderer->renderRoot($output);
      $this->assertTrue($this->xpathContent($output, "//li[contains(@class, views-row)]//div[contains(@class, views-field)]//{$element_type}[contains(@class, :class)]", array(':class' => $random_class)));

      // Set no custom css class.
      $id_field->options['element_class'] = '';
      $output = $view->preview();
      $output = $renderer->renderRoot($output);
      $this->assertFalse($this->xpathContent($output, "//li[contains(@class, views-row)]//div[contains(@class, views-field)]//{$element_type}[contains(@class, :class)]", array(':class' => $random_class)));
      $this->assertTrue($this->xpathContent($output, "//li[contains(@class, views-row)]//div[contains(@class, views-field)]//{$element_type}"));
    }

    // Tests the available html elements.
    $element_types = $id_field->getElements();
    $expected_elements = array(
      '',
      0,
      'div',
      'span',
      'h1',
      'h2',
      'h3',
      'h4',
      'h5',
      'h6',
      'p',
      'strong',
      'em',
      'marquee'
    );

    $this->assertEqual(array_keys($element_types), $expected_elements);
  }

  /**
   * Tests trimming/read-more/ellipses.
   */
  public function testTextRendering() {
    $view = Views::getView('test_field_output');
    $view->initHandlers();
    $name_field = $view->field['name'];

    // Tests stripping of html elements.
    $this->executeView($view);
    $random_text = $this->randomMachineName();
    $name_field->options['alter']['alter_text'] = TRUE;
    $name_field->options['alter']['text'] = $html_text = '<div class="views-test">' . $random_text . '</div>';
    $row = $view->result[0];

    $name_field->options['alter']['strip_tags'] = TRUE;
    $output = $name_field->advancedRender($row);
    $this->assertSubString($output, $random_text, 'Find text without html if stripping of views field output is enabled.');
    $this->assertNotSubString($output, $html_text, 'Find no text with the html if stripping of views field output is enabled.');

    // Tests preserving of html tags.
    $name_field->options['alter']['preserve_tags'] = '<div>';
    $output = $name_field->advancedRender($row);
    $this->assertSubString($output, $random_text, 'Find text without html if stripping of views field output is enabled but a div is allowed.');
    $this->assertSubString($output, $html_text, 'Find text with the html if stripping of views field output is enabled but a div is allowed.');

    $name_field->options['alter']['strip_tags'] = FALSE;
    $output = $name_field->advancedRender($row);
    $this->assertSubString($output, $random_text, 'Find text without html if stripping of views field output is disabled.');
    $this->assertSubString($output, $html_text, 'Find text with the html if stripping of views field output is disabled.');

    // Tests for removing whitespace and the beginning and the end.
    $name_field->options['alter']['alter_text'] = FALSE;
    $views_test_data_name = $row->views_test_data_name;
    $row->views_test_data_name = '  ' . $views_test_data_name . '     ';
    $name_field->options['alter']['trim_whitespace'] = TRUE;
    $output = $name_field->advancedRender($row);

    $this->assertSubString($output, $views_test_data_name, 'Make sure the trimmed text can be found if trimming is enabled.');
    $this->assertNotSubString($output, $row->views_test_data_name, 'Make sure the untrimmed text can be found if trimming is enabled.');

    $name_field->options['alter']['trim_whitespace'] = FALSE;
    $output = $name_field->advancedRender($row);
    $this->assertSubString($output, $views_test_data_name, 'Make sure the trimmed text can be found if trimming is disabled.');
    $this->assertSubString($output, $row->views_test_data_name, 'Make sure the untrimmed text can be found  if trimming is disabled.');

    // Tests for trimming to a maximum length.
    $name_field->options['alter']['trim'] = TRUE;
    $name_field->options['alter']['word_boundary'] = FALSE;

    // Tests for simple trimming by string length.
    $row->views_test_data_name = $this->randomMachineName(8);
    $name_field->options['alter']['max_length'] = 5;
    $trimmed_name = Unicode::substr($row->views_test_data_name, 0, 5);

    $output = $name_field->advancedRender($row);
    $this->assertSubString($output, $trimmed_name, format_string('Make sure the trimmed output (!trimmed) appears in the rendered output (!output).', array('!trimmed' => $trimmed_name, '!output' => $output)));
    $this->assertNotSubString($output, $row->views_test_data_name, format_string("Make sure the untrimmed value (!untrimmed) shouldn't appear in the rendered output (!output).", array('!untrimmed' => $row->views_test_data_name, '!output' => $output)));

    $name_field->options['alter']['max_length'] = 9;
    $output = $name_field->advancedRender($row);
    $this->assertSubString($output, $trimmed_name, format_string('Make sure the untrimmed (!untrimmed) output appears in the rendered output  (!output).', array('!trimmed' => $trimmed_name, '!output' => $output)));

    // Take word_boundary into account for the tests.
    $name_field->options['alter']['max_length'] = 5;
    $name_field->options['alter']['word_boundary'] = TRUE;
    $random_text_2 = $this->randomMachineName(2);
    $random_text_4 = $this->randomMachineName(4);
    $random_text_8 = $this->randomMachineName(8);
    $tuples = array(
      // Create one string which doesn't fit at all into the limit.
      array(
        'value' => $random_text_8,
        'trimmed_value' => '',
        'trimmed' => TRUE
      ),
      // Create one string with two words which doesn't fit both into the limit.
      array(
        'value' => $random_text_8 . ' ' . $random_text_8,
        'trimmed_value' => '',
        'trimmed' => TRUE
      ),
      // Create one string which contains of two words, of which only the first
      // fits into the limit.
      array(
        'value' => $random_text_4 . ' ' . $random_text_8,
        'trimmed_value' => $random_text_4,
        'trimmed' => TRUE
      ),
      // Create one string which contains of two words, of which both fits into
      // the limit.
      array(
        'value' => $random_text_2 . ' ' . $random_text_2,
        'trimmed_value' => $random_text_2 . ' ' . $random_text_2,
        'trimmed' => FALSE
      )
    );

    foreach ($tuples as $tuple) {
      $row->views_test_data_name = $tuple['value'];
      $output = $name_field->advancedRender($row);

      if ($tuple['trimmed']) {
        $this->assertNotSubString($output, $tuple['value'], format_string('The untrimmed value (!untrimmed) should not appear in the trimmed output (!output).', array('!untrimmed' => $tuple['value'], '!output' => $output)));
      }
      if (!empty($tuple['trimmed_value'])) {
        $this->assertSubString($output, $tuple['trimmed_value'], format_string('The trimmed value (!trimmed) should appear in the trimmed output (!output).', array('!trimmed' => $tuple['trimmed_value'], '!output' => $output)));
      }
    }

    // Tests for displaying a readmore link when the output got trimmed.
    $row->views_test_data_name = $this->randomMachineName(8);
    $name_field->options['alter']['max_length'] = 5;
    $name_field->options['alter']['more_link'] = TRUE;
    $name_field->options['alter']['more_link_text'] = $more_text = $this->randomMachineName();
    $name_field->options['alter']['more_link_path'] = $more_path = $this->randomMachineName();

    $output = $name_field->advancedRender($row);
    $this->assertSubString($output, $more_text, 'Make sure a read more text is displayed if the output got trimmed');
    $this->assertTrue($this->xpathContent($output, '//a[contains(@href, :path)]', array(':path' => $more_path)), 'Make sure the read more link points to the right destination.');

    $name_field->options['alter']['more_link'] = FALSE;
    $output = $name_field->advancedRender($row);
    $this->assertNotSubString($output, $more_text, 'Make sure no read more text appears.');
    $this->assertFalse($this->xpathContent($output, '//a[contains(@href, :path)]', array(':path' => $more_path)), 'Make sure no read more link appears.');

    // Check for the ellipses.
    $row->views_test_data_name = $this->randomMachineName(8);
    $name_field->options['alter']['max_length'] = 5;
    $output = $name_field->advancedRender($row);
    $this->assertSubString($output, '…', 'An ellipsis should appear if the output is trimmed');
    $name_field->options['alter']['max_length'] = 10;
    $output = $name_field->advancedRender($row);
    $this->assertNotSubString($output, '…', 'No ellipsis should appear if the output is not trimmed');
  }

}
