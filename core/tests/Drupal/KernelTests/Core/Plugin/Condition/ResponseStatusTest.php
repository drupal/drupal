<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin\Condition;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Tests the Response Status Condition, provided by the system module.
 */
#[Group('Plugin')]
#[RunTestsInSeparateProcesses]
class ResponseStatusTest extends KernelTestBase {

  /**
   * The condition plugin manager under test.
   */
  protected ConditionManager $pluginManager;

  /**
   * The request stack used for testing.
   */
  protected RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('system');

    $this->pluginManager = $this->container->get('plugin.manager.condition');

    // Set the test request stack in the container.
    $this->requestStack = new RequestStack();
    $this->container->set('request_stack', $this->requestStack);
  }

  /**
   * Tests the request path condition.
   */
  #[DataProvider('providerTestConditions')]
  public function testConditions(array $status_codes, bool $negate, int $response_code, bool $expected_execute): void {
    if ($response_code === Response::HTTP_OK) {
      $request = Request::create('/my/valid/page');
    }
    else {
      $request = new Request();
      $request->attributes->set('exception', new HttpException($response_code));
    }
    $request->setSession(new Session(new MockArraySessionStorage()));
    $this->requestStack->push($request);

    /** @var \Drupal\system\Plugin\Condition\ResponseStatus $condition */
    $condition = $this->pluginManager->createInstance('response_status');
    $condition->setConfig('status_codes', $status_codes);
    $condition->setConfig('negate', $negate);

    $this->assertSame($expected_execute, $condition->execute());
  }

  /**
   * Provides test data for testConditions.
   */
  public static function providerTestConditions() {
    // Default values with 200 response code.
    yield [
      'status_codes' => [],
      'negate' => FALSE,
      'response_code' => Response::HTTP_OK,
      'expected_execute' => TRUE,
    ];

    // Default values with 403 response code.
    yield [
      'status_codes' => [],
      'negate' => FALSE,
      'response_code' => Response::HTTP_FORBIDDEN,
      'expected_execute' => TRUE,
    ];

    // Default values with 404 response code.
    yield [
      'status_codes' => [],
      'negate' => FALSE,
      'response_code' => Response::HTTP_NOT_FOUND,
      'expected_execute' => TRUE,
    ];

    // 200 status code enabled with 200 response code.
    yield [
      'status_codes' => [Response::HTTP_OK => Response::HTTP_OK],
      'negate' => FALSE,
      'response_code' => Response::HTTP_OK,
      'expected_execute' => TRUE,
    ];

    // 200 status code enabled with 403 response code.
    yield [
      'status_codes' => [Response::HTTP_OK => Response::HTTP_OK],
      'negate' => FALSE,
      'response_code' => Response::HTTP_FORBIDDEN,
      'expected_execute' => FALSE,
    ];

    // 200 status code enabled with 404 response code.
    yield [
      'status_codes' => [Response::HTTP_OK => Response::HTTP_OK],
      'negate' => FALSE,
      'response_code' => Response::HTTP_NOT_FOUND,
      'expected_execute' => FALSE,
    ];

    // 403 status code enabled with 200 response code.
    yield [
      'status_codes' => [Response::HTTP_FORBIDDEN => Response::HTTP_FORBIDDEN],
      'negate' => FALSE,
      'response_code' => Response::HTTP_OK,
      'expected_execute' => FALSE,
    ];

    // 403 status code enabled with 403 response code.
    yield [
      'status_codes' => [Response::HTTP_FORBIDDEN => Response::HTTP_FORBIDDEN],
      'negate' => FALSE,
      'response_code' => Response::HTTP_FORBIDDEN,
      'expected_execute' => TRUE,
    ];

    // 403 status code enabled with 404 response code.
    yield [
      'status_codes' => [Response::HTTP_FORBIDDEN => Response::HTTP_FORBIDDEN],
      'negate' => FALSE,
      'response_code' => Response::HTTP_NOT_FOUND,
      'expected_execute' => FALSE,
    ];

    // 200,403 status code enabled with 200 response code.
    yield [
      'status_codes' => [
        Response::HTTP_OK => Response::HTTP_OK,
        Response::HTTP_FORBIDDEN => Response::HTTP_FORBIDDEN,
      ],
      'negate' => FALSE,
      'response_code' => Response::HTTP_OK,
      'expected_execute' => TRUE,
    ];

    // 200,403 status code enabled with 403 response code.
    yield [
      'status_codes' => [
        Response::HTTP_OK => Response::HTTP_OK,
        Response::HTTP_FORBIDDEN => Response::HTTP_FORBIDDEN,
      ],
      'negate' => FALSE,
      'response_code' => Response::HTTP_FORBIDDEN,
      'expected_execute' => TRUE,
    ];

    // 200,403 status code enabled with 404 response code.
    yield [
      'status_codes' => [
        Response::HTTP_OK => Response::HTTP_OK,
        Response::HTTP_FORBIDDEN => Response::HTTP_FORBIDDEN,
      ],
      'negate' => FALSE,
      'response_code' => Response::HTTP_NOT_FOUND,
      'expected_execute' => FALSE,
    ];

    // 200,404 status code enabled with 200 response code.
    yield [
      'status_codes' => [
        Response::HTTP_OK => Response::HTTP_OK,
        Response::HTTP_NOT_FOUND => Response::HTTP_NOT_FOUND,
      ],
      'negate' => FALSE,
      'response_code' => Response::HTTP_OK,
      'expected_execute' => TRUE,
    ];

    // 200,404 status code enabled with 403 response code.
    yield [
      'status_codes' => [
        Response::HTTP_OK => Response::HTTP_OK,
        Response::HTTP_NOT_FOUND => Response::HTTP_NOT_FOUND,
      ],
      'negate' => FALSE,
      'response_code' => Response::HTTP_FORBIDDEN,
      'expected_execute' => FALSE,
    ];

    // 200,404 status code enabled with 404 response code.
    yield [
      'status_codes' => [
        Response::HTTP_OK => Response::HTTP_OK,
        Response::HTTP_NOT_FOUND => Response::HTTP_NOT_FOUND,
      ],
      'negate' => FALSE,
      'response_code' => Response::HTTP_NOT_FOUND,
      'expected_execute' => TRUE,
    ];

    // 403,404 status code enabled with 200 response code.
    yield [
      'status_codes' => [
        Response::HTTP_FORBIDDEN => Response::HTTP_FORBIDDEN,
        Response::HTTP_NOT_FOUND => Response::HTTP_NOT_FOUND,
      ],
      'negate' => FALSE,
      'response_code' => Response::HTTP_OK,
      'expected_execute' => FALSE,
    ];

    // 403,404 status code enabled with 403 response code.
    yield [
      'status_codes' => [
        Response::HTTP_FORBIDDEN => Response::HTTP_FORBIDDEN,
        Response::HTTP_NOT_FOUND => Response::HTTP_NOT_FOUND,
      ],
      'negate' => FALSE,
      'response_code' => Response::HTTP_FORBIDDEN,
      'expected_execute' => TRUE,
    ];

    // 403,404 status code enabled with 404 response code.
    yield [
      'status_codes' => [
        Response::HTTP_FORBIDDEN => Response::HTTP_FORBIDDEN,
        Response::HTTP_NOT_FOUND => Response::HTTP_NOT_FOUND,
      ],
      'negate' => FALSE,
      'response_code' => Response::HTTP_NOT_FOUND,
      'expected_execute' => TRUE,
    ];

    // 200,403,404 status code enabled with 200 response code.
    yield [
      'status_codes' => [
        Response::HTTP_OK => Response::HTTP_OK,
        Response::HTTP_FORBIDDEN => Response::HTTP_FORBIDDEN,
        Response::HTTP_NOT_FOUND => Response::HTTP_NOT_FOUND,
      ],
      'negate' => FALSE,
      'response_code' => Response::HTTP_OK,
      'expected_execute' => TRUE,
    ];

    // 200,403 status code enabled with 403 response code.
    yield [
      'status_codes' => [
        Response::HTTP_OK => Response::HTTP_OK,
        Response::HTTP_FORBIDDEN => Response::HTTP_FORBIDDEN,
        Response::HTTP_NOT_FOUND => Response::HTTP_NOT_FOUND,
      ],
      'negate' => FALSE,
      'response_code' => Response::HTTP_FORBIDDEN,
      'expected_execute' => TRUE,
    ];

    // 200,403 status code enabled with 404 response code.
    yield [
      'status_codes' => [
        Response::HTTP_OK => Response::HTTP_OK,
        Response::HTTP_FORBIDDEN => Response::HTTP_FORBIDDEN,
        Response::HTTP_NOT_FOUND => Response::HTTP_NOT_FOUND,
      ],
      'negate' => FALSE,
      'response_code' => Response::HTTP_NOT_FOUND,
      'expected_execute' => TRUE,
    ];

  }

  /**
   * Provides test data for ::testStatusCodesValidation().
   */
  public static function providerStatusCodesValidation(): \Iterator {
    yield 'OK: empty status_codes' => [[], []];
    yield 'OK: 200' => [[Response::HTTP_OK], []];
    yield 'OK: all supported status codes' => [
      [Response::HTTP_OK, Response::HTTP_FORBIDDEN, Response::HTTP_NOT_FOUND],
      [],
    ];
    yield 'INVALID: 418' => [
      [Response::HTTP_I_AM_A_TEAPOT],
      ['status_codes.0' => 'The value you selected is not a valid choice.'],
    ];
    yield 'INVALID: 200 and 418' => [
      [Response::HTTP_OK, Response::HTTP_I_AM_A_TEAPOT],
      ['status_codes.1' => 'The value you selected is not a valid choice.'],
    ];
  }

  /**
   * Tests the schema constraints on the `status_codes` config.
   */
  #[DataProvider('providerStatusCodesValidation')]
  public function testStatusCodesValidation(array $status_codes, array $expected_messages): void {
    $typed_config = $this->container->get(TypedConfigManagerInterface::class);
    $data = [
      'id' => 'response_status',
      'negate' => FALSE,
      'context_mapping' => [],
      'status_codes' => $status_codes,
    ];
    $definition = $typed_config->getDefinition('condition.plugin.response_status');
    $data_definition = $typed_config->buildDataDefinition($definition, $data);
    $violations = $typed_config->create($data_definition, $data)->validate();

    $actual_messages = [];
    foreach ($violations as $violation) {
      $actual_messages[$violation->getPropertyPath()] = (string) $violation->getMessage();
    }
    $this->assertSame($expected_messages, $actual_messages);
  }

}
