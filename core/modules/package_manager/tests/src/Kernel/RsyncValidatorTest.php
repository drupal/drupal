<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\ValidationResult;
use Drupal\package_manager\Validator\RsyncValidator;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @covers \Drupal\package_manager\Validator\RsyncValidator
 * @group package_manager
 * @internal
 */
class RsyncValidatorTest extends PackageManagerKernelTestBase {

  /**
   * The mocked executable finder.
   *
   * @var \PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface
   */
  private $executableFinder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Set up a mocked executable finder which will always be re-injected into
    // the validator when the container is rebuilt.
    $this->executableFinder = $this->prophesize(ExecutableFinderInterface::class);
    $this->executableFinder->find('rsync')->willReturn('/path/to/rsync');

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    $container->set('mock_executable_finder', $this->executableFinder->reveal());

    $container->getDefinition(RsyncValidator::class)
      ->setArgument('$executableFinder', new Reference('mock_executable_finder'));
  }

  /**
   * Tests that the stage cannot be created if rsync is selected, but not found.
   */
  public function testPreCreateFailsIfRsyncNotFound(): void {
    /** @var \PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface $translatable_factory */
    $translatable_factory = $this->container->get(TranslatableFactoryInterface::class);
    $message = $translatable_factory->createTranslatableMessage('Nope!');
    $this->executableFinder->find('rsync')->willThrow(new LogicException($message));

    $result = ValidationResult::createError([
      t('<code>rsync</code> is not available.'),
    ]);
    $this->assertResults([$result], PreCreateEvent::class);

    $this->enableModules(['help']);

    $result = ValidationResult::createError([
      t('<code>rsync</code> is not available. See the <a href="/admin/help/package_manager#package-manager-faq-rsync">Package Manager help</a> for more information on how to resolve this.'),
    ]);
    $this->assertResults([$result], PreCreateEvent::class);
  }

}
