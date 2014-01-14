<?php

/**
 * @file
 * Contains \Drupal\field_ui\Tests\FieldUIRouteTest.
 */

namespace Drupal\field_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the functionality of the Field UI route subscriber.
 */
class FieldUIRouteTest extends WebTestBase {

  /**
   * Modules to enable.
   */
  public static $modules = array('field_ui_test', 'node');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Field UI routes',
      'description' => 'Tests the functionality of the Field UI route subscriber.',
      'group' => 'Field UI',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    $this->drupalLogin($this->root_user);
  }

  /**
   * Ensures that entity types with bundles do not break following entity types.
   */
  public function testFieldUIRoutes() {
    $this->drupalGet('field-ui-test-no-bundle/manage/fields');
    // @todo Bring back this assertion in https://drupal.org/node/1963340.
    // @see \Drupal\field_ui\FieldOverview::getRegions()
    //$this->assertText('No fields are present yet.');

    $this->drupalGet('admin/structure/types/manage/article/fields');
    $this->assertTitle('Manage fields | Drupal');
    $this->assertLocalTasks();

    $this->drupalGet('admin/structure/types/manage/article');
    $this->assertLocalTasks();

    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->assertLocalTasks();

    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertLocalTasks();
  }

  /**
   * Asserts that local tasks exists.
   */
  public function assertLocalTasks() {
    $this->assertLink('Edit');
    $this->assertLink('Manage fields');
    $this->assertLink('Manage display');
    $this->assertLink('Manage form display');
  }

}
