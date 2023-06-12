<?php

namespace Drupal\Tests\system\Functional\Entity;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\entity_test\Entity\EntityTestWithBundle;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the /add and /add/{type} controllers.
 *
 * @group entity
 */
class EntityAddUITest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the add page for an entity type using bundle entities.
   */
  public function testAddPageWithBundleEntities() {
    $admin_user = $this->drupalCreateUser([
      'administer entity_test_with_bundle content',
    ]);
    $this->drupalLogin($admin_user);

    // Users without create access for bundles do not have access to the add
    // page if there are no bundles.
    $this->drupalGet('/entity_test_with_bundle/add');
    $this->assertSession()->statusCodeEquals(403);

    $bundle_admin_user = $this->drupalCreateUser([
      'administer entity_test_with_bundle content',
      'administer entity_test_bundle content',
    ]);
    $this->drupalLogin($bundle_admin_user);

    // No bundles exist, the add bundle message should be present as the user
    // has the necessary permissions.
    $this->drupalGet('/entity_test_with_bundle/add');
    $this->assertSession()->pageTextContains('There is no test entity bundle yet.');
    $this->assertSession()->linkExists('Add a new test entity bundle.');

    // One bundle exists, confirm redirection to the add-form.
    EntityTestBundle::create([
      'id' => 'test',
      'label' => 'Test label',
      'description' => 'My test description',
    ])->save();
    $this->drupalGet('/entity_test_with_bundle/add');
    $this->assertSession()->addressEquals('/entity_test_with_bundle/add/test');

    // Two bundles exist, confirm both are shown.
    EntityTestBundle::create([
      'id' => 'test2',
      'label' => 'Test2 label',
      'description' => 'My test2 description',
    ])->save();
    $this->drupalGet('/entity_test_with_bundle/add');

    $this->assertSession()->linkExists('Test label');
    $this->assertSession()->linkExists('Test2 label');
    $this->assertSession()->pageTextContains('My test description');
    $this->assertSession()->pageTextContains('My test2 description');

    $this->clickLink('Test2 label');
    $this->drupalGet('/entity_test_with_bundle/add/test2');

    $this->submitForm(['name[0][value]' => 'test name'], 'Save');
    $entity = EntityTestWithBundle::load(1);
    $this->assertEquals('test name', $entity->label());

    // Create a new user that only has bundle specific permissions.
    $user = $this->drupalCreateUser([
      'create test entity_test_with_bundle entities',
      'create test2 entity_test_with_bundle entities',
    ]);
    $this->drupalLogin($user);

    // Create a bundle that the user does not have create permissions for.
    EntityTestBundle::create([
      'id' => 'test3',
      'label' => 'Test3 label',
      'description' => 'My test3 description',
    ])->save();

    // Create a bundle that the user is forbidden from creating (always).
    EntityTestBundle::create([
      'id' => 'forbidden_access_bundle',
      'label' => 'Forbidden to create bundle',
      'description' => 'A bundle that can never be created',
    ])->save();

    $this->drupalGet('/entity_test_with_bundle/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('Test label');
    $this->assertSession()->linkExists('Test2 label');
    $this->assertSession()->linkNotExists('Forbidden to create bundle');
    $this->assertSession()->linkNotExists('Test3 label');
    $this->clickLink('Test label');
    $this->assertSession()->statusCodeEquals(200);

    // Without any permissions, access must be denied.
    $this->drupalLogout();
    $this->drupalGet('/entity_test_with_bundle/add');
    $this->assertSession()->statusCodeEquals(403);

    // Create a new user that has bundle create permissions.
    $user = $this->drupalCreateUser([
      'administer entity_test_bundle content',
    ]);
    $this->drupalLogin($user);

    // User has access to the add page but no bundles are shown because the user
    // does not have bundle specific permissions. The add bundle message is
    // present as the user has bundle create permissions.
    $this->drupalGet('/entity_test_with_bundle/add');
    $this->assertSession()->linkNotExists('Forbidden to create bundle');
    $this->assertSession()->linkNotExists('Test label');
    $this->assertSession()->linkNotExists('Test2 label');
    $this->assertSession()->linkNotExists('Test3 label');
    $this->assertSession()->linkExists('Add a new test entity bundle.');
  }

  /**
   * Tests the add page for an entity type not using bundle entities.
   */
  public function testAddPageWithoutBundleEntities() {
    $admin_user = $this->drupalCreateUser([
      'administer entity_test content',
    ]);
    $this->drupalLogin($admin_user);

    entity_test_create_bundle('test', 'Test label', 'entity_test_mul');
    // Delete the default bundle, so that we can rely on our own.
    entity_test_delete_bundle('entity_test_mul', 'entity_test_mul');

    // One bundle exists, confirm redirection to the add-form.
    $this->drupalGet('/entity_test_mul/add');
    $this->assertSession()->addressEquals('/entity_test_mul/add/test');

    // Two bundles exist, confirm both are shown.
    entity_test_create_bundle('test2', 'Test2 label', 'entity_test_mul');
    $this->drupalGet('/entity_test_mul/add');

    $this->assertSession()->linkExists('Test label');
    $this->assertSession()->linkExists('Test2 label');

    $this->clickLink('Test2 label');
    $this->drupalGet('/entity_test_mul/add/test2');

    $this->submitForm(['name[0][value]' => 'test name'], 'Save');
    $entity = EntityTestMul::load(1);
    $this->assertEquals('test name', $entity->label());
  }

}
