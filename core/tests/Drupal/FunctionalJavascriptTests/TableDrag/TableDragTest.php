<?php

namespace Drupal\FunctionalJavascriptTests\TableDrag;

use Behat\Mink\Element\NodeElement;
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->state = $this->container->get('state');
  }

  /**
   * Tests accessibility through keyboard of the tabledrag functionality.
   */
  public function testKeyboardAccessibility() {
    $this->state->set('tabledrag_test_table', array_flip(range(1, 5)));

    $expected_table = [
      ['id' => 1, 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
      ['id' => 2, 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
      ['id' => 3, 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
      ['id' => 4, 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
      ['id' => 5, 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
    ];
    $this->drupalGet('tabledrag_test');
    $this->assertDraggableTable($expected_table);

    // Nest the row with id 2 as child of row 1.
    $this->moveRowWithKeyboard($this->findRowById(2), 'right');
    $expected_table[1] = ['id' => 2, 'weight' => -10, 'parent' => 1, 'indentation' => 1, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // Nest the row with id 3 as child of row 1.
    $this->moveRowWithKeyboard($this->findRowById(3), 'right');
    $expected_table[2] = ['id' => 3, 'weight' => -9, 'parent' => 1, 'indentation' => 1, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // Nest the row with id 3 as child of row 2.
    $this->moveRowWithKeyboard($this->findRowById(3), 'right');
    $expected_table[2] = ['id' => 3, 'weight' => -10, 'parent' => 2, 'indentation' => 2, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // Nesting should be allowed to maximum level 2.
    $this->moveRowWithKeyboard($this->findRowById(4), 'right', 4);
    $expected_table[3] = ['id' => 4, 'weight' => -9, 'parent' => 2, 'indentation' => 2, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // Re-order children of row 1.
    $this->moveRowWithKeyboard($this->findRowById(4), 'up');
    $expected_table[2] = ['id' => 4, 'weight' => -10, 'parent' => 2, 'indentation' => 2, 'changed' => TRUE];
    $expected_table[3] = ['id' => 3, 'weight' => -9, 'parent' => 2, 'indentation' => 2, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // Move back the row 3 to the 1st level.
    $this->moveRowWithKeyboard($this->findRowById(3), 'left');
    $expected_table[3] = ['id' => 3, 'weight' => -9, 'parent' => 1, 'indentation' => 1, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    $this->moveRowWithKeyboard($this->findRowById(3), 'left');
    $expected_table[0] = ['id' => 1, 'weight' => -10, 'parent' => '', 'indentation' => 0, 'changed' => FALSE];
    $expected_table[3] = ['id' => 3, 'weight' => -9, 'parent' => '', 'indentation' => 0, 'changed' => TRUE];
    $expected_table[4] = ['id' => 5, 'weight' => -8, 'parent' => '', 'indentation' => 0, 'changed' => FALSE];
    $this->assertDraggableTable($expected_table);

    // Move row 3 to the last position.
    $this->moveRowWithKeyboard($this->findRowById(3), 'down');
    $expected_table[3] = ['id' => 5, 'weight' => -9, 'parent' => '', 'indentation' => 0, 'changed' => FALSE];
    $expected_table[4] = ['id' => 3, 'weight' => -8, 'parent' => '', 'indentation' => 0, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // Nothing happens when trying to move the last row further down.
    $this->moveRowWithKeyboard($this->findRowById(3), 'down');
    $this->assertDraggableTable($expected_table);

    // Nest row 3 under 5. The max depth allowed should be 1.
    $this->moveRowWithKeyboard($this->findRowById(3), 'right', 3);
    $expected_table[4] = ['id' => 3, 'weight' => -10, 'parent' => 5, 'indentation' => 1, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);

    // The first row of the table cannot be nested.
    $this->moveRowWithKeyboard($this->findRowById(1), 'right');
    $this->assertDraggableTable($expected_table);

    // Move a row which has nested children. The children should move with it,
    // with nesting preserved. Swap the order of the top-level rows by moving
    // row 1 to after row 3.
    $this->moveRowWithKeyboard($this->findRowById(1), 'down', 2);
    $expected_table[0] = ['id' => 5, 'weight' => -10, 'parent' => '', 'indentation' => 0, 'changed' => FALSE];
    $expected_table[3] = $expected_table[1];
    $expected_table[1] = $expected_table[4];
    $expected_table[4] = $expected_table[2];
    $expected_table[2] = ['id' => 1, 'weight' => -9, 'parent' => '', 'indentation' => 0, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);
  }

  /**
   * Tests the root and leaf behaviors for rows.
   */
  public function testRootLeafDraggableRowsWithKeyboard() {
    $this->state->set('tabledrag_test_table', [
      1 => [],
      2 => ['parent' => 1, 'depth' => 1, 'classes' => ['tabledrag-leaf']],
      3 => ['parent' => 1, 'depth' => 1],
      4 => [],
      5 => ['classes' => ['tabledrag-root']],
    ]);

    $this->drupalGet('tabledrag_test');
    $expected_table = [
      ['id' => 1, 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
      ['id' => 2, 'weight' => 0, 'parent' => 1, 'indentation' => 1, 'changed' => FALSE],
      ['id' => 3, 'weight' => 0, 'parent' => 1, 'indentation' => 1, 'changed' => FALSE],
      ['id' => 4, 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
      ['id' => 5, 'weight' => 0, 'parent' => '', 'indentation' => 0, 'changed' => FALSE],
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
    $expected_table[1]['id'] = 3;
    $expected_table[1]['weight'] = -10;
    $expected_table[2] = ['id' => 2, 'weight' => -9, 'parent' => '', 'indentation' => 0, 'changed' => TRUE];
    $expected_table[3]['weight'] = -8;
    $expected_table[4]['weight'] = -7;
    $this->assertDraggableTable($expected_table);

    // Root rows can have children.
    $this->moveRowWithKeyboard($this->findRowById(4), 'down');
    $this->moveRowWithKeyboard($this->findRowById(4), 'right');
    $expected_table[3]['id'] = 5;
    $expected_table[4] = ['id' => 4, 'weight' => -10, 'parent' => 5, 'indentation' => 1, 'changed' => TRUE];
    $this->assertDraggableTable($expected_table);
  }

  /**
   * Tests the warning that appears upon making changes to a tabledrag table.
   */
  public function testTableDragChangedWarning() {
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
   * Asserts the whole structure of the draggable test table.
   *
   * @param array $structure
   *   The table structure. Each entry represents a row and consists of:
   *   - id: the expected value for the ID hidden field.
   *   - weight: the expected row weight.
   *   - parent: the expected parent ID for the row.
   *   - indentation: how many indents the row should have.
   *   - changed: whether or not the row should have been marked as changed.
   */
  protected function assertDraggableTable(array $structure) {
    $rows = $this->getSession()->getPage()->findAll('xpath', '//table[@id="tabledrag-test-table"]/tbody/tr');
    $this->assertSession()->elementsCount('xpath', '//table[@id="tabledrag-test-table"]/tbody/tr', count($structure));

    foreach ($structure as $delta => $expected) {
      $this->assertTableRow($rows[$delta], $expected['id'], $expected['weight'], $expected['parent'], $expected['indentation'], $expected['changed']);
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
   * @param bool $changed
   *   Whether or not the row should have been marked as changed.
   */
  protected function assertTableRow(NodeElement $row, $id, $weight, $parent = '', $indentation = 0, $changed = FALSE) {
    // Assert that the row position is correct by checking that the id
    // corresponds.
    $this->assertSession()->hiddenFieldValueEquals("table[$id][id]", $id, $row);
    $this->assertSession()->hiddenFieldValueEquals("table[$id][parent]", $parent, $row);
    $this->assertSession()->fieldValueEquals("table[$id][weight]", $weight, $row);
    $this->assertSession()->elementsCount('css', '.js-indentation.indentation', $indentation, $row);
    // A row is marked as changed when the related markup is present.
    $this->assertSession()->elementsCount('css', 'abbr.tabledrag-changed', (int) $changed, $row);
  }

  /**
   * Finds a row in the test table by the row ID.
   *
   * @param string $id
   *   The ID of the row.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The row element.
   */
  protected function findRowById($id) {
    $xpath = "//table[@id='tabledrag-test-table']/tbody/tr[.//input[@name='table[$id][id]']]";
    $row = $this->getSession()->getPage()->find('xpath', $xpath);
    $this->assertNotEmpty($row);
    return $row;
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
