<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Checks that the packages to install meet the minimum stability.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class ComposerMinimumStabilityValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly PathLocator $pathLocator,
    private readonly ComposerInspector $inspector,
  ) {}

  /**
   * Validates composer minimum stability.
   *
   * @param \Drupal\package_manager\Event\PreRequireEvent $event
   *   The stage event.
   */
  public function validate(PreRequireEvent $event): void {
    $dir = $this->pathLocator->getProjectRoot();
    $minimum_stability = $this->inspector->getConfig('minimum-stability', $dir);
    $requested_packages = array_merge($event->getDevPackages(), $event->getRuntimePackages());

    foreach ($requested_packages as $package_name => $version) {
      // In the root composer.json, a stability flag can also be specified. They
      // take the form `constraint@stability`. A stability flag
      // allows the project owner to deviate from the minimum-stability setting.
      // @see https://getcomposer.org/doc/04-schema.md#package-links
      // @see \Composer\Package\Loader\RootPackageLoader::extractStabilityFlags()
      if (str_contains($version, '@')) {
        continue;
      }
      $stability = VersionParser::parseStability($version);

      // Because drupal/core prefers to not depend on composer/composer we need
      // to compare two versions that are identical except for stability to
      // determine if the package stability is less that the minimum stability.
      if (Semver::satisfies("1.0.0-$stability", "< 1.0.0-$minimum_stability")) {
        $event->addError([
          $this->t("<code>@package_name</code>'s requested version @package_version is less stable (@package_stability) than the minimum stability (@minimum_stability) required in @file.",
            [
              '@package_name' => $package_name,
              '@package_version' => $version,
              '@package_stability' => $stability,
              '@minimum_stability' => $minimum_stability,
              '@file' => $this->pathLocator->getProjectRoot() . '/composer.json',
            ]
          ),
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreRequireEvent::class => 'validate',
    ];
  }

}
