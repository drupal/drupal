<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Command;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Command\BootableCommandTrait;
use Drupal\Core\Database\Database;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests the trait that helps console commands boot Drupal.
 */
#[Group('Command')]
#[CoversTrait(BootableCommandTrait::class)]
#[RequiresPhpExtension('pdo_sqlite')]
#[IgnoreDeprecations]
class BootableCommandTraitTest extends UnitTestCase {

  /**
   * The class loader, which is needed to boot Drupal.
   */
  private readonly object $classLoader;

  /**
   * A console application to manage the commands under test.
   */
  private readonly Application $application;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // To boot Drupal, we need a database. For the purposes of this test, an
    // in-memory SQLite database is sufficient.
    Database::addConnectionInfo('default', 'default', [
      'driver' => 'sqlite',
      'namespace' => 'Drupal\\sqlite\\Driver\\Database\\sqlite',
      'autoload' => 'core/modules/sqlite/src/Driver/Database/sqlite/',
      'database' => ':memory:',
    ]);
    $class_loaders = ClassLoader::getRegisteredLoaders();
    $this->classLoader = reset($class_loaders);
    $this->application = new Application('drupal', \Drupal::VERSION);
  }

  /**
   * Tests that commands are initialized with a reasonable base URL.
   */
  public function testRequestUrlIsValid(): void {
    // Create a fake command that boots Drupal and outputs the base URL.
    $this->application->addCommand(new class ($this->classLoader) extends Command {

      // Since we are testing BootableCommandTrait, we must use it.
      // @phpstan-ignore traitUse.deprecatedTrait
      use BootableCommandTrait;

      public function __construct(object $classLoader) {
        parent::__construct('test');
        $this->classLoader = $classLoader;
      }

      /**
       * {@inheritdoc}
       */
      protected function execute(InputInterface $input, OutputInterface $output): int {
        $this->boot();
        $output->write($GLOBALS['base_url']);

        // Symfony Console apparently changes the error and exception handlers,
        // which will anger PHPUnit.
        restore_error_handler();
        restore_exception_handler();

        return 0;
      }

    });

    $tester = new CommandTester($this->application->find('test'));
    $tester->execute([]);
    $this->expectUserDeprecationMessage('Drupal\Core\Command\BootableCommandTrait::boot() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. The new CLI in core automatically boots commands. See https://www.drupal.org/node/3584928');
    $this->assertSame('http://default', $tester->getDisplay());
  }

}
