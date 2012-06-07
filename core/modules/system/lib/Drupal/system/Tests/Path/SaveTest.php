<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Path\SaveTest.
 */

namespace Drupal\system\Tests\Path;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the path_save() function.
 */
class SaveTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => t('Path save'),
      'description' => t('Tests that path_save() exposes the previous alias value.'),
      'group' => t('Path API'),
    );
  }

  function setUp() {
    // Enable a helper module that implements hook_path_update().
    parent::setUp('path_test');
    path_test_reset();
  }

  /**
   * Tests that path_save() makes the original path available to modules.
   */
  function testDrupalSaveOriginalPath() {
    $account = $this->drupalCreateUser();
    $uid = $account->uid;
    $name = $account->name;

    // Create a language-neutral alias.
    $path = array(
      'source' => "user/$uid",
      'alias' => 'foo',
    );
    $path_original = $path;
    path_save($path);

    // Alter the path.
    $path['alias'] = 'bar';
    path_save($path);

    // Test to see if the original alias is available to modules during
    // hook_path_update().
    $results = variable_get('path_test_results', array());
    $this->assertIdentical($results['hook_path_update']['original']['alias'], $path_original['alias'], t('Old path alias available to modules during hook_path_update.'));
    $this->assertIdentical($results['hook_path_update']['original']['source'], $path_original['source'], t('Old path alias available to modules during hook_path_update.'));
  }
}
