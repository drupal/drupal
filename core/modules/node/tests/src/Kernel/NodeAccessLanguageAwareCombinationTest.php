<?php

namespace Drupal\Tests\node\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests node access functionality with multiple languages and two node access
 * modules.
 *
 * @group node
 */
class NodeAccessLanguageAwareCombinationTest extends NodeAccessTestBase {

  /**
   * Enable language and two node access modules.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'node_access_test_language',
    'node_access_test',
  ];

  /**
   * A set of nodes to use in testing.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = [];

  /**
   * A normal authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * User 1.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    node_access_test_add_field(NodeType::load('page'));

    // Create the 'private' field, which allows the node to be marked as private
    // (restricted access) in a given translation.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_private',
      'entity_type' => 'node',
      'type' => 'boolean',
      'cardinality' => 1,
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'widget' => [
        'type' => 'options_buttons',
      ],
      'settings' => [
        'on_label' => 'Private',
        'off_label' => 'Not private',
      ],
    ])->save();

    // After enabling a node access module, the access table has to be rebuild.
    node_access_rebuild();

    // Add Hungarian and Catalan.
    ConfigurableLanguage::createFromLangcode('hu')->save();
    ConfigurableLanguage::createFromLangcode('ca')->save();

    // Create a normal authenticated user.
    $this->webUser = $this->drupalCreateUser(['access content']);

    // Load the user 1 user for later use as an admin user with permission to
    // see everything.
    $this->adminUser = User::load(1);

    // The node_access_test_language module allows individual translations of a
    // node to be marked private (not viewable by normal users), and the
    // node_access_test module allows whole nodes to be marked private. (In a
    // real-world implementation, hook_node_access_records_alter() might be
    // implemented by one or both modules to enforce that private nodes or
    // translations are always private, but we want to test the default,
    // additive behavior of node access).

    // Create six Hungarian nodes with Catalan translations:
    // 1. One public with neither language marked as private.
    // 2. One private with neither language marked as private.
    // 3. One public with only the Hungarian translation private.
    // 4. One public with only the Catalan translation private.
    // 5. One public with both the Hungarian and Catalan translations private.
    // 6. One private with both the Hungarian and Catalan translations private.
    $this->nodes['public_both_public'] = $node = $this->drupalCreateNode([
      'body' => [[]],
      'langcode' => 'hu',
      'field_private' => [['value' => 0]],
      'private' => FALSE,
    ]);
    $translation = $node->addTranslation('ca');
    $translation->title->value = $this->randomString();
    $translation->field_private->value = 0;
    $node->save();

    $this->nodes['private_both_public'] = $node = $this->drupalCreateNode([
      'body' => [[]],
      'langcode' => 'hu',
      'field_private' => [['value' => 0]],
      'private' => TRUE,
    ]);
    $translation = $node->addTranslation('ca');
    $translation->title->value = $this->randomString();
    $translation->field_private->value = 0;
    $node->save();

    $this->nodes['public_hu_private'] = $node = $this->drupalCreateNode([
      'body' => [[]],
      'langcode' => 'hu',
      'field_private' => [['value' => 1]],
      'private' => FALSE,
    ]);
    $translation = $node->addTranslation('ca');
    $translation->title->value = $this->randomString();
    $translation->field_private->value = 0;
    $node->save();

    $this->nodes['public_ca_private'] = $node = $this->drupalCreateNode([
      'body' => [[]],
      'langcode' => 'hu',
      'field_private' => [['value' => 0]],
      'private' => FALSE,
    ]);
    $translation = $node->addTranslation('ca');
    $translation->title->value = $this->randomString();
    $translation->field_private->value = 1;
    $node->save();

    $this->nodes['public_both_private'] = $node = $this->drupalCreateNode([
      'body' => [[]],
      'langcode' => 'hu',
      'field_private' => [['value' => 1]],
      'private' => FALSE,
    ]);
    $translation = $node->addTranslation('ca');
    $translation->title->value = $this->randomString();
    $translation->field_private->value = 1;
    $node->save();

    $this->nodes['private_both_private'] = $node = $this->drupalCreateNode([
      'body' => [[]],
      'langcode' => 'hu',
      'field_private' => [['value' => 1]],
      'private' => TRUE,
    ]);
    $translation = $node->addTranslation('ca');
    $translation->title->value = $this->randomString();
    $translation->field_private->value = 1;
    $node->save();

    $this->nodes['public_no_language_private'] = $this->drupalCreateNode([
      'field_private' => [['value' => 1]],
      'private' => FALSE,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $this->nodes['public_no_language_public'] = $this->drupalCreateNode([
      'field_private' => [['value' => 0]],
      'private' => FALSE,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $this->nodes['private_no_language_private'] = $this->drupalCreateNode([
      'field_private' => [['value' => 1]],
      'private' => TRUE,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $this->nodes['private_no_language_public'] = $this->drupalCreateNode([
      'field_private' => [['value' => 1]],
      'private' => TRUE,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
  }

  /**
   * Tests node access and node access queries with multiple node languages.
   */
  public function testNodeAccessLanguageAwareCombination() {

    $expected_node_access = ['view' => TRUE, 'update' => FALSE, 'delete' => FALSE];
    $expected_node_access_no_access = ['view' => FALSE, 'update' => FALSE, 'delete' => FALSE];

    // When the node and both translations are public, access should always be
    // granted.
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_both_public'], $this->webUser);
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_both_public']->getTranslation('hu'), $this->webUser);
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_both_public']->getTranslation('ca'), $this->webUser);

    // If the node is marked private but both existing translations are not,
    // access should still be granted, because the grants are additive.
    $this->assertNodeAccess($expected_node_access, $this->nodes['private_both_public'], $this->webUser);
    $this->assertNodeAccess($expected_node_access, $this->nodes['private_both_public']->getTranslation('hu'), $this->webUser);
    $this->assertNodeAccess($expected_node_access, $this->nodes['private_both_public']->getTranslation('ca'), $this->webUser);

    // If the node is marked private, but an existing translation is public,
    // access should only be granted for the public translation. With the
    // Hungarian translation marked as private, but the Catalan translation
    // public, the access is granted.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_hu_private'], $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_hu_private']->getTranslation('hu'), $this->webUser);
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_hu_private']->getTranslation('ca'), $this->webUser);

    // With the Catalan translation marked as private, but the node public,
    // access is granted for the existing Hungarian translation, but not for the
    // Catalan.
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_ca_private'], $this->webUser);
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_ca_private']->getTranslation('hu'), $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_ca_private']->getTranslation('ca'), $this->webUser);

    // With both translations marked as private, but the node public, access
    // should be denied in all cases.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_both_private'], $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_both_private']->getTranslation('hu'), $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_both_private']->getTranslation('ca'), $this->webUser);

    // If the node and both its existing translations are private, access should
    // be denied in all cases.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_both_private'], $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_both_private']->getTranslation('hu'), $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_both_private']->getTranslation('ca'), $this->webUser);

    // No access for all languages as the language aware node access module
    // denies access.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_no_language_private'], $this->webUser);

    // Access only for request with no language defined.
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_no_language_public'], $this->webUser);

    // No access for all languages as both node access modules deny access.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_no_language_private'], $this->webUser);

    // No access for all languages as the non language aware node access module
    // denies access.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_no_language_public'], $this->webUser);

    // Query the node table with the node access tag in several languages.
    $connection = Database::getConnection();
    // Query with no language specified. The fallback (hu or und) will be used.
    $select = $connection->select('node', 'n')
      ->fields('n', ['nid'])
      ->addMetaData('account', $this->webUser)
      ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // Four nodes should be returned with public Hungarian translations or the
    // no language public node.
    $this->assertCount(4, $nids, 'Query returns 4 nodes when no langcode is specified.');
    $this->assertArrayHasKey($this->nodes['public_both_public']->id(), $nids);
    $this->assertArrayHasKey($this->nodes['public_ca_private']->id(), $nids);
    $this->assertArrayHasKey($this->nodes['private_both_public']->id(), $nids);
    $this->assertArrayHasKey($this->nodes['public_no_language_public']->id(), $nids);

    // Query with Hungarian (hu) specified.
    $select = $connection->select('node', 'n')
      ->fields('n', ['nid'])
      ->addMetaData('account', $this->webUser)
      ->addMetaData('langcode', 'hu')
      ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // Three nodes should be returned (with public Hungarian translations).
    $this->assertCount(3, $nids, 'Query returns 3 nodes.');
    $this->assertArrayHasKey($this->nodes['public_both_public']->id(), $nids);
    $this->assertArrayHasKey($this->nodes['public_ca_private']->id(), $nids);
    $this->assertArrayHasKey($this->nodes['private_both_public']->id(), $nids);

    // Query with Catalan (ca) specified.
    $select = $connection->select('node', 'n')
      ->fields('n', ['nid'])
      ->addMetaData('account', $this->webUser)
      ->addMetaData('langcode', 'ca')
      ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // Three nodes should be returned (with public Catalan translations).
    $this->assertCount(3, $nids, 'Query returns 3 nodes.');
    $this->assertArrayHasKey($this->nodes['public_both_public']->id(), $nids);
    $this->assertArrayHasKey($this->nodes['public_hu_private']->id(), $nids);
    $this->assertArrayHasKey($this->nodes['private_both_public']->id(), $nids);

    // Query with German (de) specified.
    $select = $connection->select('node', 'n')
      ->fields('n', ['nid'])
      ->addMetaData('account', $this->webUser)
      ->addMetaData('langcode', 'de')
      ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // There are no nodes with German translations, so no results are returned.
    $this->assertEmpty($nids, 'Query returns an empty result.');

    // Query the nodes table as admin user (full access) with the node access
    // tag and no specific langcode.
    $select = $connection->select('node', 'n')
      ->fields('n', ['nid'])
      ->addMetaData('account', $this->adminUser)
      ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // All nodes are returned.
    $this->assertCount(10, $nids, 'Query returns all nodes.');

    // Query the nodes table as admin user (full access) with the node access
    // tag and langcode de.
    $select = $connection->select('node', 'n')
      ->fields('n', ['nid'])
      ->addMetaData('account', $this->adminUser)
      ->addMetaData('langcode', 'de')
      ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // Even though there is no German translation, all nodes are returned
    // because node access filtering does not occur when the user is user 1.
    $this->assertCount(10, $nids, 'Query returns all nodes.');
  }

}
