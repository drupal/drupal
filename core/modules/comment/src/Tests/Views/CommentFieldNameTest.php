<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Views\CommentFieldNameTest.
 */

namespace Drupal\comment\Tests\Views;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\user\RoleInterface;
use Drupal\views\Views;

/**
 * Tests the comment field name field.
 *
 * @group comment
 */
class CommentFieldNameTest extends CommentTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment_field_name'];

  /**
   * The second comment entity used by this test.
   *
   * @var \Drupal\comment\CommentInterface
   */
  protected $customComment;

  /**
   * The comment field name used by this test.
   *
   * @var string
   */
  protected $fieldName = 'comment_custom';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->addDefaultCommentField('node', 'page', $this->fieldName);
    $this->customComment = Comment::create([
      'entity_id' => $this->nodeUserCommented->id(),
      'entity_type' => 'node',
      'field_name' => $this->fieldName,
    ]);
    $this->customComment->save();
  }

  /**
   * Test comment field name.
   */
  public function testCommentFieldName() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $view = Views::getView('test_comment_field_name');
    $this->executeView($view);

    $expected_result = [
      [
        'cid' => $this->comment->id(),
        'field_name' => $this->comment->getFieldName(),
      ],
      [
        'cid' => $this->customComment->id(),
        'field_name' => $this->customComment->getFieldName(),
      ],
    ];
    $column_map = [
      'cid' => 'cid',
      'comment_field_data_field_name' => 'field_name',
    ];
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
    // Test that no data can be rendered.
    $this->assertIdentical(FALSE, isset($view->field['field_name']));

    // Grant permission to properly check view access on render.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access comments']);
    $this->container->get('account_switcher')->switchTo(new AnonymousUserSession());
    $view = Views::getView('test_comment_field_name');
    $this->executeView($view);
    // Test that data rendered.
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['field_name']->advancedRender($view->result[0]);
    });
    $this->assertEqual($this->comment->getFieldName(), $output);
    $output = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['field_name']->advancedRender($view->result[1]);
    });
    $this->assertEqual($this->customComment->getFieldName(), $output);
  }

}
