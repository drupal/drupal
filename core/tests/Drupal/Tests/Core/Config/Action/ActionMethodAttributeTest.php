<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config\Action;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Config\Action\Attribute\ActionMethod;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Config\Action\Attribute\ActionMethod
 * @group Config
 */
class ActionMethodAttributeTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testInvalidFunctionName(): void {
    $name = "hello Goodbye";
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage("'$name' is not a valid PHP function name.");
    new ActionMethod(name: $name);
  }

}
