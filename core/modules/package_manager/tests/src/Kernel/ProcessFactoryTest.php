<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\ProcessFactory;
use PhpTuf\ComposerStager\API\Process\Factory\ProcessFactoryInterface;

/**
 * @coversDefaultClass \Drupal\package_manager\ProcessFactory
 * @group auto_updates
 * @internal
 */
class ProcessFactoryTest extends PackageManagerKernelTestBase {

  /**
   * Tests that the process factory prepends the PHP directory to PATH.
   */
  public function testPhpDirectoryPrependedToPath(): void {
    $factory = $this->container->get(ProcessFactoryInterface::class);
    $this->assertInstanceOf(ProcessFactory::class, $factory);

    // Ensure that the directory of the PHP interpreter can be found.
    $reflector = new \ReflectionObject($factory);
    $method = $reflector->getMethod('getPhpDirectory');
    $php_dir = $method->invoke(NULL);
    $this->assertNotEmpty($php_dir);

    // The process factory should always put the PHP interpreter's directory
    // at the beginning of the PATH environment variable.
    $env = $factory->create(['whoami'])->getEnv();
    $this->assertStringStartsWith("$php_dir:", $env['PATH']);
  }

}
