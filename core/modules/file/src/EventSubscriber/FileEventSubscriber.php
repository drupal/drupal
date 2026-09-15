<?php

namespace Drupal\file\EventSubscriber;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sanitizes uploaded filenames.
 *
 * @package Drupal\file\EventSubscriber
 */
class FileEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new file event listener.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    #[Autowire(service: 'transliteration')]
    protected TransliterationInterface $transliteration,
    protected LanguageManagerInterface $languageManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      FileUploadSanitizeNameEvent::class => [
        // Run before every other listener so that they can all rely on the
        // filename being valid UTF-8, and therefore safe to manipulate with
        // PCRE's /u modifier and the mb_* functions.
        ['ensureValidUtf8Filename', PHP_INT_MAX],
        ['sanitizeFilename'],
      ],
    ];
  }

  /**
   * Guarantees a valid UTF-8 filename for subsequent event handlers.
   *
   * @param \Drupal\Core\File\Event\FileUploadSanitizeNameEvent $event
   *   File upload sanitize name event.
   */
  public function ensureValidUtf8Filename(FileUploadSanitizeNameEvent $event): void {
    $filename = $event->getFilename();
    if (!mb_check_encoding($filename, 'UTF-8')) {
      $replacement = $this->configFactory->get('file.settings')
        ->get('filename_sanitization.replacement_character');
      $event->setFilename(self::coerceToValidUtf8($filename, $replacement));
    }
  }

  /**
   * Sanitizes the filename of a file being uploaded.
   *
   * @param \Drupal\Core\File\Event\FileUploadSanitizeNameEvent $event
   *   File upload sanitize name event.
   *
   * @see file_form_system_file_system_settings_alter()
   */
  public function sanitizeFilename(FileUploadSanitizeNameEvent $event) {
    $fileSettings = $this->configFactory->get('file.settings');
    $transliterate = $fileSettings->get('filename_sanitization.transliterate');

    $filename = $event->getFilename();
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    if ($extension !== '') {
      $extension = '.' . $extension;
      $filename = pathinfo($filename, PATHINFO_FILENAME);
    }

    // Sanitize the filename according to configuration.
    $alphanumeric = $fileSettings->get('filename_sanitization.replace_non_alphanumeric');
    $replacement = $fileSettings->get('filename_sanitization.replacement_character');
    if ($transliterate) {
      $transliterated_filename = $this->transliteration->transliterate(
        $filename,
        $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId(),
        $replacement
      );
      if (mb_strlen($transliterated_filename) > 0) {
        $filename = $transliterated_filename;
      }
      else {
        // If transliteration has resulted in a zero length string enable the
        // 'replace_non_alphanumeric' option and ignore the result of
        // transliteration.
        $alphanumeric = TRUE;
      }
    }

    // ::ensureValidUtf8Filename() ensures the /u modifier is safe here.
    if ($fileSettings->get('filename_sanitization.replace_whitespace')) {
      $filename = preg_replace('/\s/u', $replacement, trim($filename));
    }
    // Only honor replace_non_alphanumeric if transliterate is enabled.
    if ($transliterate && $alphanumeric) {
      $filename = preg_replace('/[^0-9A-Za-z_.-]/u', $replacement, $filename);
    }
    if ($fileSettings->get('filename_sanitization.deduplicate_separators')) {
      $filename = preg_replace('/(_)_+|(\.)\.+|(-)-+/u', $replacement, $filename);
      // Replace multiple separators with single one.
      $filename = preg_replace('/(_|\.|\-)[(_|\.|\-)]+/u', $replacement, $filename);
      $filename = preg_replace('/' . preg_quote($replacement, NULL) . '[' . preg_quote($replacement, NULL) . ']*/u', $replacement, $filename);
      // Remove replacement character from the end of the filename.
      $filename = rtrim($filename, $replacement);

      // If there is an extension remove dots from the end of the filename to
      // prevent duplicate dots.
      if (!empty($extension)) {
        $filename = rtrim($filename, '.');
      }
    }
    if ($fileSettings->get('filename_sanitization.lowercase')) {
      // Force lowercase to prevent issues on case-insensitive file systems.
      $filename = mb_strtolower($filename);
    }
    $event->setFilename($filename . $extension);
  }

  /**
   * Replaces invalid UTF-8 byte sequences in a string.
   *
   * PHP's mb_convert_encoding() replaces every invalid byte with a ? so any
   * that already present are preserved when a different $unknown_character is
   * specified.
   *
   * @param string $string
   *   The string to coerce to valid UTF-8.
   * @param string $unknown_character
   *   The character substituted for invalid byte sequences.
   *
   * @return string
   *   The string as valid UTF-8.
   */
  private static function coerceToValidUtf8(string $string, string $unknown_character = '?'): string {
    $code_point = mb_ord($unknown_character, 'UTF-8');
    if ($code_point === FALSE) {
      throw new \InvalidArgumentException('$unknown_character must be a single valid UTF-8 character.');
    }

    $previous = mb_substitute_character();
    mb_substitute_character($code_point);
    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    mb_substitute_character($previous);
    return $string;
  }

}
