<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateStub;

/**
 * Provides container handling for migration lookup unit tests.
 */
abstract class MigrationLookupTestCase extends MigrateProcessTestCase {

  /**
   * The prophecy of the migrate stub service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $migrateStub;

  /**
   * The prophecy of the migrate lookup service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $migrateLookup;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateStub = $this->prophesize(MigrateStub::class);
    $this->migrateLookup = $this->prophesize(MigrateLookupInterface::class);
  }

  /**
   * Prepares and sets the container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The prepared container.
   */
  protected function prepareContainer() {
    $container = new ContainerBuilder();
    $container->set('migrate.stub', $this->migrateStub->reveal());
    $container->set('migrate.lookup', $this->migrateLookup->reveal());
    \Drupal::setContainer($container);
    return $container;
  }

}
