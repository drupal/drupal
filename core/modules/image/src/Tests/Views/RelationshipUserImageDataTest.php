<?php

/**
 * @file
 * Contains \Drupal\image\Tests\Views\RelationshipUserImageDataTest.
 */

namespace Drupal\image\Tests\Views;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests image on user relationship handler.
 *
 * @group image
 */
class RelationshipUserImageDataTest extends ViewTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('image', 'image_test_views', 'user');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_image_user_image_data');

  protected function setUp() {
    parent::setUp();

    // Create the user profile field and instance.
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'user_picture',
      'type' => 'image',
      'translatable' => '0',
    ))->save();
    entity_create('field_config', array(
      'label' => 'User Picture',
      'description' => '',
      'field_name' => 'user_picture',
      'entity_type' => 'user',
      'bundle' => 'user',
      'required' => 0,
    ))->save();

    ViewTestData::createTestViews(get_class($this), array('image_test_views'));
  }

  /**
   * Tests using the views image relationship.
   */
  public function testViewsHandlerRelationshipUserImageData() {
    $file = entity_create('file', array(
      'fid' => 2,
      'uid' => 2,
      'filename' => 'image-test.jpg',
      'uri' => "public://image-test.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ));
    $file->enforceIsNew();
    file_put_contents($file->getFileUri(), file_get_contents('core/modules/simpletest/files/image-1.png'));
    $file->save();

    $account = $this->drupalCreateUser();
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
    $this->assertIdentical($expected, $view->calculateDependencies());
    $this->executeView($view);
    $expected_result = array(
      array(
        'file_managed_user__user_picture_fid' => '2',
      ),
    );
    $column_map = array('file_managed_user__user_picture_fid' => 'file_managed_user__user_picture_fid');
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

}
