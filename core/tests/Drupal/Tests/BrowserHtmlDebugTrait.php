<?php

namespace Drupal\Tests;

use Drupal\Component\Utility\Html;
use Drupal\Core\Utility\Error;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides the debug functions for browser tests.
 */
trait BrowserHtmlDebugTrait {

  /**
   * Class name for HTML output logging.
   *
   * @var string
   */
  protected $htmlOutputClassName;

  /**
   * Directory name for HTML output logging.
   *
   * @var string
   */
  protected $htmlOutputDirectory;

  /**
   * Counter storage for HTML output logging.
   *
   * @var string
   */
  protected $htmlOutputCounterStorage;

  /**
   * Counter for HTML output logging.
   *
   * @var int
   */
  protected $htmlOutputCounter = 1;

  /**
   * HTML output output enabled.
   *
   * @var bool
   */
  protected $htmlOutputEnabled = FALSE;

  /**
   * The file name to write the list of URLs to.
   *
   * This file is read by the PHPUnit result printer.
   *
   * @var string
   *
   * @see \Drupal\Tests\Listeners\HtmlOutputPrinter
   */
  protected $htmlOutputFile;

  /**
   * HTML output test ID.
   *
   * @var int
   */
  protected $htmlOutputTestId;

  /**
   * The Base URI to use for links to the output files.
   *
   * @var string
   */
  protected $htmlOutputBaseUrl;

  /**
   * Formats HTTP headers as string for HTML output logging.
   *
   * @param array[] $headers
   *   Headers that should be formatted.
   *
   * @return string
   *   The formatted HTML string.
   */
  protected function formatHtmlOutputHeaders(array $headers) {
    $flattened_headers = array_map(function ($header) {
      if (is_array($header)) {
        return implode(';', array_map('trim', $header));
      }
      else {
        return $header;
      }
    }, $headers);
    return '<hr />Headers: <pre>' . Html::escape(var_export($flattened_headers, TRUE)) . '</pre>';
  }

  /**
   * Returns headers in HTML output format.
   *
   * @return string
   *   HTML output headers.
   */
  protected function getHtmlOutputHeaders() {
    return $this->formatHtmlOutputHeaders($this->getSession()->getResponseHeaders());
  }

  /**
   * Logs a HTML output message in a text file.
   *
   * The link to the HTML output message will be printed by the results printer.
   *
   * @param string|null $message
   *   (optional) The HTML output message to be stored. If not supplied the
   *   current page content is used.
   *
   * @see \Drupal\Tests\Listeners\VerbosePrinter::printResult()
   */
  protected function htmlOutput($message = NULL) {
    if (!$this->htmlOutputEnabled) {
      return;
    }
    $message = $message ?: $this->getSession()->getPage()->getContent();
    $message = '<hr />ID #' . $this->htmlOutputCounter . ' (<a href="' . $this->htmlOutputClassName . '-' . ($this->htmlOutputCounter - 1) . '-' . $this->htmlOutputTestId . '.html">Previous</a> | <a href="' . $this->htmlOutputClassName . '-' . ($this->htmlOutputCounter + 1) . '-' . $this->htmlOutputTestId . '.html">Next</a>)<hr />' . $message;
    $html_output_filename = $this->htmlOutputClassName . '-' . $this->htmlOutputCounter . '-' . $this->htmlOutputTestId . '.html';
    file_put_contents($this->htmlOutputDirectory . '/' . $html_output_filename, $message);
    file_put_contents($this->htmlOutputCounterStorage, $this->htmlOutputCounter++);
    // Do not use file_create_url() as the module_handler service might not be
    // available.
    $uri = $this->htmlOutputBaseUrl . '/sites/simpletest/browser_output/' . $html_output_filename;
    file_put_contents($this->htmlOutputFile, $uri . "\n", FILE_APPEND);
  }

  /**
   * Creates the directory to store browser output.
   *
   * Creates the directory to store browser output in if a file to write
   * URLs to has been created by \Drupal\Tests\Listeners\HtmlOutputPrinter.
   */
  protected function initBrowserOutputFile() {
    $browser_output_file = getenv('BROWSERTEST_OUTPUT_FILE');
    $this->htmlOutputEnabled = is_file($browser_output_file);
    $this->htmlOutputBaseUrl = getenv('BROWSERTEST_OUTPUT_BASE_URL') ?: $GLOBALS['base_url'];
    if ($this->htmlOutputEnabled) {
      $this->htmlOutputFile = $browser_output_file;
      $this->htmlOutputClassName = str_replace("\\", "_", get_called_class());
      $this->htmlOutputDirectory = DRUPAL_ROOT . '/sites/simpletest/browser_output';
      // Do not use the file_system service so this method can be called before
      // it is available. Checks !is_dir() twice around mkdir() because a
      // concurrent test might have made the directory and caused mkdir() to
      // fail. In this case we can still use the directory even though we failed
      // to make it.
      if (!is_dir($this->htmlOutputDirectory) && !@mkdir($this->htmlOutputDirectory, 0775, TRUE) && !is_dir($this->htmlOutputDirectory)) {
        throw new \RuntimeException(sprintf('Unable to create directory: %s', $this->htmlOutputDirectory));
      }
      if (!file_exists($this->htmlOutputDirectory . '/.htaccess')) {
        file_put_contents($this->htmlOutputDirectory . '/.htaccess', "<IfModule mod_expires.c>\nExpiresActive Off\n</IfModule>\n");
      }
      $this->htmlOutputCounterStorage = $this->htmlOutputDirectory . '/' . $this->htmlOutputClassName . '.counter';
      $this->htmlOutputTestId = str_replace('sites/simpletest/', '', $this->siteDirectory);
      if (is_file($this->htmlOutputCounterStorage)) {
        $this->htmlOutputCounter = max(1, (int) file_get_contents($this->htmlOutputCounterStorage)) + 1;
      }
    }
  }

  /**
   * Provides a Guzzle middleware handler to log every response received.
   *
   * @return callable
   *   The callable handler that will do the logging.
   */
  protected function getResponseLogHandler() {
    return function (callable $handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        return $handler($request, $options)
          ->then(function (ResponseInterface $response) use ($request) {
            if ($this->htmlOutputEnabled) {

              $caller = $this->getTestMethodCaller();
              $html_output = 'Called from ' . $caller['function'] . ' line ' . $caller['line'];
              $html_output .= '<hr />' . $request->getMethod() . ' request to: ' . $request->getUri();

              // On redirect responses (status code starting with '3') we need
              // to remove the meta tag that would do a browser refresh. We
              // don't want to redirect developers away when they look at the
              // debug output file in their browser.
              $body = $response->getBody();
              $status_code = (string) $response->getStatusCode();
              if ($status_code[0] === '3') {
                $body = preg_replace('#<meta http-equiv="refresh" content=.+/>#', '', $body, 1);
              }
              $html_output .= '<hr />' . $body;
              $html_output .= $this->formatHtmlOutputHeaders($response->getHeaders());

              $this->htmlOutput($html_output);
            }
            return $response;
          });
      };
    };
  }

  /**
   * Retrieves the current calling line in the class under test.
   *
   * @return array
   *   An associative array with keys 'file', 'line' and 'function'.
   */
  protected function getTestMethodCaller() {
    $backtrace = debug_backtrace();
    // Find the test class that has the test method.
    while ($caller = Error::getLastCaller($backtrace)) {
      if (isset($caller['class']) && $caller['class'] === get_class($this)) {
        break;
      }
      // If the test method is implemented by a test class's parent then the
      // class name of $this will not be part of the backtrace.
      // In that case we process the backtrace until the caller is not a
      // subclass of $this and return the previous caller.
      if (isset($last_caller) && (!isset($caller['class']) || !is_subclass_of($this, $caller['class']))) {
        // Return the last caller since that has to be the test class.
        $caller = $last_caller;
        break;
      }
      // Otherwise we have not reached our test class yet: save the last caller
      // and remove an element from to backtrace to process the next call.
      $last_caller = $caller;
      array_shift($backtrace);
    }

    return $caller;
  }

}
