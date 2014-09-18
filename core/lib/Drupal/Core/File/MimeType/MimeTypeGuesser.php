<?php

/**
 * @file
 * Contains \Drupal\Core\File\MimeType\MimeTypeGuesser.
 */

namespace Drupal\Core\File\MimeType;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * Defines a MIME type guesser that also supports stream wrapper paths.
 */
class MimeTypeGuesser implements MimeTypeGuesserInterface {

  /**
   * An array of arrays of registered guessers keyed by priority.
   *
   * @var array
   */
  protected $guessers = array();

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
   * {@inheritdoc}
   */
  public function guess($path) {
    if ($wrapper = file_stream_wrapper_get_instance_by_uri($path)) {
      // Get the real path from the stream wrapper.
      $path = $wrapper->realpath();
    }

    if ($this->sortedGuessers === NULL) {
      // Sort is not triggered yet.
      $this->sortedGuessers = $this->sortGuessers();
    }

    foreach ($this->sortedGuessers as $guesser) {
      $mime_type = $guesser->guess($path);
      if ($mime_type !== NULL) {
        return $mime_type;
      }
    }
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
   */
  public function addGuesser(MimeTypeGuesserInterface $guesser, $priority = 0) {
    $this->guessers[$priority][] = $guesser;
    // Mark sorted guessers for rebuild.
    $this->sortedGuessers = NULL;
    return $this;
  }

  /**
   * Sorts guessers according to priority.
   *
   * @return \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface[]
   *   A sorted array of MIME type guesser objects.
   */
  protected function sortGuessers() {
    $sorted = array();
    krsort($this->guessers);

    foreach ($this->guessers as $guesser) {
      $sorted = array_merge($sorted, $guesser);
    }
    return $sorted;
  }

}
