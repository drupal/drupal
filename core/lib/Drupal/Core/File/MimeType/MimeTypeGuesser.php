<?php

namespace Drupal\Core\File\MimeType;

use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypes;

/**
 * Defines a MIME type guesser that also supports stream wrapper paths.
 */
class MimeTypeGuesser implements MimeTypeGuesserInterface {

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
   * @var \Symfony\Component\Mime\MimeTypeGuesserInterface[]
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

    return 'application/octet-stream';
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
    krsort($this->guessers);
    return array_merge(...$this->guessers);
  }

  /**
   * A helper function to register with Symfony's singleton MIME type guesser.
   *
   * Symfony's default mimetype guessers have dependencies on PHP's fileinfo
   * extension or being able to run the system command file. Drupal's guesser
   * does not have these dependencies.
   *
   * @see \Symfony\Component\Mime\MimeTypes
   */
  public static function registerWithSymfonyGuesser(
    ContainerInterface $container,
  ): void {
    $guesser = new MimeTypes();
    $guesser->registerGuesser($container->get('file.mime_type.guesser'));
    MimeTypes::setDefault($guesser);
  }

}
