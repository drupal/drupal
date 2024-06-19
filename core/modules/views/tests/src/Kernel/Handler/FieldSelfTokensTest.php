<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Core\Render\RenderContext;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests token escaping in the EntityField handler.
 *
 * @group views
 */
class FieldSelfTokensTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_field_self_tokens'];

  /**
   * This method is called before each test.
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    Node::create([
      'title' => 'Questions & Answers',
      'type' => 'article',
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testSelfTokenEscaping(): void {
    $view = Views::getView('test_field_self_tokens');
    $view->initHandlers();
    $this->executeView($view);
    $row = $view->result[0];
    $title_field = $view->field['title'];
    $title_field->options['alter']['text'] = '<p>{{ title__value }}</p>';
    $title_field->options['alter']['alter_text'] = TRUE;
    $output = \Drupal::service('renderer')->executeInRenderContext(new RenderContext(), function () use ($title_field, $row) {
      return $title_field->theme($row);
    });
    $this->assertSame('<p>Questions &amp; Answers</p>', (string) $output);
  }

}
