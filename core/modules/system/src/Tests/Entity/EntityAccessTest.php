<?php

/**
 * @file
 * Contains Drupal\system\Tests\Entity\EntityAccessTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Entity\EntityAccessController;

/**
 * Tests the entity access controller.
 */
class EntityAccessTest extends EntityLanguageTestBase  {

  public static function getInfo() {
    return array(
      'name' => 'Entity access',
      'description' => 'Tests entity access.',
      'group' => 'Entity API',
    );
  }

  function setUp() {
    parent::setUp();
    $this->installSchema('system', 'url_alias');
  }

  /**
   * Asserts entity access correctly grants or denies access.
   */
  function assertEntityAccess($ops, AccessibleInterface $object, AccountInterface $account = NULL) {
    foreach ($ops as $op => $result) {
      $message = format_string("Entity access returns @result with operation '@op'.", array(
        '@result' => !isset($result) ? 'null' : ($result ? 'true' : 'false'),
        '@op' => $op,
      ));

      $this->assertEqual($result, $object->access($op, $account), $message);
    }
  }

  /**
   * Ensures entity access is properly working.
   */
  function testEntityAccess() {
    // Set up a non-admin user that is allowed to view test entities.
    \Drupal::currentUser()->setAccount($this->createUser(array('uid' => 2), array('view test entity')));
    $entity = entity_create('entity_test', array(
      'name' => 'test',
    ));

    // The current user is allowed to view entities.
    $this->assertEntityAccess(array(
      'create' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
      'view' => TRUE,
    ), $entity);

    // The custom user is not allowed to perform any operation on test entities.
    $custom_user = $this->createUser();
    $this->assertEntityAccess(array(
      'create' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
      'view' => FALSE,
    ), $entity, $custom_user);
  }

  /**
   * Ensures that the default controller is used as a fallback.
   */
  function testEntityAccessDefaultController() {
    // The implementation requires that the global user id can be loaded.
    \Drupal::currentUser()->setAccount($this->createUser(array('uid' => 2)));

    // Check that the default access controller is used for entities that don't
    // have a specific access controller defined.
    $controller = $this->container->get('entity.manager')->getAccessController('entity_test_default_access');
    $this->assertTrue($controller instanceof EntityAccessController, 'The default entity controller is used for the entity_test_default_access entity type.');

    $entity = entity_create('entity_test_default_access');
    $this->assertEntityAccess(array(
      'create' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
      'view' => FALSE,
    ), $entity);
  }

  /**
   * Ensures entity access for entity translations is properly working.
   */
  function testEntityTranslationAccess() {

    // Set up a non-admin user that is allowed to view test entity translations.
    \Drupal::currentUser()->setAccount($this->createUser(array('uid' => 2), array('view test entity translations')));

    // Create two test languages.
    foreach (array('foo', 'bar') as $langcode) {
      $language = new Language(array(
        'id' => $langcode,
        'name' => $this->randomString(),
      ));
      language_save($language);
    }

    $entity = entity_create('entity_test', array(
      'name' => 'test',
      'langcode' => 'foo',
    ));
    $entity->save();

    $translation = $entity->getTranslation('bar');
    $this->assertEntityAccess(array(
      'view' => TRUE,
    ), $translation);
  }

  /**
   * Tests hook invocations.
   */
  protected function testHooks() {
    $state = $this->container->get('state');
    $entity = entity_create('entity_test', array(
      'name' => 'test',
    ));

    // Test hook_entity_create_access() and hook_ENTITY_TYPE_create_access().
    $entity->access('create');
    $this->assertEqual($state->get('entity_test_entity_create_access'), TRUE);
    $this->assertEqual($state->get('entity_test_entity_test_create_access'), TRUE);

    // Test hook_entity_access() and hook_ENTITY_TYPE_access().
    $entity->access('view');
    $this->assertEqual($state->get('entity_test_entity_access'), TRUE);
    $this->assertEqual($state->get('entity_test_entity_test_access'), TRUE);
  }
}
