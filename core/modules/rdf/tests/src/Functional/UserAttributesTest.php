<?php

namespace Drupal\Tests\rdf\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\rdf\Traits\RdfParsingTrait;

/**
 * Tests the RDFa markup of Users.
 *
 * @group rdf
 * @group legacy
 */
class UserAttributesTest extends BrowserTestBase {

  use RdfParsingTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['rdf', 'node', 'user_hooks_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * URI of the front page of the Drupal site.
   *
   * @var string
   */
  protected $baseUri;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    rdf_get_mapping('user', 'user')
      ->setBundleMapping([
        'types' => ['sioc:UserAccount'],
      ])
      ->setFieldMapping('name', [
        'properties' => ['foaf:name'],
      ])
      ->save();

    // Prepares commonly used URIs.
    $this->baseUri = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

    // Set to test the altered display name.
    \Drupal::state()->set('user_hooks_test_user_format_name_alter', TRUE);
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
      $this->drupalGet('user/' . $author->id());

      // Inspects RDF graph output.
      // User type.
      $expected_value = [
        'type' => 'uri',
        'value' => 'http://rdfs.org/sioc/ns#UserAccount',
      ];
      $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $account_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'User type found in RDF output (sioc:UserAccount).');

      // User name.
      $expected_value = [
        'type' => 'literal',
        'value' => $author->getDisplayName(),
      ];
      $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $account_uri, 'http://xmlns.com/foaf/0.1/name', $expected_value), 'User name found in RDF output (foaf:name).');

      // User creates a node.
      $this->drupalLogin($author);
      $node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1]);
      $this->drupalLogin($user1);
      $this->drupalGet('node/' . $node->id());

      // Ensures the default bundle mapping for user is used on the Authored By
      // information on the node.
      $expected_value = [
        'type' => 'uri',
        'value' => 'http://rdfs.org/sioc/ns#UserAccount',
      ];
      $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $account_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'User type found in RDF output (sioc:UserAccount).');

      // User name.
      $expected_value = [
        'type' => 'literal',
        'value' => $author->getDisplayName(),
      ];
      $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $account_uri, 'http://xmlns.com/foaf/0.1/name', $expected_value), 'User name found in RDF output (foaf:name).');
    }
  }

}
