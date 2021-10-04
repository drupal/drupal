<?php

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\system\Entity\Action;

/**
 * Tests actions provided by the Comment module.
 *
 * @group comment
 */
class CommentActionsTest extends EntityKernelTestBase {
  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'entity_test'];

  /**
   * Keywords used for testing.
   *
   * @var string[]
   */
  protected $keywords;

  /**
   * The comment entity.
   *
   * @var \Drupal\comment\CommentInterface
   */
  protected $comment;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['user', 'comment']);
    $this->installSchema('comment', ['comment_entity_statistics']);

    // Create a comment type.
    CommentType::create([
      'id' => 'comment',
      'label' => 'Default comments',
      'description' => 'Default comment field',
      'target_entity_type_id' => 'entity_test',
    ])->save();
    $this->addDefaultCommentField('entity_test', 'entity_test', 'comment');

    // Setup date format to render comment date.
    DateFormat::create([
      'id' => 'fallback',
      'pattern' => 'D, m/d/Y - H:i',
    ])->save();

    // Create format without filters to prevent filtering.
    FilterFormat::create([
      'format' => 'no_filters',
      'name' => 'No filters',
      'filters' => [],
    ])->save();

    // Set current user to allow filters display comment body.
    $this->drupalSetCurrentUser($this->drupalCreateUser());

    $this->keywords = [$this->randomMachineName(), $this->randomMachineName()];

    // Create a comment against a test entity.
    $host = EntityTest::create();
    $host->save();

    $this->comment = Comment::create([
      'entity_type' => 'entity_test',
      'name' => $this->randomString(),
      'hostname' => 'magic.example.com',
      'mail' => 'tonythemagicalpony@example.com',
      'subject' => $this->keywords[0],
      'comment_body' => $this->keywords[1],
      'entity_id' => $host->id(),
      'comment_type' => 'comment',
      'field_name' => 'comment',
    ]);
    $this->comment->get('comment_body')->format = 'no_filters';
    $this->comment->setPublished();
  }

  /**
   * Tests comment module's default config actions.
   *
   * @see \Drupal\Core\Entity\Form\DeleteMultipleForm::submitForm()
   * @see \Drupal\Core\Action\Plugin\Action\DeleteAction
   * @see \Drupal\Core\Action\Plugin\Action\Derivative\EntityDeleteActionDeriver
   * @see \Drupal\Core\Action\Plugin\Action\PublishAction
   * @see \Drupal\Core\Action\Plugin\Action\SaveAction
   */
  public function testCommentDefaultConfigActions() {
    $this->assertTrue($this->comment->isNew());
    $action = Action::load('comment_save_action');
    $action->execute([$this->comment]);
    $this->assertFalse($this->comment->isNew());

    $this->assertTrue($this->comment->isPublished());
    // Tests comment unpublish.
    $action = Action::load('comment_unpublish_action');
    $action->execute([$this->comment]);
    $this->assertFalse($this->comment->isPublished(), 'Comment was unpublished');
    $this->assertSame(['module' => ['comment']], $action->getDependencies());
    // Tests comment publish.
    $action = Action::load('comment_publish_action');
    $action->execute([$this->comment]);
    $this->assertTrue($this->comment->isPublished(), 'Comment was published');

    $action = Action::load('comment_delete_action');
    $action->execute([$this->comment]);
    /** @var \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store */
    $temp_store = $this->container->get('tempstore.private');
    $account_id = $this->container->get('current_user')->id();
    $store_entries = $temp_store->get('entity_delete_multiple_confirm')->get($account_id . ':comment');
    $this->assertSame([$account_id => ['en' => 'en']], $store_entries);
  }

  /**
   * Tests the unpublish comment by keyword action.
   *
   * @see \Drupal\comment\Plugin\Action\UnpublishByKeywordComment
   */
  public function testCommentUnpublishByKeyword() {
    $this->comment->save();
    $action = Action::create([
      'id' => 'comment_unpublish_by_keyword_action',
      'label' => $this->randomMachineName(),
      'type' => 'comment',
      'plugin' => 'comment_unpublish_by_keyword_action',
    ]);

    // Tests no keywords.
    $action->execute([$this->comment]);
    $this->assertTrue($this->comment->isPublished(), 'The comment status was set to published.');

    // Tests keyword in subject.
    $action->set('configuration', ['keywords' => [$this->keywords[0]]]);
    $action->execute([$this->comment]);
    $this->assertFalse($this->comment->isPublished(), 'The comment status was set to not published.');

    // Tests keyword in comment body.
    $this->comment->setPublished();
    $action->set('configuration', ['keywords' => [$this->keywords[1]]]);
    $action->execute([$this->comment]);
    $this->assertFalse($this->comment->isPublished(), 'The comment status was set to not published.');
  }

}
