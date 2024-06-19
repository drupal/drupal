<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\TableDrag;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests draggable table.
 *
 * @group javascript
 */
class TableDragTest extends WebDriverTestBase {

  /**
   * Class used to verify that dragging operations are in execution.
   */
  const DRAGGING_CSS_CLASS = 'tabledrag-test-dragging';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['tabledrag_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Xpath selector for finding tabledrag indentation elements in a table row.
   *
   * @var string
   */
  protected static $indentationXpathSelector = 'child::td[1]/*[contains(concat(" ", normalize-space(@class), " "), " js-indentation ")][contains(concat(" ", normalize-space(@class), " "), " indentation ")]';

  /**
   * Xpath selector for finding the tabledrag changed marker.
   *
   * @var string
   */
  protected static $tabledragChangedXpathSelector = 'child::td[1]/abbr[contains(concat(" ", normalize-space(@class), " "), " tabledrag-changed ")]';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->state = $this->container->get('state');
  }

  /**
   * Tests row weight switch.
   */
  public function testRowWeightSwitch(): void {
    $this->state->set('tabledrag_test_table', array_flip(range(1, 3)));

    $this->drupalGet('tabledrag_test');

    $session = $this->getSession();
    $page = $session->getPage();

    $weight_select1 = $page->findField("table[1][weight]");
    $weight_select2 = $page->findField("table[2][weight]");
    $weight_select3 = $page->findField("table[3][weight]");

    // Check that rows weight selects are hidden.
    $this->assertFalse($weight_select1->isVisible());
    $this->assertFalse($weight_select2->isVisible());
    $this->assertFalse($weight_select3->isVisible());

    // Toggle row weight selects as visible.
    $this->findWeightsToggle('Show row weights')->click();

    // Check that rows weight selects are visible.
    $this->assertTrue($weight_select1->isVisible());
    $this->assertTrue($weight_select2->isVisible());
    $this->assertTrue($weight_select3->isVisible());

    // Toggle row weight selects back to hidden.
    $this->findWeightsToggle('Hide row weights')->click();

    // Check that rows weight selects are hidden again.
    $this->assertFalse($weight_select1->isVisible());
    $this->assertFalse($weight_select2->isVisible());
    $this->assertFalse($weight_select3->isVisible());
  }

  /**
   * Tests draggable table drag'n'drop.
   */
  public function testDragAndDrop(): void {
    $this->state->set('tabledrag_test_table', array_flip(range(1, 3)));
    $this->drupalGet('tabledrag_test');

    $session = $this->getSession();
    $page = $session->getPage();

    // Confirm touchevents detection is loaded with Tabledrag
    $this->assertNotNull($this->assertSession()->waitForElement('css', 'html.no-touchevents'));
    $weight_select1 = $page->findField("table[1][weight]");
    $weight_select2 = $page->findField("table[2][weight]");
    $weight_select3 = $page->findField("table[3][weight]");

    // Check that initially the rows are in the correct order.
    $this->assertOrder(['Row with id 1', 'Row with id 2', 'Row with id 3']);

    // Check that the 'unsaved changes' text is not present in the message area.
    $this->assertSession()->pageTextNotContains('You have unsaved changes.');

    $row1 = $this->findRowById(1)->find('css', 'a.tabledrag-handle');
    $row2 = $this->findRowById(2)->find('css', 'a.tabledrag-handle');
    $row3 = $this->findRowById(3)->find('css', 'a.tabledrag-handle');

    // Drag row1 over row2.
    $row1->dragTo($row2);

    // Check that the 'unsaved changes' text was added in the message area.
    $this->assertSession()->waitForText('You have unsaved changes.');

    // Check that row1 and row2 were swapped.
    $this->assertOrder(['Row with id 2', 'Row with id 1', 'Row with id 3']);

    // Check that weights were changed.
    $this->assertGreaterThan($weight_select2->getValue(), $weight_select1->getValue());
    $this->assertGreaterThan($weight_select2->getValue(), $weight_select3->getValue());
    $this->assertGreaterThan($weight_select1->getValue(), $weight_select3->getValue());

    // Now move the last row (row3) in the second position. row1 should go last.
    $row3->dragTo($row1);

    // Check that the order is: row2, row3 and row1.
    $this->assertOrder(['Row with id 2', 'Row with id 3', 'Row with id 1']);
  }

  /**
   * Tests accessibility through keyboard of the tabledrag functionality.
   */
  public function testKeyboardAccessibility(): void {
    $this->assertKeyboardAccessibility();
  }

  /**
   * Asserts accessibility through keyboard of a test draggable table.
   *
   * @param string $drupal_path
   *   The drupal path where the '#tabledrag-test-table' test table is present.
   *   Defaults to 'tabledrag_test'.
   * @param array|null $structure
   *   The expected table structure. If this isn't specified or equals NULL,
   *   then the expected structure will be set by this method. Defaults to NULL.
   *
   * @internal
   */
  protected function assertKeyboardAccessibility(string $drupal_path = 'tabledrag_test', ?array $structure = NULL): void {
    $expected_table = $structure ?: [
      ['id' => '1', 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
      ['id' => '2', 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
      ['id' => '3', 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
      ['id' => '4', 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
      ['id' => '5', 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
    ];
    if (!empty($drupal_path)) {
      $this->state->set('tabledrag_test_table', array_flip(range(1, 5)));
      $this->drupalGet($drupal_path);
    }
    $this->assertDraggableTable($expected_table);

    // Nest the row with id 2 as child of row 1.
    $this->moveRowWithKeyboard($this->findRowById(2), 'right');
    $expected_table[1] = ['id' => '2', 'weight' => -10, 'parent' => '1', 'indentation' => 1, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // Nest the row with id 3 as child of row 1.
    $this->moveRowWithKeyboard($this->findRowById(3), 'right');
    $expected_table[2] = ['id' => '3', 'weight' => -9, 'parent' => '1', 'indentation' => 1, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // Nest the row with id 3 as child of row 2.
    $this->moveRowWithKeyboard($this->findRowById(3), 'right');
    $expected_table[2] = ['id' => '3', 'weight' => -10, 'parent' => '2', 'indentation' => 2, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // Nesting should be allowed to maximum level 2.
    $this->moveRowWithKeyboard($this->findRowById(4), 'right', 4);
    $expected_table[3] = ['id' => '4', 'weight' => -9, 'parent' => '2', 'indentation' => 2, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // Re-order children of row 1.
    $this->moveRowWithKeyboard($this->findRowById(4), 'up');
    $expected_table[2] = ['id' => '4', 'weight' => -10, 'parent' => '2', 'indentation' => 2, 'changed' => TRUE];
    $expected_table[3] = ['id' => '3', 'weight' => -9, 'parent' => '2', 'indentation' => 2, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // Move back the row 3 to the 1st level.
    $this->moveRowWithKeyboard($this->findRowById(3), 'left');
    $expected_table[3] = ['id' => '3', 'weight' => -9, 'parent' => '1', 'indentation' => 1, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    $this->moveRowWithKeyboard($this->findRowById(3), 'left');
    $expected_table[0] = ['id' => '1', 'weight' => -10, 'parent' => '', 'indentation' => 0, 'changed' => FALSE];
    $expected_table[3] = ['id' => '3', 'weight' => -9, 'parent' => '', 'indentation' => 0, 'changed' => TRUE];
    $expected_table[4] = ['id' => '5', 'weight' => -8, 'parent' => '', 'indentation' => 0, 'changed' => FALSE];
    $this->assertDraggableTable($expected_table);

    // Move row 3 to the last position.
    $this->moveRowWithKeyboard($this->findRowById(3), 'down');
    $expected_table[3] = ['id' => '5', 'weight' => -9, 'parent' => '', 'indentation' => 0, 'changed' => FALSE];
    $expected_table[4] = ['id' => '3', 'weight' => -8, 'parent' => '', 'indentation' => 0, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // Nothing happens when trying to move the last row further down.
    $this->moveRowWithKeyboard($this->findRowById(3), 'down');
    $this->assertDraggableTable($expected_table);

    // Nest row 3 under 5. The max depth allowed should be 1.
    $this->moveRowWithKeyboard($this->findRowById(3), 'right', 3);
    $expected_table[4] = ['id' => '3', 'weight' => -10, 'parent' => '5', 'indentation' => 1, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // The first row of the table cannot be nested.
    $this->moveRowWithKeyboard($this->findRowById(1), 'right');
    $this->assertDraggableTable($expected_table);

    // Move a row which has nested children. The children should move with it,
    // with nesting preserved. Swap the order of the top-level rows by moving
    // row 1 to after row 3.
    $this->moveRowWithKeyboard($this->findRowById(1), 'down', 2);
    $expected_table[0] = ['id' => '5', 'weight' => -10, 'parent' => '', 'indentation' => 0, 'changed' => FALSE];
    $expected_table[3] = $expected_table[1];
    $expected_table[1] = $expected_table[4];
    $expected_table[4] = $expected_table[2];
    $expected_table[2] = ['id' => '1', 'weight' => -9, 'parent' => '', 'indentation' => 0, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);
  }

  /**
   * Tests the root and leaf behaviors for rows.
   */
  public function testRootLeafDraggableRowsWithKeyboard(): void {
    $this->state->set('tabledrag_test_table', [
      1 => [],
      2 => ['parent' => 1, 'depth' => 1, 'classes' => ['tabledrag-leaf']],
      3 => ['parent' => 1, 'depth' => 1],
      4 => [],
      5 => ['classes' => ['tabledrag-root']],
    ]);

    $this->drupalGet('tabledrag_test');
    $expected_table = [
      ['id' => '1', 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
      ['id' => '2', 'weight' => 0, 'parent' => '1', 'indentation' => 1, 'changed' => FALSE],
      ['id' => '3', 'weight' => 0, 'parent' => '1', 'indentation' => 1, 'changed' => FALSE],
      ['id' => '4', 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
      ['id' => '5', 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
    ];
    $this->assertDraggableTable($expected_table);

    // Rows marked as root cannot be moved as children of another row.
    $this->moveRowWithKeyboard($this->findRowById(5), 'right');
    $this->assertDraggableTable($expected_table);

    // Rows marked as leaf cannot have children. Trying to move the row #3
    // as child of #2 should have no results.
    $this->moveRowWithKeyboard($this->findRowById(3), 'right');
    $this->assertDraggableTable($expected_table);

    // Leaf can be still swapped and moved to first level.
    $this->moveRowWithKeyboard($this->findRowById(2), 'down');
    $this->moveRowWithKeyboard($this->findRowById(2), 'left');
    $expected_table[0]['weight'] = -10;
    $expected_table[1]['id'] = '3';
    $expected_table[1]['weight'] = -10;
    $expected_table[2] = ['id' => '2', 'weight' => -9, 'parent' => '', 'indentation' => 0, 'changed' => TRUE];
    $expected_table[3]['weight'] = -8;
    $expected_table[4]['weight'] = -7;
    $this->assertDraggableTable($expected_table);

    // Root rows can have children.
    $this->moveRowWithKeyboard($this->findRowById(4), 'down');
    $this->moveRowWithKeyboard($this->findRowById(4), 'right');
    $expected_table[3]['id'] = '5';
    $expected_table[4] = ['id' => '4', 'weight' => -10, 'parent' => '5', 'indentation' => 1, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);
  }

  /**
   * Tests the warning that appears upon making changes to a tabledrag table.
   */
  public function testTableDragChangedWarning(): void {
    $this->drupalGet('tabledrag_test');

    // By default no text is visible.
    $this->assertSession()->pageTextNotContains('You have unsaved changes.');
    // Try to make a non-allowed action, like moving further down the last row.
    // No changes happen, so no message should be shown.
    $this->moveRowWithKeyboard($this->findRowById(5), 'down');
    $this->assertSession()->pageTextNotContains('You have unsaved changes.');

    // Make a change. The message will appear.
    $this->moveRowWithKeyboard($this->findRowById(5), 'right');
    $this->assertSession()->pageTextContainsOnce('You have unsaved changes.');

    // Make another change, the text will stay visible and appear only once.
    $this->moveRowWithKeyboard($this->findRowById(2), 'up');
    $this->assertSession()->pageTextContainsOnce('You have unsaved changes.');
  }

  /**
   * Asserts that several pieces of markup are in a given order in the page.
   *
   * @param string[] $items
   *   An ordered list of strings.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When any of the given string is not found.
   *
   * @todo Remove this and use the WebAssert method when #2817657 is done.
   *
   * @internal
   */
  protected function assertOrder(array $items): void {
    $session = $this->getSession();
    $text = $session->getPage()->getHtml();
    $strings = [];
    foreach ($items as $item) {
      if (($pos = strpos($text, $item)) === FALSE) {
        throw new ExpectationException("Cannot find '$item' in the page", $session->getDriver());
      }
      $strings[$pos] = $item;
    }
    ksort($strings);
    $this->assertSame($items, array_values($strings), "Strings found on the page but incorrectly ordered.");
  }

  /**
   * Tests nested draggable tables through keyboard.
   */
  public function testNestedDraggableTables(): void {
    $this->state->set('tabledrag_test_table', array_flip(range(1, 5)));
    $this->drupalGet('tabledrag_test_nested');
    $this->assertKeyboardAccessibility('');

    // Now move the rows of the parent table.
    $expected_parent_table = [
      [
        'id' => 'parent_1',
        'weight' => 0,
        'parent' => '',
        'indentation' => 0,
        'changed' => FALSE,
      ],
      [
        'id' => 'parent_2',
        'weight' => 0,
        'parent' => '',
        'indentation' => 0,
        'changed' => FALSE,
      ],
      [
        'id' => 'parent_3',
        'weight' => 0,
        'parent' => '',
        'indentation' => 0,
        'changed' => FALSE,
      ],
    ];
    $this->assertDraggableTable($expected_parent_table, 'tabledrag-test-parent-table', TRUE);

    // Switch parent table rows children.
    $this->moveRowWithKeyboard($this->findRowById('parent_2', 'tabledrag-test-parent-table'), 'up');
    $expected_parent_table = [
      [
        'id' => 'parent_2',
        'weight' => -10,
        'parent' => '',
        'indentation' => 0,
        'changed' => TRUE,
      ],
      [
        'id' => 'parent_1',
        'weight' => -9,
        'parent' => '',
        'indentation' => 0,
        'changed' => FALSE,
      ],
      [
        'id' => 'parent_3',
        'weight' => -8,
        'parent' => '',
        'indentation' => 0,
        'changed' => FALSE,
      ],
    ];
    $this->assertDraggableTable($expected_parent_table, 'tabledrag-test-parent-table', TRUE);

    // Try to move the row that contains the nested table to the last position.
    // Order should be changed, but changed marker isn't added.
    // This seems to be buggy, but this is the original behavior.
    $this->moveRowWithKeyboard($this->findRowById('parent_1', 'tabledrag-test-parent-table'), 'down');
    $expected_parent_table = [
      [
        'id' => 'parent_2',
        'weight' => -10,
        'parent' => '',
        'indentation' => 0,
        'changed' => TRUE,
      ],
      [
        'id' => 'parent_3',
        'weight' => -9,
        'parent' => '',
        'indentation' => 0,
        'changed' => FALSE,
      ],
      // Since 'parent_1' row was moved, it should be marked as changed, but
      // this would fail with core tabledrag.js.
      [
        'id' => 'parent_1',
        'weight' => -8,
        'parent' => '',
        'indentation' => 0,
        'changed' => NULL,
      ],
    ];
    $this->assertDraggableTable($expected_parent_table, 'tabledrag-test-parent-table', TRUE);

    // Re-test the nested draggable table.
    $expected_child_table_structure = [
      [
        'id' => '5',
        'weight' => -10,
        'parent' => '',
        'indentation' => 0,
        'changed' => FALSE,
      ],
      [
        'id' => '3',
        'weight' => -10,
        'parent' => '5',
        'indentation' => 1,
        'changed' => TRUE,
      ],
      [
        'id' => '1',
        'weight' => -9,
        'parent' => '',
        'indentation' => 0,
        'changed' => TRUE,
      ],
      [
        'id' => '2',
        'weight' => -10,
        'parent' => '1',
        'indentation' => 1,
        'changed' => TRUE,
      ],
      [
        'id' => '4',
        'weight' => -10,
        'parent' => '2',
        'indentation' => 2,
        'changed' => TRUE,
      ],
    ];
    $this->assertDraggableTable($expected_child_table_structure);
  }

  /**
   * Asserts the whole structure of the draggable test table.
   *
   * @param array $structure
   *   The table structure. Each entry represents a row and consists of:
   *   - id: the expected value for the ID hidden field.
   *   - weight: the expected row weight.
   *   - parent: the expected parent ID for the row.
   *   - indentation: how many indents the row should have.
   *   - changed: whether or not the row should have been marked as changed.
   * @param string $table_id
   *   The ID of the table. Defaults to 'tabledrag-test-table'.
   * @param bool $skip_missing
   *   Whether assertions done on missing elements value may be skipped or not.
   *   Defaults to FALSE.
   *
   * @internal
   */
  protected function assertDraggableTable(array $structure, string $table_id = 'tabledrag-test-table', bool $skip_missing = FALSE): void {
    $rows = $this->getSession()->getPage()->findAll('xpath', "//table[@id='$table_id']/tbody/tr");
    $this->assertSession()->elementsCount('xpath', "//table[@id='$table_id']/tbody/tr", count($structure));

    foreach ($structure as $delta => $expected) {
      $this->assertTableRow($rows[$delta], $expected['id'], $expected['weight'], $expected['parent'], $expected['indentation'], $expected['changed'], $skip_missing);
    }
  }

  /**
   * Asserts the values of a draggable row.
   *
   * @param \Behat\Mink\Element\NodeElement $row
   *   The row element to assert.
   * @param string $id
   *   The expected value for the ID hidden input of the row.
   * @param int $weight
   *   The expected weight of the row.
   * @param string $parent
   *   The expected parent ID.
   * @param int $indentation
   *   The expected indentation of the row.
   * @param bool|null $changed
   *   Whether or not the row should have been marked as changed. NULL means
   *   that this assertion should be skipped.
   * @param bool $skip_missing
   *   Whether assertions done on missing elements value may be skipped or not.
   *   Defaults to FALSE.
   *
   * @internal
   */
  protected function assertTableRow(NodeElement $row, string $id, int $weight, string $parent = '', int $indentation = 0, ?bool $changed = FALSE, bool $skip_missing = FALSE): void {
    // Assert that the row position is correct by checking that the id
    // corresponds.
    $id_name = "table[$id][id]";
    if (!$skip_missing || $row->find('hidden_field_selector', ['hidden_field', $id_name])) {
      $this->assertSession()->hiddenFieldValueEquals($id_name, $id, $row);
    }
    $parent_name = "table[$id][parent]";
    if (!$skip_missing || $row->find('hidden_field_selector', ['hidden_field', $parent_name])) {
      $this->assertSession()->hiddenFieldValueEquals($parent_name, $parent, $row);
    }
    $this->assertSession()->fieldValueEquals("table[$id][weight]", $weight, $row);
    $this->assertSession()->elementsCount('xpath', static::$indentationXpathSelector, $indentation, $row);
    // A row is marked as changed when the related markup is present.
    if ($changed !== NULL) {
      $this->assertSession()->elementsCount('xpath', static::$tabledragChangedXpathSelector, (int) $changed, $row);
    }
  }

  /**
   * Finds a row in the test table by the row ID.
   *
   * @param string $id
   *   The ID of the row.
   * @param string $table_id
   *   The ID of the parent table. Defaults to 'tabledrag-test-table'.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The row element.
   */
  protected function findRowById($id, $table_id = 'tabledrag-test-table') {
    $xpath = "//table[@id='$table_id']/tbody/tr[.//input[@name='table[$id][id]']]";
    $row = $this->getSession()->getPage()->find('xpath', $xpath);
    $this->assertNotEmpty($row);
    return $row;
  }

  /**
   * Finds the show/hide weight toggle element.
   *
   * @param string $expected_text
   *   The expected text on the element.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The toggle element.
   */
  protected function findWeightsToggle($expected_text) {
    $toggle = $this->getSession()->getPage()->findButton($expected_text);
    $this->assertNotEmpty($toggle);
    return $toggle;
  }

  /**
   * Moves a row through the keyboard.
   *
   * @param \Behat\Mink\Element\NodeElement $row
   *   The row to move.
   * @param string $arrow
   *   The arrow button to use to move the row. Either one of 'left', 'right',
   *   'up' or 'down'.
   * @param int $repeat
   *   (optional) How many times to press the arrow button. Defaults to 1.
   */
  protected function moveRowWithKeyboard(NodeElement $row, $arrow, $repeat = 1) {
    $keys = [
      'left' => 37,
      'right' => 39,
      'up' => 38,
      'down' => 40,
    ];
    if (!isset($keys[$arrow])) {
      throw new \InvalidArgumentException('The arrow parameter must be one of "left", "right", "up" or "down".');
    }

    $key = $keys[$arrow];

    $handle = $row->find('css', 'a.tabledrag-handle');
    $handle->focus();

    for ($i = 0; $i < $repeat; $i++) {
      $this->markRowHandleForDragging($handle);
      $handle->keyDown($key);
      $handle->keyUp($key);
      $this->waitUntilDraggingCompleted($handle);
    }

    $handle->blur();
  }

  /**
   * Marks a row handle for dragging.
   *
   * The handle is marked by adding a css class that is removed by an helper
   * js library once the dragging is over.
   *
   * @param \Behat\Mink\Element\NodeElement $handle
   *   The draggable row handle element.
   *
   * @throws \Exception
   *   Thrown when the class is not added successfully to the handle.
   */
  protected function markRowHandleForDragging(NodeElement $handle) {
    $class = self::DRAGGING_CSS_CLASS;
    $script = <<<JS
document.evaluate("{$handle->getXpath()}", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null)
  .singleNodeValue.classList.add('{$class}');
JS;

    $this->getSession()->executeScript($script);
    $has_class = $this->getSession()->getPage()->waitFor(1, function () use ($handle, $class) {
      return $handle->hasClass($class);
    });

    if (!$has_class) {
      throw new \Exception(sprintf('Dragging css class was not added on handle "%s".', $handle->getXpath()));
    }
  }

  /**
   * Waits until the dragging operations are finished on a row handle.
   *
   * @param \Behat\Mink\Element\NodeElement $handle
   *   The draggable row handle element.
   *
   * @throws \Exception
   *   Thrown when the dragging operations are not completed on time.
   */
  protected function waitUntilDraggingCompleted(NodeElement $handle) {
    $class_removed = $this->getSession()->getPage()->waitFor(1, function () use ($handle) {
      return !$handle->hasClass($this::DRAGGING_CSS_CLASS);
    });

    if (!$class_removed) {
      throw new \Exception(sprintf('Dragging operations did not complete on time on handle %s', $handle->getXpath()));
    }
  }

}
