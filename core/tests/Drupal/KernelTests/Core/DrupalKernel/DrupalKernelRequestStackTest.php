<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\DrupalKernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tests the usage of the request stack as part of request processing.
 */
#[CoversClass(DrupalKernel::class)]
#[Group('DrupalKernel')]
#[RunTestsInSeparateProcesses]
class DrupalKernelRequestStackTest extends KernelTestBase implements EventSubscriberInterface {

  /**
   * The request stack requests when the kernel request event is fired.
   *
   * @var array{'main': \Symfony\Component\HttpFoundation\Request, 'parent': \Symfony\Component\HttpFoundation\Request, 'current': \Symfony\Component\HttpFoundation\Request}|null
   */
  protected ?array $recordedRequests;

  /**
   * The request stack count when the kernel request event is fired.
   */
  protected ?int $recordedRequestStackCount;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['http_kernel_test', 'system'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $this->container->set(self::class, $this);
    $container->getDefinition(self::class)
      ->addTag('event_subscriber');
  }

  /**
   * Tests request stack when sub requests are made.
   *
   * It compares master, current, and parent Request objects before and after
   * StackedHttpKernel::handle(), StackedHttpKernel::terminate(), and sub
   * requests
   */
  public function testRequestStackHandling(): void {
    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = \Drupal::service('request_stack');
    // KernelTestBase pushes a request on to the stack.
    $request_stack->pop();

    $http_kernel = \Drupal::service('kernel');

    $main_request = Request::create('/http-kernel-test');
    $sub_request_1 = Request::create('/http-kernel-test');
    $sub_request_2 = Request::create('/http-kernel-test-sub-request');
    $request_404 = Request::create('/does_not_exist');

    $this->assertNull($request_stack->getMainRequest());

    // Make the main request.
    $this->recordedRequestStackCount = $this->recordedRequests = NULL;
    $main_response = $http_kernel->handle($main_request);
    $this->assertSame($main_request, $request_stack->getMainRequest());
    $this->assertSame($main_request, $request_stack->getCurrentRequest());
    $this->assertSame($main_request, $this->recordedRequests['current']);
    $this->assertSame($main_request, $this->recordedRequests['main']);
    $this->assertNull($this->recordedRequests['parent']);
    $this->assertSame(1, $this->recordedRequestStackCount);
    $this->assertSame(1, $this->getRequestStackCount($request_stack));

    // Make a sub request.
    $this->recordedRequestStackCount = $this->recordedRequests = NULL;
    $http_kernel->handle($sub_request_1, HttpKernelInterface::SUB_REQUEST);
    $this->assertSame($main_request, $request_stack->getMainRequest());
    $this->assertSame($main_request, $request_stack->getCurrentRequest());
    $this->assertSame($sub_request_1, $this->recordedRequests['current']);
    $this->assertSame($main_request, $this->recordedRequests['main']);
    $this->assertSame($main_request, $this->recordedRequests['parent']);
    $this->assertSame(2, $this->recordedRequestStackCount);
    $this->assertSame(1, $this->getRequestStackCount($request_stack));

    // Make a sub request that makes a sub request.
    $this->recordedRequestStackCount = $this->recordedRequests = NULL;
    $http_kernel->handle($sub_request_2, HttpKernelInterface::SUB_REQUEST);
    $this->assertSame($main_request, $request_stack->getMainRequest());
    $this->assertSame($main_request, $request_stack->getCurrentRequest());
    $this->assertNotSame($sub_request_2, $this->recordedRequests['current']);
    $this->assertSame('/http-kernel-test-sub-sub-request', $this->recordedRequests['current']->getPathInfo());
    $this->assertSame($sub_request_2, $this->recordedRequests['parent']);
    $this->assertSame($main_request, $this->recordedRequests['main']);
    $this->assertSame(3, $this->recordedRequestStackCount);
    $this->assertSame(1, $this->getRequestStackCount($request_stack));

    // Make 404 sub request.
    $this->recordedRequestStackCount = $this->recordedRequests = NULL;
    $http_kernel->handle($request_404, HttpKernelInterface::SUB_REQUEST);
    $this->assertSame($main_request, $request_stack->getMainRequest());
    $this->assertSame($main_request, $request_stack->getCurrentRequest());
    $this->assertNotSame($request_404, $this->recordedRequests['current']);
    $this->assertSame('/does_not_exist', $this->recordedRequests['current']->getPathInfo());
    $this->assertSame('system.404', $this->recordedRequests['current']->attributes->get(RouteObjectInterface::ROUTE_NAME));
    $this->assertSame($request_404, $this->recordedRequests['parent']);
    $this->assertSame($main_request, $this->recordedRequests['main']);
    $this->assertSame(3, $this->recordedRequestStackCount);
    $this->assertSame(1, $this->getRequestStackCount($request_stack));

    $http_kernel->terminate($main_request, $main_response);
    // After termination the stack should be empty.
    $this->assertNull($request_stack->getMainRequest());
    $this->assertSame(0, $this->getRequestStackCount($request_stack));

    // Make 404 main request.
    $this->recordedRequestStackCount = $this->recordedRequests = NULL;
    $response_404 = $http_kernel->handle($request_404);
    $this->assertSame($request_404, $request_stack->getMainRequest());
    $this->assertSame($request_404, $request_stack->getCurrentRequest());
    $this->assertNotSame($request_404, $this->recordedRequests['current']);
    $this->assertSame('/does_not_exist', $this->recordedRequests['current']->getPathInfo());
    $this->assertSame('system.404', $this->recordedRequests['current']->attributes->get(RouteObjectInterface::ROUTE_NAME));
    $this->assertSame($request_404, $this->recordedRequests['parent']);
    $this->assertSame($request_404, $this->recordedRequests['main']);
    $this->assertSame(2, $this->recordedRequestStackCount);
    $this->assertSame(1, $this->getRequestStackCount($request_stack));

    $http_kernel->terminate($request_404, $response_404);
    // After termination the stack should be empty.
    $this->assertNull($request_stack->getMainRequest());
    $this->assertSame(0, $this->getRequestStackCount($request_stack));
  }

  /**
   * {@inheritdoc}
   */
  public function checkErrorHandlerOnTearDown(): void {
    // This test calls DrupalKernel::terminate() which removes the error
    // handler invalidating this check.
  }

  /**
   * Records the current request and master request for testing.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event): void {
    $request_stack = $this->container->get('request_stack');
    if ($request_stack->getCurrentRequest() !== $event->getRequest()) {
      throw new \Exception('Current request is not the same as the event request.');
    }
    $this->recordedRequests = [
      'main' => $request_stack->getMainRequest(),
      'parent' => $request_stack->getParentRequest(),
      'current' => $request_stack->getCurrentRequest(),
    ];

    $this->recordedRequestStackCount = $this->getRequestStackCount($request_stack);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onRequest', 50],
    ];
  }

  /**
   * Uses reflection to count the number of requests in the request stack.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   *
   * @return int
   *   The number of requests in the stack.
   */
  private function getRequestStackCount(RequestStack $request_stack): int {
    // Create reflection object
    $reflection = new \ReflectionClass($request_stack);
    // Get the private property
    return count($reflection->getProperty('requests')->getValue($request_stack));
  }

}
