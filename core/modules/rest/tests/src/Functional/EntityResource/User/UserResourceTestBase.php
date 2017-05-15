<?php

namespace Drupal\Tests\rest\Functional\EntityResource\User;

use Drupal\Core\Url;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\user\Entity\User;
use GuzzleHttp\RequestOptions;

abstract class UserResourceTestBase extends EntityResourceTestBase {

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
  protected static $patchProtectedFieldNames = [
    'changed',
  ];

  /**
   * @var \Drupal\user\UserInterface
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
  protected function getExpectedNormalizedEntity() {
    return [
      'uid' => [
        ['value' => 3],
      ],
      'uuid' => [
        ['value' => $this->entity->uuid()],
      ],
      'langcode' => [
        [
          'value' => 'en',
        ],
      ],
      'name' => [
        [
          'value' => 'Llama',
        ],
      ],
      'created' => [
        [
          'value' => 123456789,
        ],
      ],
      'changed' => [
        [
          'value' => $this->entity->getChangedTime(),
        ],
      ],
      'default_langcode' => [
        [
          'value' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return [
      'name' => [
        [
          'value' => 'Dramallama ' . $this->randomMachineName(),
        ],
      ],
    ];
  }

  /**
   * Tests PATCHing security-sensitive base fields of the logged in account.
   */
  public function testPatchDxForSecuritySensitiveBaseFields() {
    // The anonymous user is never allowed to modify itself.
    if (!static::$auth) {
      $this->markTestSkipped();
    }

    $this->initAuthentication();
    $this->provisionEntityResource();

    /** @var \Drupal\user\UserInterface $user */
    $user = static::$auth ? $this->account : User::load(0);
    // @todo Remove the array_diff_key() call in https://www.drupal.org/node/2821077.
    $original_normalization = array_diff_key($this->serializer->normalize($user, static::$format), ['created' => TRUE, 'changed' => TRUE, 'name' => TRUE]);


    // Since this test must be performed by the user that is being modified,
    // we cannot use $this->getUrl().
    $url = $user->toUrl()->setOption('query', ['_format' => static::$format]);
    $request_options = [
      RequestOptions::HEADERS => ['Content-Type' => static::$mimeType],
    ];
    $request_options = array_merge_recursive($request_options, $this->getAuthenticationRequestOptions('PATCH'));


    // Test case 1: changing email.
    $normalization = $original_normalization;
    $normalization['mail'] = [['value' => 'new-email@example.com']];
    $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);


    // DX: 422 when changing email without providing the password.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\nmail: Your current password is missing or incorrect; it's required to change the Email.\n", $response);


    $normalization['pass'] = [['existing' => 'wrong']];
    $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);

    // DX: 422 when changing email while providing a wrong password.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\nmail: Your current password is missing or incorrect; it's required to change the Email.\n", $response);


    $normalization['pass'] = [['existing' => $this->account->passRaw]];
    $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);


    // 200 for well-formed request.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);


    // Test case 2: changing password.
    $normalization = $original_normalization;
    $new_password = $this->randomString();
    $normalization['pass'] = [['value' => $new_password]];
    $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);


    // DX: 422 when changing password without providing the current password.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\npass: Your current password is missing or incorrect; it's required to change the Password.\n", $response);


    $normalization['pass'][0]['existing'] = $this->account->pass_raw;
    $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);


    // 200 for well-formed request.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);


    // Verify that we can log in with the new password.
    $this->assertRpcLogin($user->getAccountName(), $new_password);


    // Update password in $this->account, prepare for future requests.
    $this->account->passRaw = $new_password;
    $this->initAuthentication();
    $request_options = [
      RequestOptions::HEADERS => ['Content-Type' => static::$mimeType],
    ];
    $request_options = array_merge_recursive($request_options, $this->getAuthenticationRequestOptions('PATCH'));


    // Test case 3: changing name.
    $normalization = $original_normalization;
    $normalization['name'] = [['value' => 'Cooler Llama']];
    $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);


    // DX: 403 when modifying username without required permission.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, "Access denied on updating field 'name'.", $response);


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
      RequestOptions::BODY => $this->serializer->encode($request_body, 'json'),
    ];
    $response = $this->request('POST', Url::fromRoute('user.login.http')->setRouteParameter('_format', 'json'), $request_options);
    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    switch ($method) {
      case 'GET':
        return "The 'access user profiles' permission is required and the user must be active.";
      case 'PATCH':
        return "You are not authorized to update this user entity.";
      case 'DELETE':
        return 'You are not authorized to delete this user entity.';
      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

}
