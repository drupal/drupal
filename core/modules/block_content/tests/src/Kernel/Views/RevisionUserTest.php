<?php

namespace Drupal\Tests\block_content\Kernel\Views;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the block_content_revision_user field.
 *
 * @group block_content
 */
class RevisionUserTest extends ViewsKernelTestBase {

  use UserCreationTrait;
  use ViewResultAssertionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'block_content_test_views',
    'system',
    'user',
    'views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_block_content_revision_user'];

  /**
   * Map column names.
   *
   * @var array
   */
  public static $columnMap = [
    'id' => 'id',
    'revision_id' => 'revision_id',
    'revision_user' => 'revision_user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('block_content');
    $this->installEntitySchema('user');

    if ($import_test_views) {
      ViewTestData::createTestViews(get_class($this), ['block_content_test_views']);
    }
  }

  /**
   * Tests the block_content_revision_user relationship.
   */
  public function testRevisionUser() {
    $primary_author = $this->createUser();
    $secondary_author = $this->createUser();

    $block_content_type = BlockContentType::create([
      'id' => 'basic',
      'label' => 'basic block',
    ]);
    $block_content_type->save();

    $block_content = BlockContent::create([
      'info' => 'Test block content',
      'type' => 'basic',
    ]);
    $block_content->setRevisionUserId($primary_author->id());
    $block_content->save();

    $view = Views::getView('test_block_content_revision_user');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [
      [
        'id' => 1,
        'revision_id' => 1,
        'revision_user' => $primary_author->id(),
      ],
    ], static::$columnMap);

    // Test results shows the revision author.
    $block_content->setRevisionUser($secondary_author);
    $block_content->setNewRevision();
    $block_content->save();

    $view = Views::getView('test_block_content_revision_user');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [
      [
        'id' => 1,
        'revision_id' => 2,
        'revision_user' => $secondary_author->id(),
      ],
    ], static::$columnMap);

    // Build a larger dataset to allow filtering.
    $block_content2_title = $this->randomString();
    $block_content2 = BlockContent::create([
      'info' => $block_content2_title,
      'type' => 'basic',
    ]);
    $block_content2->save();
    $block_content2->setRevisionUser($primary_author);
    $block_content2->setNewRevision();
    $block_content2->save();

    $view = Views::getView('test_block_content_revision_user');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [
      [
        'id' => 1,
        'revision_id' => 2,
        'revision_user' => $secondary_author->id(),
      ],
      [
        'id' => 2,
        'revision_id' => 4,
        'revision_user' => $primary_author->id(),
      ],
    ], static::$columnMap);

    // Test filter by revision_author.
    $view = Views::getView('test_block_content_revision_user');
    $view->initHandlers();
    $view->filter['revision_user']->value = [$secondary_author->id()];
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [
      [
        'id' => 1,
        'revision_id' => 2,
        'revision_user' => $secondary_author->id(),
      ],
    ], static::$columnMap);
  }

}
