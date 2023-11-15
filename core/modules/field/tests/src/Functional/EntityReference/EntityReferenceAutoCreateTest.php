<?php

namespace Drupal\Tests\field\Functional\EntityReference;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests creating new entity (e.g. taxonomy-term) from an autocomplete widget.
 *
 * @group entity_reference
 */
class EntityReferenceAutoCreateTest extends BrowserTestBase {

  use EntityReferenceFieldCreationTrait;

  protected static $modules = ['node', 'taxonomy', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The name of a content type that will reference $referencedType.
   *
   * @var string
   */
  protected $referencingType;

  /**
   * The name of a content type that will be referenced by $referencingType.
   *
   * @var string
   */
  protected $referencedType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create "referencing" and "referenced" node types.
    $referencing = $this->drupalCreateContentType();
    $this->referencingType = $referencing->id();

    $referenced = $this->drupalCreateContentType();
    $this->referencedType = $referenced->id();

    FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'translatable' => FALSE,
      'entity_types' => [],
      'settings' => [
        'target_type' => 'node',
      ],
      'type' => 'entity_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();

    FieldConfig::create([
      'label' => 'Entity reference field',
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'bundle' => $referencing->id(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          // Reference a single vocabulary.
          'target_bundles' => [
            $referenced->id(),
          ],
          // Enable auto-create.
          'auto_create' => TRUE,
        ],
      ],
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getViewDisplay('node', $referencing->id())
      ->setComponent('test_field')
      ->save();
    $display_repository->getFormDisplay('node', $referencing->id(), 'default')
      ->setComponent('test_field', [
        'type' => 'entity_reference_autocomplete',
      ])
      ->save();

    $account = $this->drupalCreateUser([
      'access content',
      "create $this->referencingType content",
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests the autocomplete input element and entity auto-creation.
   */
  public function testAutoCreate() {
    $this->drupalGet('node/add/' . $this->referencingType);
    $target = $this->assertSession()->fieldExists("edit-test-field-0-target-id");
    $this->assertTrue($target->hasClass("form-autocomplete"));

    $new_title = $this->randomMachineName();

    // Assert referenced node does not exist.
    $base_query = \Drupal::entityQuery('node')->accessCheck(FALSE);
    $base_query
      ->condition('type', $this->referencedType)
      ->condition('title', $new_title);

    $query = clone $base_query;
    $result = $query->execute();
    $this->assertEmpty($result, 'Referenced node does not exist yet.');

    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'test_field[0][target_id]' => $new_title,
    ];
    $this->drupalGet("node/add/{$this->referencingType}");
    $this->submitForm($edit, 'Save');

    // Assert referenced node was created.
    $query = clone $base_query;
    $result = $query->execute();
    $this->assertNotEmpty($result, 'Referenced node was created.');
    $referenced_nid = key($result);
    $referenced_node = Node::load($referenced_nid);

    // Assert the referenced node is associated with referencing node.
    $result = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', $this->referencingType)
      ->execute();

    $referencing_nid = key($result);
    $referencing_node = Node::load($referencing_nid);
    $this->assertEquals($referenced_nid, $referencing_node->test_field->target_id, 'Newly created node is referenced from the referencing node.');

    // Now try to view the node and check that the referenced node is shown.
    $this->drupalGet('node/' . $referencing_node->id());
    $this->assertSession()->pageTextContains($referencing_node->label());
    $this->assertSession()->pageTextContains($referenced_node->label());
  }

  /**
   * Tests multiple target bundles.
   *
   * Tests if an entity reference field having multiple target bundles is
   * storing the auto-created entity in the right destination.
   */
  public function testMultipleTargetBundles() {
    /** @var \Drupal\taxonomy\Entity\Vocabulary[] $vocabularies */
    $vocabularies = [];
    for ($i = 0; $i < 2; $i++) {
      $vid = $this->randomMachineName();
      $vocabularies[$i] = Vocabulary::create([
        'name' => $this->randomMachineName(),
        'vid' => $vid,
      ]);
      $vocabularies[$i]->save();
    }

    // Create a taxonomy term entity reference field that saves the auto-created
    // taxonomy terms in the second vocabulary from the two that were configured
    // as targets.
    $field_name = $this->randomMachineName();
    $handler_settings = [
      'target_bundles' => [
        $vocabularies[0]->id() => $vocabularies[0]->id(),
        $vocabularies[1]->id() => $vocabularies[1]->id(),
      ],
      'auto_create' => TRUE,
      'auto_create_bundle' => $vocabularies[1]->id(),
    ];
    $this->createEntityReferenceField('node', $this->referencingType, $field_name, $this->randomString(), 'taxonomy_term', 'default', $handler_settings);
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $fd */
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', $this->referencingType)
      ->setComponent($field_name, ['type' => 'entity_reference_autocomplete'])
      ->save();

    $term_name = $this->randomString();
    $edit = [
      $field_name . '[0][target_id]' => $term_name,
      'title[0][value]' => $this->randomString(),
    ];

    $this->drupalGet('node/add/' . $this->referencingType);
    $this->submitForm($edit, 'Save');

    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = $term_storage->loadByProperties(['name' => $term_name]);
    $term = reset($term);

    // The new term is expected to be stored in the second vocabulary.
    $this->assertEquals($vocabularies[1]->id(), $term->bundle());

    /** @var \Drupal\field\Entity\FieldConfig $field_config */
    $field_config = FieldConfig::loadByName('node', $this->referencingType, $field_name);
    $handler_settings = $field_config->getSetting('handler_settings');

    // Change the field setting to store the auto-created terms in the first
    // vocabulary and test again.
    $handler_settings['auto_create_bundle'] = $vocabularies[0]->id();
    $field_config->setSetting('handler_settings', $handler_settings);
    $field_config->save();

    $term_name = $this->randomString();
    $edit = [
      $field_name . '[0][target_id]' => $term_name,
      'title[0][value]' => $this->randomString(),
    ];

    $this->drupalGet('node/add/' . $this->referencingType);
    $this->submitForm($edit, 'Save');
    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = $term_storage->loadByProperties(['name' => $term_name]);
    $term = reset($term);

    // The second term is expected to be stored in the first vocabulary.
    $this->assertEquals($vocabularies[0]->id(), $term->bundle());

    // @todo Re-enable this test when WebTestBase::curlHeaderCallback() provides
    //   a way to catch and assert user-triggered errors.

    // Test the case when the field config settings are inconsistent.
    // @code
    // unset($handler_settings['auto_create_bundle']);
    // $field_config->setSetting('handler_settings', $handler_settings);
    // $field_config->save();
    //
    // $this->drupalGet('node/add/' . $this->referencingType);
    // $error_message = sprintf(
    //   "Create referenced entities if they don't already exist option is enabled but a specific destination bundle is not set. You should re-visit and fix the settings of the '%s' (%s) field.",
    //   $field_config->getLabel(),
    //   $field_config->getName()
    // );
    // $this->assertErrorLogged($error_message);
    // @endcode
  }

  /**
   * Tests autocreation for an entity that has no bundles.
   */
  public function testNoBundles() {
    $account = $this->drupalCreateUser([
      'access content',
      "create $this->referencingType content",
      'administer entity_test content',
    ]);
    $this->drupalLogin($account);

    $field_name = $this->randomMachineName();
    $handler_settings = [
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', $this->referencingType, $field_name, $this->randomString(), 'entity_test_no_bundle_with_label', 'default', $handler_settings);
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', $this->referencingType)
      ->setComponent($field_name, ['type' => 'entity_reference_autocomplete'])
      ->save();

    $node_title = $this->randomMachineName();
    $name = $this->randomMachineName();
    $edit = [
      $field_name . '[0][target_id]' => $name,
      'title[0][value]' => $node_title,
    ];

    $this->drupalGet('node/add/' . $this->referencingType);
    $this->submitForm($edit, 'Save');

    // Assert referenced entity was created.
    $result = \Drupal::entityQuery('entity_test_no_bundle_with_label')
      ->accessCheck(FALSE)
      ->condition('name', $name)
      ->execute();
    $this->assertNotEmpty($result, 'Referenced entity was created.');
    $referenced_id = key($result);

    // Assert the referenced entity is associated with referencing node.
    $result = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', $this->referencingType)
      ->execute();
    $this->assertCount(1, $result);
    $referencing_nid = key($result);
    $referencing_node = Node::load($referencing_nid);
    $this->assertEquals($referenced_id, $referencing_node->$field_name->target_id, 'Newly created node is referenced from the referencing entity.');
  }

}
