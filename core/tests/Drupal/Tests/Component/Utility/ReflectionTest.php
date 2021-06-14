<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Reflection;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Utility\Reflection
 * @group Utility
 */
class ReflectionTest extends TestCase {

  /**
   * @param string|null $expected
   * @param \ReflectionParameter $parameter
   *
   * @covers ::getParameterClassName
   * @dataProvider providerGetParameterClassName
   */
  public function testGetParameterClassName(?string $expected, \ReflectionParameter $parameter) {
    $this->assertEquals($expected, Reflection::getParameterClassName($parameter));
  }

  /**
   * Data provider for ::testGetParameterClassName().
   *
   * @return array[]
   */
  public function providerGetParameterClassName() {
    $reflection_method = new \ReflectionMethod(static::class, 'existsForTesting');
    $parameters = $reflection_method->getParameters();
    return [
      'string' => [NULL, $parameters[0]],
      'array' => [NULL, $parameters[1]],
      'same class' => ['Drupal\Tests\Component\Utility\ReflectionTest', $parameters[2]],
      'class' => ['Drupal\Component\Utility\Reflection', $parameters[3]],
      'parent' => ['PHPUnit\Framework\TestCase', $parameters[4]],
      'self' => ['Drupal\Tests\Component\Utility\ReflectionTest', $parameters[5]],
    ];
  }

  /**
   * This method exists for reflection testing only.
   *
   * Note the capital P in Parent is intentional and for testing purposes.
   */
  // phpcs:disable Generic.PHP.LowerCaseKeyword.Found
  protected function existsForTesting(string $string, array $array, ReflectionTest $test, Reflection $reflection, Parent $parent, self $self) {
  }

  // phpcs:enable

}
