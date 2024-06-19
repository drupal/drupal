<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin\Condition;

use Drupal\Core\Condition\ConditionManager;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Tests the Response Status Condition, provided by the system module.
 *
 * @group Plugin
 */
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
   *
   * @dataProvider providerTestConditions
   */
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

}
