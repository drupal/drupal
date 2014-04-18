<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceAutocompleteTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Tags;
use Drupal\entity_reference\EntityReferenceController;
use Drupal\system\Tests\Entity\EntityUnitTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Tests the autocomplete functionality of Entity Reference.
 */
class EntityReferenceAutocompleteTest extends EntityUnitTestBase {

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * The name of the field used in this test.
   *
   * @var string
   */
  protected $fieldName = 'field_test';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_reference', 'entity_reference_test');

  public static function getInfo() {
    return array(
      'name' => 'Autocomplete',
      'description' => 'Tests the autocomplete functionality.',
      'group' => 'Entity Reference',
    );
  }

  function setUp() {
    parent::setUp();

    entity_reference_create_instance($this->entityType, $this->bundle, $this->fieldName, 'Field test', $this->entityType);
  }

  /**
   * Tests autocompletion edge cases with slashes in the names.
   */
  function testEntityReferenceAutocompletion() {
    // Add an entity with a slash in its name.
    $entity_1 = entity_create($this->entityType, array('name' => '10/16/2011', $this->fieldName => NULL));
    $entity_1->save();

    // Add another entity that differs after the slash character.
    $entity_2 = entity_create($this->entityType, array('name' => '10/17/2011', $this->fieldName => NULL));
    $entity_2->save();

    // Add another entity that has both a comma and a slash character.
    $entity_3 = entity_create($this->entityType, array('name' => 'label with, and / test', $this->fieldName => NULL));
    $entity_3->save();

    // Try to autocomplete a entity label that matches both entities.
    // We should get both entities in a JSON encoded string.
    $input = '10/';
    $data = $this->getAutocompleteResult('single', $input);
    $this->assertIdentical($data[0]['label'], String::checkPlain($entity_1->name->value), 'Autocomplete returned the first matching entity');
    $this->assertIdentical($data[1]['label'], String::checkPlain($entity_2->name->value), 'Autocomplete returned the second matching entity');

    // Try to autocomplete a entity label that matches the first entity.
    // We should only get the first entity in a JSON encoded string.
    $input = '10/16';
    $data = $this->getAutocompleteResult('single', $input);
    $target = array(
      'value' => $entity_1->name->value . ' (1)',
      'label' => String::checkPlain($entity_1->name->value),
    );
    $this->assertIdentical(reset($data), $target, 'Autocomplete returns only the expected matching entity.');

    // Try to autocomplete a entity label that matches the second entity, and
    // the first entity  is already typed in the autocomplete (tags) widget.
    $input = $entity_1->name->value . ' (1), 10/17';
    $data = $this->getAutocompleteResult('tags', $input);
    $this->assertIdentical($data[0]['label'], String::checkPlain($entity_2->name->value), 'Autocomplete returned the second matching entity');

    // Try to autocomplete a entity label with both a comma and a slash.
    $input = '"label with, and / t';
    $data = $this->getAutocompleteResult('single', $input);
    $n = $entity_3->name->value . ' (3)';
    // Entity labels containing commas or quotes must be wrapped in quotes.
    $n = Tags::encode($n);
    $target = array(
      'value' => $n,
      'label' => String::checkPlain($entity_3->name->value),
    );
    $this->assertIdentical(reset($data), $target, 'Autocomplete returns an entity label containing a comma and a slash.');
  }

  /**
   * Returns the result of an Entity reference autocomplete request.
   *
   * @param string $type
   *   The Entity reference autocomplete type (e.g. 'single', 'tags').
   * @param string $input
   *   The label of the entity to query by.
   *
   * @return mixed
   *  The JSON value encoded in its appropriate PHP type.
   */
  protected function getAutocompleteResult($type, $input) {
    $request = Request::create('entity_reference/autocomplete/' . $type . '/' . $this->fieldName . '/node/article/NULL');
    $request->query->set('q', $input);

    $entity_reference_controller = EntityReferenceController::create($this->container);
    $result = $entity_reference_controller->handleAutocomplete($request, $type, $this->fieldName, $this->entityType, $this->bundle, 'NULL')->getContent();

    return Json::decode($result);
  }

  /**
   * Tests autocomplete for entity base fields.
   */
  public function testBaseField() {
    // Add two users.
    $user_1 = entity_create('user', array('name' => 'auto1'));
    $user_1->save();
    $user_2 = entity_create('user', array('name' => 'auto2'));
    $user_2->save();

    $request = Request::create('entity_reference/autocomplete/single/user_id/entity_test/entity_test/NULL');
    $request->query->set('q', 'auto');

    $entity_reference_controller = EntityReferenceController::create($this->container);
    $result = $entity_reference_controller->handleAutocomplete($request, 'single', 'user_id', 'entity_test', 'entity_test', 'NULL')->getContent();

    $data = Json::decode($result);
    $this->assertIdentical($data[0]['label'], String::checkPlain($user_1->getUsername()), 'Autocomplete returned the first matching entity');
    $this->assertIdentical($data[1]['label'], String::checkPlain($user_2->getUsername()), 'Autocomplete returned the second matching entity');

    // Checks that exception thrown for unknown field.
    try {
      $entity_reference_controller->handleAutocomplete($request, 'single', 'unknown_field', 'entity_test', 'entity_test', 'NULL')->getContent();
      $this->fail('Autocomplete throws exception for unknown field.');
    }
    catch (AccessDeniedHttpException $e) {
      $this->pass('Autocomplete throws exception for unknown field.');
    }
  }

}
