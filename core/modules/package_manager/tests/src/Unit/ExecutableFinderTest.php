<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Unit;

use Drupal\package_manager\ExecutableFinder;
use Drupal\Tests\UnitTestCase;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;

/**
 * @covers \Drupal\package_manager\ExecutableFinder
 * @group package_manager
 * @internal
 */
class ExecutableFinderTest extends UnitTestCase {

  /**
   * Tests that the executable finder looks for paths in configuration.
   */
  public function testCheckConfigurationForExecutablePath(): void {
    $config_factory = $this->getConfigFactoryStub([
      'package_manager.settings' => [
        'executables' => [
          'composer' => '/path/to/composer',
        ],
      ],
    ]);

    $decorated = $this->prophesize(ExecutableFinderInterface::class);
    $decorated->find('composer')->shouldNotBeCalled();
    $decorated->find('rsync')->shouldBeCalled();

    $finder = new ExecutableFinder($decorated->reveal(), $config_factory);
    $this->assertSame('/path/to/composer', $finder->find('composer'));
    $finder->find('rsync');
  }

}
