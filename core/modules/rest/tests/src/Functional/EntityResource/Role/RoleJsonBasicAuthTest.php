<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Role;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * @group rest
 */
class RoleJsonBasicAuthTest extends RoleResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected static $expectedErrorMimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'basic_auth';

  /**
   * {@inheritdoc}
   */
  protected function assertResponseWhenMissingAuthentication(ResponseInterface $response) {
    $this->assertSame(401, $response->getStatusCode());
    $this->assertSame('{"message":"A fatal error occurred: No authentication credentials provided."}', (string) $response->getBody());
  }

}
