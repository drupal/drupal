<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\package_manager\Exception\StageFailureMarkerException;

/**
 * Handles failure marker file operation.
 *
 * The failure marker is a file placed in the active directory while staged
 * code is copied back into it, and then removed afterward. This allows us to
 * know if a commit operation failed midway through, which could leave the site
 * code base in an indeterminate state -- which, in the worst case scenario,
 * might render Drupal being unable to boot.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class FailureMarker implements EventSubscriberInterface {

  public function __construct(private readonly PathLocator $pathLocator) {
  }

  /**
   * Gets the marker file path.
   *
   * @return string
   *   The absolute path of the marker file.
   */
  public function getPath(): string {
    return $this->pathLocator->getProjectRoot() . '/PACKAGE_MANAGER_FAILURE.yml';
  }

  /**
   * Deletes the marker file.
   */
  public function clear(): void {
    unlink($this->getPath());
  }

  /**
   * Writes data to marker file.
   *
   * @param \Drupal\package_manager\StageBase $stage
   *   The stage.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   Failure message to be added.
   * @param \Throwable|null $throwable
   *   (optional) The throwable that caused the failure.
   */
  public function write(StageBase $stage, TranslatableMarkup $message, ?\Throwable $throwable = NULL): void {
    $data = [
      'stage_class' => get_class($stage),
      'stage_type' => $stage->getType(),
      'stage_file' => (new \ReflectionObject($stage))->getFileName(),
      'message' => (string) $message,
      'throwable_class' => $throwable ? get_class($throwable) : FALSE,
      'throwable_message' => $throwable?->getMessage() ?? 'Not available',
      'throwable_backtrace' => $throwable?->getTraceAsString() ?? 'Not available.',
    ];
    file_put_contents($this->getPath(), Yaml::dump($data));
  }

  /**
   * Gets the data from the file if it exists.
   *
   * @return array|null
   *   The data from the file if it exists.
   *
   * @throws \Drupal\package_manager\Exception\StageFailureMarkerException
   *   Thrown if failure marker exists but cannot be decoded.
   */
  private function getData(): ?array {
    $path = $this->getPath();
    if (file_exists($path)) {
      $data = file_get_contents($path);
      try {
        return Yaml::parse($data);

      }
      catch (ParseException $exception) {
        throw new StageFailureMarkerException('Failure marker file exists but cannot be decoded.', $exception->getCode(), $exception);
      }
    }
    return NULL;
  }

  /**
   * Gets the message from the file if it exists.
   *
   * @param bool $include_backtrace
   *   Whether to include the backtrace in the message. Defaults to TRUE. May be
   *   set to FALSE in a context where it does not make sense to include, such
   *   as emails.
   *
   * @return string|null
   *   The message from the file if it exists, otherwise NULL.
   *
   * @throws \Drupal\package_manager\Exception\StageFailureMarkerException
   *   Thrown if failure marker exists but cannot be decoded.
   */
  public function getMessage(bool $include_backtrace = TRUE): ?string {
    $data = $this->getData();
    if ($data === NULL) {
      return NULL;
    }
    $message = $data['message'];
    if ($data['throwable_class']) {
      $message .= sprintf(
        ' Caused by %s, with this message: %s',
        $data['throwable_class'],
        $data['throwable_message'],
      );
      if ($include_backtrace) {
        $message .= "\nBacktrace:\n" . $data['throwable_backtrace'];
      }
    }
    return $message;
  }

  /**
   * Asserts the failure file doesn't exist.
   *
   * @throws \Drupal\package_manager\Exception\StageFailureMarkerException
   *   Thrown if the marker file exists.
   */
  public function assertNotExists(): void {
    if ($message = $this->getMessage()) {
      throw new StageFailureMarkerException($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CollectPathsToExcludeEvent::class => 'excludeMarkerFile',
    ];
  }

  /**
   * Excludes the failure marker file from stage operations.
   *
   * @param \Drupal\package_manager\Event\CollectPathsToExcludeEvent $event
   *   The event being handled.
   */
  public function excludeMarkerFile(CollectPathsToExcludeEvent $event): void {
    $event->addPathsRelativeToProjectRoot([
      $this->getPath(),
    ]);
  }

}
