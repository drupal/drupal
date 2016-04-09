<?php

namespace Drupal\file\Tests\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests file on user relationship handler.
 *
 * @group file
 */
class RelationshipUserFileDataTest extends ViewTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('file', 'file_test_views', 'user');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_file_user_file_data');

  protected function setUp() {
    parent::setUp();

    // Create the user profile field and instance.
    FieldStorageConfig::create(array(
      'entity_type' => 'user',
      'field_name' => 'user_file',
      'type' => 'file',
      'translatable' => '0',
    ))->save();
    FieldConfig::create([
      'label' => 'User File',
      'description' => '',
      'field_name' => 'user_file',
      'entity_type' => 'user',
      'bundle' => 'user',
      'required' => 0,
    ])->save();

    ViewTestData::createTestViews(get_class($this), array('file_test_views'));
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
    file_put_contents($file->getFileUri(), file_get_contents('core/modules/simpletest/files/image-1.png'));
    $file->save();

    $account = $this->drupalCreateUser();
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
    $this->assertIdentical($expected, $view->getDependencies());
    $this->executeView($view);
    $expected_result = array(
      array(
        'file_managed_user__user_file_fid' => '2',
      ),
    );
    $column_map = array('file_managed_user__user_file_fid' => 'file_managed_user__user_file_fid');
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

}
