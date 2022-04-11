<?php

namespace Drupal\Tests\action\Unit\Plugin\migrate\source;

use Drupal\action\Plugin\migrate\source\Action;
use Drupal\Tests\migrate\Unit\MigrateTestCase;

/**
 * Tests actions source plugin.
 *
 * @covers \Drupal\action\Plugin\migrate\source\Action
 * @group legacy
 */
class ActionTest extends MigrateTestCase {

  /**
   * Tests deprecation of Action plugin.
   */
  public function testDeprecatedPlugin() {
    $this->expectDeprecation("The Drupal\action\Plugin\migrate\source\Action is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use \Drupal\system\Plugin\migrate\source\Action instead. See https://www.drupal.org/node/3110401");
    new Action(
      [],
      'action',
      [],
      $this->prophesize('Drupal\migrate\Plugin\MigrationInterface')->reveal(),
      $this->prophesize('Drupal\Core\State\StateInterface')->reveal(),
      $this->prophesize('Drupal\Core\Entity\EntityTypeManagerInterface')->reveal()
    );
  }

}
