<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeAccessLanguageAwareCombinationTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Tests node access functionality with multiple languages and two node access
 * modules.
 *
 * @group node
 */
class NodeAccessLanguageAwareCombinationTest extends NodeTestBase {

  /**
   * Enable language and two node access modules.
   *
   * @var array
   */
  public static $modules = array('language', 'node_access_test_language', 'node_access_test');

  /**
   * A set of nodes to use in testing.
   *
   * @var array
   */
  protected $nodes = array();

  /**
   * A normal authenticated user.
   *
   * @var \Drupal\user\Entity\UserInterface.
   */
  protected $webUser;

  /**
   * User 1.
   *
   * @var \Drupal\user\Entity\UserInterface.
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    node_access_test_add_field(NodeType::load('page'));

    // Create the 'private' field, which allows the node to be marked as private
    // (restricted access) in a given translation.
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => 'field_private',
      'entity_type' => 'node',
      'type' => 'boolean',
      'cardinality' => 1,
    ));
    $field_storage->save();

    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'widget' => array(
        'type' => 'options_buttons',
      ),
      'settings' => array(
        'on_label' => 'Private',
        'off_label' => 'Not private',
      ),
    ))->save();

    // After enabling a node access module, the access table has to be rebuild.
    node_access_rebuild();

    // Add Hungarian and Catalan.
    ConfigurableLanguage::createFromLangcode('hu')->save();
    ConfigurableLanguage::createFromLangcode('ca')->save();

    // Create a normal authenticated user.
    $this->webUser = $this->drupalCreateUser(array('access content'));

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
    $this->nodes['public_both_public'] = $node = $this->drupalCreateNode(array(
      'body' => array(array()),
      'langcode' => 'hu',
      'field_private' => array(array('value' => 0)),
      'private' => FALSE,
    ));
    $translation = $node->getTranslation('ca');
    $translation->field_private->value = 0;
    $node->save();

    $this->nodes['private_both_public'] = $node = $this->drupalCreateNode(array(
      'body' => array(array()),
      'langcode' => 'hu',
      'field_private' => array(array('value' => 0)),
      'private' => TRUE,
    ));
    $translation = $node->getTranslation('ca');
    $translation->field_private->value = 0;
    $node->save();

    $this->nodes['public_hu_private'] = $node = $this->drupalCreateNode(array(
      'body' => array(array()),
      'langcode' => 'hu',
      'field_private' => array(array('value' => 1)),
      'private' => FALSE,
    ));
    $translation = $node->getTranslation('ca');
    $translation->field_private->value = 0;
    $node->save();

    $this->nodes['public_ca_private'] = $node = $this->drupalCreateNode(array(
      'body' => array(array()),
      'langcode' => 'hu',
      'field_private' => array(array('value' => 0)),
      'private' => FALSE,
    ));
    $translation = $node->getTranslation('ca');
    $translation->field_private->value = 1;
    $node->save();

    $this->nodes['public_both_private'] = $node = $this->drupalCreateNode(array(
      'body' => array(array()),
      'langcode' => 'hu',
      'field_private' => array(array('value' => 1)),
      'private' => FALSE,
    ));
    $translation = $node->getTranslation('ca');
    $translation->field_private->value = 1;
    $node->save();

    $this->nodes['private_both_private'] = $node = $this->drupalCreateNode(array(
      'body' => array(array()),
      'langcode' => 'hu',
      'field_private' => array(array('value' => 1)),
      'private' => TRUE,
    ));
    $translation = $node->getTranslation('ca');
    $translation->field_private->value = 1;
    $node->save();

    $this->nodes['public_no_language_private'] = $this->drupalCreateNode(array(
      'field_private' => array(array('value' => 1)),
      'private' => FALSE,
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $this->nodes['public_no_language_public'] = $this->drupalCreateNode(array(
      'field_private' => array(array('value' => 0)),
      'private' => FALSE,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $this->nodes['private_no_language_private'] = $this->drupalCreateNode(array(
      'field_private' => array(array('value' => 1)),
      'private' => TRUE,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $this->nodes['private_no_language_public'] = $this->drupalCreateNode(array(
      'field_private' => array(array('value' => 1)),
      'private' => TRUE,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
  }

  /**
   * Tests node access and node access queries with multiple node languages.
   */
  function testNodeAccessLanguageAwareCombination() {

    $expected_node_access = array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE);
    $expected_node_access_no_access = array('view' => FALSE, 'update' => FALSE, 'delete' => FALSE);

    // When the node and both translations are public, access should only be
    // denied when a translation that does not exist is requested.
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_both_public'], $this->webUser);
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_both_public'], $this->webUser, 'hu');
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_both_public'], $this->webUser, 'ca');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_both_public'], $this->webUser, 'en');

    // If the node is marked private but both existing translations are not,
    // access should still be granted, because the grants are additive.
    $this->assertNodeAccess($expected_node_access, $this->nodes['private_both_public'], $this->webUser);
    $this->assertNodeAccess($expected_node_access, $this->nodes['private_both_public'], $this->webUser, 'hu');
    $this->assertNodeAccess($expected_node_access, $this->nodes['private_both_public'], $this->webUser, 'ca');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_both_public'], $this->webUser, 'en');

    // If the node is marked private, but a existing translation is public,
    // access should only be granted for the public translation. For a
    // translation that does not exist yet (English translation), the access is
    // denied. With the Hungarian translation marked as private, but the Catalan
    // translation public, the access is granted.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_hu_private'], $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_hu_private'], $this->webUser, 'hu');
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_hu_private'], $this->webUser, 'ca');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_hu_private'], $this->webUser, 'en');

    // With the Catalan translation marked as private, but the node public,
    // access is granted for the existing Hungarian translation, but not for the
    // Catalan nor the English ones.
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_ca_private'], $this->webUser);
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_ca_private'], $this->webUser, 'hu');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_ca_private'], $this->webUser, 'ca');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_ca_private'], $this->webUser, 'en');

    // With both translations marked as private, but the node public, access
    // should be denied in all cases.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_both_private'], $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_both_private'], $this->webUser, 'hu');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_both_private'], $this->webUser, 'ca');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_both_private'], $this->webUser, 'en');

    // If the node and both its existing translations are private, access should
    // be denied in all cases.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_both_private'], $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_both_private'], $this->webUser, 'hu');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_both_private'], $this->webUser, 'ca');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_both_private'], $this->webUser, 'en');

    // No access for all languages as the language aware node access module
    // denies access.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_no_language_private'], $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_no_language_private'], $this->webUser, 'hu');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_no_language_private'], $this->webUser, 'ca');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_no_language_private'], $this->webUser, 'en');

    // Access only for request with no language defined.
    $this->assertNodeAccess($expected_node_access, $this->nodes['public_no_language_public'], $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_no_language_public'], $this->webUser, 'hu');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_no_language_public'], $this->webUser, 'ca');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['public_no_language_public'], $this->webUser, 'en');

    // No access for all languages as both node access modules deny access.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_no_language_private'], $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_no_language_private'], $this->webUser, 'hu');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_no_language_private'], $this->webUser, 'ca');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_no_language_private'], $this->webUser, 'en');

    // No access for all languages as the non language aware node access module
    // denies access.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_no_language_public'], $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_no_language_public'], $this->webUser, 'hu');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_no_language_public'], $this->webUser, 'ca');
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['private_no_language_public'], $this->webUser, 'en');


    // Query the node table with the node access tag in several languages.

    // Query with no language specified. The fallback (hu or und) will be used.
    $select = db_select('node', 'n')
    ->fields('n', array('nid'))
    ->addMetaData('account', $this->webUser)
    ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // Four nodes should be returned with public Hungarian translations or the
    // no language public node.
    $this->assertEqual(count($nids), 4, 'db_select() returns 4 nodes when no langcode is specified.');
    $this->assertTrue(array_key_exists($this->nodes['public_both_public']->id(), $nids), 'Returned node ID is full public node.');
    $this->assertTrue(array_key_exists($this->nodes['public_ca_private']->id(), $nids), 'Returned node ID is Hungarian public only node.');
    $this->assertTrue(array_key_exists($this->nodes['private_both_public']->id(), $nids), 'Returned node ID is both public non-language-aware private only node.');
    $this->assertTrue(array_key_exists($this->nodes['public_no_language_public']->id(), $nids), 'Returned node ID is no language public node.');

    // Query with Hungarian (hu) specified.
    $select = db_select('node', 'n')
    ->fields('n', array('nid'))
    ->addMetaData('account', $this->webUser)
    ->addMetaData('langcode', 'hu')
    ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // Three nodes should be returned (with public Hungarian translations).
    $this->assertEqual(count($nids), 3, 'db_select() returns 3 nodes.');
    $this->assertTrue(array_key_exists($this->nodes['public_both_public']->id(), $nids), 'Returned node ID is both public node.');
    $this->assertTrue(array_key_exists($this->nodes['public_ca_private']->id(), $nids), 'Returned node ID is Hungarian public only node.');
    $this->assertTrue(array_key_exists($this->nodes['private_both_public']->id(), $nids), 'Returned node ID is both public non-language-aware private only node.');

    // Query with Catalan (ca) specified.
    $select = db_select('node', 'n')
    ->fields('n', array('nid'))
    ->addMetaData('account', $this->webUser)
    ->addMetaData('langcode', 'ca')
    ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // Three nodes should be returned (with public Catalan translations).
    $this->assertEqual(count($nids), 3, 'db_select() returns 3 nodes.');
    $this->assertTrue(array_key_exists($this->nodes['public_both_public']->id(), $nids), 'Returned node ID is both public node.');
    $this->assertTrue(array_key_exists($this->nodes['public_hu_private']->id(), $nids), 'Returned node ID is Catalan public only node.');
    $this->assertTrue(array_key_exists($this->nodes['private_both_public']->id(), $nids), 'Returned node ID is both public non-language-aware private only node.');

    // Query with German (de) specified.
    $select = db_select('node', 'n')
    ->fields('n', array('nid'))
    ->addMetaData('account', $this->webUser)
    ->addMetaData('langcode', 'de')
    ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // There are no nodes with German translations, so no results are returned.
    $this->assertTrue(empty($nids), 'db_select() returns an empty result.');

    // Query the nodes table as admin user (full access) with the node access
    // tag and no specific langcode.
    $select = db_select('node', 'n')
    ->fields('n', array('nid'))
    ->addMetaData('account', $this->adminUser)
    ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // All nodes are returned.
    $this->assertEqual(count($nids), 10, 'db_select() returns all nodes.');

    // Query the nodes table as admin user (full access) with the node access
    // tag and langcode de.
    $select = db_select('node', 'n')
    ->fields('n', array('nid'))
    ->addMetaData('account', $this->adminUser)
    ->addMetaData('langcode', 'de')
    ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // Even though there is no German translation, all nodes are returned
    // because node access filtering does not occur when the user is user 1.
    $this->assertEqual(count($nids), 10, 'db_select() returns all nodes.');
  }

}
