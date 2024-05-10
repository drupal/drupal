<?php

namespace Drupal\system\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The final subscriber to 'file.upload.sanitize.name'.
 *
 * This prevents insecure filenames.
 */
class SecurityFileUploadEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new file event listener.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // This event must be run last to ensure the filename obeys the security
    // rules.
    $events[FileUploadSanitizeNameEvent::class][] = [
      'sanitizeName',
      PHP_INT_MIN,
    ];
    return $events;
  }

  /**
   * Sanitizes the upload's filename to make it secure.
   *
   * @param \Drupal\Core\File\Event\FileUploadSanitizeNameEvent $event
   *   File upload sanitize name event.
   */
  public function sanitizeName(FileUploadSanitizeNameEvent $event): void {
    $filename = $event->getFilename();
    // Dot files are renamed regardless of security settings.
    $filename = trim($filename, '.');

    // Remove any null bytes. See
    // http://php.net/manual/security.filesystem.nullbytes.php
    $filename = str_replace(chr(0), '', $filename);

    // Split up the filename by periods. The first part becomes the basename,
    // the last part the final extension.
    $filename_parts = explode('.', $filename);
    // Remove file basename.
    $filename = array_shift($filename_parts);
    // Remove final extension.
    $final_extension = (string) array_pop($filename_parts);
    // Check if we're dealing with a dot file that is also an insecure extension
    // e.g. .htaccess. In this scenario there is only one 'part' and the
    // extension becomes the filename. We use the original filename from the
    // event rather than the trimmed version above.
    $insecure_uploads = $this->configFactory->get('system.file')->get('allow_insecure_uploads');
    if (!$insecure_uploads && $final_extension === '' && str_contains($event->getFilename(), '.') && in_array(strtolower($filename), FileSystemInterface::INSECURE_EXTENSIONS, TRUE)) {
      $final_extension = $filename;
      $filename = '';
    }

    $extensions = $event->getAllowedExtensions();
    if (!empty($extensions) && !in_array(strtolower($final_extension), $extensions, TRUE)) {
      // This upload will be rejected by FileExtension constraint anyway so do
      // not make any alterations to the filename. This prevents a file named
      // 'example.php' being renamed to 'example.php_.txt' and uploaded if the
      // .txt extension is allowed but .php is not. It is the responsibility of
      // the function that dispatched the event to ensure
      // FileValidator::validate() is called with 'FileExtension' in the list of
      // validators if $extensions is not empty.
      return;
    }

    if (!$insecure_uploads && in_array(strtolower($final_extension), FileSystemInterface::INSECURE_EXTENSIONS, TRUE)) {
      if (empty($extensions) || in_array('txt', $extensions, TRUE)) {
        // Add .txt to potentially executable files prior to munging to help prevent
        // exploits. This results in a filenames like filename.php being changed to
        // filename.php.txt prior to munging.
        $filename_parts[] = $final_extension;
        $final_extension = 'txt';
      }
      else {
        // Since .txt is not an allowed extension do not rename the file. The
        // file will be rejected by FileValidator::validate().
        return;
      }
    }

    // If there are any insecure extensions in the filename munge all the
    // internal extensions.
    $munge_everything = !empty(array_intersect(array_map('strtolower', $filename_parts), FileSystemInterface::INSECURE_EXTENSIONS));

    // Munge the filename to protect against possible malicious extension hiding
    // within an unknown file type (i.e. filename.html.foo). This was introduced
    // as part of SA-2006-006 to fix Apache's risky fallback behavior.

    // Loop through the middle parts of the name and add an underscore to the
    // end of each section that could be a file extension but isn't in the
    // list of allowed extensions.
    foreach ($filename_parts as $filename_part) {
      $filename .= '.' . $filename_part;
      if ($munge_everything) {
        $filename .= '_';
      }
      elseif (!empty($extensions) && !in_array(strtolower($filename_part), $extensions) && preg_match("/^[a-zA-Z]{2,5}\d?$/", $filename_part)) {
        $filename .= '_';
      }
    }
    if ($final_extension !== '') {
      $filename .= '.' . $final_extension;
    }
    if ($filename !== $event->getFilename()) {
      $event->setFilename($filename)->setSecurityRename();
    }
  }

}
