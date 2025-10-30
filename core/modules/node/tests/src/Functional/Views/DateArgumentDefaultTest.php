<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional\Views;

use Drupal\views\Entity\View;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests pluggable argument_default for views.
 */
#[Group('views')]
#[RunTestsInSeparateProcesses]
class DateArgumentDefaultTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_argument_node_date'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'node', 'node_test_views'];

  /**
   * The current node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $currentNode;

  /**
   * Node with the same create time as the current node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $sameTimeNode;

  /**
   * Node with a different create time then the current node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $otherTimeNode;

  /**
   * The node representing the page.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $fixedTimeNode;

  /**
   * The node representing the page.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $sameMonthNode;

  /**
   * {@inheritdoc}
   */
  public function setUp($import_test_views = TRUE, $modules = ['node_test_views']): void {
    parent::setUp($import_test_views, $modules);

    $this->drupalCreateContentType(['type' => 'page']);
    $this->currentNode = $this->drupalCreateNode(['type' => 'page']);
    $this->sameTimeNode = $this->drupalCreateNode(['type' => 'page']);
    $this->otherTimeNode = $this->drupalCreateNode([
      'type' => 'page',
      'created' => strtotime('-5 days'),
      'changed' => strtotime('-5 days'),
    ]);
    $this->fixedTimeNode = $this->drupalCreateNode([
      'type' => 'page',
      'created' => strtotime('1975-05-18'),
      'changed' => strtotime('1975-05-18'),
    ]);
    $this->sameMonthNode = $this->drupalCreateNode([
      'type' => 'page',
      'created' => strtotime('1975-05-13'),
      'changed' => strtotime('1975-05-13'),
    ]);
  }

  /**
   * Test the 'Current node created time' default argument handler.
   *
   * @see \Drupal\node\Plugin\views\argument_default\NodeCreated
   */
  public function testArgumentDefaultNodeCreated(): void {
    $this->drupalPlaceBlock('views_block:test_argument_node_date-block_1', ['label' => 'test_argument_node_date-block_1:1']);
    $assert = $this->assertSession();

    // Assert that only nodes with the same creation time as the current node
    // are shown in the block.
    $this->drupalGet($this->currentNode->toUrl());
    $assert->pageTextContains($this->currentNode->getTitle());
    $assert->pageTextContains($this->sameTimeNode->getTitle());
    $assert->pageTextNotContains($this->otherTimeNode->getTitle());

    // Update the View to use the Y-m format argument.
    $view = View::load('test_argument_node_date');
    $display = &$view->getDisplay('block_1');
    $display['display_options']['arguments']['created']['plugin_id'] = 'date_year_month';
    $display['display_options']['arguments']['created']['field'] = 'created_year_month';
    $display['display_options']['arguments']['created']['id'] = 'created_year_month';
    $view->save();

    // Test that the nodes with a create date in the same month are shown.
    $this->drupalGet($this->fixedTimeNode->toUrl());
    $assert->pageTextContains($this->fixedTimeNode->getTitle());
    $assert->pageTextContains($this->sameMonthNode->getTitle());
    $assert->pageTextNotContains($this->currentNode->getTitle());

    // Update the View to use the date format argument for non-date field.
    $display['display_options']['arguments']['created']['field'] = 'title';
    $view->save();

    // Test that the nodes with a title in the same create date are shown.
    $nodeTitleFixed = $this->drupalCreateNode(['type' => 'page', 'title' => '1975-05-18']);
    $this->drupalGet($this->fixedTimeNode->toUrl());
    $assert->pageTextContains($nodeTitleFixed->getTitle());
    $assert->pageTextNotContains($this->sameMonthNode->getTitle());

    // Test the getDefaultArgument() outside of node page.
    $view = Views::getView('test_argument_node_date');
    $view->setDisplay('block_1');
    $view->initHandlers();
    $this->assertFalse($view->argument['created']->getDefaultArgument());
  }

  /**
   * Test the 'Current node changed time' default argument handler.
   *
   * @see \Drupal\node\Plugin\views\argument_default\NodeChanged
   */
  public function testArgumentDefaultNodeChanged(): void {
    $this->drupalPlaceBlock('views_block:test_argument_node_date-block_2', ['label' => 'test_argument_node_date-block_2:2']);
    $assert = $this->assertSession();

    // Assert that only nodes with the same changed time as the current node
    // are shown in the block.
    $this->drupalGet($this->currentNode->toUrl());
    $assert->pageTextContains($this->currentNode->getTitle());
    $assert->pageTextContains($this->sameTimeNode->getTitle());
    $assert->pageTextNotContains($this->otherTimeNode->getTitle());

    // Update the View to use the Y-m format argument.
    $view = View::load('test_argument_node_date');
    $display = &$view->getDisplay('block_2');
    $display['display_options']['arguments']['changed']['plugin_id'] = 'date_year_month';
    $display['display_options']['arguments']['changed']['field'] = 'changed_year_month';
    $display['display_options']['arguments']['changed']['id'] = 'changed_year_month';
    $view->save();

    // Test that the nodes with a changed date in the same month are shown.
    $this->drupalGet($this->fixedTimeNode->toUrl());
    $assert->pageTextContains($this->fixedTimeNode->getTitle());
    $assert->pageTextContains($this->sameMonthNode->getTitle());
    $assert->pageTextNotContains($this->currentNode->getTitle());

    // Update the View to use the date format argument for non-date field.
    $display['display_options']['arguments']['changed']['field'] = 'title';
    $view->save();

    // Test that the nodes with a title in the same changed date are shown.
    $nodeTitleFixed = $this->drupalCreateNode(['type' => 'page', 'title' => '1975-05-18']);
    $this->drupalGet($this->fixedTimeNode->toUrl());
    $assert->pageTextContains($nodeTitleFixed->getTitle());
    $assert->pageTextNotContains($this->sameMonthNode->getTitle());

    // Test the getDefaultArgument() outside of node page.
    $view = Views::getView('test_argument_node_date');
    $view->setDisplay('block_2');
    $view->initHandlers();
    $this->assertFalse($view->argument['changed']->getDefaultArgument());
  }

}
