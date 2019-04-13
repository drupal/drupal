<?php

namespace Drupal\Tests\migrate\Unit\Plugin\migrate\destination;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\Config;

/**
 * Tests check requirements exception on DestinationBase.
 *
 * @group migrate
 */
class CheckRequirementsTest extends UnitTestCase {

  /**
   * Tests the check requirements exception message.
   */
  public function testException() {
    $destination = new Config(
      ['config_name' => 'test'],
      'test',
      [],
      $this->prophesize(MigrationInterface::class)->reveal(),
      $this->prophesize(ConfigFactoryInterface::class)->reveal(),
      $this->prophesize(LanguageManagerInterface::class)->reveal()
    );
    $this->setExpectedException(RequirementsException::class, "Destination plugin 'test' did not meet the requirements");
    $destination->checkRequirements();
  }

}
