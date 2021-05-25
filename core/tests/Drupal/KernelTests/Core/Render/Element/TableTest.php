<?php

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests built-in table theme functions.
 *
 * @group Theme
 */
class TableTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'form_test'];

  /**
   * Tableheader.js provides 'sticky' table headers, and is included by default.
   */
  public function testThemeTableStickyHeaders() {
    $header = ['one', 'two', 'three'];
    $rows = [[1, 2, 3], [4, 5, 6], [7, 8, 9]];
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
    ];
    $this->render($table);
    // Make sure tableheader.js was attached.
    $tableheader = $this->xpath("//script[contains(@src, 'tableheader.js')]");
    $this->assertCount(1, $tableheader);
    $this->assertRaw('sticky-enabled');
  }

  /**
   * If $sticky is FALSE, no tableheader.js should be included.
   */
  public function testThemeTableNoStickyHeaders() {
    $header = ['one', 'two', 'three'];
    $rows = [[1, 2, 3], [4, 5, 6], [7, 8, 9]];
    $attributes = [];
    $caption = NULL;
    $colgroups = [];
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => $attributes,
      '#caption' => $caption,
      '#colgroups' => $colgroups,
      '#sticky' => FALSE,
    ];
    $this->render($table);
    // Make sure tableheader.js was not attached.
    $tableheader = $this->xpath("//script[contains(@src, 'tableheader.js')]");
    $this->assertCount(0, $tableheader);
    $this->assertNoRaw('sticky-enabled');
  }

  /**
   * Tests that the table header is printed correctly even if there are no rows,
   * and that the empty text is displayed correctly.
   */
  public function testThemeTableWithEmptyMessage() {
    $header = [
      'Header 1',
      [
        'data' => 'Header 2',
        'colspan' => 2,
      ],
    ];
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => [],
      '#empty' => 'Empty row.',
    ];

    // Enable the Classy theme.
    \Drupal::service('theme_installer')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();

    $this->render($table);
    $this->removeWhiteSpace();
    $this->assertRaw('<thead><tr><th>Header 1</th><th colspan="2">Header 2</th></tr>', 'Table header found.');
    $this->assertRaw('<tr class="odd"><td colspan="3" class="empty message">Empty row.</td>', 'Colspan on #empty row found.');
  }

  /**
   * Tests that the 'no_striping' option works correctly.
   */
  public function testThemeTableWithNoStriping() {
    $rows = [
      [
        'data' => [1],
        'no_striping' => TRUE,
      ],
    ];
    $table = [
      '#type' => 'table',
      '#rows' => $rows,
    ];
    $this->render($table);
    $this->assertNoRaw('class="odd"', 'Odd/even classes were not added because $no_striping = TRUE.');
    $this->assertNoRaw('no_striping', 'No invalid no_striping HTML attribute was printed.');
  }

  /**
   * Tests that the 'footer' option works correctly.
   */
  public function testThemeTableFooter() {
    $footer = [
      [
        'data' => [1],
      ],
      ['Foo'],
    ];

    $table = [
      '#type' => 'table',
      '#rows' => [],
      '#footer' => $footer,
    ];

    $this->render($table);
    $this->removeWhiteSpace();
    $this->assertRaw('<tfoot><tr><td>1</td></tr><tr><td>Foo</td></tr></tfoot>', 'Table footer found.');
  }

  /**
   * Tests that the 'header' option in cells works correctly.
   */
  public function testThemeTableHeaderCellOption() {
    $rows = [
      [
        ['data' => 1, 'header' => TRUE],
        ['data' => 1, 'header' => FALSE],
        ['data' => 1],
      ],
    ];
    $table = [
      '#type' => 'table',
      '#rows' => $rows,
    ];
    $this->render($table);
    $this->removeWhiteSpace();
    $this->assertRaw('<th>1</th><td>1</td><td>1</td>', 'The th and td tags was printed correctly.');
  }

  /**
   * Tests that the 'responsive-table' class is applied correctly.
   */
  public function testThemeTableResponsive() {
    $header = ['one', 'two', 'three'];
    $rows = [[1, 2, 3], [4, 5, 6], [7, 8, 9]];
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#responsive' => TRUE,
    ];
    $this->render($table);
    $this->assertRaw('responsive-enabled', 'The responsive-enabled class was printed correctly.');
  }

  /**
   * Tests that the 'responsive-table' class is not applied without headers.
   */
  public function testThemeTableNotResponsiveHeaders() {
    $rows = [[1, 2, 3], [4, 5, 6], [7, 8, 9]];
    $table = [
      '#type' => 'table',
      '#rows' => $rows,
      '#responsive' => TRUE,
    ];
    $this->render($table);
    $this->assertNoRaw('responsive-enabled', 'The responsive-enabled class is not applied without table headers.');
  }

  /**
   * Tests that 'responsive-table' class only applied when responsive is TRUE.
   */
  public function testThemeTableNotResponsiveProperty() {
    $header = ['one', 'two', 'three'];
    $rows = [[1, 2, 3], [4, 5, 6], [7, 8, 9]];
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#responsive' => FALSE,
    ];
    $this->render($table);
    $this->assertNoRaw('responsive-enabled', 'The responsive-enabled class is not applied without the "responsive" property set to TRUE.');
  }

  /**
   * Tests 'priority-medium' and 'priority-low' classes.
   */
  public function testThemeTableResponsivePriority() {
    $header = [
      // Test associative header indices.
      'associative_key' => ['data' => 1, 'class' => [RESPONSIVE_PRIORITY_MEDIUM]],
      // Test non-associative header indices.
      ['data' => 2, 'class' => [RESPONSIVE_PRIORITY_LOW]],
      // Test no responsive priorities.
      ['data' => 3],
    ];
    $rows = [[4, 5, 6]];
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#responsive' => TRUE,
    ];
    $this->render($table);
    $this->assertRaw('<th class="priority-medium">1</th>', 'Header 1: the priority-medium class was applied correctly.');
    $this->assertRaw('<th class="priority-low">2</th>', 'Header 2: the priority-low class was applied correctly.');
    $this->assertRaw('<th>3</th>', 'Header 3: no priority classes were applied.');
    $this->assertRaw('<td class="priority-medium">4</td>', 'Cell 1: the priority-medium class was applied correctly.');
    $this->assertRaw('<td class="priority-low">5</td>', 'Cell 2: the priority-low class was applied correctly.');
    $this->assertRaw('<td>6</td>', 'Cell 3: no priority classes were applied.');
  }

  /**
   * Tests header elements with a mix of string and render array values.
   */
  public function testThemeTableHeaderRenderArray() {
    $header = [
      [
        'data' => [
          '#markup' => 'one',
        ],
      ],
      'two',
      [
        'data' => [
          '#type' => 'html_tag',
          '#tag' => 'b',
          '#value' => 'three',
        ],
      ],
    ];
    $rows = [[1, 2, 3], [4, 5, 6], [7, 8, 9]];
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#responsive' => FALSE,
    ];
    $this->render($table);
    $this->removeWhiteSpace();
    $this->assertRaw('<thead><tr><th>one</th><th>two</th><th><b>three</b></th></tr>', 'Table header found.');
  }

  /**
   * Tests row elements with a mix of string and render array values.
   */
  public function testThemeTableRowRenderArray() {
    $header = ['one', 'two', 'three'];
    $rows = [
      [
        '1-one',
        [
          'data' => '1-two',
        ],
        '1-three',
      ],
      [
        [
          'data' => [
            '#markup' => '2-one',
          ],
        ],
        '2-two',
        [
          'data' => [
            '#type' => 'html_tag',
            '#tag' => 'b',
            '#value' => '2-three',
          ],
        ],
      ],
    ];
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#responsive' => FALSE,
    ];
    $this->render($table);
    $this->removeWhiteSpace();
    $this->assertRaw('<tbody><tr><td>1-one</td><td>1-two</td><td>1-three</td></tr>', 'Table row 1 found.');
    $this->assertRaw('<tr><td>2-one</td><td>2-two</td><td><b>2-three</b></td></tr></tbody>', 'Table row 2 found.');
  }

  /**
   * Tests that the select/checkbox label is being generated and escaped.
   */
  public function testThemeTableTitle() {
    $form = \Drupal::formBuilder()->getForm('\Drupal\form_test\Form\FormTestTableForm');
    $this->render($form);
    $this->assertEscaped('Update <em>kitten</em>');
    $this->assertRaw('Update my favorite fruit is <strong>bananas</strong>');
  }

}
