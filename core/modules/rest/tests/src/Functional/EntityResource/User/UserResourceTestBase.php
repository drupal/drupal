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
        ['value' => '3'],
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
          'value' => '123456789',
        ],
      ],
      'changed' => [
        [
          'value' => '123456789',
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
    $this->setUpAuthorization('PATCH');

    /** @var \Drupal\user\UserInterface $user */
    $user = static::$auth ? $this->account : User::load(0);
    $original_normalization = array_diff_key($this->serializer->normalize($user, static::$format), ['changed' => TRUE]);


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
    // @todo use this commented line instead of the 3 lines thereafter once https://www.drupal.org/node/2813755 lands.
    // $this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\nmail: Your current password is missing or incorrect; it's required to change the <em class=\"placeholder\">Email</em>.\n", $response);
    $this->assertSame(422, $response->getStatusCode());
    $this->assertSame([static::$mimeType], $response->getHeader('Content-Type'));
    $this->assertSame($this->serializer->encode(['message' => "Unprocessable Entity: validation failed.\nmail: Your current password is missing or incorrect; it's required to change the <em class=\"placeholder\">Email</em>.\n"], static::$format), (string) $response->getBody());


    $normalization['pass'] = [['existing' => 'wrong']];
    $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);

    // DX: 422 when changing email while providing a wrong password.
    $response = $this->request('PATCH', $url, $request_options);
    // @todo use this commented line instead of the 3 lines thereafter once https://www.drupal.org/node/2813755 lands.
    // $this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\nmail: Your current password is missing or incorrect; it's required to change the <em class=\"placeholder\">Email</em>.\n", $response);
    $this->assertSame(422, $response->getStatusCode());
    $this->assertSame([static::$mimeType], $response->getHeader('Content-Type'));
    $this->assertSame($this->serializer->encode(['message' => "Unprocessable Entity: validation failed.\nmail: Your current password is missing or incorrect; it's required to change the <em class=\"placeholder\">Email</em>.\n"], static::$format), (string) $response->getBody());


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
    // @todo use this commented line instead of the 3 lines thereafter once https://www.drupal.org/node/2813755 lands.
    // $this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\npass: Your current password is missing or incorrect; it's required to change the <em class=\"placeholder\">Password</em>.\n", $response);
    $this->assertSame(422, $response->getStatusCode());
    $this->assertSame([static::$mimeType], $response->getHeader('Content-Type'));
    $this->assertSame($this->serializer->encode(['message' => "Unprocessable Entity: validation failed.\npass: Your current password is missing or incorrect; it's required to change the <em class=\"placeholder\">Password</em>.\n"], static::$format), (string) $response->getBody());


    $normalization['pass'][0]['existing'] = $this->account->pass_raw;
    $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);


    // 200 for well-formed request.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);


    // Verify that we can log in with the new password.
    $request_body = [
      'name' => $user->getAccountName(),
      'pass' => $new_password,
    ];
    $request_options = [
      RequestOptions::HEADERS => [],
      RequestOptions::BODY => $this->serializer->encode($request_body, 'json'),
    ];
    $response = $this->httpClient->request('POST', Url::fromRoute('user.login.http')->setRouteParameter('_format', 'json')->toString(), $request_options);
    $this->assertSame(200, $response->getStatusCode());
  }

}
