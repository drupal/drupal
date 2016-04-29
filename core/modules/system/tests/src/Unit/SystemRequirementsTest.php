<?php

namespace Drupal\Tests\system\Unit;

use Drupal\system\SystemRequirements;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\system\SystemRequirements
 * @group system
 */
class SystemRequirementsTest extends UnitTestCase {

  /**
   * @covers ::phpVersionWithPdoDisallowMultipleStatements
   * @dataProvider providerTestPhpVersionWithPdoDisallowMultipleStatements
   */
  public function testPhpVersionWithPdoDisallowMultipleStatements($version, $expected) {
    $this->assertEquals($expected, SystemRequirements::phpVersionWithPdoDisallowMultipleStatements($version));
  }

  public function providerTestPhpVersionWithPdoDisallowMultipleStatements() {
    $data = [];
    $data[] = ['5.4.2', FALSE];
    $data[] = ['5.4.21', FALSE];
    $data[] = ['5.5.9', FALSE];
    $data[] = ['5.5.20', FALSE];
    $data[] = ['5.5.21', TRUE];
    $data[] = ['5.5.30', TRUE];
    $data[] = ['5.6.2', FALSE];
    $data[] = ['5.6.5', TRUE];
    $data[] = ['5.5.21', TRUE];
    return $data;
  }

}
