<?php

namespace Drupal\Composer\Plugin\RecipeUnpack;

use Composer\Command\RequireCommand;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Drupal\Composer\Plugin\RecipeUnpack\CommandProvider as UnpackCommandProvider;

/**
 * Composer plugin for handling dependency unpacking.
 *
 * @internal
 */
final class Plugin implements PluginInterface, EventSubscriberInterface, Capable {

  /**
   * The composer package type of Drupal recipes.
   */
  public const string RECIPE_PACKAGE_TYPE = 'drupal-recipe';

  /**
   * The handler for dependency unpacking.
   */
  private UnpackManager $manager;

  /**
   * {@inheritdoc}
   */
  public function getCapabilities(): array {
    return [CommandProvider::class => UnpackCommandProvider::class];
  }

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io): void {
    $this->manager = new UnpackManager($composer, $io);
  }

  /**
   * {@inheritdoc}
   */
  public function deactivate(Composer $composer, IOInterface $io): void {
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(Composer $composer, IOInterface $io): void {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ScriptEvents::POST_UPDATE_CMD => 'unpackOnRequire',
      ScriptEvents::POST_CREATE_PROJECT_CMD => 'unpackOnCreateProject',
    ];
  }

  /**
   * Post update command event callback.
   */
  public function unpackOnRequire(Event $event): void {
    if (!$this->manager->unpackOptions->options['on-require']) {
      return;
    }

    // @todo https://www.drupal.org/project/drupal/issues/3523269 Use Composer
    //   API once it exists.
    $backtrace = debug_backtrace();
    $composer = $event->getComposer();
    foreach ($backtrace as $trace) {
      if (isset($trace['object']) && $trace['object'] instanceof Installer) {
        $installer = $trace['object'];

        // Get the list of packages being required. This code is largely copied
        // from https://github.com/symfony/flex/blob/2.x/src/Flex.php#L218.
        $updateAllowList = \Closure::bind(function () {
          return $this->updateAllowList ?? [];
        }, $installer, $installer)();

        // Determine if the --no-install flag has been passed to require.
        $isInstalling = \Closure::bind(function () {
          return $this->install;
        }, $installer, $installer)();
      }

      // If the command is a require command, populate the list of recipes to
      // unpack.
      if (isset($trace['object']) && $trace['object'] instanceof RequireCommand && isset($installer, $updateAllowList, $isInstalling)) {
        // Determines if a message has been sent about require-dev and recipes.
        $devRecipeWarningEmitted = FALSE;
        $unpackCollection = new UnpackCollection();

        foreach ($updateAllowList as $package_name) {
          $packages = $composer->getRepositoryManager()->getLocalRepository()->findPackages($package_name);
          $package = reset($packages);

          if (!$package instanceof Package) {
            if (!$isInstalling) {
              $event->getIO()->write('Recipes are not unpacked when the --no-install option is used.', verbosity: IOInterface::VERBOSE);
              return;
            }
            $event->getIO()->error(sprintf('%s does not resolve to a package.', $package_name));
            return;
          }

          // Only recipes are supported.
          if ($package->getType() === self::RECIPE_PACKAGE_TYPE) {
            if ($this->manager->unpackOptions->isIgnored($package)) {
              $event->getIO()->write(sprintf('<info>%s</info> not unpacked because it is ignored.', $package_name), verbosity: IOInterface::VERBOSE);
            }
            elseif (UnpackManager::isDevRequirement($package)) {
              if (!$devRecipeWarningEmitted) {
                $event->getIO()->write('<info>Recipes required as a development dependency are not automatically unpacked.</info>');
                $devRecipeWarningEmitted = TRUE;
              }
            }
            else {
              $unpackCollection->add($package);
            }
          }
        }

        // Unpack any recipes that have been added to the collection.
        $this->manager->unpack($unpackCollection);
        // The trace has been processed far enough and the $updateAllowList has
        // been used.
        break;
      }
    }
  }

  /**
   * Post create-project command event callback.
   */
  public function unpackOnCreateProject(Event $event): void {
    $composer = $event->getComposer();
    $unpackCollection = new UnpackCollection();
    foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
      // Only recipes are supported.
      if ($package->getType() === self::RECIPE_PACKAGE_TYPE) {
        if ($this->manager->unpackOptions->isIgnored($package)) {
          $event->getIO()->write(sprintf('<info>%s</info> not unpacked because it is ignored.', $package->getName()), verbosity: IOInterface::VERBOSE);
        }
        elseif (UnpackManager::isDevRequirement($package)) {
          continue;
        }
        else {
          $unpackCollection->add($package);
        }
      }
    }

    // Unpack any recipes that have been registered.
    $this->manager->unpack($unpackCollection);
  }

}
