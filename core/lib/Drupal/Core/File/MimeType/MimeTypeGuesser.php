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
   * Constructs a MimeTypeGuesser object.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream wrapper manager.
   * @param \Traversable<\Symfony\Component\Mime\MimeTypeGuesserInterface> $guessers
   *   The MIME type guessers.
   */
  public function __construct(
    protected StreamWrapperManagerInterface $streamWrapperManager,
    protected \Traversable $guessers,
  ) {}

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

    foreach ($this->guessers as $guesser) {
      if ($guesser->isGuesserSupported()) {
        $mime_type = $guesser->guessMimeType($path);
        if ($mime_type !== NULL) {
          return $mime_type;
        }
      }
    }

    return 'application/octet-stream';
  }

  /**
   * {@inheritdoc}
   */
  public function isGuesserSupported(): bool {
    return TRUE;
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
