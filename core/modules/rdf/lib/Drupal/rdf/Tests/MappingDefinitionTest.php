<?php

/**
 * @file
 * Definition of Drupal\rdf\Tests\MappingDefinitionTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\taxonomy\Tests\TaxonomyTestBase;

/**
 * Test the RDF mapping definition functionality.
 */
class MappingDefinitionTest extends TaxonomyTestBase {
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'RDF mapping definition functionality',
      'description' => 'Test the different types of RDF mappings and ensure the proper RDFa markup in included in nodes and user profile pages.',
      'group' => 'RDF',
    );
  }

  function setUp() {
    parent::setUp('rdf', 'rdf_test');
  }

  /**
   * Create a node of type article and test whether the RDF mapping defined for
   * this node type in rdf_test.module is used in the node page.
   */
  function testAttributesInMarkup1() {
    $node = $this->drupalCreateNode(array('type' => 'article'));
    $isoDate = date('c', $node->changed);
    $url = url('node/' . $node->nid);
    $this->drupalGet('node/' . $node->nid);

    // Ensure the default bundle mapping for node is used. These attributes come
    // from the node default bundle definition.
    $node_title = $this->xpath("//meta[@property='dc:title' and @content='$node->title']");
    $node_meta = $this->xpath("//div[(@about='$url')]//span[contains(@property, 'dc:date') and contains(@property, 'dc:created') and @datatype='xsd:dateTime' and @content='$isoDate']");
    $this->assertTrue(!empty($node_title), t('Property dc:title is present in meta tag.'));
    $this->assertTrue(!empty($node_meta), t('RDF type is present on post. Properties dc:date and dc:created are present on post date.'));
  }

  /**
   * Create a content type and a node of type test_bundle_hook_install and test
   * whether the RDF mapping defined in rdf_test.install is used.
   */
  function testAttributesInMarkup2() {
    $type = $this->drupalCreateContentType(array('type' => 'test_bundle_hook_install'));
    $node = $this->drupalCreateNode(array('type' => 'test_bundle_hook_install'));
    $isoDate = date('c', $node->changed);
    $url = url('node/' . $node->nid);
    $this->drupalGet('node/' . $node->nid);

    // Ensure the mapping defined in rdf_module.test is used.
    $test_bundle_title = $this->xpath("//meta[@property='dc:title' and @content='$node->title']");
    $test_bundle_meta = $this->xpath("//div[(@about='$url') and contains(@typeof, 'foo:mapping_install1') and contains(@typeof, 'bar:mapping_install2')]//span[contains(@property, 'dc:date') and contains(@property, 'dc:created') and @datatype='xsd:dateTime' and @content='$isoDate']");
    $this->assertTrue(!empty($test_bundle_title), t('Property dc:title is present in meta tag.'));
    $this->assertTrue(!empty($test_bundle_meta), t('RDF type is present on post. Properties dc:date and dc:created are present on post date.'));
  }

  /**
   * Create a random content type and node and ensure the default mapping for
   * node is used.
   */
  function testAttributesInMarkup3() {
    $type = $this->drupalCreateContentType();
    $node = $this->drupalCreateNode(array('type' => $type->type));
    $isoDate = date('c', $node->changed);
    $url = url('node/' . $node->nid);
    $this->drupalGet('node/' . $node->nid);

    // Ensure the default bundle mapping for node is used. These attributes come
    // from the node default bundle definition.
    $random_bundle_title = $this->xpath("//meta[@property='dc:title' and @content='$node->title']");
    $random_bundle_meta = $this->xpath("//div[(@about='$url') and contains(@typeof, 'sioc:Item') and contains(@typeof, 'foaf:Document')]//span[contains(@property, 'dc:date') and contains(@property, 'dc:created') and @datatype='xsd:dateTime' and @content='$isoDate']");
    $this->assertTrue(!empty($random_bundle_title), t('Property dc:title is present in meta tag.'));
    $this->assertTrue(!empty($random_bundle_meta), t('RDF type is present on post. Properties dc:date and dc:created are present on post date.'));
  }

  /**
   * Create a random user and ensure the default mapping for user is used.
   */
  function testUserAttributesInMarkup() {
    // Create two users, one with access to user profiles.
    $user1 = $this->drupalCreateUser(array('access user profiles'));
    $user2 = $this->drupalCreateUser();
    $username = $user2->name;
    $this->drupalLogin($user1);
    // Browse to the user profile page.
    $this->drupalGet('user/' . $user2->uid);
    // Ensure the default bundle mapping for user is used on the user profile
    // page. These attributes come from the user default bundle definition.
    $account_uri = url('user/' . $user2->uid);
    $person_uri = url('user/' . $user2->uid, array('fragment' => 'me'));

    $user2_profile_about = $this->xpath('//article[@class="profile" and @typeof="sioc:UserAccount" and @about=:account-uri]', array(
      ':account-uri' => $account_uri,
    ));
    $this->assertTrue(!empty($user2_profile_about), t('RDFa markup found on user profile page'));

    $user_account_holder = $this->xpath('//meta[contains(@typeof, "foaf:Person") and @about=:person-uri and @resource=:account-uri and contains(@rel, "foaf:account")]', array(
      ':person-uri' => $person_uri,
      ':account-uri' => $account_uri,
    ));
    $this->assertTrue(!empty($user_account_holder), t('URI created for account holder and username set on sioc:UserAccount.'));

    $user_username = $this->xpath('//meta[@about=:account-uri and contains(@property, "foaf:name") and @content=:username]', array(
      ':account-uri' => $account_uri,
      ':username' => $username,
    ));
    $this->assertTrue(!empty($user_username), t('foaf:name set on username.'));

    // User 2 creates node.
    $this->drupalLogin($user2);
    $node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1));
    $this->drupalLogin($user1);
    $this->drupalGet('node/' . $node->nid);
    // Ensures the default bundle mapping for user is used on the Authored By
    // information on the node.
    $author_about = $this->xpath('//a[@typeof="sioc:UserAccount" and @about=:account-uri and @property="foaf:name" and contains(@lang, "")]', array(
      ':account-uri' => $account_uri,
    ));
    $this->assertTrue(!empty($author_about), t('RDFa markup found on author information on post. The lang attribute on username is set to empty string.'));
  }

  /**
   * Creates a random term and ensures the right RDFa markup is used.
   */
  function testTaxonomyTermRdfaAttributes() {
    $vocabulary = $this->createVocabulary();
    $term = $this->createTerm($vocabulary);

    // Views the term and checks that the RDFa markup is correct.
    $this->drupalGet('taxonomy/term/' . $term->tid);
    $term_url = url('taxonomy/term/' . $term->tid);
    $term_name = $term->name;
    $term_rdfa_meta = $this->xpath('//meta[@typeof="skos:Concept" and @about=:term-url and contains(@property, "rdfs:label") and contains(@property, "skos:prefLabel") and @content=:term-name]', array(
      ':term-url' => $term_url,
      ':term-name' => $term_name,
    ));
    $this->assertTrue(!empty($term_rdfa_meta), t('RDFa markup found on term page.'));
  }
}
