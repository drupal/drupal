<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\EntityTestHelper;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\system\Functional\Entity\EntityWithUriCacheTagsTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

// cspell:ignore amphibius Hippopotamidae

/**
 * Tests the Comment entity's cache tags.
 *
 * @group comment
 */
class CommentCacheTagsTest extends EntityWithUriCacheTagsTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $entityTestCamelid;

  /**
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $entityTestHippopotamidae;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Give anonymous users permission to view comments, so that we can verify
    // the cache tags of cached versions of comment pages.
    $user_role = Role::load(RoleInterface::ANONYMOUS_ID);
    $user_role->grantPermission('access comments');
    $user_role->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "bar" bundle for the "entity_test" entity type and create.
    $bundle = 'bar';
    EntityTestHelper::createBundle($bundle, NULL, 'entity_test');

    // Create a comment field on this bundle.
    $this->addDefaultCommentField('entity_test', 'bar', 'comment');

    // Display comments in a flat list; threaded comments are not render cached.
    $field = FieldConfig::loadByName('entity_test', 'bar', 'comment');
    $field->setSetting('default_mode', CommentManagerInterface::COMMENT_MODE_FLAT);
    $field->save();

    // Create a "Camelids" test entity that the comment will be assigned to.
    $this->entityTestCamelid = EntityTest::create([
      'name' => 'Camelids',
      'type' => 'bar',
    ]);
    $this->entityTestCamelid->save();

    // Create a "Llama" comment.
    $comment = Comment::create([
      'subject' => 'Llama',
      'comment_body' => [
        'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
        'format' => 'plain_text',
      ],
      'entity_id' => $this->entityTestCamelid->id(),
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'status' => CommentInterface::PUBLISHED,
    ]);
    $comment->save();

    return $comment;
  }

  /**
   * Tests that comments invalidate the cache tag of their host entity.
   */
  public function testCommentEntity(): void {
    $this->verifyPageCache($this->entityTestCamelid->toUrl(), 'MISS');
    $this->verifyPageCache($this->entityTestCamelid->toUrl(), 'HIT');

    // Create a "Hippopotamus" comment.
    $this->entityTestHippopotamidae = EntityTest::create([
      'name' => 'Hippopotamus',
      'type' => 'bar',
    ]);
    $this->entityTestHippopotamidae->save();

    $this->verifyPageCache($this->entityTestHippopotamidae->toUrl(), 'MISS');
    $this->verifyPageCache($this->entityTestHippopotamidae->toUrl(), 'HIT');

    $hippo_comment = Comment::create([
      'subject' => 'Hippopotamus',
      'comment_body' => [
        'value' => 'The common hippopotamus (Hippopotamus amphibius), or hippo, is a large, mostly herbivorous mammal in sub-Saharan Africa',
        'format' => 'plain_text',
      ],
      'entity_id' => $this->entityTestHippopotamidae->id(),
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'status' => CommentInterface::PUBLISHED,
    ]);
    $hippo_comment->save();

    // Ensure that a new comment only invalidates the commented entity.
    $this->verifyPageCache($this->entityTestCamelid->toUrl(), 'HIT');
    $this->verifyPageCache($this->entityTestHippopotamidae->toUrl(), 'MISS');
    $this->assertSession()->pageTextContains($hippo_comment->getSubject());

    // Ensure that updating an existing comment only invalidates the commented
    // entity.
    $this->entity->save();
    $this->verifyPageCache($this->entityTestCamelid->toUrl(), 'MISS');
    $this->verifyPageCache($this->entityTestHippopotamidae->toUrl(), 'HIT');
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdditionalCacheContextsForEntity(EntityInterface $entity): array {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * Each comment must have a comment body, which always has a text format.
   */
  protected function getAdditionalCacheTagsForEntity(EntityInterface $entity): array {
    /** @var \Drupal\comment\CommentInterface $entity */
    return [
      'config:filter.format.plain_text',
      'user:' . $entity->getOwnerId(),
      'user_view',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultCacheContexts(): array {
    return [
      'languages:' . LanguageInterface::TYPE_INTERFACE,
      'theme',
      'user.permissions',
    ];
  }

}
