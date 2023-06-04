<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Views;

use Drupal\Core\Database\Database;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests file on user relationship handler.
 *
 * @group file
 */
class RelationshipUserFileDataTest extends ViewsKernelTestBase {

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
  public static $testViews = ['test_file_user_file_data', 'test_user_to_file', 'test_file_to_user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    if (Database::getConnection()->driver() === 'pgsql') {
      $this->markTestSkipped('Skipped because PostgreSQL is currently failing: https://www.drupal.org/project/drupal/issues/3364621');
    }

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
    $this->installConfig(['field', 'file_test_views']);
  }

  /**
   * Tests using the views file relationship.
   */
  public function testViewsHandlerRelationshipUserFileData(): void {
    $file = File::create([
      'fid' => 2,
      'uid' => 2,
      'filename' => 'image-test.jpg',
      'uri' => "public://image-test.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
    ]);
    $file->enforceIsNew()->setPermanent();
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

  /**
   * Tests using the views user_to_file relationship.
   */
  public function testViewsHandlerRelationshipUserToFile(): void {
    $file1 = File::create([
      'filename' => 'image-test.jpg',
      'uri' => "public://image-test.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file1->enforceIsNew();
    file_put_contents($file1->getFileUri(), file_get_contents('core/tests/fixtures/files/image-1.png'));
    $file1->save();

    $file2 = File::create([
      'filename' => 'image-test-2.jpg',
      'uri' => "public://image-test-2.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file2->enforceIsNew();
    file_put_contents($file2->getFileUri(), file_get_contents('core/tests/fixtures/files/image-1.png'));
    $file2->save();

    User::create([
      'name' => $this->randomMachineName(8),
      'mail' => $this->randomMachineName(4) . '@' . $this->randomMachineName(4) . '.com',
    ])->save();

    $account = User::create([
      'name' => $this->randomMachineName(8),
      'mail' => $this->randomMachineName(4) . '@' . $this->randomMachineName(4) . '.com',
      'user_file' => ['target_id' => $file2->id()],
    ]);
    $account->save();

    $view = Views::getView('test_user_to_file');
    $this->executeView($view);
    // We should only see a single file, the one on the user account. The other
    // account's UUID, nor the other unlinked file, should appear in the
    // results.
    $expected_result = [
      [
        'fid' => $file2->id(),
        'uuid' => $account->uuid(),
      ],
    ];
    $column_map = ['fid' => 'fid', 'uuid' => 'uuid'];
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

  /**
   * Tests using the views file_to_user relationship.
   */
  public function testViewsHandlerRelationshipFileToUser(): void {
    $file1 = File::create([
      'filename' => 'image-test.jpg',
      'uri' => "public://image-test.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file1->enforceIsNew();
    file_put_contents($file1->getFileUri(), file_get_contents('core/tests/fixtures/files/image-1.png'));
    $file1->save();

    $file2 = File::create([
      'filename' => 'image-test-2.jpg',
      'uri' => "public://image-test-2.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file2->enforceIsNew();
    file_put_contents($file2->getFileUri(), file_get_contents('core/tests/fixtures/files/image-1.png'));
    $file2->save();

    User::create([
      'name' => $this->randomMachineName(8),
      'mail' => $this->randomMachineName(4) . '@' . $this->randomMachineName(4) . '.com',
    ])->save();

    $account = User::create([
      'name' => $this->randomMachineName(8),
      'mail' => $this->randomMachineName(4) . '@' . $this->randomMachineName(4) . '.com',
      'user_file' => ['target_id' => $file2->id()],
    ]);
    $account->save();

    $view = Views::getView('test_file_to_user');
    $this->executeView($view);
    // We should only see a single file, the one on the user account. The other
    // account's UUID, nor the other unlinked file, should appear in the
    // results.
    $expected_result = [
      [
        'fid' => $file2->id(),
        'uuid' => $account->uuid(),
      ],
    ];
    $column_map = ['fid' => 'fid', 'uuid' => 'uuid'];
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

}
