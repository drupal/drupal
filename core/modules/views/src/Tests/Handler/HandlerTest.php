<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\HandlerTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\ViewExecutable;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Plugin\views\HandlerBase;
use Drupal\views\Views;

/**
 * Tests abstract handlers of views.
 */
class HandlerTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view', 'test_view_handler_weight', 'test_handler_relationships', 'test_handler_test_access', 'test_filter_in_operator_ui');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui', 'comment', 'node');

  public static function getInfo() {
    return array(
      'name' => 'Handler: Base',
      'description' => 'Tests abstract handler definitions.',
      'group' => 'Views Handlers',
    );
  }

  protected function setUp() {
    parent::setUp();
    $this->drupalCreateContentType(array('type' => 'page'));
    $this->container->get('comment.manager')->addDefaultField('node', 'page');
    $this->enableViewsTestModule();
  }

  /**
   * Overrides Drupal\views\Tests\ViewTestBase::viewsData().
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
        $data['views_test_data']['access_callback_arguments'][$type]['access arguments'] = array(TRUE);
      }
    }

    return $data;
  }

  /**
   * @todo
   * This should probably moved to a filter related test.
   */
  function testFilterInOperatorUi() {
    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration'));
    $this->drupalLogin($admin_user);

    $path = 'admin/structure/views/nojs/handler/test_filter_in_operator_ui/default/filter/type';
    $this->drupalGet($path);
    $this->assertFieldByName('options[expose][reduce]', FALSE);

    $edit = array(
      'options[expose][reduce]' => TRUE,
    );
    $this->drupalPostForm($path, $edit, t('Apply'));
    $this->drupalGet($path);
    $this->assertFieldByName('options[expose][reduce]', TRUE);
  }

  /**
   * Tests the breakPhraseString() method.
   */
  function testBreakPhraseString() {
    $empty_stdclass = new \stdClass();
    $empty_stdclass->operator = 'or';
    $empty_stdclass->value = array();

    // check defaults
    $null = NULL;
    $this->assertEqual($empty_stdclass, HandlerBase::breakPhraseString('', $null));

    $item = array(
      'table' => 'node',
      'field' => 'title',
    );
    $handler = $this->container->get('plugin.manager.views.argument')->getHandler($item);
    $this->assertEqual($handler, HandlerBase::breakPhraseString('', $handler), 'The breakPhraseString() method works correctly.');

    // test ors
    $handler = HandlerBase::breakPhraseString('word1 word2+word');
    $this->assertEqualValue(array('word1', 'word2', 'word'), $handler);
    $this->assertEqual('or', $handler->operator);
    $handler = HandlerBase::breakPhraseString('word1+word2+word');
    $this->assertEqualValue(array('word1', 'word2', 'word'), $handler);
    $this->assertEqual('or', $handler->operator);
    $handler = HandlerBase::breakPhraseString('word1 word2 word');
    $this->assertEqualValue(array('word1', 'word2', 'word'), $handler);
    $this->assertEqual('or', $handler->operator);
    $handler = HandlerBase::breakPhraseString('word-1+word-2+word');
    $this->assertEqualValue(array('word-1', 'word-2', 'word'), $handler);
    $this->assertEqual('or', $handler->operator);
    $handler = HandlerBase::breakPhraseString('wõrd1+wõrd2+wõrd');
    $this->assertEqualValue(array('wõrd1', 'wõrd2', 'wõrd'), $handler);
    $this->assertEqual('or', $handler->operator);

    // test ands.
    $handler = HandlerBase::breakPhraseString('word1,word2,word');
    $this->assertEqualValue(array('word1', 'word2', 'word'), $handler);
    $this->assertEqual('and', $handler->operator);
    $handler = HandlerBase::breakPhraseString('word1 word2,word');
    $this->assertEqualValue(array('word1 word2', 'word'), $handler);
    $this->assertEqual('and', $handler->operator);
    $handler = HandlerBase::breakPhraseString('word1,word2 word');
    $this->assertEqualValue(array('word1', 'word2 word'), $handler);
    $this->assertEqual('and', $handler->operator);
    $handler = HandlerBase::breakPhraseString('word-1,word-2,word');
    $this->assertEqualValue(array('word-1', 'word-2', 'word'), $handler);
    $this->assertEqual('and', $handler->operator);
    $handler = HandlerBase::breakPhraseString('wõrd1,wõrd2,wõrd');
    $this->assertEqualValue(array('wõrd1', 'wõrd2', 'wõrd'), $handler);
    $this->assertEqual('and', $handler->operator);

    // test a single word
    $handler = HandlerBase::breakPhraseString('word');
    $this->assertEqualValue(array('word'), $handler);
    $this->assertEqual('and', $handler->operator);
  }

  /**
   * Tests Drupal\views\Plugin\views\HandlerBase::breakPhrase() function.
   */
  function testBreakPhrase() {
    $empty_stdclass = new \stdClass();
    $empty_stdclass->operator = 'or';
    $empty_stdclass->value = array();

    $null = NULL;
    // check defaults
    $this->assertEqual($empty_stdclass, HandlerBase::breakPhrase('', $null));

    $item = array(
      'table' => 'node',
      'field' => 'title',
    );
    $handler = $this->container->get('plugin.manager.views.argument')->getHandler($item);
    $this->assertEqual($handler, HandlerBase::breakPhrase('', $handler), 'The breakPhrase() method works correctly.');

    // Generate three random numbers which can be used below;
    $n1 = rand(0, 100);
    $n2 = rand(0, 100);
    $n3 = rand(0, 100);
    // test ors
    $this->assertEqualValue(array($n1, $n2, $n3), HandlerBase::breakPhrase("$n1 $n2+$n3", $handler));
    $this->assertEqual('or', $handler->operator);
    $this->assertEqualValue(array($n1, $n2, $n3), HandlerBase::breakPhrase("$n1+$n2+$n3", $handler));
    $this->assertEqual('or', $handler->operator);
    $this->assertEqualValue(array($n1, $n2, $n3), HandlerBase::breakPhrase("$n1 $n2 $n3", $handler));
    $this->assertEqual('or', $handler->operator);
    $this->assertEqualValue(array($n1, $n2, $n3), HandlerBase::breakPhrase("$n1 $n2++$n3", $handler));
    $this->assertEqual('or', $handler->operator);

    // test ands.
    $this->assertEqualValue(array($n1, $n2, $n3), HandlerBase::breakPhrase("$n1,$n2,$n3", $handler));
    $this->assertEqual('and', $handler->operator);
    $this->assertEqualValue(array($n1, $n2, $n3), HandlerBase::breakPhrase("$n1,,$n2,$n3", $handler));
    $this->assertEqual('and', $handler->operator);
  }

  /**
   * Tests the order of handlers is the same before and after saving.
   */
  public function testHandlerWeights() {
    $handler_types = array('fields', 'filters', 'sorts');

    $view = Views::getView('test_view_handler_weight');
    $view->initDisplay();

    // Store the order of handlers before saving the view.
    $original_order = array();
    foreach ($handler_types as $type) {
      $original_order[$type] = array_keys($view->display_handler->getOption($type));
    }

    // Save the view and see if our filters are in the same order.
    $view->save();
    $view = views::getView('test_view_handler_weight');
    $view->initDisplay();

    foreach ($handler_types as $type) {
      $loaded_order = array_keys($view->display_handler->getOption($type));
      $this->assertIdentical($original_order[$type], $loaded_order);
    }

  }


  /**
   * Check to see if a value is the same as the value on a certain handler.
   *
   * @param $first
   *   The first value to check.
   * @param \Drupal\views\Plugin\views\HandlerBase $handler
   *   The handler that has the $handler->value property to compare with first.
   * @param string $message
   *   The message to display along with the assertion.
   * @param string $group
   *   The type of assertion - examples are "Browser", "PHP".
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertEqualValue($first, $handler, $message = '', $group = 'Other') {
    return $this->assert($first == $handler->value, $message ? $message : t('First value is equal to second value'), $group);
  }

  /**
   * Tests the relationship ui for field/filter/argument/relationship.
   */
  public function testRelationshipUI() {
    $views_admin = $this->drupalCreateUser(array('administer views'));
    $this->drupalLogin($views_admin);

    // Make sure the link to the field options exists.
    $handler_options_path = 'admin/structure/views/nojs/handler/test_handler_relationships/default/field/title';
    $view_edit_path = 'admin/structure/views/view/test_handler_relationships/edit';
    $this->drupalGet($view_edit_path);
    $this->assertLinkByHref($handler_options_path);

    // The test view has a relationship to node_revision so the field should
    // show a relationship selection.

    $this->drupalGet($handler_options_path);
    $relationship_name = 'options[relationship]';
    $this->assertFieldByName($relationship_name);

    // Check for available options.
    $xpath = $this->constructFieldXpath('name', $relationship_name);
    $fields = $this->xpath($xpath);
    $options = array();
    foreach ($fields as $field) {
      $items = $this->getAllOptions($field);
      foreach ($items as $item) {
        $options[] = $item->attributes()->value;
      }
    }
    $expected_options = array('none', 'nid');
    $this->assertEqual($options, $expected_options);

    // Remove the relationship and make sure no relationship option appears.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_handler_relationships/default/relationship/nid', array(), t('Remove'));
    $this->drupalGet($handler_options_path);
    $this->assertNoFieldByName($relationship_name, 'Make sure that no relationship option is available');
  }

  /**
   * Tests the relationship method on the base class.
   */
  public function testSetRelationship() {
    $view = Views::getView('test_handler_relationships');
    $view->setDisplay();
    // Setup a broken relationship.
    $view->addHandler('default', 'relationship', $this->randomName(), $this->randomName(), array(), 'broken_relationship');
    // Setup a valid relationship.
    $view->addHandler('default', 'relationship', 'comment', 'node', array('relationship' => 'cid'), 'valid_relationship');
    $view->initHandlers();
    $field = $view->field['title'];

    $field->options['relationship'] = NULL;
    $field->setRelationship();
    $this->assertFalse($field->relationship, 'Make sure that an empty relationship does not create a relationship on the field.');

    $field->options['relationship'] = $this->randomName();
    $field->setRelationship();
    $this->assertFalse($field->relationship, 'Make sure that a random relationship does not create a relationship on the field.');

    $field->options['relationship'] = 'broken_relationship';
    $field->setRelationship();
    $this->assertFalse($field->relationship, 'Make sure that a broken relationship does not create a relationship on the field.');

    $field->options['relationship'] = 'valid_relationship';
    $field->setRelationship();
    $this->assertFalse(!empty($field->relationship), 'Make sure that the relationship alias was not set without building a views query before.');

    // Remove the invalid relationship.
    unset($view->relationship['broken_relationship']);

    $view->build();
    $field->setRelationship();
    $this->assertEqual($field->relationship, $view->relationship['valid_relationship']->alias, 'Make sure that a valid relationship does create the right relationship query alias.');
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
    $this->assertEqual($handler->getPlaceholder(), $string);
    $this->assertEqual($handler->getPlaceholder(), $string . 1);
    $this->assertEqual($handler->getPlaceholder(), $string . 2);

    // Set another table/field combination and make sure there are new
    // placeholders.
    $table = $handler->table = $this->randomName();
    $field = $handler->field = $this->randomName();
    $string = ':' . $table . '_' . $field;

    // Make sure the placeholder variables are like expected.
    $this->assertEqual($handler->getPlaceholder(), $string);
    $this->assertEqual($handler->getPlaceholder(), $string . 1);
    $this->assertEqual($handler->getPlaceholder(), $string . 2);
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
    \Drupal::config('views_test_data.tests')->set('handler_access_callback', TRUE)->save();
    \Drupal::config('views_test_data.tests')->set('handler_access_callback_argument', FALSE)->save();
    $view->initDisplay();
    $view->initHandlers();

    foreach ($views_data['access_callback'] as $type => $info) {
      if (!in_array($type, array('title', 'help'))) {
        $this->assertTrue($view->field['access_callback'] instanceof HandlerBase, 'Make sure the user got access to the access_callback field ');
        $this->assertFalse(isset($view->field['access_callback_arguments']), 'Make sure the user got no access to the access_callback_arguments field ');
      }
    }

    // Enable access to the callback + argument handlers and deny for callback.
    \Drupal::config('views_test_data.tests')->set('handler_access_callback', FALSE)->save();
    \Drupal::config('views_test_data.tests')->set('handler_access_callback_argument', TRUE)->save();
    $view->destroy();
    $view->initDisplay();
    $view->initHandlers();

    foreach ($views_data['access_callback'] as $type => $info) {
      if (!in_array($type, array('title', 'help'))) {
        $this->assertFalse(isset($view->field['access_callback']), 'Make sure the user got no access to the access_callback field ');
        $this->assertTrue($view->field['access_callback_arguments'] instanceof HandlerBase, 'Make sure the user got access to the access_callback_arguments field ');
      }
    }
  }

}
