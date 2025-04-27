<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Validates no enabled Drupal extensions are removed from the stage directory.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class EnabledExtensionsValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly PathLocator $pathLocator,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly ComposerInspector $composerInspector,
    private readonly ThemeHandlerInterface $themeHandler,
  ) {}

  /**
   * Validates that no enabled Drupal extensions have been removed.
   *
   * @param \Drupal\package_manager\Event\PreApplyEvent $event
   *   The event object.
   */
  public function validate(PreApplyEvent $event): void {
    $active_packages_list = $this->composerInspector->getInstalledPackagesList($this->pathLocator->getProjectRoot());
    $stage_packages_list = $this->composerInspector->getInstalledPackagesList($event->sandboxManager->getSandboxDirectory());

    $extensions_list = $this->moduleHandler->getModuleList() + $this->themeHandler->listInfo();
    foreach ($extensions_list as $extension) {
      $extension_name = $extension->getName();
      $package = $active_packages_list->getPackageByDrupalProjectName($extension_name);
      if ($package && $stage_packages_list->getPackageByDrupalProjectName($extension_name) === NULL) {
        $removed_project_messages[] = $this->t("'@name' @type (provided by <code>@package</code>)", [
          '@name' => $extension_name,
          '@type' => $extension->getType(),
          '@package' => $package->name,
        ]);
      }
    }

    if (!empty($removed_project_messages)) {
      $removed_packages_summary = $this->formatPlural(
        count($removed_project_messages),
        'The update cannot proceed because the following enabled Drupal extension was removed during the update.',
        'The update cannot proceed because the following enabled Drupal extensions were removed during the update.'
      );
      $event->addError($removed_project_messages, $removed_packages_summary);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreApplyEvent::class => 'validate',
    ];
  }

}
