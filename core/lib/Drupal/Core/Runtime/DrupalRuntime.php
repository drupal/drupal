<?php

declare(strict_types=1);

namespace Drupal\Core\Runtime;

use Symfony\Component\Runtime\Internal\MissingDotenv;
use Symfony\Component\Runtime\SymfonyRuntime;

/**
 * The custom Drupal framework runtime.
 *
 * This runtime understands how to provide the arguments that Drupal
 * front-controllers need to instantiate the DrupalKernel.
 */
class DrupalRuntime extends SymfonyRuntime {

  public function __construct(array $options = []) {
    // Unless explicitly opted-in, we disable Symfony's runtime handler.
    // Drupal has its own error handlers.
    $options['error_handler'] ??= FALSE;
    // We disable dotenv by default. Drupal applications may already have this
    // as dependency in their project for other reasons but may not be expecting
    // Drupal to read this file.
    $options['disable_dotenv'] ??= TRUE;

    // Although accessing the autoloader through globals is discouraged. It is
    // possible at the time of introducing symfony/runtime, so we must keep this
    // available for backwards compatibility.
    $GLOBALS['autoloader'] = new DeprecatedAutoloadAccess();

    // Symfony's default runtime is `dev`, but Drupal expects this to be `prod`
    // if nothing is set so we must overwrite it.
    $envKey = $options['env_var_name'] ??= 'APP_ENV';
    // If people opted into dotenv then this is not explicitly set by Symfony.
    if ($options['disable_dotenv'] || class_exists(MissingDotenv::class, FALSE)) {
      $_SERVER[$envKey] ??= $_ENV[$envKey] ?? 'prod';
    }

    parent::__construct($options);
  }

}
