<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeAccessLanguageAwareTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\user\Entity\User;

/**
 * Tests node_access and db_select() with node_access tag functionality with
 * multiple languages with node_access_test_language which is language-aware.
 *
 * @group node
 */
class NodeAccessLanguageAwareTest extends NodeTestBase {

  /**
   * Enable language and a language-aware node access module.
   *
   * @var array
   */
  public static $modules = array('language', 'node_access_test_language');

  /**
   * A set of nodes to use in testing.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = array();

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A normal authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

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

    // Create a normal authenticated user.
    $this->webUser = $this->drupalCreateUser(array('access content'));

    // Load the user 1 user for later use as an admin user with permission to
    // see everything.
    $this->adminUser = User::load(1);

    // Add Hungarian and Catalan.
    ConfigurableLanguage::createFromLangcode('hu')->save();
    ConfigurableLanguage::createFromLangcode('ca')->save();

    // The node_access_test_language module allows individual translations of a
    // node to be marked private (not viewable by normal users).

    // Create six nodes:
    // 1. Four Hungarian nodes with Catalan translations
    //   - One with neither language marked as private.
    //   - One with only the Hungarian translation private.
    //   - One with only the Catalan translation private.
    //   - One with both the Hungarian and Catalan translations private.
    // 2. Two nodes with no language specified.
    //   - One public.
    //   - One private.
    $this->nodes['both_public'] = $node = $this->drupalCreateNode(array(
      'body' => array(array()),
      'langcode' => 'hu',
      'field_private' => array(array('value' => 0)),
    ));
    $translation = $node->addTranslation('ca');
    $translation->title->value = $this->randomString();
    $translation->field_private->value = 0;
    $node->save();

    $this->nodes['ca_private'] = $node = $this->drupalCreateNode(array(
      'body' => array(array()),
      'langcode' => 'hu',
      'field_private' => array(array('value' => 0)),
    ));
    $translation = $node->addTranslation('ca');
    $translation->title->value = $this->randomString();
    $translation->field_private->value = 1;
    $node->save();

    $this->nodes['hu_private'] = $node = $this->drupalCreateNode(array(
      'body' => array(array()),
      'langcode' => 'hu',
      'field_private' => array(array('value' => 1)),
    ));
    $translation = $node->addTranslation('ca');
    $translation->title->value = $this->randomString();
    $translation->field_private->value = 0;
    $node->save();

    $this->nodes['both_private'] = $node = $this->drupalCreateNode(array(
      'body' => array(array()),
      'langcode' => 'hu',
      'field_private' => array(array('value' => 1)),
    ));
    $translation = $node->addTranslation('ca');
    $translation->title->value = $this->randomString();
    $translation->field_private->value = 1;
    $node->save();

    $this->nodes['no_language_public'] = $this->drupalCreateNode(array(
      'field_private' => array(array('value' => 0)),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $this->nodes['no_language_private'] = $this->drupalCreateNode(array(
      'field_private' => array(array('value' => 1)),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
  }

  /**
   * Tests node access and node access queries with multiple node languages.
   */
  function testNodeAccessLanguageAware() {
    // The node_access_test_language module only grants view access.
    $expected_node_access = array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE);
    $expected_node_access_no_access = array('view' => FALSE, 'update' => FALSE, 'delete' => FALSE);

    // When both Hungarian and Catalan are marked as public, access to the
    // Hungarian translation should be granted with the default entity object or
    // when the Hungarian translation is specified explicitly.
    $this->assertNodeAccess($expected_node_access, $this->nodes['both_public'], $this->webUser);
    $this->assertNodeAccess($expected_node_access, $this->nodes['both_public']->getTranslation('hu'), $this->webUser);
    // Access to the Catalan translation should also be granted.
    $this->assertNodeAccess($expected_node_access, $this->nodes['both_public']->getTranslation('ca'), $this->webUser);

    // When Hungarian is marked as private, access to the Hungarian translation
    // should be denied with the default entity object or when the Hungarian
    // translation is specified explicitly.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['hu_private'], $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['hu_private']->getTranslation('hu'), $this->webUser);
    // Access to the Catalan translation should be granted.
    $this->assertNodeAccess($expected_node_access, $this->nodes['hu_private']->getTranslation('ca'), $this->webUser);

    // When Catalan is marked as private, access to the Hungarian translation
    // should be granted with the default entity object or when the Hungarian
    // translation is specified explicitly.
    $this->assertNodeAccess($expected_node_access, $this->nodes['ca_private'], $this->webUser);
    $this->assertNodeAccess($expected_node_access, $this->nodes['ca_private']->getTranslation('hu'), $this->webUser);
    // Access to the Catalan translation should be granted.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['ca_private']->getTranslation('ca'), $this->webUser);

    // When both translations are marked as private, access should be denied
    // regardless of the entity object specified.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['both_private'], $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['both_private']->getTranslation('hu'), $this->webUser);
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['both_private']->getTranslation('ca'), $this->webUser);

    // When no language is specified for a private node, access to every node
    // translation is denied.
    $this->assertNodeAccess($expected_node_access_no_access, $this->nodes['no_language_private'], $this->webUser);

    // When no language is specified for a public node, access should be
    // granted.
    $this->assertNodeAccess($expected_node_access, $this->nodes['no_language_public'], $this->webUser);

    // Query the node table with the node access tag in several languages.

    // Query with no language specified. The fallback (hu) will be used.
    $select = db_select('node', 'n')
    ->fields('n', array('nid'))
    ->addMetaData('account', $this->webUser)
    ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // Three nodes should be returned:
    // - Node with both translations public.
    // - Node with only the Catalan translation marked as private.
    // - No language node marked as public.
    $this->assertEqual(count($nids), 3, 'db_select() returns 3 nodes when no langcode is specified.');
    $this->assertTrue(array_key_exists($this->nodes['both_public']->id(), $nids), 'The node with both translations public is returned.');
    $this->assertTrue(array_key_exists($this->nodes['ca_private']->id(), $nids), 'The node with only the Catalan translation private is returned.');
    $this->assertTrue(array_key_exists($this->nodes['no_language_public']->id(), $nids), 'The node with no language is returned.');

    // Query with Hungarian (hu) specified.
    $select = db_select('node', 'n')
    ->fields('n', array('nid'))
    ->addMetaData('account', $this->webUser)
    ->addMetaData('langcode', 'hu')
    ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // Two nodes should be returned: the node with both translations public, and
    // the node with only the Catalan translation marked as private.
    $this->assertEqual(count($nids), 2, 'db_select() returns 2 nodes when the hu langcode is specified.');
    $this->assertTrue(array_key_exists($this->nodes['both_public']->id(), $nids), 'The node with both translations public is returned.');
    $this->assertTrue(array_key_exists($this->nodes['ca_private']->id(), $nids), 'The node with only the Catalan translation private is returned.');

    // Query with Catalan (ca) specified.
    $select = db_select('node', 'n')
    ->fields('n', array('nid'))
    ->addMetaData('account', $this->webUser)
    ->addMetaData('langcode', 'ca')
    ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // Two nodes should be returned: the node with both translations public, and
    // the node with only the Hungarian translation marked as private.
    $this->assertEqual(count($nids), 2, 'db_select() returns 2 nodes when the hu langcode is specified.');
    $this->assertTrue(array_key_exists($this->nodes['both_public']->id(), $nids), 'The node with both translations public is returned.');
    $this->assertTrue(array_key_exists($this->nodes['hu_private']->id(), $nids), 'The node with only the Hungarian translation private is returned.');

    // Query with German (de) specified.
    $select = db_select('node', 'n')
    ->fields('n', array('nid'))
    ->addMetaData('account', $this->webUser)
    ->addMetaData('langcode', 'de')
    ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // There are no nodes with German translations, so no results are returned.
    $this->assertTrue(empty($nids), 'db_select() returns an empty result when the de langcode is specified.');

    // Query the nodes table as admin user (full access) with the node access
    // tag and no specific langcode.
    $select = db_select('node', 'n')
    ->fields('n', array('nid'))
    ->addMetaData('account', $this->adminUser)
    ->addTag('node_access');
    $nids = $select->execute()->fetchAllAssoc('nid');

    // All nodes are returned.
    $this->assertEqual(count($nids), 6, 'db_select() returns all nodes.');

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
    $this->assertEqual(count($nids), 6, 'db_select() returns all nodes.');
  }

}
