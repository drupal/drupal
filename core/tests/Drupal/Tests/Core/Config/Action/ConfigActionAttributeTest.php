<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config\Action;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Config\Action\Attribute\ConfigAction
 * @group Config
 */
class ConfigActionAttributeTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testNoLabelNoDeriver(): void {
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage("The 'test' config action plugin must have either an admin label or a deriver");
    new ConfigAction('test');
  }

}
