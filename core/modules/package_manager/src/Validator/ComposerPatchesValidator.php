<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Composer\Semver\Semver;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\SandboxValidationEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Validates the configuration of the cweagans/composer-patches plugin.
 *
 * To ensure that applied patches remain consistent between the active and
 * stage directories, the following rules are enforced if the patcher is
 * installed:
 * - It must be installed in both places, or in neither of them. It can't, for
 *   example, be installed in the active directory but not the stage directory
 *   (or vice versa).
 * - It must be one of the project's direct runtime or dev dependencies.
 * - It cannot be installed or removed by Package Manager. In other words, it
 *   must be added to the project at the command line by someone technical
 *   enough to install and configure it properly.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class ComposerPatchesValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The name of the plugin being analyzed.
   *
   * @var string
   */
  private const PLUGIN_NAME = 'cweagans/composer-patches';

  public function __construct(
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly ComposerInspector $composerInspector,
    private readonly PathLocator $pathLocator,
  ) {}

  /**
   * Validates the status of the patcher plugin.
   *
   * @param \Drupal\package_manager\Event\SandboxValidationEvent $event
   *   The event object.
   */
  public function validate(SandboxValidationEvent $event): void {
    $messages = [];

    [$plugin_installed_in_active, $is_active_root_requirement, $active_configuration_ok] = $this->computePatcherStatus($this->pathLocator->getProjectRoot());
    if ($event instanceof PreApplyEvent) {
      [$plugin_installed_in_stage, $is_stage_root_requirement, $stage_configuration_ok] = $this->computePatcherStatus($event->sandboxManager->getSandboxDirectory());
      $has_staged_update = TRUE;
    }
    else {
      // No staged update exists.
      $has_staged_update = FALSE;
    }

    // If there's a staged update and the patcher has been installed or removed
    // in the stage directory, that's a problem.
    if ($has_staged_update && $plugin_installed_in_active !== $plugin_installed_in_stage) {
      if ($plugin_installed_in_stage) {
        $message = $this->t('It cannot be installed by Package Manager.');
      }
      else {
        $message = $this->t('It cannot be removed by Package Manager.');
      }
      $messages[] = $this->createErrorMessage($message, 'package-manager-faq-composer-patches-installed-or-removed');
    }

    // If the patcher is not listed in the runtime or dev dependencies, that's
    // an error as well.
    if (($plugin_installed_in_active && !$is_active_root_requirement) || ($has_staged_update && $plugin_installed_in_stage && !$is_stage_root_requirement)) {
      $messages[] = $this->createErrorMessage($this->t('It must be a root dependency.'), 'package-manager-faq-composer-patches-not-a-root-dependency');
    }

    // If the plugin is misconfigured in either the active or stage directories,
    // flag an error.
    if (($plugin_installed_in_active && !$active_configuration_ok) || ($has_staged_update && $plugin_installed_in_stage && !$stage_configuration_ok)) {
      $messages[] = $this->t('The <code>composer-exit-on-patch-failure</code> key is not set to <code>true</code> in the <code>extra</code> section of <code>composer.json</code>.');
    }

    if ($messages) {
      $summary = $this->t("Problems detected related to the Composer plugin <code>@plugin</code>.", [
        '@plugin' => static::PLUGIN_NAME,
      ]);
      $event->addError($messages, $summary);
    }
  }

  /**
   * Appends a link to online help to an error message.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The error message.
   * @param string $fragment
   *   The fragment of the online help to link to.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The final, translated error message.
   */
  private function createErrorMessage(TranslatableMarkup $message, string $fragment): TranslatableMarkup {
    if ($this->moduleHandler->moduleExists('help')) {
      $url = Url::fromRoute('help.page', ['name' => 'package_manager'])
        ->setOption('fragment', $fragment)
        ->toString();

      return $this->t('@message See <a href=":url">the help page</a> for information on how to resolve the problem.', [
        '@message' => $message,
        ':url' => $url,
      ]);
    }
    return $message;
  }

  /**
   * Computes the status of the patcher plugin in a particular directory.
   *
   * @param string $working_dir
   *   The directory in which to run Composer.
   *
   * @return bool[]
   *   An indexed array containing three booleans, in order:
   *   - Whether the patcher plugin is installed.
   *   - Whether the patcher plugin is a root requirement in composer.json (in
   *     either the runtime or dev dependencies).
   *   - Whether the `composer-exit-on-patch-failure` flag is set in the `extra`
   *     section of composer.json.
   */
  private function computePatcherStatus(string $working_dir): array {
    $list = $this->composerInspector->getInstalledPackagesList($working_dir);
    $installed_version = $list[static::PLUGIN_NAME]?->version;

    $info = $this->composerInspector->getRootPackageInfo($working_dir);
    $is_root_requirement = array_key_exists(static::PLUGIN_NAME, $info['requires'] ?? []) || array_key_exists(static::PLUGIN_NAME, $info['devRequires'] ?? []);

    // The 2.x version of the plugin always exits with an error if a patch can't
    // be applied.
    if ($installed_version && Semver::satisfies($installed_version, '^2')) {
      $exit_on_failure = TRUE;
    }
    else {
      $extra = Json::decode($this->composerInspector->getConfig('extra', $working_dir));
      $exit_on_failure = $extra['composer-exit-on-patch-failure'] ?? FALSE;
    }

    return [
      is_string($installed_version),
      $is_root_requirement,
      $exit_on_failure,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreCreateEvent::class => 'validate',
      PreApplyEvent::class => 'validate',
      StatusCheckEvent::class => 'validate',
    ];
  }

}
