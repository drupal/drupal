<?php
/**
 * Contains \Drupal\rest\Tests\CsrfTest.
 */

namespace Drupal\rest\Tests;

/**
 * Tests the CSRF protection.
 *
 * @group rest
 */
class CsrfTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('hal', 'rest', 'entity_test', 'basic_auth');

  /**
   * A testing user account.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * The serialized entity.
   *
   * @var string
   */
  protected $serialized;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->enableService('entity:' . $this->testEntityType, 'POST', 'hal_json', array('basic_auth', 'cookie'));

    // Create a user account that has the required permissions to create
    // resources via the REST API.
    $permissions = $this->entityPermissions($this->testEntityType, 'create');
    $permissions[] = 'restful post entity:' . $this->testEntityType;
    $this->account = $this->drupalCreateUser($permissions);

    // Serialize an entity to a string to use in the content body of the POST
    // request.
    $serializer = $this->container->get('serializer');
    $entity_values = $this->entityValues($this->testEntityType);
    $entity = entity_create($this->testEntityType, $entity_values);
    $this->serialized = $serializer->serialize($entity, $this->defaultFormat);
  }

  /**
   * Tests that CSRF check is not triggered for Basic Auth requests.
   */
  public function testBasicAuth() {
    // Login so the session cookie is sent in addition to the basic auth header.
    $this->drupalLogin($this->account);

    $curl_options = $this->getCurlOptions();
    $curl_options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
    $curl_options[CURLOPT_USERPWD] = $this->account->getUsername() . ':' . $this->account->pass_raw;
    $this->curlExec($curl_options);
    $this->assertResponse(201);
    // Ensure that the entity was created.
    $loaded_entity = $this->loadEntityFromLocationHeader($this->drupalGetHeader('location'));
    $this->assertTrue($loaded_entity, 'An entity was created in the database');
  }

  /**
   * Tests that CSRF check is triggered for Cookie Auth requests.
   */
  public function testCookieAuth() {
    $this->drupalLogin($this->account);

    $curl_options = $this->getCurlOptions();

    // Try to create an entity without the CSRF token.
    $this->curlExec($curl_options);
    $this->assertResponse(403);
    // Ensure that the entity was not created.
    $this->assertFalse(entity_load_multiple($this->testEntityType, NULL, TRUE), 'No entity has been created in the database.');

    // Create an entity with the CSRF token.
    $token = $this->drupalGet('rest/session/token');
    $curl_options[CURLOPT_HTTPHEADER][] = "X-CSRF-Token: $token";
    $this->curlExec($curl_options);
    $this->assertResponse(201);
    // Ensure that the entity was created.
    $loaded_entity = $this->loadEntityFromLocationHeader($this->drupalGetHeader('location'));
    $this->assertTrue($loaded_entity, 'An entity was created in the database');
  }

  /**
   * Gets the cURL options to create an entity with POST.
   *
   * @return array
   *   The array of cURL options.
   */
  protected function getCurlOptions() {
    return array(
      CURLOPT_HTTPGET => FALSE,
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $this->serialized,
      CURLOPT_URL => _url('entity/' . $this->testEntityType, array('absolute' => TRUE)),
      CURLOPT_NOBODY => FALSE,
      CURLOPT_HTTPHEADER => array(
        "Content-Type: {$this->defaultMimeType}",
      ),
    );
  }
}
