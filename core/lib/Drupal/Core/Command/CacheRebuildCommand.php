<?php

declare(strict_types=1);

namespace Drupal\Core\Command;

use Composer\Autoload\ClassLoader;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;

/**
 * Rebuild all caches.
 *
 * @internal
 */
#[AsCommand(
  name: 'cache:rebuild',
  description: 'Rebuild all caches.',
  aliases: [
    'cr',
    'rebuild',
  ],
)]
class CacheRebuildCommand {

  public function __construct(
    protected ClassLoader $classLoader,
  ) {}

  /**
   * Rebuild all caches.
   */
  public function __invoke(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    // Prior to booting the kernel, change the current working directory to the
    // Drupal root temporarily, which may either be the current working
    // directory or within the document or web root. This is done because the
    // YAML file loader uses a relative directory to find core.services.yml.
    // @todo https://www.drupal.org/node/2899837
    $cwd = getcwd();
    chdir(DRUPAL_ROOT);

    $request = Request::createFromGlobals();
    // Manually resemble early bootstrap of DrupalKernel::boot().
    DrupalKernel::bootEnvironment();
    Settings::initialize(DRUPAL_ROOT, DrupalKernel::findSitePath($request), $this->classLoader);
    require_once DRUPAL_ROOT . '/core/includes/utility.inc';
    drupal_rebuild($this->classLoader, $request);
    $io->success('All caches have been rebuilt.');

    // Restores the current working directory of the command to the app root.
    chdir($cwd);

    return Command::SUCCESS;
  }

}
