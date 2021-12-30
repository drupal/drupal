<?php

namespace Drupal\Tests\views\Functional\Handler;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\HandlerBase;
use Drupal\views\Views;

// cspell:ignore wõrd

/**
 * Tests abstract handler definitions.
 *
 * @group views
 */
class HandlerTest extends ViewTestBase {

  use CommentTestTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view', 'test_view_handler_weight', 'test_handler_relationships', 'test_handler_test_access', 'test_filter_in_operator_ui'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views_ui', 'comment', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);
    $this->drupalCreateContentType(['type' => 'page']);
    $this->addDefaultCommentField('node', 'page');
    $this->enableViewsTestModule();
  }

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    // Override the name handler to be able to call placeholder() from outside.
    $data['views_test_data']['name']['field']['id'] = 'test_field';

    // Setup one field with an access callback and one with an access callback
    // and arguments.
    $data['views_test_data']['access_callback'] = $data['views_test_data']['id'];
    $data['views_test_data']['access_callback_arguments'] = $data['views_test_data']['id'];
    foreach (ViewExecutable::getHandlerTypes() as $type => $info) {
      if (isset($data['views_test_data']['access_callback'][$type]['id'])) {
        $data['views_test_data']['access_callback'][$type]['access callback'] = 'views_test_data_handler_test_access_callback';

        $data['views_test_data']['access_callback_arguments'][$type]['access callback'] = 'views_test_data_handler_test_access_callback_argument';
        $data['views_test_data']['access_callback_arguments'][$type]['access arguments'] = [TRUE];
      }
    }

    return $data;
  }

  /**
   * Tests the breakString method.
   */
  public function testBreakString() {
    // Check defaults.
    $this->assertEquals((object) ['value' => [], 'operator' => NULL], HandlerBase::breakString(''));

    // Test ors
    $handler = HandlerBase::breakString('word1 word2+word');
    $this->assertEquals(['word1', 'word2', 'word'], $handler->value);
    $this->assertEquals('or', $handler->operator);
    $handler = HandlerBase::breakString('word1+word2+word');
    $this->assertEquals(['word1', 'word2', 'word'], $handler->value);
    $this->assertEquals('or', $handler->operator);
    $handler = HandlerBase::breakString('word1 word2 word');
    $this->assertEquals(['word1', 'word2', 'word'], $handler->value);
    $this->assertEquals('or', $handler->operator);
    $handler = HandlerBase::breakString('word-1+word-2+word');
    $this->assertEquals(['word-1', 'word-2', 'word'], $handler->value);
    $this->assertEquals('or', $handler->operator);
    $handler = HandlerBase::breakString('wõrd1+wõrd2+wõrd');
    $this->assertEquals(['wõrd1', 'wõrd2', 'wõrd'], $handler->value);
    $this->assertEquals('or', $handler->operator);

    // Test ands.
    $handler = HandlerBase::breakString('word1,word2,word');
    $this->assertEquals(['word1', 'word2', 'word'], $handler->value);
    $this->assertEquals('and', $handler->operator);
    $handler = HandlerBase::breakString('word1 word2,word');
    $this->assertEquals(['word1 word2', 'word'], $handler->value);
    $this->assertEquals('and', $handler->operator);
    $handler = HandlerBase::breakString('word1,word2 word');
    $this->assertEquals(['word1', 'word2 word'], $handler->value);
    $this->assertEquals('and', $handler->operator);
    $handler = HandlerBase::breakString('word-1,word-2,word');
    $this->assertEquals(['word-1', 'word-2', 'word'], $handler->value);
    $this->assertEquals('and', $handler->operator);
    $handler = HandlerBase::breakString('wõrd1,wõrd2,wõrd');
    $this->assertEquals(['wõrd1', 'wõrd2', 'wõrd'], $handler->value);
    $this->assertEquals('and', $handler->operator);

    // Test a single word
    $handler = HandlerBase::breakString('word');
    $this->assertEquals(['word'], $handler->value);
    $this->assertEquals('and', $handler->operator);

    $s1 = $this->randomMachineName();
    // Generate three random numbers which can be used below;
    $n1 = rand(0, 100);
    $n2 = rand(0, 100);
    $n3 = rand(0, 100);

    // Test "or"s.
    $handlerBase = HandlerBase::breakString("$s1 $n2+$n3");
    $this->assertEquals([$s1, $n2, $n3], $handlerBase->value);
    $this->assertEquals('or', $handlerBase->operator);

    $handlerBase = HandlerBase::breakString("$s1+$n2+$n3");
    $this->assertEquals([$s1, $n2, $n3], $handlerBase->value);
    $this->assertEquals('or', $handlerBase->operator);

    $handlerBase = HandlerBase::breakString("$s1 $n2 $n3");
    $this->assertEquals([$s1, $n2, $n3], $handlerBase->value);
    $this->assertEquals('or', $handlerBase->operator);

    $handlerBase = HandlerBase::breakString("$s1 $n2++$n3");
    $this->assertEquals([$s1, $n2, $n3], $handlerBase->value);
    $this->assertEquals('or', $handlerBase->operator);

    // Test "and"s.
    $handlerBase = HandlerBase::breakString("$s1,$n2,$n3");
    $this->assertEquals([$s1, $n2, $n3], $handlerBase->value);
    $this->assertEquals('and', $handlerBase->operator);

    $handlerBase = HandlerBase::breakString("$s1,,$n2,$n3");
    $this->assertEquals([$s1, $n2, $n3], $handlerBase->value);
    $this->assertEquals('and', $handlerBase->operator);

    // Enforce int values.
    $handlerBase = HandlerBase::breakString("$n1,$n2,$n3", TRUE);
    $this->assertEquals([$n1, $n2, $n3], $handlerBase->value);
    $this->assertEquals('and', $handlerBase->operator);

    $handlerBase = HandlerBase::breakString("$n1+$n2+$n3", TRUE);
    $this->assertEquals([$n1, $n2, $n3], $handlerBase->value);
    $this->assertEquals('or', $handlerBase->operator);

    $handlerBase = HandlerBase::breakString("$s1,$n2,$n3", TRUE);
    $this->assertEquals([(int) $s1, $n2, $n3], $handlerBase->value);
    $this->assertEquals('and', $handlerBase->operator);

    $handlerBase = HandlerBase::breakString("$s1+$n2+$n3", TRUE);
    $this->assertEquals([(int) $s1, $n2, $n3], $handlerBase->value);
    $this->assertEquals('or', $handlerBase->operator);

    // Generate three random decimals which can be used below;
    $d1 = rand(0, 10) / 10;
    $d2 = rand(0, 10) / 10;
    $d3 = rand(0, 10) / 10;

    // Test "or"s.
    $handlerBase = HandlerBase::breakString("$s1 $d1+$d2");
    $this->assertEquals([$s1, $d1, $d2], $handlerBase->value);
    $this->assertEquals('or', $handlerBase->operator);

    $handlerBase = HandlerBase::breakString("$s1+$d1+$d3");
    $this->assertEquals([$s1, $d1, $d3], $handlerBase->value);
    $this->assertEquals('or', $handlerBase->operator);

    $handlerBase = HandlerBase::breakString("$s1 $d2 $d3");
    $this->assertEquals([$s1, $d2, $d3], $handlerBase->value);
    $this->assertEquals('or', $handlerBase->operator);

    $handlerBase = HandlerBase::breakString("$s1 $d2++$d3");
    $this->assertEquals([$s1, $d2, $d3], $handlerBase->value);
    $this->assertEquals('or', $handlerBase->operator);

    // Test "and"s.
    $handlerBase = HandlerBase::breakString("$s1,$d2,$d3");
    $this->assertEquals([$s1, $d2, $d3], $handlerBase->value);
    $this->assertEquals('and', $handlerBase->operator);

    $handlerBase = HandlerBase::breakString("$s1,,$d2,$d3");
    $this->assertEquals([$s1, $d2, $d3], $handlerBase->value);
    $this->assertEquals('and', $handlerBase->operator);
  }

  /**
   * Tests the order of handlers is the same before and after saving.
   */
  public function testHandlerWeights() {
    $handler_types = ['fields', 'filters', 'sorts'];

    $view = Views::getView('test_view_handler_weight');
    $view->initDisplay();

    // Store the order of handlers before saving the view.
    $original_order = [];
    foreach ($handler_types as $type) {
      $original_order[$type] = array_keys($view->display_handler->getOption($type));
    }

    // Save the view and see if our filters are in the same order.
    $view->save();
    $view = views::getView('test_view_handler_weight');
    $view->initDisplay();

    foreach ($handler_types as $type) {
      $loaded_order = array_keys($view->display_handler->getOption($type));
      $this->assertSame($original_order[$type], $loaded_order);
    }
  }

  /**
   * Tests the relationship ui for field/filter/argument/relationship.
   */
  public function testRelationshipUI() {
    $views_admin = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($views_admin);

    // Make sure the link to the field options exists.
    $handler_options_path = 'admin/structure/views/nojs/handler/test_handler_relationships/default/field/title';
    $view_edit_path = 'admin/structure/views/view/test_handler_relationships/edit';
    $this->drupalGet($view_edit_path);
    $this->assertSession()->linkByHrefExists($handler_options_path);

    // The test view has a relationship to node_revision so the field should
    // show a relationship selection.

    $this->drupalGet($handler_options_path);
    $relationship_name = 'options[relationship]';
    $this->assertSession()->fieldExists($relationship_name);

    // Check for available options.
    $fields = $this->getSession()->getPage()->findAll('named_exact', ['field', $relationship_name]);
    $options = [];
    foreach ($fields as $field) {
      $items = $field->findAll('css', 'option');
      foreach ($items as $item) {
        $options[] = $item->getAttribute('value');
      }
    }
    $expected_options = ['none', 'nid'];
    $this->assertEquals($expected_options, $options);

    // Remove the relationship and make sure no relationship option appears.
    $this->drupalGet('admin/structure/views/nojs/handler/test_handler_relationships/default/relationship/nid');
    $this->submitForm([], 'Remove');
    $this->drupalGet($handler_options_path);
    $this->assertSession()->fieldNotExists($relationship_name);

    // Create a view of comments with node relationship.
    View::create(['base_table' => 'comment_field_data', 'id' => 'test_get_entity_type'])->save();
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_get_entity_type/default/relationship');
    $this->submitForm(['name[comment_field_data.node]' => 'comment_field_data.node'], 'Add and configure relationships');
    $this->submitForm([], 'Apply');
    // Add a content type filter.
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_get_entity_type/default/filter');
    $this->submitForm(['name[node_field_data.type]' => 'node_field_data.type'], 'Add and configure filter criteria');
    $this->assertTrue($this->assertSession()->optionExists('edit-options-relationship', 'node')->isSelected());
    $this->submitForm(['options[value][page]' => 'page'], 'Apply');
    // Check content type filter options.
    $this->drupalGet('admin/structure/views/nojs/handler/test_get_entity_type/default/filter/type');
    $this->assertTrue($this->assertSession()->optionExists('edit-options-relationship', 'node')->isSelected());
    $this->assertSession()->checkboxChecked('edit-options-value-page');
  }

  /**
   * Tests the relationship method on the base class.
   */
  public function testSetRelationship() {
    $view = Views::getView('test_handler_relationships');
    $view->setDisplay();
    // Setup a broken relationship.
    $view->addHandler('default', 'relationship', $this->randomMachineName(), $this->randomMachineName(), [], 'broken_relationship');
    // Setup a valid relationship.
    $view->addHandler('default', 'relationship', 'comment_field_data', 'node', ['relationship' => 'cid'], 'valid_relationship');
    $view->initHandlers();
    $field = $view->field['title'];

    $field->options['relationship'] = NULL;
    $field->setRelationship();
    $this->assertNull($field->relationship, 'Make sure that an empty relationship does not create a relationship on the field.');

    $field->options['relationship'] = $this->randomMachineName();
    $field->setRelationship();
    $this->assertNull($field->relationship, 'Make sure that a random relationship does not create a relationship on the field.');

    $field->options['relationship'] = 'broken_relationship';
    $field->setRelationship();
    $this->assertNull($field->relationship, 'Make sure that a broken relationship does not create a relationship on the field.');

    $field->options['relationship'] = 'valid_relationship';
    $field->setRelationship();
    $this->assertEmpty($field->relationship, 'Make sure that the relationship alias was not set without building a views query before.');

    // Remove the invalid relationship.
    unset($view->relationship['broken_relationship']);

    $view->build();
    $field->setRelationship();
    $this->assertEquals($field->relationship, $view->relationship['valid_relationship']->alias, 'Make sure that a valid relationship does create the right relationship query alias.');
  }

  /**
   * Tests the placeholder function.
   *
   * @see \Drupal\views\Plugin\views\HandlerBase::placeholder()
   */
  public function testPlaceholder() {
    $view = Views::getView('test_view');
    $view->initHandlers();
    $view->initQuery();

    $handler = $view->field['name'];
    $table = $handler->table;
    $field = $handler->field;
    $string = ':' . $table . '_' . $field;

    // Make sure the placeholder variables are like expected.
    $this->assertEquals($string, $handler->getPlaceholder());
    $this->assertEquals($string . 1, $handler->getPlaceholder());
    $this->assertEquals($string . 2, $handler->getPlaceholder());

    // Set another table/field combination and make sure there are new
    // placeholders.
    $table = $handler->table = $this->randomMachineName();
    $field = $handler->field = $this->randomMachineName();
    $string = ':' . $table . '_' . $field;

    // Make sure the placeholder variables are like expected.
    $this->assertEquals($string, $handler->getPlaceholder());
    $this->assertEquals($string . 1, $handler->getPlaceholder());
    $this->assertEquals($string . 2, $handler->getPlaceholder());
  }

  /**
   * Tests access to a handler.
   *
   * @see views_test_data_handler_test_access_callback
   */
  public function testAccess() {
    $view = Views::getView('test_handler_test_access');
    $views_data = $this->viewsData();
    $views_data = $views_data['views_test_data'];

    // Enable access to callback only field and deny for callback + arguments.
    $this->config('views_test_data.tests')->set('handler_access_callback', TRUE)->save();
    $this->config('views_test_data.tests')->set('handler_access_callback_argument', FALSE)->save();
    $view->initDisplay();
    $view->initHandlers();

    foreach ($views_data['access_callback'] as $type => $info) {
      if (!in_array($type, ['title', 'help'])) {
        $this->assertInstanceOf(HandlerBase::class, $view->field['access_callback']);
        $this->assertFalse(isset($view->field['access_callback_arguments']), 'Make sure the user got no access to the access_callback_arguments field ');
      }
    }

    // Enable access to the callback + argument handlers and deny for callback.
    $this->config('views_test_data.tests')->set('handler_access_callback', FALSE)->save();
    $this->config('views_test_data.tests')->set('handler_access_callback_argument', TRUE)->save();
    $view->destroy();
    $view->initDisplay();
    $view->initHandlers();

    foreach ($views_data['access_callback'] as $type => $info) {
      if (!in_array($type, ['title', 'help'])) {
        $this->assertFalse(isset($view->field['access_callback']), 'Make sure the user got no access to the access_callback field ');
        $this->assertInstanceOf(HandlerBase::class, $view->field['access_callback_arguments']);
      }
    }
  }

}
