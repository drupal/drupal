<?php

namespace Drupal\Tests\file\Kernel\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests file on user relationship handler.
 *
 * @group file
 */
class RelationshipUserFileDataTest extends KernelTestBase {

  use UserCreationTrait;
  use ViewResultAssertionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'file_test_views',
    'system',
    'user',
    'views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_file_user_file_data'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');

    // Create the user profile field and instance.
    FieldStorageConfig::create([
      'entity_type' => 'user',
      'field_name' => 'user_file',
      'type' => 'file',
      'translatable' => '0',
    ])->save();
    FieldConfig::create([
      'label' => 'User File',
      'description' => '',
      'field_name' => 'user_file',
      'entity_type' => 'user',
      'bundle' => 'user',
      'required' => 0,
    ])->save();

    ViewTestData::createTestViews(static::class, ['file_test_views']);
  }

  /**
   * Tests using the views file relationship.
   */
  public function testViewsHandlerRelationshipUserFileData() {
    $file = File::create([
      'fid' => 2,
      'uid' => 2,
      'filename' => 'image-test.jpg',
      'uri' => "public://image-test.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $file->enforceIsNew();
    file_put_contents($file->getFileUri(), file_get_contents('core/tests/fixtures/files/image-1.png'));
    $file->save();

    $account = $this->createUser();
    $account->user_file->target_id = 2;
    $account->save();

    $view = Views::getView('test_file_user_file_data');
    // Tests \Drupal\taxonomy\Plugin\views\relationship\NodeTermData::calculateDependencies().
    $expected = [
      'module' => [
        'file',
        'user',
      ],
    ];
    $this->assertSame($expected, $view->getDependencies());
    $view->preview();
    $expected_result = [
      [
        'file_managed_user__user_file_fid' => '2',
      ],
    ];
    $column_map = ['file_managed_user__user_file_fid' => 'file_managed_user__user_file_fid'];
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

}
