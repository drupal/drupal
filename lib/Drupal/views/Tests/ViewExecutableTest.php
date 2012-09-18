<?php

/**
 * Definition of Drupal\views\Tests\ViewExecutable.
 */

namespace Drupal\views\Tests;

/**
 * Tests the ViewExecutable class.
 *
 * @see Drupal\views\ViewExecutableExecutable
 */
class ViewExecutableTest extends ViewTestBase {

  /**
   * Properties that should be stored in the configuration.
   *
   * @var array
   */
  protected $configProperties = array(
    'disabled',
    'api_version',
    'name',
    'description',
    'tag',
    'base_table',
    'human_name',
    'core',
    'display',
  );

  /**
   * Properties that should be stored in the executable.
   *
   * @var array
   */
  protected $executableProperties = array(
    'build_info'
  );

  public static function getInfo() {
    return array(
      'name' => 'View executable tests',
      'description' => 'Tests the ViewExecutable class.',
      'group' => 'Views'
    );
  }

  /**
   * Tests the generation of the executable object.
   */
  public function testConstructing() {
    $view = $this->getView();
  }

  /**
   * Tests the accessing of values on the object.
   */
  public function testProperties() {
    $view = $this->getView();
    $storage = $view->storage;
    foreach ($this->configProperties as $property) {
      $this->assertTrue(isset($view->{$property}));
      $this->assertIdentical($view->{$property}, $storage->{$storage});
    }
    foreach ($this->executableProperties as $property) {
      $this->assertTrue(isset($view->{$property}));
    }

    // Set one storage property manually on the storage and verify that it is
    // access on the executable.
    $storage->human_name = $this->randomName();
    $this->assertIdentical($view->human_name, $storage->human_name);
  }
}
