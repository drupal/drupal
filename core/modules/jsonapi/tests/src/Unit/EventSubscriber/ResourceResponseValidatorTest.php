<?php

namespace Drupal\Tests\jsonapi\Unit\EventSubscriber;

use Drupal\jsonapi\EventSubscriber\ResourceResponseValidator;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\Routing\Routes;
use JsonSchema\Validator;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\rest\ResourceResponse;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
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
   * @covers ::doValidateResponse
   */
  public function testDoValidateResponse() {
    $request = $this->createRequest(
      'jsonapi.node--article.individual',
      new ResourceType('node', 'article', NULL)
    );

    $response = $this->createResponse('{"data":null}');

    // Capture the default assert settings.
    $zend_assertions_default = ini_get('zend.assertions');
    $assert_active_default = assert_options(ASSERT_ACTIVE);

    // The validator *should* be called when asserts are active.
    $validator = $this->prophesize(Validator::class);
    $validator->check(Argument::any(), Argument::any())->shouldBeCalled('Validation should be run when asserts are active.');
    $validator->isValid()->willReturn(TRUE);
    $this->subscriber->setValidator($validator->reveal());

    // Ensure asset is active.
    ini_set('zend.assertions', 1);
    assert_options(ASSERT_ACTIVE, 1);
    $this->subscriber->doValidateResponse($response, $request);

    // The validator should *not* be called when asserts are inactive.
    $validator = $this->prophesize(Validator::class);
    $validator->check(Argument::any(), Argument::any())->shouldNotBeCalled('Validation should not be run when asserts are not active.');
    $this->subscriber->setValidator($validator->reveal());

    // Ensure asset is inactive.
    ini_set('zend.assertions', 0);
    assert_options(ASSERT_ACTIVE, 0);
    $this->subscriber->doValidateResponse($response, $request);

    // Reset the original assert values.
    ini_set('zend.assertions', $zend_assertions_default);
    assert_options(ASSERT_ACTIVE, $assert_active_default);
  }

  /**
   * @covers ::validateResponse
   * @dataProvider validateResponseProvider
   */
  public function testValidateResponse($request, $response, $expected, $description) {
    // Expose protected ResourceResponseSubscriber::validateResponse() method.
    $object = new \ReflectionObject($this->subscriber);
    $method = $object->getMethod('validateResponse');
    $method->setAccessible(TRUE);

    $this->assertSame($expected, $method->invoke($this->subscriber, $response, $request), $description);
  }

  /**
   * Provides test cases for testValidateResponse.
   *
   * @return array
   *   An array of test cases.
   */
  public function validateResponseProvider() {
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
        $this->createRequest($route_name, $resource_type),
        $this->createResponse($json),
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
  protected function createRequest($route_name, ResourceType $resource_type) {
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
  protected function createResponse($json = NULL) {
    $response = new ResourceResponse();
    if ($json) {
      $response->setContent($json);
    }
    return $response;
  }

}
