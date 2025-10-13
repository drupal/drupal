<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\TranslatableStringAdapter;
use Drupal\package_manager\TranslatableStringFactory;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Translatable String.
 */
#[Group('package_manager')]
#[CoversClass(TranslatableStringFactory::class)]
#[CoversClass(TranslatableStringAdapter::class)]
#[RunTestsInSeparateProcesses]
class TranslatableStringTest extends PackageManagerKernelTestBase {

  /**
   * Tests various ways of creating a translatable string.
   */
  public function testCreateTranslatableString(): void {
    // Ensure that we have properly overridden Composer Stager's factory.
    $factory = $this->container->get(TranslatableFactoryInterface::class);
    $this->assertInstanceOf(TranslatableStringFactory::class, $factory);

    /** @var \Drupal\package_manager\TranslatableStringAdapter $string */
    $string = $factory->createTranslatableMessage('This string has no parameters.');
    $this->assertInstanceOf(TranslatableStringAdapter::class, $string);
    $this->assertEmpty($string->getArguments());
    $this->assertEmpty($string->getOption('context'));
    $this->assertSame('This string has no parameters.', (string) $string);

    $parameters = $factory->createTranslationParameters([
      '%name' => 'Slim Shady',
    ]);
    $string = $factory->createTranslatableMessage('My name is %name.', $parameters, 'outer space');
    $this->assertSame($parameters->getAll(), $string->getArguments());
    $this->assertSame('outer space', $string->getOption('context'));
    $this->assertSame('My name is <em class="placeholder">Slim Shady</em>.', (string) $string);
  }

}
