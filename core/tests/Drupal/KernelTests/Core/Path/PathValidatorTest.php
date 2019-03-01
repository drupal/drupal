<?php

namespace Drupal\KernelTests\Core\Path;

use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the path validator.
 *
 * @group Path
 *
 * @see \Drupal\Core\Path\PathValidator
 */
class PathValidatorTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['path', 'entity_test', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpCurrentUser();
    $this->installEntitySchema('entity_test');
  }

  public function testGetUrlIfValidWithoutAccessCheck() {
    $requestContext = \Drupal::service('router.request_context');
    $pathValidator = \Drupal::service('path.validator');

    $entity = EntityTest::create([
      'name' => 'test',
    ]);
    $entity->save();

    $methods = [
      'POST',
      'GET',
      'PUT',
      'PATCH',
      'DELETE',
      // Used in CLI context.
      NULL,
      // If no request was even pushed onto the request stack, and hence.
      FALSE,
    ];
    foreach ($methods as $method) {
      if ($method === FALSE) {
        $request_stack = $this->container->get('request_stack');
        while ($request_stack->getCurrentRequest()) {
          $request_stack->pop();
        }
        $this->container->set('router.request_context', new RequestContext());
      }

      $requestContext->setMethod($method);
      /** @var \Drupal\Core\Url $url */
      $url = $pathValidator->getUrlIfValidWithoutAccessCheck($entity->toUrl()->toString(TRUE)->getGeneratedUrl());
      $this->assertEquals($method, $requestContext->getMethod());
      $this->assertInstanceOf(Url::class, $url);
      $this->assertSame(['entity_test' => $entity->id()], $url->getRouteParameters());
    }
  }

}
