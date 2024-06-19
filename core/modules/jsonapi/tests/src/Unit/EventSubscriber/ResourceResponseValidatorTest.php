<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Unit\EventSubscriber;

use Drupal\jsonapi\EventSubscriber\ResourceResponseValidator;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\Routing\Routes;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\rest\ResourceResponse;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\jsonapi\EventSubscriber\ResourceResponseValidator
 * @group jsonapi
 *
 * @internal
 */
class ResourceResponseValidatorTest extends UnitTestCase {

  /**
   * The subscriber under test.
   *
   * @var \Drupal\jsonapi\EventSubscriber\ResourceResponseSubscriber
   */
  protected $subscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Check that the validation class is available.
    if (!class_exists("\\JsonSchema\\Validator")) {
      $this->fail('The JSON Schema validator is missing. You can install it with `composer require justinrainbow/json-schema`.');
    }

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module = $this->prophesize(Extension::class);
    $module_path = dirname(__DIR__, 4);
    $module->getPath()->willReturn($module_path);
    $module_handler->getModule('jsonapi')->willReturn($module->reveal());
    $subscriber = new ResourceResponseValidator(
      $this->prophesize(LoggerInterface::class)->reveal(),
      $module_handler->reveal(),
      ''
    );
    $subscriber->setValidator();
    $this->subscriber = $subscriber;
  }

  /**
   * @covers ::validateResponse
   * @dataProvider validateResponseProvider
   */
  public function testValidateResponse($request, $response, $expected, $description): void {
    // Expose protected ResourceResponseSubscriber::validateResponse() method.
    $object = new \ReflectionObject($this->subscriber);
    $method = $object->getMethod('validateResponse');

    $this->assertSame($expected, $method->invoke($this->subscriber, $response, $request), $description);
  }

  /**
   * Provides test cases for testValidateResponse.
   *
   * @return array
   *   An array of test cases.
   */
  public static function validateResponseProvider() {
    $defaults = [
      'route_name' => 'jsonapi.node--article.individual',
      'resource_type' => new ResourceType('node', 'article', NULL),
    ];

    $test_data = [
      // Test validation success.
      [
        'json' => <<<'EOD'
{
  "data": {
    "type": "node--article",
    "id": "4f342419-e668-4b76-9f87-7ce20c436169",
    "attributes": {
      "nid": "1",
      "uuid": "4f342419-e668-4b76-9f87-7ce20c436169"
    }
  }
}
EOD
        ,
        'expected' => TRUE,
        'description' => 'Response validation flagged a valid response.',
      ],
      // Test validation failure: no "type" in "data".
      [
        'json' => <<<'EOD'
{
  "data": {
    "id": "4f342419-e668-4b76-9f87-7ce20c436169",
    "attributes": {
      "nid": "1",
      "uuid": "4f342419-e668-4b76-9f87-7ce20c436169"
    }
  }
}
EOD
        ,
        'expected' => FALSE,
        'description' => 'Response validation failed to flag an invalid response.',
      ],
      // Test validation failure: "errors" at the root level.
      [
        'json' => <<<'EOD'
{
  "data": {
  "type": "node--article",
    "id": "4f342419-e668-4b76-9f87-7ce20c436169",
    "attributes": {
    "nid": "1",
      "uuid": "4f342419-e668-4b76-9f87-7ce20c436169"
    }
  },
  "errors": [{}]
}
EOD
        ,
        'expected' => FALSE,
        'description' => 'Response validation failed to flag an invalid response.',
      ],
      // Test validation of an empty response passes.
      [
        'json' => NULL,
        'expected' => TRUE,
        'description' => 'Response validation flagged a valid empty response.',
      ],
      // Test validation fails on empty object.
      [
        'json' => '{}',
        'expected' => FALSE,
        'description' => 'Response validation flags empty array as invalid.',
      ],
    ];

    $test_cases = array_map(function ($input) use ($defaults) {
      [$json, $expected, $description, $route_name, $resource_type] = array_values($input + $defaults);
      return [
        static::createRequest($route_name, $resource_type),
        static::createResponse($json),
        $expected,
        $description,
      ];
    }, $test_data);

    return $test_cases;
  }

  /**
   * Helper method to create a request object.
   *
   * @param string $route_name
   *   The route name with which to construct a request.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type for the requested route.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The mock request object.
   */
  protected static function createRequest(string $route_name, ResourceType $resource_type): Request {
    $request = new Request();
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, $route_name);
    $request->attributes->set(Routes::RESOURCE_TYPE_KEY, $resource_type);
    return $request;
  }

  /**
   * Helper method to create a resource response from arbitrary JSON.
   *
   * @param string|null $json
   *   The JSON with which to create a mock response.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The mock response object.
   */
  protected static function createResponse(?string $json = NULL): ResourceResponse {
    $response = new ResourceResponse();
    if ($json) {
      $response->setContent($json);
    }
    return $response;
  }

}
