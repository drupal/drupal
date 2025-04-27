<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\StatusCheckEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\SandboxValidationEvent;
use Drupal\package_manager\PathLocator;

/**
 * Validates the list of packages that are allowed to scaffold files.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class AllowedScaffoldPackagesValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly ComposerInspector $composerInspector,
    private readonly PathLocator $pathLocator,
  ) {}

  /**
   * Validates that only the implicitly allowed packages can use scaffolding.
   */
  public function validate(SandboxValidationEvent $event): void {
    $sandbox_manager = $event->sandboxManager;
    $path = $event instanceof PreApplyEvent
      ? $sandbox_manager->getSandboxDirectory()
      : $this->pathLocator->getProjectRoot();

    // @see https://www.drupal.org/docs/develop/using-composer/using-drupals-composer-scaffold
    $implicitly_allowed_packages = [
      "drupal/legacy-scaffold-assets",
      "drupal/core",
    ];
    $extra = Json::decode($this->composerInspector->getConfig('extra', $path . '/composer.json'));
    $allowed_packages = $extra['drupal-scaffold']['allowed-packages'] ?? [];
    $extra_packages = array_diff($allowed_packages, $implicitly_allowed_packages);
    if (!empty($extra_packages)) {
      $event->addError(
        // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
        array_map($this->t(...), $extra_packages),
        $this->t('Any packages other than the implicitly allowed packages are not allowed to scaffold files. See <a href=":url">the scaffold documentation</a> for more information.', [
          ':url' => 'https://www.drupal.org/docs/develop/using-composer/using-drupals-composer-scaffold',
        ])
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    return [
      StatusCheckEvent::class => 'validate',
      PreCreateEvent::class => 'validate',
      PreApplyEvent::class => 'validate',
    ];
  }

}
