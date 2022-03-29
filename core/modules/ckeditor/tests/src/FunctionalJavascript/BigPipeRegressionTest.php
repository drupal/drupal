<?php

namespace Drupal\Tests\ckeditor\FunctionalJavascript;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * BigPipe regression test for CKEditor 4.
 *
 * @group legacy
 */
class BigPipeRegressionTest extends WebDriverTestBase {

  use CommentTestTrait;
  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'big_pipe',
    'big_pipe_regression_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Use the big_pipe_test_theme theme.
    $this->container->get('theme_installer')->install(['big_pipe_test_theme']);
    $this->container->get('config.factory')->getEditable('system.theme')->set('default', 'big_pipe_test_theme')->save();
  }

  /**
   * Ensure comment form works with history and big_pipe modules.
   *
   * @see https://www.drupal.org/node/2698811
   */
  public function testCommentForm_2698811() {
    $this->assertTrue($this->container->get('module_installer')->install(['comment', 'history', 'ckeditor'], TRUE), 'Installed modules.');

    // Ensure an `article` node type exists.
    $this->createContentType(['type' => 'article']);
    $this->addDefaultCommentField('node', 'article');

    // Enable CKEditor.
    $format = $this->randomMachineName();
    FilterFormat::create([
      'format' => $format,
      'name' => $this->randomString(),
      'weight' => 1,
      'filters' => [],
    ])->save();
    $settings['toolbar']['rows'] = [
      [
        [
          'name' => 'Links',
          'items' => [
            'DrupalLink',
            'DrupalUnlink',
          ],
        ],
      ],
    ];
    $editor = Editor::create([
      'format' => $format,
      'editor' => 'ckeditor',
    ]);
    $editor->setSettings($settings);
    $editor->save();

    $admin_user = $this->drupalCreateUser([
      'access comments',
      'post comments',
      'use text format ' . $format,
    ]);
    $this->drupalLogin($admin_user);

    $node = $this->createNode([
      'type' => 'article',
      'comment' => CommentItemInterface::OPEN,
    ]);
    // Create some comments.
    foreach (range(1, 5) as $i) {
      $comment = Comment::create([
        'status' => CommentInterface::PUBLISHED,
        'field_name' => 'comment',
        'entity_type' => 'node',
        'entity_id' => $node->id(),
      ]);
      $comment->save();
    }
    $this->drupalGet($node->toUrl()->toString());
    // Confirm that CKEditor loaded.
    $javascript = <<<JS
    (function(){
      return Object.keys(CKEDITOR.instances).length > 0;
    }())
JS;
    $this->assertJsCondition($javascript);
  }

}
