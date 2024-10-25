<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Composer\Semver\Semver;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreOperationStageEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use Drupal\package_manager\PathLocator;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Validates the allowed Composer plugins, both in active and stage.
 *
 * Composer plugins can make far-reaching changes on the filesystem. That is why
 * they can cause Package Manager (more specifically the infrastructure it uses:
 * php-tuf/composer-stager) to not work reliably; potentially even break a site!
 *
 * This validator restricts the use of Composer plugins:
 * - Allowing all plugins to run indiscriminately is discouraged by Composer,
 *   but disallowed by this module (it is too risky):
 *   `config.allowed-plugins = true` is forbidden.
 * - Installed Composer plugins that are not allowed (in composer.json's
 *   `config.allowed-plugins ) are not executed by Composer, so
 *   these are safe.
 * - Installed Composer plugins that are allowed need to be either explicitly
 *   supported by this validator (they may still need their own validation to
 *   ensure their configuration is safe, for example Drupal core's vendor
 *   hardening plugin), or explicitly trusted by adding it to the
 *   `package_manager.settings` configuration's
 *   `additional_trusted_composer_plugins` list.
 *
 * @todo Determine how other Composer plugins will be supported in
 *    https://drupal.org/i/3339417.
 *
 * @see https://getcomposer.org/doc/04-schema.md#type
 * @see https://getcomposer.org/doc/articles/plugins.md
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class ComposerPluginsValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Composer plugins known to modify other packages, but are validated.
   *
   * The validation guarantees they are safe to use.
   *
   * @var string[]
   *   Keys are Composer plugin package names, values are version constraints
   *   for those plugins that this validator explicitly supports.
   */
  private const SUPPORTED_PLUGINS_THAT_DO_MODIFY = [
    // @see \Drupal\package_manager\Validator\ComposerPatchesValidator
    'cweagans/composer-patches' => '^1.7.3 || ^2',
    // @see \Drupal\package_manager\PathExcluder\VendorHardeningExcluder
    'drupal/core-vendor-hardening' => '*',
    'php-http/discovery' => '*',
  ];

  /**
   * Composer plugins known to NOT modify other packages.
   *
   * @var string[]
   *   Keys are Composer plugin package names, values are version constraints
   *   for those plugins that this validator explicitly supports.
   */
  private const SUPPORTED_PLUGINS_THAT_DO_NOT_MODIFY = [
    'composer/installers' => '^2.0',
    'dealerdirect/phpcodesniffer-composer-installer' => '^0.7.1 || ^1.0.0',
    'drupal/core-composer-scaffold' => '*',
    'drupal/core-project-message' => '*',
    'phpstan/extension-installer' => '^1.1',
    PhpTufValidator::PLUGIN_NAME => '^1',
  ];

  /**
   * The additional trusted Composer plugin package names.
   *
   * The package names are normalized.
   *
   * @var string[]
   *   Keys are package names, values are version constraints.
   */
  private array $additionalTrustedComposerPlugins;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    private readonly ComposerInspector $inspector,
    private readonly PathLocator $pathLocator,
  ) {
    $settings = $config_factory->get('package_manager.settings');
    $this->additionalTrustedComposerPlugins = array_fill_keys(
      array_map(
        [__CLASS__, 'normalizePackageName'],
        $settings->get('additional_trusted_composer_plugins')
      ),
      // The additional_trusted_composer_plugins setting cannot specify a
      // version constraint. The plugins are either trusted or they're not.
      '*'
    );
  }

  /**
   * Normalizes a package name.
   *
   * @param string $package_name
   *   A package name.
   *
   * @return string
   *   The normalized package name.
   */
  private static function normalizePackageName(string $package_name): string {
    return strtolower($package_name);
  }

  /**
   * Validates the allowed Composer plugins, both in active and stage.
   */
  public function validate(PreOperationStageEvent $event): void {
    $stage = $event->stage;

    // When about to copy the changes from the stage directory to the active
    // directory, use the stage directory's composer instead of the active.
    // Because composer plugins may be added or removed; the only thing that
    // matters is the set of composer plugins that *will* apply — if a composer
    // plugin is being removed, that's fine.
    $dir = $event instanceof PreApplyEvent
      ? $stage->getStageDirectory()
      : $this->pathLocator->getProjectRoot();
    try {
      $allowed_plugins = $this->inspector->getAllowPluginsConfig($dir);
    }
    catch (RuntimeException $exception) {
      $event->addErrorFromThrowable($exception, $this->t('Unable to determine Composer <code>allow-plugins</code> setting.'));
      return;
    }

    if ($allowed_plugins === TRUE) {
      $event->addError([$this->t('All composer plugins are allowed because <code>config.allow-plugins</code> is configured to <code>true</code>. This is an unacceptable security risk.')]);
      return;
    }

    // TRICKY: additional trusted Composer plugins is listed first, to allow
    // site owners who know what they're doing to use unsupported versions of
    // supported Composer plugins.
    $trusted_plugins = $this->additionalTrustedComposerPlugins
      + self::SUPPORTED_PLUGINS_THAT_DO_MODIFY
      + self::SUPPORTED_PLUGINS_THAT_DO_NOT_MODIFY;

    assert(is_array($allowed_plugins));
    // Only packages with `true` as a value are actually executed by Composer.
    $allowed_plugins = array_keys(array_filter($allowed_plugins));
    // The keys are normalized package names, and the values are the original,
    // non-normalized package names.
    $allowed_plugins = array_combine(
      array_map([__CLASS__, 'normalizePackageName'], $allowed_plugins),
      $allowed_plugins
    );

    $installed_packages = $this->inspector->getInstalledPackagesList($dir);
    // Determine which plugins are both trusted by us, AND allowed by Composer's
    // configuration.
    $supported_plugins = array_intersect_key($allowed_plugins, $trusted_plugins);
    // Create an array whose keys are the names of those plugins, and the values
    // are their installed versions.
    $supported_plugins_installed_versions = array_combine(
      $supported_plugins,
      array_map(
        fn (string $name): ?string => $installed_packages[$name]?->version,
        $supported_plugins
      )
    );
    // Find the plugins whose installed versions aren't in the supported range.
    $unsupported_installed_versions = array_filter(
      $supported_plugins_installed_versions,
      fn (?string $version, string $name): bool => $version && !Semver::satisfies($version, $trusted_plugins[$name]),
      ARRAY_FILTER_USE_BOTH
    );

    $untrusted_plugins = array_diff_key($allowed_plugins, $trusted_plugins);

    $messages = array_map(
      fn (string $raw_name) => $this->t('<code>@name</code>', ['@name' => $raw_name]),
      $untrusted_plugins
    );
    foreach ($unsupported_installed_versions as $name => $installed_version) {
      $messages[] = $this->t("<code>@name</code> is supported, but only version <code>@supported_version</code>, found <code>@installed_version</code>.", [
        '@name' => $name,
        '@supported_version' => $trusted_plugins[$name],
        '@installed_version' => $installed_version,
      ]);
    }

    if ($messages) {
      $summary = $this->formatPlural(
        count($messages),
        'An unsupported Composer plugin was detected.',
        'Unsupported Composer plugins were detected.',
      );
      $event->addError($messages, $summary);
    }
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
