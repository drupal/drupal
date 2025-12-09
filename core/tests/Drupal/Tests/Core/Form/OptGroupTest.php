<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\OptGroup;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Form\OptGroup.
 */
#[CoversClass(OptGroup::class)]
#[Group('Form')]
class OptGroupTest extends UnitTestCase {

  /**
   * Tests the flattenOptions() method.
   */
  #[DataProvider('providerTestFlattenOptions')]
  public function testFlattenOptions($options): void {
    $this->assertSame(['foo' => 'foo'], OptGroup::flattenOptions($options));
  }

  /**
   * Provides test data for the flattenOptions() method.
   *
   * @return array
   *   An array of option structures to be flattened.
   */
  public static function providerTestFlattenOptions(): array {
    $object1 = new \stdClass();
    $object1->option = ['foo' => 'foo'];
    $object2 = new \stdClass();
    $object2->option = [['foo' => 'foo'], ['foo' => 'foo']];
    $object3 = new \stdClass();
    return [
      [['foo' => 'foo']],
      [[['foo' => 'foo']]],
      [[$object1]],
      [[$object2]],
      [[$object1, $object2]],
      [['foo' => $object3, $object1, ['foo' => 'foo']]],
    ];
  }

}
