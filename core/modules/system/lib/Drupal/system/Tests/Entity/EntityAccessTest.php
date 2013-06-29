<?php

/**
 * @file
 * Contains Drupal\system\Tests\Entity\EntityAccessTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\AccessibleInterface;
use Drupal\Core\Entity\EntityAccessController;

/**
 * Tests the entity access controller.
 */
class EntityAccessTest extends EntityUnitTestBase  {

  public static $modules = array('language', 'locale');

  public static function getInfo() {
    return array(
      'name' => 'Entity access',
      'description' => 'Tests entity access.',
      'group' => 'Entity API',
    );
  }

  function setUp() {
    parent::setUp();
    $this->installSchema('user', array('users_roles'));
    $this->installSchema('system', array('variable', 'url_alias'));
    $this->installConfig(array('language'));

    // Create the default languages.
    $default_language = language_save(language_default());
    $languages = language_default_locked_languages($default_language->weight);
    foreach ($languages as $language) {
      language_save($language);
    }

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
    global $user;
    $user = $this->createUser(array('uid' => 2), array('view test entity'));
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
    global $user;
    $user = $this->createUser(array('uid' => 2));

    // Check that the default access controller is used for entities that don't
    // have a specific access controller defined.
    $controller = $this->container->get('plugin.manager.entity')->getAccessController('entity_test_default_access');
    $this->assertTrue($controller instanceof EntityAccessController, 'The default entity controller is used for the entity_test_default_access entity type.');

    $entity = entity_create('entity_test_default_access', array());
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
    global $user;
    $user = $this->createUser(array('uid' => 2), array('view test entity translations'));

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
}
