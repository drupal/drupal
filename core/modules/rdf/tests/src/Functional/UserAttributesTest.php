<?php

namespace Drupal\Tests\rdf\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the RDFa markup of Users.
 *
 * @group rdf
 */
class UserAttributesTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['rdf', 'node'];

  protected function setUp() {
    parent::setUp();
    rdf_get_mapping('user', 'user')
      ->setBundleMapping([
        'types' => ['sioc:UserAccount'],
      ])
      ->setFieldMapping('name', [
        'properties' => ['foaf:name'],
      ])
      ->save();
  }

  /**
   * Tests if default mapping for user is being used.
   *
   * Creates a random user and ensures the default mapping for the user is
   * being used.
   */
  public function testUserAttributesInMarkup() {
    // Creates users that should and should not be truncated
    // by template_preprocess_username (20 characters)
    // one of these users tests right on the cusp (20).
    $user1 = $this->drupalCreateUser(['access user profiles']);

    $authors = [
      $this->drupalCreateUser([], $this->randomMachineName(30)),
      $this->drupalCreateUser([], $this->randomMachineName(20)),
      $this->drupalCreateUser([], $this->randomMachineName(5)),
    ];

    $this->drupalLogin($user1);

    $this->drupalCreateContentType(['type' => 'article']);

    /** @var \Drupal\user\UserInterface[] $authors */
    foreach ($authors as $author) {
      $account_uri = $author->toUrl('canonical', ['absolute' => TRUE])->toString();

      // Parses the user profile page where the default bundle mapping for user
      // should be used.
      $parser = new \EasyRdf_Parser_Rdfa();
      $graph = new \EasyRdf_Graph();
      $base_uri = \Drupal::url('<front>', [], ['absolute' => TRUE]);
      $parser->parse($graph, $this->drupalGet('user/' . $author->id()), 'rdfa', $base_uri);

      // Inspects RDF graph output.
      // User type.
      $expected_value = [
        'type' => 'uri',
        'value' => 'http://rdfs.org/sioc/ns#UserAccount',
      ];
      $this->assertTrue($graph->hasProperty($account_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'User type found in RDF output (sioc:UserAccount).');
      // User name.
      $expected_value = [
        'type' => 'literal',
        'value' => $author->getAccountName(),
      ];
      $this->assertTrue($graph->hasProperty($account_uri, 'http://xmlns.com/foaf/0.1/name', $expected_value), 'User name found in RDF output (foaf:name).');

      // User creates a node.
      $this->drupalLogin($author);
      $node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1]);
      $this->drupalLogin($user1);

      // Parses the node created by the user.
      $parser = new \EasyRdf_Parser_Rdfa();
      $graph = new \EasyRdf_Graph();
      $base_uri = \Drupal::url('<front>', [], ['absolute' => TRUE]);
      $parser->parse($graph, $this->drupalGet('node/' . $node->id()), 'rdfa', $base_uri);

      // Ensures the default bundle mapping for user is used on the Authored By
      // information on the node.
      $expected_value = [
        'type' => 'uri',
        'value' => 'http://rdfs.org/sioc/ns#UserAccount',
      ];
      $this->assertTrue($graph->hasProperty($account_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'User type found in RDF output (sioc:UserAccount).');
      // User name.
      $expected_value = [
        'type' => 'literal',
        'value' => $author->getAccountName(),
      ];
      $this->assertTrue($graph->hasProperty($account_uri, 'http://xmlns.com/foaf/0.1/name', $expected_value), 'User name found in RDF output (foaf:name).');

    }
  }

}
