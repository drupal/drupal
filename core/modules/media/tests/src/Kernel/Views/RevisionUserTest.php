<?php

namespace Drupal\Tests\media\Kernel\Views;

use Drupal\media\Entity\Media;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests the media_revision_user field.
 *
 * @group media
 */
class RevisionUserTest extends ViewsKernelTestBase {

  use UserCreationTrait;
  use ViewResultAssertionTrait;
  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'media_test_views',
    'media_test_source',
    'system',
    'user',
    'views',
    'image',
    'field',
    'file',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_media_revision_uid'];

  /**
   * The test media type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $testMediaType;

  /**
   * Map column names.
   *
   * @var array
   */
  public static $columnMap = [
    'mid' => 'mid',
    'vid' => 'vid',
    'uid' => 'uid',
    'revision_user' => 'revision_user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    $this->installEntitySchema('user');
    $this->installConfig(['field', 'system', 'image', 'file', 'media']);

    if ($import_test_views) {
      ViewTestData::createTestViews(get_class($this), ['media_test_views']);
    }

    $this->testMediaType = $this->createMediaType('test');
  }

  /**
   * Tests the media_revision_user relationship.
   */
  public function testRevisionUser() {
    $primary_author = $this->createUser();
    $secondary_author = $this->createUser();

    $media = Media::create([
      'name' => 'Test media',
      'bundle' => $this->testMediaType->id(),
      'uid' => $primary_author->id(),
    ]);
    $media->setRevisionUserId($primary_author->id());
    $media->save();

    $view = Views::getView('test_media_revision_uid');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [
      [
        'mid' => 1,
        'vid' => 1,
        'uid' => $primary_author->id(),
        'revision_user' => $primary_author->id(),
      ],
    ], static::$columnMap);

    // Test results shows the original author as well as the revision author.
    $media->setRevisionUser($secondary_author);
    $media->setNewRevision();
    $media->save();

    $view = Views::getView('test_media_revision_uid');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [
      [
        'mid' => 1,
        'vid' => 2,
        'uid' => $primary_author->id(),
        'revision_user' => $secondary_author->id(),
      ],
    ], static::$columnMap);

    // Build a larger dataset to allow filtering.
    $media2_name = $this->randomString();
    $media2 = Media::create([
      'name' => $media2_name,
      'bundle' => $this->testMediaType->id(),
      'uid' => $primary_author->id(),
    ]);
    $media2->save();
    $media2->setRevisionUser($primary_author);
    $media2->setNewRevision();
    $media2->save();

    $view = Views::getView('test_media_revision_uid');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [
      [
        'mid' => 1,
        'vid' => 2,
        'uid' => $primary_author->id(),
        'revision_user' => $secondary_author->id(),
      ],
      [
        'mid' => 2,
        'vid' => 4,
        'uid' => $primary_author->id(),
        'revision_user' => $primary_author->id(),
      ],
    ], static::$columnMap);

    // Test filter by revision_user.
    $view = Views::getView('test_media_revision_uid');
    $view->initHandlers();
    $view->filter['revision_user']->value = [$secondary_author->id()];
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [
      [
        'mid' => 1,
        'vid' => 2,
        'uid' => $primary_author->id(),
        'revision_user' => $secondary_author->id(),
      ],
    ], static::$columnMap);
  }

}
