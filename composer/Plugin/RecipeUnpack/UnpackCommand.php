<?php

namespace Drupal\Composer\Plugin\RecipeUnpack;

use Composer\Command\BaseCommand;
use Composer\Package\Package;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The "drupal:recipe-unpack" command class.
 *
 * Manually run the unpack operation that normally happens after
 * 'composer require'.
 *
 * @internal
 */
final class UnpackCommand extends BaseCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $name = 'drupal:recipe-unpack';
    $this
      ->setName($name)
      ->setDescription('Unpack Drupal recipes.')
      ->addArgument('recipes', InputArgument::IS_ARRAY, "A list of recipe package names separated by a space, e.g. drupal/recipe_one drupal/recipe_two. If not provided, all recipes listed in the require section of the root composer are unpacked.")
      ->setHelp(
        <<<EOT
The <info>$name</info> command unpacks dependencies from the specified recipe
packages into the composer.json file.

<info>php composer.phar $name drupal/my-recipe [...]</info>

It is usually not necessary to call <info>$name</info> manually,
because by default it is called automatically as needed, after a
<info>require</info> command.
EOT
            );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $composer = $this->requireComposer();
    $io = $this->getIO();
    $local_repo = $composer->getRepositoryManager()->getLocalRepository();
    $package_names = $input->getArgument('recipes') ?? [];

    // If no recipes are provided unpack all recipes that are required by the
    // root package.
    if (empty($package_names)) {
      foreach ($composer->getPackage()->getRequires() as $link) {
        $package = $local_repo->findPackage($link->getTarget(), $link->getConstraint());
        if ($package->getType() === Plugin::RECIPE_PACKAGE_TYPE) {
          $package_names[] = $package->getName();
        }
      }
      if (empty($package_names)) {
        $io->write('<info>No recipes to unpack.</info>');
        return 0;
      }
    }

    $manager = new UnpackManager($composer, $io);
    $unpack_collection = new UnpackCollection();
    foreach ($package_names as $package_name) {
      if (!$manager->isRootDependency($package_name)) {
        $io->error(sprintf('<info>%s</info> not found in the root composer.json.', $package_name));
        return 1;
      }
      $packages = $local_repo->findPackages($package_name);
      $package = reset($packages);

      if (!$package instanceof Package) {
        $io->error(sprintf('<info>%s</info> does not resolve to a package.', $package_name));
        return 1;
      }

      if ($package->getType() !== Plugin::RECIPE_PACKAGE_TYPE) {
        $io->error(sprintf('<info>%s</info> is not a recipe.', $package->getPrettyName()));
        return 1;
      }

      if ($manager->unpackOptions->isIgnored($package)) {
        $io->error(sprintf('<info>%s</info> is in the extra.drupal-recipe-unpack.ignore list.', $package->getName()));
        return 1;
      }

      if (UnpackManager::isDevRequirement($package)) {
        $io->warning(sprintf('<info>%s</info> is present in the require-dev key. Unpacking will move the recipe\'s dependencies to the require key.', $package->getName()));
        if ($io->isInteractive() && !$io->askConfirmation('<info>Do you want to continue</info> [<comment>yes</comment>]?')) {
          return 0;
        }
      }
      $unpack_collection->add($package);
    }
    $manager->unpack($unpack_collection);
    return 0;
  }

}
