<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\SandboxValidationEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Validates that PHP-TUF is installed and correctly configured.
 *
 * In both the active and stage directories, this checks for the following
 * conditions:
 * - The PHP-TUF plugin is installed.
 * - The plugin is not explicitly blocked by Composer's `allow-plugins`
 *   configuration.
 * - Composer is aware of at least one repository that has TUF support
 *   explicitly enabled.
 *
 * Until it's more real world-tested, TUF protection is bypassed by default.
 * Ultimately, though, Package Manager will not treat TUF as optional.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class PhpTufValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The name of the PHP-TUF Composer integration plugin.
   *
   * @var string
   */
  public const PLUGIN_NAME = 'php-tuf/composer-integration';

  public function __construct(
    private readonly PathLocator $pathLocator,
    private readonly ComposerInspector $composerInspector,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly Settings $settings,
    private readonly array $repositories,
  ) {
    assert(Inspector::assertAllStrings($repositories));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      StatusCheckEvent::class => 'validate',
      PreCreateEvent::class => 'validate',
      PreRequireEvent::class => 'validate',
      PreApplyEvent::class => 'validate',
    ];
  }

  /**
   * Reacts to a stage event by validating PHP-TUF configuration as needed.
   *
   * @param \Drupal\package_manager\Event\SandboxValidationEvent $event
   *   The event object.
   */
  public function validate(SandboxValidationEvent $event): void {
    $messages = $this->validateTuf($this->pathLocator->getProjectRoot());
    if ($messages) {
      $event->addError($messages, $this->t('The active directory is not protected by PHP-TUF, which is required to use Package Manager securely.'));
    }

    $sandbox_manager = $event->sandboxManager;
    if ($sandbox_manager->sandboxDirectoryExists()) {
      $messages = $this->validateTuf($sandbox_manager->getSandboxDirectory());
      if ($messages) {
        $event->addError($messages, $this->t('The stage directory is not protected by PHP-TUF, which is required to use Package Manager securely.'));
      }
    }
  }

  /**
   * Flags messages if PHP-TUF is not installed and configured properly.
   *
   * @param string $dir
   *   The directory to examine.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The error messages, if any.
   */
  private function validateTuf(string $dir): array {
    $messages = [];

    // This setting will be removed without warning when no longer need.
    if ($this->settings->get('package_manager_bypass_tuf', TRUE)) {
      return $messages;
    }

    if ($this->moduleHandler->moduleExists('help')) {
      $help_url = Url::fromRoute('help.page', ['name' => 'package_manager'])
        ->setOption('fragment', 'package-manager-tuf-info')
        ->toString();
    }

    // The Composer plugin must be installed.
    $installed_packages = $this->composerInspector->getInstalledPackagesList($dir);
    if (!isset($installed_packages[static::PLUGIN_NAME])) {
      $message = $this->t('The <code>@plugin</code> plugin is not installed.', [
        '@plugin' => static::PLUGIN_NAME,
      ]);
      if (isset($help_url)) {
        $message = $this->t('@message See <a href=":url">the help page</a> for more information on how to install the plugin.', [
          '@message' => $message,
          ':url' => $help_url,
        ]);
      }
      $messages[] = $message;
    }

    // And it has to be explicitly enabled.
    $allowed_plugins = $this->composerInspector->getAllowPluginsConfig($dir);
    if ($allowed_plugins !== TRUE && empty($allowed_plugins[static::PLUGIN_NAME])) {
      $message = $this->t('The <code>@plugin</code> plugin is not listed as an allowed plugin.', [
        '@plugin' => static::PLUGIN_NAME,
      ]);
      if (isset($help_url)) {
        $message = $this->t('@message See <a href=":url">the help page</a> for more information on how to configure the plugin.', [
          '@message' => $message,
          ':url' => $help_url,
        ]);
      }
      $messages[] = $message;
    }

    // Confirm that all repositories we're configured to look at have opted into
    // TUF protection.
    foreach ($this->getRepositoryStatus($dir) as $url => $is_protected) {
      if ($is_protected) {
        continue;
      }
      $message = $this->t('TUF is not enabled for the <code>@url</code> repository.', [
        '@url' => $url,
      ]);
      if (isset($help_url)) {
        $message = $this->t('@message See <a href=":url">the help page</a> for more information on how to set up this repository.', [
          '@message' => $message,
          ':url' => $help_url,
        ]);
      }
      $messages[] = $message;
    }
    return $messages;
  }

  /**
   * Gets the TUF protection status of Composer repositories.
   *
   * @param string $dir
   *   The directory in which to run Composer.
   *
   * @return bool[]
   *   An array of booleans, keyed by repository URL, indicating whether TUF
   *   protection is enabled for that repository.
   */
  private function getRepositoryStatus(string $dir): array {
    $status = [];

    $repositories = $this->composerInspector->getConfig('repositories', $dir);
    $repositories = Json::decode($repositories);

    foreach ($repositories as $repository) {
      // Only Composer repositories can have TUF protection.
      if ($repository['type'] === 'composer') {
        $url = $repository['url'];
        $status[$url] = !empty($repository['tuf']);
      }
    }
    return array_intersect_key($status, array_flip($this->repositories));
  }

}
