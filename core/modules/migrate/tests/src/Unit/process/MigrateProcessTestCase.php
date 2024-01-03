<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Tests\migrate\Unit\MigrateTestCase;

abstract class MigrateProcessTestCase extends MigrateTestCase {

  /**
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $plugin;

  /**
   * @var \Drupal\migrate\Row|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $row;

  /**
   * @var \Drupal\migrate\MigrateExecutable|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $migrateExecutable;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();
    $this->migrateExecutable = $this->getMockBuilder('Drupal\migrate\MigrateExecutable')
      ->disableOriginalConstructor()
      ->getMock();

    parent::setUp();
  }

}
