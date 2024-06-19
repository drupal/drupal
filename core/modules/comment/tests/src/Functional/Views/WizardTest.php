<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Functional\Views;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\views\Views;
use Drupal\Tests\views\Functional\Wizard\WizardTestBase;

/**
 * Tests the comment module integration into the wizard.
 *
 * @group comment
 * @see \Drupal\comment\Plugin\views\wizard\Comment
 */
class WizardTest extends WizardTestBase {

  use CommentTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['node', 'comment'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views, $modules);
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    // Add comment field to page node type.
    $this->addDefaultCommentField('node', 'page');
  }

  /**
   * Tests adding a view of comments.
   */
  public function testCommentWizard(): void {
    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = $this->randomMachineName(16);
    $view['show[wizard_key]'] = 'comment';
    $view['page[create]'] = TRUE;
    $view['page[path]'] = $this->randomMachineName(16);

    // Just triggering the saving should automatically choose a proper row
    // plugin.
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view, 'Save and edit');
    // Verify that the view saving was successful and the browser got redirected
    // to the edit page.
    $this->assertSession()->addressEquals('admin/structure/views/view/' . $view['id']);

    // If we update the type first we should get a selection of comment valid
    // row plugins as the select field.

    $this->drupalGet('admin/structure/views/add');
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view, 'Update "of type" choice');

    // Check for available options of the row plugin.
    $expected_options = ['entity:comment', 'fields'];
    $items = $this->getSession()->getPage()->findField('page[style][row_plugin]')->findAll('xpath', 'option');
    $actual_options = [];
    foreach ($items as $item) {
      $actual_options[] = $item->getValue();
    }
    $this->assertEquals($expected_options, $actual_options);

    $view['id'] = $this->randomMachineName(16);
    $this->submitForm($view, 'Save and edit');
    // Verify that the view saving was successful and the browser got redirected
    // to the edit page.
    $this->assertSession()->addressEquals('admin/structure/views/view/' . $view['id']);

    $user = $this->drupalCreateUser(['access comments']);
    $this->drupalLogin($user);

    $view = Views::getView($view['id']);
    $view->initHandlers();
    $row = $view->display_handler->getOption('row');
    $this->assertEquals('entity:comment', $row['type']);

    // Check for the default filters.
    $this->assertEquals('comment_field_data', $view->filter['status']->table);
    $this->assertEquals('status', $view->filter['status']->field);
    $this->assertEquals('1', $view->filter['status']->value);
    $this->assertEquals('node_field_data', $view->filter['status_node']->table);
    $this->assertEquals('status', $view->filter['status_node']->field);
    $this->assertEquals('1', $view->filter['status_node']->value);

    // Check for the default fields.
    $this->assertEquals('comment_field_data', $view->field['subject']->table);
    $this->assertEquals('subject', $view->field['subject']->field);
  }

}
