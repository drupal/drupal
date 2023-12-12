<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Theme;

use Drupal\FunctionalJavascriptTests\TableDrag\TableDragTest;

/**
 * Tests draggable tables with Claro theme.
 *
 * @group claro
 *
 * @see \Drupal\FunctionalJavascriptTests\TableDrag\TableDragTest
 */
class ClaroTableDragTest extends TableDragTest {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $indentationXpathSelector = 'child::td[1]/div[contains(concat(" ", normalize-space(@class), " "), " js-tabledrag-cell-content ")]/div[contains(concat(" ", normalize-space(@class), " "), " js-indentation ")]';

  /**
   * {@inheritdoc}
   */
  protected static $tabledragChangedXpathSelector = 'child::td[1]/div[contains(concat(" ", normalize-space(@class), " "), " js-tabledrag-cell-content ")]/abbr[contains(concat(" ", normalize-space(@class), " "), " tabledrag-changed ")]';

  /**
   * Ensures that there are no duplicate tabledrag handles.
   */
  public function testNoDuplicates() {
    $this->drupalGet('tabledrag_test_nested');
    $this->assertCount(1, $this->findRowById(1)->findAll('css', '.tabledrag-handle'));
  }

}
