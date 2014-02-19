<?php

/**
 * @file
 * Definition of Drupal\Core\Entity\Tests\EntityQueryRelationshipTest.
 */

namespace Drupal\system\Tests\Entity;

/**
 * Tests Entity Query API relationship functionality.
 */
class EntityQueryRelationshipTest extends EntityUnitTestBase  {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'options');

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $factory;

  /**
   * Term entities.
   *
   * @var array
   */
  protected $terms;

  /**
   * User entities.
   *
   * @var array
   */
  public $accounts;

  /**
   * entity_test entities.
   *
   * @var array
   */
  protected $entities;

  /**
   * The name of the taxonomy field used for test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The results returned by EntityQuery.
   *
   * @var array
   */
  protected $queryResults;

  public static function getInfo() {
    return array(
      'name' => 'Entity Query relationship',
      'description' => 'Tests the Entity Query relationship API',
      'group' => 'Entity API',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->installSchema('taxonomy', array('taxonomy_term_data', 'taxonomy_term_hierarchy'));

    // We want a taxonomy term reference field. It needs a vocabulary, terms,
    // a field and an instance. First, create the vocabulary.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'vid' => drupal_strtolower($this->randomName()),
    ));
    $vocabulary->save();
    // Second, create the field.
    $this->fieldName = strtolower($this->randomName());
    $field = array(
      'name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'taxonomy_term_reference',
    );
    $field['settings']['allowed_values']['vocabulary'] = $vocabulary->id();
    entity_create('field_config', $field)->save();
    entity_test_create_bundle('test_bundle');
    // Third, create the instance.
    entity_create('field_instance_config', array(
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'bundle' => 'test_bundle',
    ))->save();
    // Create two terms and also two accounts.
    for ($i = 0; $i <= 1; $i++) {
      $term = entity_create('taxonomy_term', array(
        'name' => $this->randomName(),
        'vid' => $vocabulary->id(),
      ));
      $term->save();
      $this->terms[] = $term;
      $this->accounts[] = $this->createUser();
    }
    // Create three entity_test entities, the 0th entity will point to the
    // 0th account and 0th term, the 1st and 2nd entity will point to the
    // 1st account and 1st term.
    for ($i = 0; $i <= 2; $i++) {
      $entity = entity_create('entity_test', array('type' => 'test_bundle'));
      $entity->name->value = $this->randomName();
      $index = $i ? 1 : 0;
      $entity->user_id->target_id = $this->accounts[$index]->id();
      $entity->{$this->fieldName}->target_id = $this->terms[$index]->id();
      $entity->save();
      $this->entities[] = $entity;
    }
    $this->factory = \Drupal::service('entity.query');
  }

  /**
   * Tests querying.
   */
  public function testQuery() {
    // This returns the 0th entity as that's only one pointing to the 0th
    // account.
    $this->queryResults = $this->factory->get('entity_test')
      ->condition("user_id.entity.name", $this->accounts[0]->getUsername())
      ->execute();
    $this->assertResults(array(0));
    // This returns the 1st and 2nd entity as those point to the 1st account.
    $this->queryResults = $this->factory->get('entity_test')
      ->condition("user_id.entity.name", $this->accounts[0]->getUsername(), '<>')
      ->execute();
    $this->assertResults(array(1, 2));
    // This returns all three entities because all of them point to an
    // account.
    $this->queryResults = $this->factory->get('entity_test')
      ->exists("user_id.entity.name")
      ->execute();
    $this->assertResults(array(0, 1, 2));
    // This returns no entities because all of them point to an account.
    $this->queryResults = $this->factory->get('entity_test')
      ->notExists("user_id.entity.name")
      ->execute();
    $this->assertEqual(count($this->queryResults), 0);
    // This returns the 0th entity as that's only one pointing to the 0th
    // term (test without specifying the field column).
    $this->queryResults = $this->factory->get('entity_test')
      ->condition("$this->fieldName.entity.name", $this->terms[0]->name->value)
      ->execute();
    $this->assertResults(array(0));
    // This returns the 0th entity as that's only one pointing to the 0th
    // term (test with specifying the column name).
    $this->queryResults = $this->factory->get('entity_test')
      ->condition("$this->fieldName.target_id.entity.name", $this->terms[0]->name->value)
      ->execute();
    $this->assertResults(array(0));
    // This returns the 1st and 2nd entity as those point to the 1st term.
    $this->queryResults = $this->factory->get('entity_test')
      ->condition("$this->fieldName.entity.name", $this->terms[0]->name->value, '<>')
      ->execute();
    $this->assertResults(array(1, 2));
  }

  /**
   * Assert the results.
   *
   * @param array $expected
   *   A list of indexes in the $this->entities array.
   */
  protected function assertResults($expected) {
    $this->assertEqual(count($this->queryResults), count($expected));
    foreach ($expected as $key) {
      $id = $this->entities[$key]->id();
      $this->assertEqual($this->queryResults[$id], $id);
    }
  }
}
