<?php

/**
 * @file
 * Contains \Drupal\file\Tests\Views\FileViewsFieldAccessTest.
 */

namespace Drupal\file\Tests\Views;

use Drupal\file\Entity\File;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;
use Drupal\views\Tests\Handler\FieldFieldAccessTestBase;

/**
 * Tests base field access in Views for the file entity.
 *
 * @group File
 */
class FileViewsFieldAccessTest extends FieldFieldAccessTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file', 'entity_test', 'language', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('file');
  }

  /**
   * Check access for file fields.
   */
  public function testFileFields() {
    ConfigurableLanguage::create([
      'id' => 'fr',
      'name' => 'French',
    ])->save();

    $user = User::create([
      'name' => 'test user',
    ]);
    $user->save();

    file_put_contents('public://test.txt', 'test');
    $file = File::create([
      'filename' => 'test.txt',
      'uri' => 'public://test.txt',
      'status' => TRUE,
      'langcode' => 'fr',
      'uid' => $user->id()
    ]);
    $file->save();

    // @todo Expand the test coverage in https://www.drupal.org/node/2464635

    // $this->assertFieldAccess('file', 'fid', $file->id());
    // $this->assertFieldAccess('file', 'uuid', $file->uuid());
    $this->assertFieldAccess('file', 'langcode', $file->language()->getName());
    $this->assertFieldAccess('file', 'uid', 'test user');
    // $this->assertFieldAccess('file', 'filename', $file->getFilename());
    // $this->assertFieldAccess('file', 'uri', $file->getFileUri());
    // $this->assertFieldAccess('file', 'filemime', $file->filemime->value);
    // $this->assertFieldAccess('file', 'size', '4 Bytes');
    // $this->assertFieldAccess('file', 'status', 'On');
    // $this->assertFieldAccess('file', 'created', \Drupal::service('date.formatter')->format(123456));
    // $this->assertFieldAccess('file', 'changed', \Drupal::service('date.formatter')->format(REQUEST_TIME));
  }

}
