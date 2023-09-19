<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\ResourceTestBase;

/**
 * Ensures that the 'api_json' format is not supported by the REST module.
 *
 * @group jsonapi
 *
 * @internal
 */
class RestJsonApiUnsupported extends ResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['jsonapi', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $format = 'api_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/vnd.api+json';

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'entity.node';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access content']);
        break;

      default:
        throw new \UnexpectedValueException();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.logging')->set('error_level', ERROR_REPORTING_HIDE)->save();

    // Create a "Camelids" node type.
    NodeType::create([
      'name' => 'Camelids',
      'type' => 'camelids',
    ])->save();

    // Create a "Llama" node.
    $node = Node::create(['type' => 'camelids']);
    $node->setTitle('Llama')
      ->setOwnerId(0)
      ->setPublished()
      ->save();
  }

  /**
   * Deploying a REST resource using api_json format results in 400 responses.
   *
   * @see \Drupal\jsonapi\EventSubscriber\JsonApiRequestValidator::validateQueryParams()
   */
  public function testApiJsonNotSupportedInRest() {
    $this->assertSame(['json', 'xml'], $this->container->getParameter('serializer.formats'));

    $this->provisionResource(['api_json'], []);
    $this->setUpAuthorization('GET');

    $url = Node::load(1)->toUrl()
      ->setOption('query', ['_format' => 'api_json']);
    $request_options = [];

    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(
      400,
      FALSE,
      $response,
      ['4xx-response', 'config:system.logging', 'config:user.role.anonymous', 'http_response', 'node:1'],
      ['url.query_args:_format', 'url.site', 'user.permissions'],
      'MISS',
      'MISS'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function assertNormalizationEdgeCases($method, Url $url, array $request_options): void {}

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {
    return (new CacheableMetadata());
  }

}
