<?php

namespace Drupal\Core\File\MimeType;

use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser as SymfonyMimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface as LegacyMimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface as MimeTypeGuesserInterface;

/**
 * Defines a MIME type guesser that also supports stream wrapper paths.
 */
class MimeTypeGuesser implements LegacyMimeTypeGuesserInterface, MimeTypeGuesserInterface {

  /**
   * An array of arrays of registered guessers keyed by priority.
   *
   * @var array
   */
  protected $guessers = [];

  /**
   * Holds the array of guessers sorted by priority.
   *
   * If this is NULL a rebuild will be triggered.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface[]
   *
   * @see \Drupal\Core\File\MimeType\MimeTypeGuesser::addGuesser()
   * @see \Drupal\Core\File\MimeType\MimeTypeGuesser::sortGuessers()
   */
  protected $sortedGuessers = NULL;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a MimeTypeGuesser object.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function guessMimeType(string $path) : ?string {
    if ($wrapper = $this->streamWrapperManager->getViaUri($path)) {
      // Get the real path from the stream wrapper, if available. Files stored
      // in remote file systems will not have one.
      $real_path = $wrapper->realpath();
      if ($real_path !== FALSE) {
        $path = $real_path;
      }
    }

    if ($this->sortedGuessers === NULL) {
      // Sort is not triggered yet.
      $this->sortedGuessers = $this->sortGuessers();
    }

    foreach ($this->sortedGuessers as $guesser) {
      $mime_type = $guesser->guessMimeType($path);
      if ($mime_type !== NULL) {
        return $mime_type;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function guess($path) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use ::guessMimeType() instead. See https://www.drupal.org/node/3133341', E_USER_DEPRECATED);
    return $this->guessMimeType($path);
  }

  /**
   * Appends a MIME type guesser to the guessers chain.
   *
   * @param \Symfony\Component\Mime\MimeTypeGuesserInterface $guesser
   *   The guesser to be appended.
   * @param int $priority
   *   The priority of the guesser being added.
   *
   * @return $this
   */
  public function addMimeTypeGuesser(MimeTypeGuesserInterface $guesser, $priority = 0) {
    if ($guesser->isGuesserSupported()) {
      $this->guessers[$priority][] = $guesser;
      // Mark sorted guessers for rebuild.
      $this->sortedGuessers = NULL;
    }
    return $this;
  }

  /**
   * Appends a MIME type guesser to the guessers chain.
   *
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $guesser
   *   The guesser to be appended.
   * @param int $priority
   *   The priority of the guesser being added.
   *
   * @return $this
   *
   * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use
   * ::addMimeTypeGuesser() instead.
   *
   * @see https://www.drupal.org/node/3133341
   */
  public function addGuesser(LegacyMimeTypeGuesserInterface $guesser, $priority = 0) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use ::addMimeTypeGuesser() instead. See https://www.drupal.org/node/3133341', E_USER_DEPRECATED);
    $this->guessers[$priority][] = $guesser;
    // Mark sorted guessers for rebuild.
    $this->sortedGuessers = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isGuesserSupported(): bool {
    return TRUE;
  }

  /**
   * Sorts guessers according to priority.
   *
   * @return \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface[]
   *   A sorted array of MIME type guesser objects.
   */
  protected function sortGuessers() {
    $sorted = [];
    krsort($this->guessers);

    foreach ($this->guessers as $guesser) {
      $sorted = array_merge($sorted, $guesser);
    }
    return $sorted;
  }

  /**
   * A helper function to register with Symfony's singleton MIME type guesser.
   *
   * Symfony's default mimetype guessers have dependencies on PHP's fileinfo
   * extension or being able to run the system command file. Drupal's guesser
   * does not have these dependencies.
   *
   * @see \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser
   */
  public static function registerWithSymfonyGuesser(ContainerInterface $container) {
    // Reset state, so we do not store more and more services during test runs.
    SymfonyMimeTypeGuesser::reset();
    $singleton = SymfonyMimeTypeGuesser::getInstance();
    $singleton->register($container->get('file.mime_type.guesser'));
  }

}
