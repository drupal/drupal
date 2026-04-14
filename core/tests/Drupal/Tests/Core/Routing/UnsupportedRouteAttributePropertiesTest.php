<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\AttributeRouteDiscovery;
use Drupal\Core\Routing\UnsupportedRouteAttributePropertyException;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Tests \Drupal\Core\Routing\AttributeRouteDiscovery.
 */
#[CoversClass(AttributeRouteDiscovery::class)]
#[Group('Routing')]
class UnsupportedRouteAttributePropertiesTest extends UnitTestCase {

  /**
   * @legacy-covers ::createRouteCollection
   */
  #[DataProvider('providerTestException')]
  public function testException(string $class, string $message): void {
    $discovery = new AttributeRouteDiscovery(new \ArrayIterator());
    $reflection = new \ReflectionClass($discovery);
    $method = $reflection->getMethod('createRouteCollection');
    $this->expectException(UnsupportedRouteAttributePropertyException::class);
    $this->expectExceptionMessage($message);
    $method->invoke($discovery, $class);
  }

  public static function providerTestException(): array {
    return [
      'method: missing_name' => [
        MethodRouteMissingName::class,
        'The Route attribute on "Drupal\Tests\Core\Routing\MethodRouteMissingName::attributeMethod()" is missing a required "name" property.',
      ],
      'method: locale' => [
        MethodRouteLocale::class,
        'The "locale" route attribute is not supported on route "MethodRouteLocale" in "Drupal\Tests\Core\Routing\MethodRouteLocale::attributeMethod()"',
      ],
      'method: localized_paths' => [
        MethodRouteLocalizedPaths::class,
        'The "path" route attribute does not support arrays on route "MethodRouteLocalizedPaths" in "Drupal\Tests\Core\Routing\MethodRouteLocalizedPaths::attributeMethod()"',
      ],
      'method: condition' => [
        MethodRouteCondition::class,
        'The "condition" route attribute is not supported on route "MethodRouteCondition" in "Drupal\Tests\Core\Routing\MethodRouteCondition::attributeMethod()"',
      ],
      'class: locale' => [
        ClassRouteLocale::class,
        'The "locale" route attribute is not supported in class "Drupal\Tests\Core\Routing\ClassRouteLocale"',
      ],
      'class: localized_paths' => [
        ClassRouteLocalizedPaths::class,
        'The "path" route attribute does not support arrays in class "Drupal\Tests\Core\Routing\ClassRouteLocalizedPaths"',
      ],
      'class: condition' => [
        ClassRouteCondition::class,
        'The "condition" route attribute is not supported in class "Drupal\Tests\Core\Routing\ClassRouteCondition"',
      ],
    ];
  }

}

/**
 * Test class.
 */
class MethodRouteLocale {

  #[Route('/test_method_attribute', 'MethodRouteLocale', locale: 'de')]
  public function attributeMethod(): array {
    return ['#markup' => 'Testing method with a Route attribute'];
  }

}

/**
 * Test class.
 */
class MethodRouteLocalizedPaths {

  #[Route(['de' => '/test_method_attribute'], 'MethodRouteLocalizedPaths')]
  public function attributeMethod(): array {
    return ['#markup' => 'Testing method with a Route attribute'];
  }

}

/**
 * Test class.
 */
class MethodRouteCondition {

  #[Route('/test_method_attribute', 'MethodRouteCondition', condition: "context.getMethod() == 'GET'")]
  public function attributeMethod(): array {
    return ['#markup' => 'Testing method with a Route attribute'];
  }

}

/**
 * Test class.
 */
#[Route(locale: 'de')]
class ClassRouteLocale {

  #[Route('/test_method_attribute')]
  public function attributeMethod(): array {
    return ['#markup' => 'Testing method with a Route attribute'];
  }

}

/**
 * Test class.
 */
#[Route(['de' => 'prefix/'])]
class ClassRouteLocalizedPaths {

  #[Route('/test_method_attribute')]
  public function attributeMethod(): array {
    return ['#markup' => 'Testing method with a Route attribute'];
  }

}

/**
 * Test class.
 */
#[Route(condition: "context.getMethod() == 'GET'")]
class ClassRouteCondition {

  #[Route('/test_method_attribute', 'test')]
  public function attributeMethod(): array {
    return ['#markup' => 'Testing method with a Route attribute'];
  }

}

/**
 * Test class.
 */
#[Route(name: 'prefix')]
class MethodRouteMissingName {

  #[Route('/test_method_attribute')]
  public function attributeMethod(): array {
    return ['#markup' => 'Testing method with a Route attribute'];
  }

}
