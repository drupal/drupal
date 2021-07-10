<?php

namespace Drupal\Tests\jsonapi\Unit\EventSubscriber;

use Drupal\jsonapi\EventSubscriber\ResourceResponseValidator;
use JsonSchema\Validator;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\jsonapi\ResourceResponse;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

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
    if (!class_exists(Validator::class)) {
      static::fail('The JSON Schema validator is missing. You can install it with `composer require justinrainbow/json-schema`.');
    }

    $module = $this->prophesize(Extension::class);
    $module->getPath()->willReturn(dirname(__DIR__, 4));

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module_handler->getModule('jsonapi')->willReturn($module->reveal());

    $this->subscriber = new ResourceResponseValidator(
      $this->prophesize(LoggerInterface::class)->reveal(),
      $module_handler->reveal(),
      ''
    );
    $this->subscriber->setValidator();
  }

  /**
   * @covers ::doValidateResponse
   */
  public function testDoValidateResponse() {
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
    $this->subscriber->doValidateResponse($response);

    // The validator should *not* be called when asserts are inactive.
    $validator = $this->prophesize(Validator::class);
    $validator->check(Argument::any(), Argument::any())->shouldNotBeCalled('Validation should not be run when asserts are not active.');
    $this->subscriber->setValidator($validator->reveal());

    // Ensure asset is inactive.
    ini_set('zend.assertions', 0);
    assert_options(ASSERT_ACTIVE, 0);
    $this->subscriber->doValidateResponse($response);

    // Reset the original assert values.
    ini_set('zend.assertions', $zend_assertions_default);
    assert_options(ASSERT_ACTIVE, $assert_active_default);
  }

  /**
   * @covers ::validateResponse
   * @dataProvider validateResponseProvider
   */
  public function testValidateResponse($response, $expected, $description) {
    // Expose protected ResourceResponseSubscriber::validateResponse() method.
    $method = new \ReflectionMethod($this->subscriber, 'validateResponse');
    $method->setAccessible(TRUE);

    static::assertSame($expected, $method->invoke($this->subscriber, $response), $description);
  }

  /**
   * Provides test cases for testValidateResponse.
   *
   * @return array
   *   An array of test cases.
   */
  public function validateResponseProvider() {
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

    return array_map(function (array $input): array {
      return [
        $this->createResponse($input['json']),
        $input['expected'],
        $input['description'],
      ];
    }, $test_data);
  }

  /**
   * Helper method to create a resource response from arbitrary JSON.
   *
   * @param string|null $json
   *   The JSON with which to create a mock response.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The mock response object.
   */
  protected function createResponse(string $json = NULL): ResourceResponse {
    $response = new ResourceResponse();
    if ($json) {
      $response->setContent($json);
    }
    return $response;
  }

}
