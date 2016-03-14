<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityAddUITest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\entity_test\Entity\EntityTestWithBundle;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the /add and /add/{type} controllers.
 *
 * @group entity
 */
class EntityAddUITest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser([
      "administer entity_test_with_bundle content",
      "administer entity_test content",
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the add page for an entity type using bundle entities.
   */
  public function testAddPageWithBundleEntities() {
    $this->drupalGet('/entity_test_with_bundle/add');
    // No bundles exist, the add bundle message should be present.
    $this->assertText('There is no test entity bundle yet.');
    $this->assertLink('Add a new test entity bundle.');

    // One bundle exists, confirm redirection to the add-form.
    EntityTestBundle::create([
      'id' => 'test',
      'label' => 'Test label',
      'description' => 'My test description',
    ])->save();
    $this->drupalGet('/entity_test_with_bundle/add');
    $this->assertUrl('/entity_test_with_bundle/add/test');

    // Two bundles exist, confirm both are shown.
    EntityTestBundle::create([
      'id' => 'test2',
      'label' => 'Test2 label',
      'description' => 'My test2 description',
    ])->save();
    $this->drupalGet('/entity_test_with_bundle/add');

    $this->assertLink('Test label');
    $this->assertLink('Test2 label');
    $this->assertText('My test description');
    $this->assertText('My test2 description');

    $this->clickLink('Test2 label');
    $this->drupalGet('/entity_test_with_bundle/add/test2');

    $this->drupalPostForm(NULL, ['name[0][value]' => 'test name'], t('Save'));
    $entity = EntityTestWithBundle::load(1);
    $this->assertEqual('test name', $entity->label());
  }

  /**
   * Tests the add page for an entity type not using bundle entities.
   */
  public function testAddPageWithoutBundleEntities() {
    entity_test_create_bundle('test', 'Test label', 'entity_test_mul');
    // Delete the default bundle, so that we can rely on our own.
    entity_test_delete_bundle('entity_test_mul', 'entity_test_mul');

    // One bundle exists, confirm redirection to the add-form.
    $this->drupalGet('/entity_test_mul/add');
    $this->assertUrl('/entity_test_mul/add/test');

    // Two bundles exist, confirm both are shown.
    entity_test_create_bundle('test2', 'Test2 label', 'entity_test_mul');
    $this->drupalGet('/entity_test_mul/add');

    $this->assertLink('Test label');
    $this->assertLink('Test2 label');

    $this->clickLink('Test2 label');
    $this->drupalGet('/entity_test_mul/add/test2');

    $this->drupalPostForm(NULL, ['name[0][value]' => 'test name'], t('Save'));
    $entity = EntityTestMul::load(1);
    $this->assertEqual('test name', $entity->label());
  }

}
