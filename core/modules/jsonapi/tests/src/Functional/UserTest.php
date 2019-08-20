<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use GuzzleHttp\RequestOptions;

/**
 * JSON:API integration test for the "User" content entity type.
 *
 * @group jsonapi
 */
class UserTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'user';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'user--user';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'changed' => NULL,
  ];

  /**
   * {@inheritdoc}
   */
  protected static $anonymousUsersCanViewLabels = TRUE;

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected static $labelFieldName = 'name';

  /**
   * {@inheritdoc}
   */
  protected static $firstCreatedEntityId = 4;

  /**
   * {@inheritdoc}
   */
  protected static $secondCreatedEntityId = 5;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    // @todo Remove this in
    $this->grantPermissionsToTestedRole(['access content']);

    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access user profiles']);
        break;

      case 'POST':
      case 'PATCH':
      case 'DELETE':
        $this->grantPermissionsToTestedRole(['administer users']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Llama" user.
    $user = User::create(['created' => 123456789]);
    $user->setUsername('Llama')
      ->setChangedTime(123456789)
      ->activate()
      ->save();

    return $user;
  }

  /**
   * {@inheritdoc}
   */
  protected function createAnotherEntity($key) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->getEntityDuplicate($this->entity, $key);
    $user->setUsername($user->label() . '_' . $key);
    $user->setEmail("$key@example.com");
    $user->save();
    return $user;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/user/user/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'user--user',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'created' => '1973-11-29T21:33:09+00:00',
          'changed' => (new \DateTime())->setTimestamp($this->entity->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'default_langcode' => TRUE,
          'langcode' => 'en',
          'name' => 'Llama',
          'drupal_internal__uid' => 3,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts(array $sparse_fieldset = NULL) {
    $cache_contexts = parent::getExpectedCacheContexts($sparse_fieldset);
    if ($sparse_fieldset === NULL || in_array('mail', $sparse_fieldset)) {
      $cache_contexts = Cache::mergeContexts($cache_contexts, ['user']);
    }
    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    return [
      'data' => [
        'type' => 'user--user',
        'attributes' => [
          'name' => 'Dramallama',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    switch ($method) {
      case 'GET':
        return "The 'access user profiles' permission is required and the user must be active.";

      case 'PATCH':
        return "Users can only update their own account, unless they have the 'administer users' permission.";

      case 'DELETE':
        return "The 'cancel account' permission is required.";

      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

  /**
   * Tests PATCHing security-sensitive base fields of the logged in account.
   */
  public function testPatchDxForSecuritySensitiveBaseFields() {
    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $url = Url::fromRoute(sprintf('jsonapi.user--user.individual'), ['entity' => $this->account->uuid()]);
    /* $url = $this->account->toUrl('jsonapi'); */

    // Since this test must be performed by the user that is being modified,
    // we must use $this->account, not $this->entity.
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    $response = $this->request('GET', $url, $request_options);
    $original_normalization = Json::decode((string) $response->getBody());

    // Test case 1: changing email.
    $normalization = $original_normalization;
    $normalization['data']['attributes']['mail'] = 'new-email@example.com';
    $request_options[RequestOptions::BODY] = Json::encode($normalization);

    // DX: 405 when read-only mode is enabled.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(405, sprintf("JSON:API is configured to accept only read operations. Site administrators can configure this at %s.", Url::fromUri('base:/admin/config/services/jsonapi')->setAbsolute()->toString(TRUE)->getGeneratedUrl()), $url, $response);
    $this->assertSame(['GET'], $response->getHeader('Allow'));

    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // DX: 422 when changing email without providing the password.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'mail: Your current password is missing or incorrect; it\'s required to change the Email.', NULL, $response, '/data/attributes/mail');

    $normalization['data']['attributes']['pass']['existing'] = 'wrong';
    $request_options[RequestOptions::BODY] = Json::encode($normalization);

    // DX: 422 when changing email while providing a wrong password.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'mail: Your current password is missing or incorrect; it\'s required to change the Email.', NULL, $response, '/data/attributes/mail');

    $normalization['data']['attributes']['pass']['existing'] = $this->account->passRaw;
    $request_options[RequestOptions::BODY] = Json::encode($normalization);

    // 200 for well-formed request.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);

    // Test case 2: changing password.
    $normalization = Json::decode((string) $response->getBody());
    $normalization['data']['attributes']['mail'] = 'new-email@example.com';
    $new_password = $this->randomString();
    $normalization['data']['attributes']['pass']['value'] = $new_password;
    $request_options[RequestOptions::BODY] = Json::encode($normalization);

    // DX: 422 when changing password without providing the current password.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'pass: Your current password is missing or incorrect; it\'s required to change the Password.', NULL, $response, '/data/attributes/pass');

    $normalization['data']['attributes']['pass']['existing'] = $this->account->passRaw;
    $request_options[RequestOptions::BODY] = Json::encode($normalization);

    // 200 for well-formed request.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);

    // Verify that we can log in with the new password.
    $this->assertRpcLogin($this->account->getAccountName(), $new_password);

    // Update password in $this->account, prepare for future requests.
    $this->account->passRaw = $new_password;
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // Test case 3: changing name.
    $normalization = Json::decode((string) $response->getBody());
    $normalization['data']['attributes']['mail'] = 'new-email@example.com';
    $normalization['data']['attributes']['pass']['existing'] = $new_password;
    $normalization['data']['attributes']['name'] = 'Cooler Llama';
    $request_options[RequestOptions::BODY] = Json::encode($normalization);

    // DX: 403 when modifying username without required permission.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, 'The current user is not allowed to PATCH the selected field (name).', $url, $response, '/data/attributes/name');

    $this->grantPermissionsToTestedRole(['change own username']);

    // 200 for well-formed request.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);

    // Verify that we can log in with the new username.
    $this->assertRpcLogin('Cooler Llama', $new_password);
  }

  /**
   * Verifies that logging in with the given username and password works.
   *
   * @param string $username
   *   The username to log in with.
   * @param string $password
   *   The password to log in with.
   */
  protected function assertRpcLogin($username, $password) {
    $request_body = [
      'name' => $username,
      'pass' => $password,
    ];
    $request_options = [
      RequestOptions::HEADERS => [],
      RequestOptions::BODY => Json::encode($request_body),
    ];
    $response = $this->request('POST', Url::fromRoute('user.login.http')->setRouteParameter('_format', 'json'), $request_options);
    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * Tests PATCHing security-sensitive base fields to change other users.
   */
  public function testPatchSecurityOtherUser() {
    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $url = Url::fromRoute(sprintf('jsonapi.user--user.individual'), ['entity' => $this->account->uuid()]);
    /* $url = $this->account->toUrl('jsonapi'); */

    $original_normalization = $this->normalize($this->account, $url);

    // Since this test must be performed by the user that is being modified,
    // we must use $this->account, not $this->entity.
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    $normalization = $original_normalization;
    $normalization['data']['attributes']['mail'] = 'new-email@example.com';
    $request_options[RequestOptions::BODY] = Json::encode($normalization);

    // DX: 405 when read-only mode is enabled.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(405, sprintf("JSON:API is configured to accept only read operations. Site administrators can configure this at %s.", Url::fromUri('base:/admin/config/services/jsonapi')->setAbsolute()->toString(TRUE)->getGeneratedUrl()), $url, $response);
    $this->assertSame(['GET'], $response->getHeader('Allow'));

    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Try changing user 1's email.
    $user1 = $original_normalization;
    $user1['data']['attributes']['mail'] = 'another_email_address@example.com';
    $user1['data']['attributes']['uid'] = 1;
    $user1['data']['attributes']['name'] = 'another_user_name';
    $user1['data']['attributes']['pass']['existing'] = $this->account->passRaw;
    $request_options[RequestOptions::BODY] = Json::encode($user1);
    $response = $this->request('PATCH', $url, $request_options);
    // Ensure the email address has not changed.
    $this->assertEquals('admin@example.com', $this->entityStorage->loadUnchanged(1)->getEmail());
    $this->assertResourceErrorResponse(403, 'The current user is not allowed to PATCH the selected field (uid). The entity ID cannot be changed.', $url, $response, '/data/attributes/uid');
  }

  /**
   * Tests GETting privacy-sensitive base fields.
   */
  public function testGetMailFieldOnlyVisibleToOwner() {
    // Create user B, with the same roles (and hence permissions) as user A.
    $user_a = $this->account;
    $pass = user_password();
    $user_b = User::create([
      'name' => 'sibling-of-' . $user_a->getAccountName(),
      'mail' => 'sibling-of-' . $user_a->getAccountName() . '@example.com',
      'pass' => $pass,
      'status' => 1,
      'roles' => $user_a->getRoles(),
    ]);
    $user_b->save();
    $user_b->passRaw = $pass;

    // Grant permission to role that both users use.
    $this->grantPermissionsToTestedRole(['access user profiles']);

    $collection_url = Url::fromRoute('jsonapi.user--user.collection', [], ['query' => ['sort' => 'drupal_internal__uid']]);
    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $user_a_url = Url::fromRoute(sprintf('jsonapi.user--user.individual'), ['entity' => $user_a->uuid()]);
    /* $user_a_url = $user_a->toUrl('jsonapi'); */
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // Viewing user A as user A: "mail" field is accessible.
    $response = $this->request('GET', $user_a_url, $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertArrayHasKey('mail', $doc['data']['attributes']);
    // Also when looking at the collection.
    $response = $this->request('GET', $collection_url, $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertSame($user_a->uuid(), $doc['data']['2']['id']);
    $this->assertArrayHasKey('mail', $doc['data'][2]['attributes'], "Own user--user resource's 'mail' field is visible.");
    $this->assertSame($user_b->uuid(), $doc['data'][count($doc['data']) - 1]['id']);
    $this->assertArrayNotHasKey('mail', $doc['data'][count($doc['data']) - 1]['attributes']);

    // Now request the same URLs, but as user B (same roles/permissions).
    $this->account = $user_b;
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());
    // Viewing user A as user B: "mail" field should be inaccessible.
    $response = $this->request('GET', $user_a_url, $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertArrayNotHasKey('mail', $doc['data']['attributes']);
    // Also when looking at the collection.
    $response = $this->request('GET', $collection_url, $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertSame($user_a->uuid(), $doc['data']['2']['id']);
    $this->assertArrayNotHasKey('mail', $doc['data'][2]['attributes']);
    $this->assertSame($user_b->uuid(), $doc['data'][count($doc['data']) - 1]['id']);
    $this->assertArrayHasKey('mail', $doc['data'][count($doc['data']) - 1]['attributes']);
  }

  /**
   * Test good error DX when trying to filter users by role.
   */
  public function testQueryInvolvingRoles() {
    $this->setUpAuthorization('GET');

    $collection_url = Url::fromRoute('jsonapi.user--user.collection', [], ['query' => ['filter[roles.id][value]' => 'e9b1de3f-9517-4c27-bef0-0301229de792']]);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // The 'administer users' permission is required to filter by role entities.
    $this->grantPermissionsToTestedRole(['administer users']);

    $response = $this->request('GET', $collection_url, $request_options);
    $expected_cache_contexts = ['url.path', 'url.query_args:filter', 'url.site'];
    $this->assertResourceErrorResponse(400, "Filtering on config entities is not supported by Drupal's entity API. You tried to filter on a Role config entity.", $collection_url, $response, FALSE, ['4xx-response', 'http_response'], $expected_cache_contexts, FALSE, 'MISS');
  }

  /**
   * Tests that the collection contains the anonymous user.
   */
  public function testCollectionContainsAnonymousUser() {
    $url = Url::fromRoute('jsonapi.user--user.collection', [], ['query' => ['sort' => 'drupal_internal__uid']]);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    $response = $this->request('GET', $url, $request_options);
    $doc = Json::decode((string) $response->getBody());

    $this->assertCount(4, $doc['data']);
    $this->assertSame(User::load(0)->uuid(), $doc['data'][0]['id']);
    $this->assertSame('Anonymous', $doc['data'][0]['attributes']['name']);
  }

  /**
   * {@inheritdoc}
   */
  public function testCollectionFilterAccess() {
    // Set up data model.
    $this->assertTrue($this->container->get('module_installer')->install(['node'], TRUE), 'Installed modules.');
    FieldStorageConfig::create([
      'entity_type' => static::$entityTypeId,
      'field_name' => 'field_favorite_animal',
      'type' => 'string',
    ])
      ->setCardinality(1)
      ->save();
    FieldConfig::create([
      'entity_type' => static::$entityTypeId,
      'field_name' => 'field_favorite_animal',
      'bundle' => 'user',
    ])
      ->setLabel('Test field')
      ->setTranslatable(FALSE)
      ->save();
    $this->drupalCreateContentType(['type' => 'x']);
    $this->rebuildAll();
    $this->grantPermissionsToTestedRole(['access content']);

    // Create data.
    $user_a = User::create([])->setUsername('A')->activate();
    $user_a->save();
    $user_b = User::create([])->setUsername('B')->set('field_favorite_animal', 'stegosaurus')->block();
    $user_b->save();
    $node_a = Node::create(['type' => 'x'])->setTitle('Owned by A')->setOwner($user_a);
    $node_a->save();
    $node_b = Node::create(['type' => 'x'])->setTitle('Owned by B')->setOwner($user_b);
    $node_b->save();
    $node_anon_1 = Node::create(['type' => 'x'])->setTitle('Owned by anon #1')->setOwnerId(0);
    $node_anon_1->save();
    $node_anon_2 = Node::create(['type' => 'x'])->setTitle('Owned by anon #2')->setOwnerId(0);
    $node_anon_2->save();
    $node_auth_1 = Node::create(['type' => 'x'])->setTitle('Owned by auth #1')->setOwner($this->account);
    $node_auth_1->save();

    $favorite_animal_test_url = Url::fromRoute('jsonapi.user--user.collection')->setOption('query', ['filter[field_favorite_animal]' => 'stegosaurus']);

    // Test.
    $collection_url = Url::fromRoute('jsonapi.node--x.collection');
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());
    // ?filter[uid.id]=OWN_UUID requires no permissions: 1 result.
    $response = $this->request('GET', $collection_url->setOption('query', ['filter[uid.id]' => $this->account->uuid()]), $request_options);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Contexts', 'user.permissions');
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(1, $doc['data']);
    $this->assertSame($node_auth_1->uuid(), $doc['data'][0]['id']);
    // ?filter[uid.id]=ANONYMOUS_UUID: 0 results.
    $response = $this->request('GET', $collection_url->setOption('query', ['filter[uid.id]' => User::load(0)->uuid()]), $request_options);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Contexts', 'user.permissions');
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(0, $doc['data']);
    // ?filter[uid.name]=A: 0 results.
    $response = $this->request('GET', $collection_url->setOption('query', ['filter[uid.name]' => 'A']), $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(0, $doc['data']);
    // /jsonapi/user/user?filter[field_favorite_animal]: 0 results.
    $response = $this->request('GET', $favorite_animal_test_url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(0, $doc['data']);
    // Grant "view" permission.
    $this->grantPermissionsToTestedRole(['access user profiles']);
    // ?filter[uid.id]=ANONYMOUS_UUID: 0 results.
    $response = $this->request('GET', $collection_url->setOption('query', ['filter[uid.id]' => User::load(0)->uuid()]), $request_options);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Contexts', 'user.permissions');
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(0, $doc['data']);
    // ?filter[uid.name]=A: 1 result since user A is active.
    $response = $this->request('GET', $collection_url->setOption('query', ['filter[uid.name]' => 'A']), $request_options);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Contexts', 'user.permissions');
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(1, $doc['data']);
    $this->assertSame($node_a->uuid(), $doc['data'][0]['id']);
    // ?filter[uid.name]=B: 0 results since user B is blocked.
    $response = $this->request('GET', $collection_url->setOption('query', ['filter[uid.name]' => 'B']), $request_options);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Contexts', 'user.permissions');
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(0, $doc['data']);
    // /jsonapi/user/user?filter[field_favorite_animal]: 0 results.
    $response = $this->request('GET', $favorite_animal_test_url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(0, $doc['data']);
    // Grant "admin" permission.
    $this->grantPermissionsToTestedRole(['administer users']);
    // ?filter[uid.name]=B: 1 result.
    $response = $this->request('GET', $collection_url->setOption('query', ['filter[uid.name]' => 'B']), $request_options);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Contexts', 'user.permissions');
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(1, $doc['data']);
    $this->assertSame($node_b->uuid(), $doc['data'][0]['id']);
    // /jsonapi/user/user?filter[field_favorite_animal]: 1 result.
    $response = $this->request('GET', $favorite_animal_test_url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(1, $doc['data']);
    $this->assertSame($user_b->uuid(), $doc['data'][0]['id']);
  }

}
