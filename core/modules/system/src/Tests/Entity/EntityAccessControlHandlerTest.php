<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityAccessHControlandlerTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the entity access control handler.
 *
 * @group Entity
 */
class EntityAccessControlHandlerTest extends EntityLanguageTestBase  {

  protected function setUp() {
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
   * Ensures default entity access is checked when necessary.
   *
   * This ensures that the default checkAccess() implementation of the
   * entity access control handler is considered if hook_entity_access() has not
   * explicitly forbidden access. Therefore the default checkAccess()
   * implementation can forbid access, even after access was already explicitly
   * allowed by hook_entity_access().
   *
   * @see \Drupal\entity_test\EntityTestAccessControlHandler::checkAccess()
   * @see entity_test_entity_access()
   */
  function testDefaultEntityAccess() {
    // Set up a non-admin user that is allowed to view test entities.
    \Drupal::currentUser()->setAccount($this->createUser(array('uid' => 2), array('view test entity')));
    $entity = entity_create('entity_test', array(
        'name' => 'forbid_access',
      ));

    // The user is denied access to the entity.
    $this->assertEntityAccess(array(
        'create' => FALSE,
        'update' => FALSE,
        'delete' => FALSE,
        'view' => FALSE,
      ), $entity);
  }

  /**
   * Ensures that the default handler is used as a fallback.
   */
  function testEntityAccessDefaultController() {
    // The implementation requires that the global user id can be loaded.
    \Drupal::currentUser()->setAccount($this->createUser(array('uid' => 2)));

    // Check that the default access control handler is used for entities that don't
    // have a specific access control handler defined.
    $handler = $this->container->get('entity.manager')->getAccessControlHandler('entity_test_default_access');
    $this->assertTrue($handler instanceof EntityAccessControlHandler, 'The default entity handler is used for the entity_test_default_access entity type.');

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
      ConfigurableLanguage::create(array(
        'id' => $langcode,
        'label' => $this->randomString(),
      ))->save();
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
  public function testHooks() {
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
