<?php

namespace Drupal\Tests\system\Unit;

use Drupal\system\SystemRequirements;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\system\SystemRequirements
 * @group system
 * @group legacy
 */
class SystemRequirementsTest extends UnitTestCase {

  /**
   * @covers ::phpVersionWithPdoDisallowMultipleStatements
   * @dataProvider providerTestPhpVersionWithPdoDisallowMultipleStatements
   * @expectedDeprecation Drupal\system\SystemRequirements::phpVersionWithPdoDisallowMultipleStatements() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. All supported PHP versions support disabling multi-statement queries in MySQL. See https://www.drupal.org/node/3054692
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
