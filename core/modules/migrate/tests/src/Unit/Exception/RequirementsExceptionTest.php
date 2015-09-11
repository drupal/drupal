<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\Exception\RequirementsExceptionTest.
 */

namespace Drupal\Tests\migrate\Unit\Exception;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate\Exception\RequirementsException
 * @group migration
 */
class RequirementsExceptionTest extends UnitTestCase {

  protected $missingRequirements = ['random_jackson_pivot', '51_Eridani_b'];

  /**
   * @covers ::getRequirements
   */
  public function testGetRequirements() {
    $exception = new RequirementsException('Missing requirements ', ['requirements' => $this->missingRequirements]);
    $this->assertArrayEquals(['requirements' => $this->missingRequirements], $exception->getRequirements());
  }

  /**
   * @covers ::getRequirementsString
   * @dataProvider getRequirementsProvider
   */
  public function testGetExceptionString($expected, $message, $requirements) {
    $exception = new RequirementsException($message, $requirements);
    $this->assertEquals($expected, $exception->getRequirementsString());
  }

  /**
   * Provides a list of requirements to test.
   */
  public function getRequirementsProvider() {
    return array(
      array(
        'requirements: random_jackson_pivot.',
        'Single Requirement',
        array('requirements' => $this->missingRequirements[0]),
      ),
      array(
        'requirements: random_jackson_pivot. requirements: 51_Eridani_b.',
        'Multiple Requirements',
        array('requirements' => $this->missingRequirements),
      ),
    );
  }
}
