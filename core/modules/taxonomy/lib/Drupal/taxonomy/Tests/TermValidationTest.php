<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\TermValidationTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests term validation constraints.
 */
class TermValidationTest extends EntityUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  public static function getInfo() {
    return array(
      'name' => 'Term Validation',
      'description' => 'Tests the term validation constraints.',
      'group' => 'Taxonomy',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('taxonomy', array('taxonomy_term_data'));
  }

  /**
   * Tests the term validation constraints.
   */
  public function testValidation() {
    $this->entityManager->getStorage('taxonomy_vocabulary')->create(array(
      'vid' => 'tags',
      'name' => 'Tags',
    ))->save();
    $term = $this->entityManager->getStorage('taxonomy_term')->create(array(
      'name' => 'test',
      'vid' => 'tags',
    ));
    $violations = $term->validate();
    $this->assertEqual(count($violations), 0, 'No violations when validating a default term.');

    $term->set('name', $this->randomString(256));
    $violations = $term->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when name is too long.');
    $this->assertEqual($violations[0]->getPropertyPath(), 'name.0.value');
    $field_label = $term->get('name')->getFieldDefinition()->getLabel();
    $this->assertEqual($violations[0]->getMessage(), t('%name: may not be longer than @max characters.', array('%name' => $field_label, '@max' => 255)));

    $term->set('name', NULL);
    $violations = $term->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when name is NULL.');
    $this->assertEqual($violations[0]->getPropertyPath(), 'name');
    $this->assertEqual($violations[0]->getMessage(), t('This value should not be null.'));
    $term->set('name', 'test');

    $term->set('parent', 9999);
    $violations = $term->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when term parent is invalid.');
    $this->assertEqual($violations[0]->getMessage(), format_string('%id is not a valid parent for this term.', array('%id' => 9999)));
  }
}
