<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests loading, saving and deleting vocabularies.
 *
 * @group taxonomy
 */
class VocabularyCrudTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_test', 'taxonomy_crud'];

  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(['create article content', 'administer taxonomy']);
    $this->drupalLogin($admin_user);
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Test deleting a taxonomy that contains terms.
   */
  public function testTaxonomyVocabularyDeleteWithTerms() {
    // Delete any existing vocabularies.
    foreach (Vocabulary::loadMultiple() as $vocabulary) {
      $vocabulary->delete();
    }
    $query = \Drupal::entityQuery('taxonomy_term')->count();

    // Assert that there are no terms left.
    $this->assertEqual(0, $query->execute(), 'There are no terms remaining.');

    $terms = [];
    for ($i = 0; $i < 5; $i++) {
      $terms[$i] = $this->createTerm($vocabulary);
    }

    // Set up hierarchy. term 2 is a child of 1 and 4 a child of 1 and 2.
    $terms[2]->parent = [$terms[1]->id()];
    $terms[2]->save();
    $terms[4]->parent = [$terms[1]->id(), $terms[2]->id()];
    $terms[4]->save();

    // Assert that there are now 5 terms.
    $this->assertEqual(5, $query->execute(), 'There are 5 terms found.');

    $vocabulary->delete();

    // Assert that there are no terms left.
    $this->assertEqual(0, $query->execute(), 'All terms are deleted.');
  }

  /**
   * Ensure that the vocabulary static reset works correctly.
   */
  public function testTaxonomyVocabularyLoadStaticReset() {
    $original_vocabulary = Vocabulary::load($this->vocabulary->id());
    $this->assertTrue(is_object($original_vocabulary), 'Vocabulary loaded successfully.');
    $this->assertEqual($this->vocabulary->label(), $original_vocabulary->label(), 'Vocabulary loaded successfully.');

    // Change the name and description.
    $vocabulary = $original_vocabulary;
    $vocabulary->set('name', $this->randomMachineName());
    $vocabulary->set('description', $this->randomMachineName());
    $vocabulary->save();

    // Load the vocabulary.
    $new_vocabulary = Vocabulary::load($original_vocabulary->id());
    $this->assertEqual($new_vocabulary->label(), $vocabulary->label(), 'The vocabulary was loaded.');

    // Delete the vocabulary.
    $this->vocabulary->delete();
    $vocabularies = Vocabulary::loadMultiple();
    $this->assertTrue(!isset($vocabularies[$this->vocabulary->id()]), 'The vocabulary was deleted.');
  }

  /**
   * Tests for loading multiple vocabularies.
   */
  public function testTaxonomyVocabularyLoadMultiple() {

    // Delete any existing vocabularies.
    foreach (Vocabulary::loadMultiple() as $vocabulary) {
      $vocabulary->delete();
    }

    // Create some vocabularies and assign weights.
    $vocabulary1 = $this->createVocabulary();
    $vocabulary1->set('weight', 0);
    $vocabulary1->save();
    $vocabulary2 = $this->createVocabulary();
    $vocabulary2->set('weight', 1);
    $vocabulary2->save();
    $vocabulary3 = $this->createVocabulary();
    $vocabulary3->set('weight', 2);
    $vocabulary3->save();

    // Check if third party settings exist.
    $this->assertEqual('bar', $vocabulary1->getThirdPartySetting('taxonomy_crud', 'foo'), 'Third party settings were added to the vocabulary.');
    $this->assertEqual('bar', $vocabulary2->getThirdPartySetting('taxonomy_crud', 'foo'), 'Third party settings were added to the vocabulary.');
    $this->assertEqual('bar', $vocabulary3->getThirdPartySetting('taxonomy_crud', 'foo'), 'Third party settings were added to the vocabulary.');

    // Fetch the names for all vocabularies, confirm that they are keyed by
    // machine name.
    $names = taxonomy_vocabulary_get_names();
    $this->assertEqual($names[$vocabulary1->id()], $vocabulary1->id(), 'Vocabulary 1 name found.');

    // Fetch the vocabularies with entity_load_multiple(), specifying IDs.
    // Ensure they are returned in the same order as the original array.
    $vocabularies = Vocabulary::loadMultiple([$vocabulary3->id(), $vocabulary2->id(), $vocabulary1->id()]);
    $loaded_order = array_keys($vocabularies);
    $expected_order = [$vocabulary3->id(), $vocabulary2->id(), $vocabulary1->id()];
    $this->assertIdentical($loaded_order, $expected_order);

    // Test loading vocabularies by their properties.
    $controller = $this->container->get('entity_type.manager')->getStorage('taxonomy_vocabulary');
    // Fetch vocabulary 1 by name.
    $vocabulary = current($controller->loadByProperties(['name' => $vocabulary1->label()]));
    $this->assertEqual($vocabulary->id(), $vocabulary1->id(), 'Vocabulary loaded successfully by name.');

    // Fetch vocabulary 2 by name and ID.
    $vocabulary = current($controller->loadByProperties([
      'name' => $vocabulary2->label(),
      'vid' => $vocabulary2->id(),
    ]));
    $this->assertEqual($vocabulary->id(), $vocabulary2->id(), 'Vocabulary loaded successfully by name and ID.');
  }

  /**
   * Test uninstall and reinstall of the taxonomy module.
   */
  public function testUninstallReinstall() {
    // Field storages and fields attached to taxonomy term bundles should be
    // removed when the module is uninstalled.
    $field_name = mb_strtolower($this->randomMachineName() . '_field_name');
    $storage_definition = [
      'field_name' => $field_name,
      'entity_type' => 'taxonomy_term',
      'type' => 'text',
      'cardinality' => 4,
    ];
    FieldStorageConfig::create($storage_definition)->save();
    $field_definition = [
      'field_name' => $field_name,
      'entity_type' => 'taxonomy_term',
      'bundle' => $this->vocabulary->id(),
      'label' => $this->randomMachineName() . '_label',
    ];
    FieldConfig::create($field_definition)->save();

    // Remove the third party setting from the memory copy of the vocabulary.
    // We keep this invalid copy around while the taxonomy module is not even
    // installed for testing below.
    $this->vocabulary->unsetThirdPartySetting('taxonomy_crud', 'foo');

    require_once $this->root . '/core/includes/install.inc';
    $this->container->get('module_installer')->uninstall(['taxonomy']);
    $this->container->get('module_installer')->install(['taxonomy']);

    // Now create a vocabulary with the same name. All fields
    // connected to this vocabulary name should have been removed when the
    // module was uninstalled. Creating a new field with the same name and
    // an instance of this field on the same bundle name should be successful.
    $this->vocabulary->enforceIsNew();
    $this->vocabulary->save();
    FieldStorageConfig::create($storage_definition)->save();
    FieldConfig::create($field_definition)->save();
  }

}
