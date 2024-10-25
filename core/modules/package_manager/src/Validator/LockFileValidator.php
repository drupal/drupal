<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\PostApplyEvent;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreOperationStageEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Checks that the active lock file is unchanged during stage operations.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class LockFileValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The key under which to store the hash of the active lock file.
   *
   * @var string
   */
  private const KEY = 'lock_hash';

  /**
   * The key-value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  private readonly KeyValueStoreInterface $keyValue;

  public function __construct(
    KeyValueFactoryInterface $keyValueFactory,
    private readonly PathLocator $pathLocator,
  ) {
    $this->keyValue = $keyValueFactory->get('package_manager');
  }

  /**
   * Returns the XXH64 hash of a file.
   *
   * This method is a thin wrapper around hash_file() to facilitate testing. On
   * failure, hash_file() emits a warning but doesn't throw an exception. In
   * tests, however, PHPUnit converts warnings to exceptions, so we need to
   * catch those and convert them to the value hash_file() will actually return
   * on error, which is FALSE. We could also just call `hash_file` directly and
   * use @ to suppress warnings, but those would be unclear and likely to be
   * accidentally removed later.
   *
   * @param string $path
   *   Path of the file to hash.
   *
   * @return string|false
   *   The hash of the given file, or FALSE if the file doesn't exist or cannot
   *   be hashed.
   */
  private function getHash(string $path): string|false {
    try {
      return @hash_file('xxh64', $path);
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   * Stores the XXH64 hash of the active lock file.
   *
   * We store the hash of the lock file itself, rather than its content-hash
   * value, which is actually a hash of certain parts of composer.json. Our aim
   * is to verify that the actual installed packages have not changed
   * unexpectedly; we don't care about the contents of composer.json.
   *
   * @param \Drupal\package_manager\Event\PreCreateEvent $event
   *   The event being handled.
   */
  public function storeHash(PreCreateEvent $event): void {
    $active_lock_file_path = $this->pathLocator->getProjectRoot() . DIRECTORY_SEPARATOR . 'composer.lock';
    $hash = $this->getHash($active_lock_file_path);
    if ($hash) {
      $this->keyValue->set(static::KEY, $hash);
    }
    else {
      $event->addError([
        $this->t('The active lock file (@file) does not exist.', [
          '@file' => $active_lock_file_path,
        ]),
      ]);
    }
  }

  /**
   * Checks that the active lock file is unchanged during stage operations.
   *
   * @param \Drupal\package_manager\Event\PreOperationStageEvent $event
   *   The event being handled.
   */
  public function validate(PreOperationStageEvent $event): void {
    $stage = $event->stage;

    // Early return if the stage is not already created.
    if ($event instanceof StatusCheckEvent && $stage->isAvailable()) {
      return;
    }

    $messages = [];
    // Ensure we can get a current hash of the lock file.
    $active_lock_file_path = $this->pathLocator->getProjectRoot() . DIRECTORY_SEPARATOR . 'composer.lock';
    $active_lock_file_hash = $this->getHash($active_lock_file_path);
    if (empty($active_lock_file_hash)) {
      $messages[] = $this->t('The active lock file (@file) does not exist.', [
        '@file' => $active_lock_file_path,
      ]);
    }

    // Ensure we also have a stored hash of the lock file.
    $active_lock_file_stored_hash = $this->keyValue->get(static::KEY);
    if (empty($active_lock_file_stored_hash)) {
      throw new \LogicException('Stored hash key deleted.');
    }

    // If we have both hashes, ensure they match.
    if ($active_lock_file_hash && !hash_equals($active_lock_file_stored_hash, $active_lock_file_hash)) {
      $messages[] = $this->t('Unexpected changes were detected in the active lock file (@file), which indicates that other Composer operations were performed since this Package Manager operation started. This can put the code base into an unreliable state and therefore is not allowed.', [
        '@file' => $active_lock_file_path,
      ]);
    }

    // Don't allow staged changes to be applied if the staged lock file has no
    // apparent changes.
    if (empty($messages) && $event instanceof PreApplyEvent) {
      $staged_lock_file_path = $stage->getStageDirectory() . DIRECTORY_SEPARATOR . 'composer.lock';
      $staged_lock_file_hash = $this->getHash($staged_lock_file_path);
      if ($staged_lock_file_hash && hash_equals($active_lock_file_hash, $staged_lock_file_hash)) {
        $messages[] = $this->t('There appear to be no pending Composer operations because the active lock file (@active_file) and the staged lock file (@staged_file) are identical.', [
          '@active_file' => $active_lock_file_path,
          '@staged_file' => $staged_lock_file_path,
        ]);
      }
    }

    if (!empty($messages)) {
      $summary = $this->formatPlural(
        count($messages),
        'Problem detected in lock file during stage operations.',
        'Problems detected in lock file during stage operations.',
      );
      $event->addError($messages, $summary);
    }
  }

  /**
   * Deletes the stored lock file hash.
   */
  public function deleteHash(): void {
    $this->keyValue->delete(static::KEY);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreCreateEvent::class => 'storeHash',
      PreRequireEvent::class => 'validate',
      PreApplyEvent::class => 'validate',
      StatusCheckEvent::class => 'validate',
      PostApplyEvent::class => 'deleteHash',
    ];
  }

}
