<?php

namespace Drupal\KernelTests\Core\Field;

use Drupal\Core\Access\AccessResult;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests Field level access hooks.
 *
 * @group Entity
 */
class FieldAccessTest extends KernelTestBase {

  /**
   * Modules to load code from.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'field', 'system', 'text', 'filter', 'user'];

  /**
   * Holds the currently active global user ID that initiated the test run.
   *
   * The user ID gets replaced during the test and needs to be kept here so that
   * it can be restored at the end of the test run.
   *
   * @var int
   */
  protected $activeUid;

  protected function setUp() {
    parent::setUp();
    // Install field configuration.
    $this->installConfig(['field']);

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('entity_test_mul_langcode_key');
    $this->installEntitySchema('entity_test_mul_changed');
    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('entity_test_mulrev');
    $this->installEntitySchema('entity_test_mulrev_changed');

    // The users table is needed for creating dummy user accounts.
    $this->installEntitySchema('user');
    // Register entity_test text field.
    module_load_install('entity_test');
    entity_test_install();
  }

  /**
   * Tests hook_entity_field_access() and hook_entity_field_access_alter().
   *
   * @see entity_test_entity_field_access()
   * @see entity_test_entity_field_access_alter()
   */
  public function testFieldAccess() {
    $values = [
      'name' => $this->randomMachineName(),
      'user_id' => 1,
      'field_test_text' => [
        'value' => 'no access value',
        'format' => 'full_html',
      ],
    ];
    $entity = EntityTest::create($values);

    // Create a dummy user account for testing access with.
    $values = ['name' => 'test'];
    $account = User::create($values);

    $this->assertFalse($entity->field_test_text->access('view', $account), 'Access to the field was denied.');
    $expected = AccessResult::forbidden()->addCacheableDependency($entity);
    $this->assertEqual($expected, $entity->field_test_text->access('view', $account, TRUE), 'Access to the field was denied.');

    $entity->field_test_text = 'access alter value';
    $this->assertFalse($entity->field_test_text->access('view', $account), 'Access to the field was denied.');
    $this->assertEqual($expected, $entity->field_test_text->access('view', $account, TRUE), 'Access to the field was denied.');

    $entity->field_test_text = 'standard value';
    $this->assertTrue($entity->field_test_text->access('view', $account), 'Access to the field was granted.');
    $this->assertEqual(AccessResult::allowed(), $entity->field_test_text->access('view', $account, TRUE), 'Access to the field was granted.');
  }

}
