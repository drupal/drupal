<?php

/**
 * @file
 * Contains Drupal\system\Tests\Entity\EntityAccessTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Language\Language;
use Drupal\Core\TypedData\AccessibleInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\user\Plugin\Core\Entity\User;
use Drupal\Core\Entity\EntityAccessController;

/**
 * Tests the entity access controller.
 */
class EntityAccessTest extends WebTestBase  {

  public static function getInfo() {
    return array(
      'name' => 'Entity access',
      'description' => 'Tests entity access.',
      'group' => 'Entity API',
    );
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  /**
   * Asserts entity access correctly grants or denies access.
   */
  function assertEntityAccess($ops, AccessibleInterface $object, User $account = NULL) {
    foreach ($ops as $op => $result) {
      $message = format_string("Entity access returns @result with operation '@op'.", array(
        '@result' => isset($result) ? 'null' : ($result ? 'true' : 'false'),
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
    $user = $this->drupalCreateUser(array('view test entity'));
    $this->drupalLogin($user);

    $entity = entity_create('entity_test', array(
      'name' => 'test',
    ));
    $entity->save();

    // The current user is allowed to view, create, update and delete entities.
    $this->assertEntityAccess(array(
      'create' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
      'view' => TRUE,
    ), $entity);

    // The custom user is not allowed to view test entities.
    $custom_user = $this->drupalCreateUser();
    $this->assertEntityAccess(array(
      'create' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
      'view' => FALSE,
    ), $entity, $custom_user);
  }

  /**
   * Ensures that the default controller is used as a fallback.
   */
  function testEntityAccessDefaultController() {
    // Check that the default access controller is used for entities that don't
    // have a specific access controller defined.
    $controller = entity_access_controller('entity_test_default_access');
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
    // Enable translations for the test entity type.
    variable_set('entity_test_translation', TRUE);
    module_enable(array('locale'));

    // Set up a non-admin user that is allowed to view test entity translations.
    $user = $this->drupalCreateUser(array('view test entity translations'));
    $this->drupalLogin($user);

    // Create two test languages.
    foreach (array('foo', 'bar') as $langcode) {
      $language = new Language(array(
        'langcode' => $langcode,
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
