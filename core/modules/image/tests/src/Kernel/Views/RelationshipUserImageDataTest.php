<?php

namespace Drupal\Tests\image\Kernel\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests image on user relationship handler.
 *
 * @group image
 */
class RelationshipUserImageDataTest extends ViewsKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'file',
    'field',
    'image',
    'image_test_views',
    'system',
    'user',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_image_user_image_data'];

  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('user');

    // Create the user profile field and instance.
    FieldStorageConfig::create([
      'entity_type' => 'user',
      'field_name' => 'user_picture',
      'type' => 'image',
      'translatable' => '0',
    ])->save();
    FieldConfig::create([
      'label' => 'User Picture',
      'description' => '',
      'field_name' => 'user_picture',
      'entity_type' => 'user',
      'bundle' => 'user',
      'required' => 0,
    ])->save();

    ViewTestData::createTestViews(static::class, ['image_test_views']);
  }

  /**
   * Tests using the views image relationship.
   */
  public function testViewsHandlerRelationshipUserImageData() {
    $file = File::create([
      'fid' => 2,
      'uid' => 2,
      'filename' => 'image-test.jpg',
      'uri' => "public://image-test.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
    ]);
    $file->setPermanent();
    $file->enforceIsNew();
    file_put_contents($file->getFileUri(), file_get_contents('core/tests/fixtures/files/image-1.png'));
    $file->save();

    $account = User::create([
      'name' => 'foo',
    ]);
    $account->user_picture->target_id = 2;
    $account->save();

    $view = Views::getView('test_image_user_image_data');
    // Tests \Drupal\taxonomy\Plugin\views\relationship\NodeTermData::calculateDependencies().
    $expected = [
      'module' => [
        'file',
        'user',
      ],
    ];
    $this->assertSame($expected, $view->getDependencies());
    $this->executeView($view);
    $expected_result = [
      [
        'file_managed_user__user_picture_fid' => '2',
      ],
    ];
    $column_map = ['file_managed_user__user_picture_fid' => 'file_managed_user__user_picture_fid'];
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

}
