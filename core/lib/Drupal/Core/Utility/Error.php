<?php

namespace Drupal\Core\Utility;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Site\Settings;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Log;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Drupal error utility class.
 */
class Error {

  /**
   * The error severity level.
   *
   * @var int
   */
  const ERROR = 3;

  /**
   * An array of ignored functions.
   *
   * @var array
   */
  protected static $ignoredFunctions = ['debug', '_drupal_error_handler', '_drupal_exception_handler'];

  /**
   * Decodes an exception and retrieves the correct caller.
   *
   * @param \Exception|\Throwable $exception
   *   The exception object that was thrown.
   *
   * @return array
   *   An error in the format expected by _drupal_log_error().
   */
  public static function decodeException($exception) {
    $message = $exception->getMessage();

    $backtrace = $exception->getTrace();
    // Add the line throwing the exception to the backtrace.
    array_unshift($backtrace, ['line' => $exception->getLine(), 'file' => $exception->getFile()]);

    // For PDOException errors, we try to return the initial caller,
    // skipping internal functions of the database layer.
    if ($exception instanceof \PDOException || $exception instanceof DatabaseExceptionWrapper) {
      $driver_namespace = Database::getConnectionInfo()['default']['namespace'];
      $backtrace = Log::removeDatabaseEntries($backtrace, $driver_namespace);
      if (isset($exception->query_string, $exception->args)) {
        $message .= ": " . $exception->query_string . "; " . print_r($exception->args, TRUE);
      }
    }

    $caller = static::getLastCaller($backtrace);

    return [
      '%type' => get_class($exception),
      // The standard PHP exception handler considers that the exception message
      // is plain-text. We mimic this behavior here.
      '@message' => $message,
      '%function' => $caller['function'],
      '%file' => $caller['file'],
      '%line' => $caller['line'],
      'severity_level' => static::ERROR,
      'backtrace' => $backtrace,
      '@backtrace_string' => $exception->getTraceAsString(),
      'exception' => $exception,
    ];
  }

  /**
   * Renders an exception error message without further exceptions.
   *
   * @param \Exception|\Throwable $exception
   *   The exception object that was thrown.
   *
   * @return string
   *   An error message.
   */
  public static function renderExceptionSafe($exception) {
    $decode = static::decodeException($exception);
    $backtrace = $decode['backtrace'];
    unset($decode['backtrace'], $decode['exception']);
    // Remove 'main()'.
    array_shift($backtrace);

    // Even though it is possible that this method is called on a public-facing
    // site, it is only called when the exception handler itself threw an
    // exception, which normally means that a code change caused the system to
    // no longer function correctly (as opposed to a user-triggered error), so
    // we assume that it is safe to include a verbose backtrace.
    $decode['@backtrace'] = Error::formatBacktrace($backtrace);
    return new FormattableMarkup('%type: @message in %function (line %line of %file). <pre class="backtrace">@backtrace</pre>', $decode);
  }

  /**
   * Renders fatal error page from twig template using Symfony twig engine.
   *
   * @param array $context
   *   Variables to be passed to twig template.
   *
   * @return string
   *   An html code with rendered fatal error page.
   */
  public static function renderFatalError(array $context) {
    $template_path = '/templates/maintenance-page--offline.html.twig';
    $system_path = 'core/modules/system';
    $theme = '';

    // Get offline theme from settings.php and check if the template exists.
    try {
      $theme = Settings::get('maintenance_theme', '');
      if (!$theme) {
        $theme_path = $system_path;
      }
      else {
        $theme_path = \Drupal::service('extension.list.theme')->getPath($theme);
      }
    }
    catch (ContainerNotInitializedException $e) {
      // The maintenance theme is set but the container doesn't exist
      // since the database is inactive. Hence there are no services available
      // to retrieve a maintenance theme path. The path can be obtained by using
      // ExtensionDiscovery::scan() but the app root should be guessed first
      // in the same way as DrupalKernel::guessApplicationRoot() does.
      $app_root = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)), 2);
      $listing = new ExtensionDiscovery($app_root, FALSE, NULL, NULL);
      // An empty profile directory prevents ExtensionDiscovery::scan()
      // from calling \Drupal::installProfile() that needs working container.
      $listing->setProfileDirectories([]);
      $themes = $listing->scan('theme');
      $theme_path = isset($themes[$theme]) ? $themes[$theme]->getPath() : $system_path;
      if ($context['displayable']) {
        $context['content'] = $context['content'] . "<pre>" . $e . "</pre>";
      }
    }
    catch (\Throwable $error) {
      // Handle any other cases.
      $theme_path = $system_path;
      if ($context['displayable']) {
        $context['content'] = $context['content'] . "<pre>" . $error . "</pre>";
      }
    }

    $path = $theme_path . $template_path;
    if (!file_exists($path)) {
      $path = $system_path . $template_path;
    }

    // Directly use Symfony twig engine without Drupal wrapper to minimize
    // possibility of nested exception.
    $template = file_get_contents($path);
    $loader = new ArrayLoader(['maintenance_page_offline' => $template]);
    $environment = new Environment($loader);

    return $environment->render('maintenance_page_offline', $context);
  }

  /**
   * Gets the last caller from a backtrace.
   *
   * @param array $backtrace
   *   A standard PHP backtrace. Passed by reference.
   *
   * @return array
   *   An associative array with keys 'file', 'line' and 'function'.
   */
  public static function getLastCaller(array &$backtrace) {
    // Errors that occur inside PHP internal functions do not generate
    // information about file and line. Ignore the ignored functions.
    while (($backtrace && !isset($backtrace[0]['line'])) ||
      (isset($backtrace[1]['function']) && in_array($backtrace[1]['function'], static::$ignoredFunctions))) {
      array_shift($backtrace);
    }

    // The first trace is the call itself.
    // It gives us the line and the file of the last call.
    $call = $backtrace[0];

    // The second call gives us the function where the call originated.
    if (isset($backtrace[1])) {
      if (isset($backtrace[1]['class'])) {
        $call['function'] = $backtrace[1]['class'] . $backtrace[1]['type'] . $backtrace[1]['function'] . '()';
      }
      else {
        $call['function'] = $backtrace[1]['function'] . '()';
      }
    }
    else {
      $call['function'] = 'main()';
    }

    return $call;
  }

  /**
   * Formats a backtrace into a plain-text string.
   *
   * The calls show values for scalar arguments and type names for complex ones.
   *
   * @param array $backtrace
   *   A standard PHP backtrace.
   *
   * @return string
   *   A plain-text line-wrapped string ready to be put inside <pre>.
   */
  public static function formatBacktrace(array $backtrace) {
    $return = '';

    foreach ($backtrace as $trace) {
      $call = ['function' => '', 'args' => []];

      if (isset($trace['class'])) {
        $call['function'] = $trace['class'] . $trace['type'] . $trace['function'];
      }
      elseif (isset($trace['function'])) {
        $call['function'] = $trace['function'];
      }
      else {
        $call['function'] = 'main';
      }

      if (isset($trace['args'])) {
        foreach ($trace['args'] as $arg) {
          if (is_scalar($arg)) {
            $call['args'][] = is_string($arg) ? '\'' . Xss::filter($arg) . '\'' : $arg;
          }
          else {
            $call['args'][] = ucfirst(gettype($arg));
          }
        }
      }

      $line = '';
      if (isset($trace['line'])) {
        $line = " (Line: {$trace['line']})";
      }

      $return .= $call['function'] . '(' . implode(', ', $call['args']) . ")$line\n";
    }

    return $return;
  }

}
