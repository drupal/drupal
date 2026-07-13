<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\DeprecationBridge;

use Drupal\TestTools\ErrorHandler\BootstrapErrorHandler;
use PHPUnit\Runner\ErrorHandler as PhpUnitErrorHandler;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration as PhpUnitConfiguration;
use Symfony\Component\ErrorHandler\DebugClassLoader;

// cspell:ignore depsta bootstrapper bootstrappers

/**
 * Drupal's PHPUnit extension to manage code deprecation.
 *
 * In the future this extension might be dropped if PHPUnit adds support for
 * ignoring a specified list of deprecations.
 *
 * @internal
 */
final class DeprecationHandler implements Extension {

  /**
   * Load configuration and setup.
   *
   * This needs to execute before PHPUnit's extension bootstrap phase, since
   * there is the need to set early both the error handler and the debug class
   * loader. This static method is called by the bootstrap.php code, that is
   * executed first before the extensions bootstrap.
   *
   * @param \PHPUnit\TextUI\Configuration\Configuration $configuration
   *   The PHPUnit configuration.
   */
  public static function preBootstrap(PhpUnitConfiguration $configuration): void {
    $config = Configuration::instance();

    // Find the extension parameters from current PHPUnit configuration.
    $extensionBootstrappers = $configuration->extensionBootstrappers();
    $parameters = [];
    foreach ($extensionBootstrappers as $bootstrapper) {
      if ($bootstrapper['className'] === self::class) {
        $parameters = $bootstrapper['parameters'];
        break;
      }
    }
    $config->load($parameters);

    // Get the overridden configuration provided via env variable.
    $environmentVariable = getenv('DRUPAL_DEPRECATION_FILTER_CONFIG');
    if ($environmentVariable !== FALSE) {
      $overrideConfig = (array) json_decode($environmentVariable);
      $config->load($overrideConfig);
    }

    // Get the legacy configuration provided via Symfony env variable.
    $config->load(self::legacySymfonyConfiguration());

    // Determine if project ignores are enabled. If so, loads the deprecation
    // patterns to be ignored from the ignore file and sets the error handler
    // to Drupal\TestTools\ErrorHandler\BootstrapErrorHandler. This allows to
    // capture deprecations triggered by PHP or by the DebugClassLoader, that
    // can occur before tests' ::setUp() methods are called.
    if ($config->projectIgnoresEnabled) {
      $config->loadDeprecationIgnorePatterns();
      // We pass an instance of the PHPUnit error handler to redirect any error
      // not managed by our layer back to PHPUnit.
      set_error_handler(new BootstrapErrorHandler(PhpUnitErrorHandler::instance()));
    }

    // Enable the DebugClassLoader to get deprecations for methods' signature
    // changes.
    if ($config->debugClassLoaderEnabled) {
      DebugClassLoader::enable();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function bootstrap(
    PhpUnitConfiguration $configuration,
    Facade $facade,
    ParameterCollection $parameters,
  ): void {
  }

  /**
   * Handles a deprecation error.
   *
   * This method is invoked by BootstrapErrorHandler.
   *
   * @param int $errorNumber
   *   The level of the error raised.
   * @param string $errorString
   *   The error message.
   * @param string $errorFile
   *   The filename that the error was raised in.
   * @param int $errorLine
   *   The line number the error was raised at.
   *
   * @return bool
   *   TRUE to stop error handling, FALSE to let the normal error handler
   *   continue.
   *
   * @see \Drupal\TestTools\ErrorHandler\BootstrapErrorHandler
   */
  public static function handle(int $errorNumber, string $errorString, string $errorFile, int $errorLine): bool {
    assert(Configuration::instance()->projectIgnoresEnabled, __METHOD__ . '() must not be called if the deprecation handler is not enabled.');
    return self::isIgnoredByProject($errorString);
  }

  /**
   * Returns the legacy configuration provided via Symfony env variable.
   *
   * For historical reasons, the configuration can be stored in the
   * SYMFONY_DEPRECATIONS_HELPER environment variable.
   *
   * @return array
   *   An array of configuration variables.
   */
  private static function legacySymfonyConfiguration(): array {
    $environmentVariable = getenv('SYMFONY_DEPRECATIONS_HELPER');
    if ($environmentVariable === FALSE) {
      return [];
    }
    if ($environmentVariable === 'disabled') {
      return [
        'enableProjectIgnores' => FALSE,
        'enableDebugClassLoader' => FALSE,
      ];
    }
    parse_str($environmentVariable, $configuration);
    $ret = [
      'enableProjectIgnores' => TRUE,
      'enableDebugClassLoader' => TRUE,
    ];
    if (isset($configuration['ignoreFile'])) {
      $ret['projectIgnoreFile'] = $configuration['ignoreFile'];
    }
    return $ret;
  }

  /**
   * Determines if an actual deprecation is ignored by the project.
   *
   * Deprecations that match the patterns included in the ignore file should
   * be ignored.
   *
   * @param string $deprecationMessage
   *   The actual deprecation message triggered via trigger_error().
   *
   * @return bool
   *   TRUE if the deprecation is ignored at project level.
   */
  private static function isIgnoredByProject(string $deprecationMessage): bool {
    foreach (Configuration::instance()->deprecationIgnorePatterns as $pattern) {
      $result = @preg_filter($pattern, '$0', $deprecationMessage);
      if (preg_last_error() !== \PREG_NO_ERROR) {
        throw new \RuntimeException(preg_last_error_msg());
      }
      if ((bool) $result) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
