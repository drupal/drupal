<?php

namespace Drupal\Core\Batch;

use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Builds an array for a batch process.
 *
 * Example code to create a batch:
 * @code
 * $batch_builder = (new BatchBuilder())
 *   ->setTitle(t('Batch Title'))
 *   ->setFinishCallback('batch_example_finished_callback')
 *   ->setInitMessage(t('The initialization message (optional)'));
 * foreach ($ids as $id) {
 *   $batch_builder->addOperation('batch_example_callback', [$id]);
 * }
 * batch_set($batch_builder->toArray());
 * @endcode
 */
class BatchBuilder {

  /**
   * The set of operations to be processed.
   *
   * Each operation is a tuple of the function / method to use and an array
   * containing any parameters to be passed.
   *
   * @var array
   */
  protected $operations = [];

  /**
   * The title for the batch.
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $title;

  /**
   * The initializing message for the batch.
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $initMessage;

  /**
   * The message to be shown while the batch is in progress.
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $progressMessage;

  /**
   * The message to be shown if a problem occurs.
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $errorMessage;

  /**
   * The name of a function / method to be called when the batch finishes.
   *
   * @var string
   */
  protected $finished;

  /**
   * The file containing the operation and finished callbacks.
   *
   * If the callbacks are in the .module file or can be autoloaded, for example,
   * static methods on a class, then this does not need to be set.
   *
   * @var string
   */
  protected $file;

  /**
   * An array of libraries to be included when processing the batch.
   *
   * @var string[]
   */
  protected $libraries = [];

  /**
   * An array of options to be used with the redirect URL.
   *
   * @var array
   */
  protected $urlOptions = [];

  /**
   * Specifies if the batch is progressive.
   *
   * If true, multiple calls are used. Otherwise an attempt is made to process
   * the batch in a single run.
   *
   * @var bool
   */
  protected $progressive = TRUE;

  /**
   * The details of the queue to use.
   *
   * A tuple containing the name of the queue and the class of the queue to use.
   *
   * @var array
   */
  protected $queue;

  /**
   * Sets the default values for the batch builder.
   */
  public function __construct() {
    $this->title = new TranslatableMarkup('Processing');
    $this->initMessage = new TranslatableMarkup('Initializing.');
    $this->progressMessage = new TranslatableMarkup('Completed @current of @total.');
    $this->errorMessage = new TranslatableMarkup('An error has occurred.');
  }

  /**
   * Sets the title.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The title.
   *
   * @return $this
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * Sets the finished callback.
   *
   * This callback will be executed if the batch process is done.
   *
   * @param callable $callback
   *   The callback.
   *
   * @return $this
   */
  public function setFinishCallback(callable $callback) {
    $this->finished = $callback;
    return $this;
  }

  /**
   * Sets the displayed message while processing is initialized.
   *
   * Defaults to 'Initializing.'.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The text to display.
   *
   * @return $this
   */
  public function setInitMessage($message) {
    $this->initMessage = $message;
    return $this;
  }

  /**
   * Sets the message to display when the batch is being processed.
   *
   * Defaults to 'Completed @current of @total.'.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The text to display.  Available placeholders are:
   *   - '@current'
   *   - '@remaining'
   *   - '@total'
   *   - '@percentage'
   *   - '@estimate'
   *   - '@elapsed'.
   *
   * @return $this
   */
  public function setProgressMessage($message) {
    $this->progressMessage = $message;
    return $this;
  }

  /**
   * Sets the message to display if an error occurs while processing.
   *
   * Defaults to 'An error has occurred.'.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The text to display.
   *
   * @return $this
   */
  public function setErrorMessage($message) {
    $this->errorMessage = $message;
    return $this;
  }

  /**
   * Sets the file that contains the callback functions.
   *
   * The path should be relative to base_path(), and thus should be built using
   * \Drupal\Core\Extension\ExtensionList::getPath(). Defaults to
   * {module_name}.module.
   *
   * The file needs to be set before using ::addOperation(),
   * ::setFinishCallback(), or any other function that uses callbacks from the
   * file. This is so that PHP knows about the included functions.
   *
   * @param string $filename
   *   The path to the file.
   *
   * @return $this
   */
  public function setFile($filename) {
    include_once $filename;

    $this->file = $filename;
    return $this;
  }

  /**
   * Sets the libraries to use when processing the batch.
   *
   * Adds the libraries for use on the progress page. Any previously added
   * libraries are removed.
   *
   * @param string[] $libraries
   *   The libraries to be used.
   *
   * @return $this
   */
  public function setLibraries(array $libraries) {
    $this->libraries = $libraries;
    return $this;
  }

  /**
   * Sets the options for redirect URLs.
   *
   * @param array $options
   *   The options to use.
   *
   * @return $this
   *
   * @see \Drupal\Core\Url
   */
  public function setUrlOptions(array $options) {
    $this->urlOptions = $options;
    return $this;
  }

  /**
   * Sets the batch to run progressively.
   *
   * @param bool $is_progressive
   *   (optional) A Boolean that indicates whether or not the batch needs to run
   *   progressively. TRUE indicates that the batch will run in more than one
   *   run. FALSE indicates that the batch will finish in a single run. Defaults
   *   to TRUE.
   *
   * @return $this
   */
  public function setProgressive($is_progressive = TRUE) {
    $this->progressive = $is_progressive;
    return $this;
  }

  /**
   * Sets an override for the default queue.
   *
   * The class will typically either be \Drupal\Core\Queue\Batch or
   * \Drupal\Core\Queue\BatchMemory. The class defaults to Batch if progressive
   * is TRUE, or to BatchMemory if progressive is FALSE.
   *
   * @param string $name
   *   The unique identifier for the queue.
   * @param string $class
   *   The fully qualified name of a class that implements
   *   \Drupal\Core\Queue\QueueInterface.
   *
   * @return $this
   */
  public function setQueue($name, $class) {
    if (!class_exists($class)) {
      throw new \InvalidArgumentException('Class ' . $class . ' does not exist.');
    }

    if (!in_array(QueueInterface::class, class_implements($class))) {
      throw new \InvalidArgumentException(
        'Class ' . $class . ' does not implement \Drupal\Core\Queue\QueueInterface.'
      );
    }

    $this->queue = [
      'name' => $name,
      'class' => $class,
    ];
    return $this;
  }

  /**
   * Adds a batch operation.
   *
   * @param callable $callback
   *   The name of the callback function.
   * @param array $arguments
   *   An array of arguments to pass to the callback function.
   *
   * @return $this
   */
  public function addOperation(callable $callback, array $arguments = []) {
    $this->operations[] = [$callback, $arguments];
    return $this;
  }

  /**
   * Converts a \Drupal\Core\Batch\Batch object into an array.
   *
   * @return array
   *   The array representation of the object.
   */
  public function toArray() {
    $array = [
      'operations' => $this->operations ?: [],
      'title' => $this->title ?: '',
      'init_message' => $this->initMessage ?: '',
      'progress_message' => $this->progressMessage ?: '',
      'error_message' => $this->errorMessage ?: '',
      'finished' => $this->finished,
      'file' => $this->file,
      'library' => $this->libraries ?: [],
      'url_options' => $this->urlOptions ?: [],
      'progressive' => $this->progressive,
    ];

    if ($this->queue) {
      $array['queue'] = $this->queue;
    }

    return $array;
  }

}
