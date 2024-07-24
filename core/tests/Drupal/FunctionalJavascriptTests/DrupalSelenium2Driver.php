<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests;

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Exception\DriverException;
use WebDriver\Element;
use WebDriver\Exception;
use WebDriver\Exception\UnknownError;

/**
 * Provides a driver for Selenium testing.
 */
class DrupalSelenium2Driver extends Selenium2Driver {

  /**
   * Uploads a file to the Selenium instance and returns the remote path.
   *
   * \Behat\Mink\Driver\Selenium2Driver::uploadFile() is a private method so
   * that can't be used inside a test, but we need the remote path that is
   * generated when uploading to make sure the file reference exists on the
   * container running selenium.
   *
   * @param string $path
   *   The path to the file to upload.
   *
   * @return string
   *   The remote path.
   *
   * @throws \Behat\Mink\Exception\DriverException
   *   When PHP is compiled without zip support, or the file doesn't exist.
   * @throws \WebDriver\Exception\UnknownError
   *   When an unknown error occurred during file upload.
   * @throws \Exception
   *   When a known error occurred during file upload.
   */
  public function uploadFileAndGetRemoteFilePath($path) {
    if (!is_file($path)) {
      throw new DriverException('File does not exist locally and cannot be uploaded to the remote instance.');
    }

    if (!class_exists('ZipArchive')) {
      throw new DriverException('Could not compress file, PHP is compiled without zip support.');
    }

    // Selenium only accepts uploads that are compressed as a Zip archive.
    $tempFilename = tempnam('', 'WebDriverZip');

    $archive = new \ZipArchive();
    $result = $archive->open($tempFilename, \ZipArchive::OVERWRITE);
    if (!$result) {
      throw new DriverException('Zip archive could not be created. Error ' . $result);
    }
    $result = $archive->addFile($path, basename($path));
    if (!$result) {
      throw new DriverException('File could not be added to zip archive.');
    }
    $result = $archive->close();
    if (!$result) {
      throw new DriverException('Zip archive could not be closed.');
    }

    try {
      $remotePath = $this->getWebDriverSession()->file(['file' => base64_encode(file_get_contents($tempFilename))]);

      // If no path is returned the file upload failed silently.
      if (empty($remotePath)) {
        throw new UnknownError();
      }
    }
    catch (\Exception $e) {
      throw $e;
    }
    finally {
      unlink($tempFilename);
    }

    return $remotePath;
  }

  /**
   * {@inheritdoc}
   */
  public function click($xpath) {
    /** @var \Exception $not_clickable_exception */
    $not_clickable_exception = NULL;
    $result = $this->waitFor(10, function () use (&$not_clickable_exception, $xpath) {
      try {
        parent::click($xpath);
        return TRUE;
      }
      catch (Exception $exception) {
        if (!JSWebAssert::isExceptionNotClickable($exception)) {
          // Rethrow any unexpected exceptions.
          throw $exception;
        }
        $not_clickable_exception = $exception;
        return NULL;
      }
    });
    if ($result !== TRUE) {
      throw $not_clickable_exception;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($xpath, $value) {
    /** @var \Exception $not_clickable_exception */
    $not_clickable_exception = NULL;
    $result = $this->waitFor(10, function () use (&$not_clickable_exception, $xpath, $value) {
      try {
        $element = $this->getWebDriverSession()->element('xpath', $xpath);
        // \Behat\Mink\Driver\Selenium2Driver::setValue() will call .blur() on
        // the element, modify that to trigger the "input" and "change" events
        // instead. They indicate the value has changed, rather than implying
        // user focus changes. This script only runs when Drupal javascript has
        // been loaded.
        $this->executeJsOnElement($element, <<<JS
if (typeof Drupal !== 'undefined') {
  var node = {{ELEMENT}};
  var original = node.blur;
  node.blur = function() {
    node.dispatchEvent(new Event("input", {bubbles:true}));
    node.dispatchEvent(new Event("change", {bubbles:true}));
    // Do not wait for the debounce, which only triggers the 'formUpdated` event
    // up to once every 0.3 seconds. In tests, no humans are typing, hence there
    // is no need to debounce.
    // @see Drupal.behaviors.formUpdated
    node.dispatchEvent(new Event("formUpdated", {bubbles:true}));
    node.blur = original;
  };
}
JS);
        if (!is_string($value) && strtolower($element->name()) === 'input' && in_array(strtolower($element->attribute('type')), ['text', 'number', 'radio'], TRUE)) {
          // @todo Trigger deprecation in
          //   https://www.drupal.org/project/drupal/issues/3421105.
          $value = (string) $value;
        }

        parent::setValue($xpath, $value);
        return TRUE;
      }
      catch (Exception $exception) {
        if (!JSWebAssert::isExceptionNotClickable($exception) && !str_contains($exception->getMessage(), 'invalid element state')) {
          // Rethrow any unexpected exceptions.
          throw $exception;
        }
        $not_clickable_exception = $exception;
        return NULL;
      }
    });
    if ($result !== TRUE) {
      throw $not_clickable_exception;
    }
  }

  /**
   * Waits for a callback to return a truthy result and returns it.
   *
   * @param int|float $timeout
   *   Maximal allowed waiting time in seconds.
   * @param callable $callback
   *   Callback, which result is both used as waiting condition and returned.
   *   Will receive reference to `this driver` as first argument.
   *
   * @return mixed
   *   The result of the callback.
   */
  private function waitFor($timeout, callable $callback) {
    $start = microtime(TRUE);
    $end = $start + $timeout;

    do {
      $result = call_user_func($callback, $this);

      if ($result) {
        break;
      }

      usleep(10000);
    } while (microtime(TRUE) < $end);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function dragTo($sourceXpath, $destinationXpath) {
    // Ensure both the source and destination exist at this point.
    $this->getWebDriverSession()->element('xpath', $sourceXpath);
    $this->getWebDriverSession()->element('xpath', $destinationXpath);

    try {
      parent::dragTo($sourceXpath, $destinationXpath);
    }
    catch (Exception) {
      // Do not care if this fails for any reason. It is a source of random
      // fails. The calling code should be doing assertions on the results of
      // dragging anyway. See upstream issues:
      // - https://github.com/minkphp/MinkSelenium2Driver/issues/97
      // - https://github.com/minkphp/MinkSelenium2Driver/issues/51
    }
  }

  /**
   * Executes JS on a given element.
   *
   * @param \WebDriver\Element $element
   *   The webdriver element.
   * @param string $script
   *   The script to execute.
   *
   * @return mixed
   *   The result of executing the script.
   */
  private function executeJsOnElement(Element $element, string $script) {
    $script = str_replace('{{ELEMENT}}', 'arguments[0]', $script);

    $options = [
      'script' => $script,
      'args' => [$element],
    ];

    return $this->getWebDriverSession()->execute($options);
  }

}
