<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Composer\Semver\VersionParser;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Random;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\SharedTempStore;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\Core\Utility\Error;
use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager\Event\PostApplyEvent;
use Drupal\package_manager\Event\PostCreateEvent;
use Drupal\package_manager\Event\PostRequireEvent;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreOperationStageEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Event\StageEvent;
use Drupal\package_manager\Exception\ApplyFailedException;
use Drupal\package_manager\Exception\StageEventException;
use Drupal\package_manager\Exception\StageException;
use Drupal\package_manager\Exception\StageOwnershipException;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Core\StagerInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Creates and manages a stage directory in which to install or update code.
 *
 * Allows calling code to copy the current Drupal site into a temporary stage
 * directory, use Composer to require packages into it, sync changes from the
 * stage directory back into the active code base, and then delete the
 * stage directory.
 *
 * Only one stage directory can exist at any given time, and the stage is
 * owned by the user or session that originally created it. Only the owner can
 * perform operations on the stage directory, and the stage must be "claimed"
 * by its owner before any such operations are done. A stage is claimed by
 * presenting a unique token that is generated when the stage is created.
 *
 * Although a site can only have one stage directory, it is possible for
 * privileged users to destroy a stage created by another user. To prevent such
 * actions from putting the file system into an uncertain state (for example, if
 * a stage is destroyed by another user while it is still being created), the
 * stage directory has a randomly generated name. For additional cleanliness,
 * all stage directories created by a specific site live in a single directory
 * ,called the "stage root directory" and identified by the UUID of the current
 * site (e.g. `/tmp/.package_managerSITE_UUID`), which is deleted when any stage
 * created by that site is destroyed.
 */
abstract class StageBase implements LoggerAwareInterface {

  use LoggerAwareTrait;
  use StringTranslationTrait;

  /**
   * The tempstore key under which to store the locking info for this stage.
   *
   * @var string
   */
  final protected const TEMPSTORE_LOCK_KEY = 'lock';

  /**
   * The tempstore key under which to store arbitrary metadata for this stage.
   *
   * @var string
   */
  final protected const TEMPSTORE_METADATA_KEY = 'metadata';

  /**
   * The tempstore key under which to store the path of stage root directory.
   *
   * @var string
   *
   * @see ::getStagingRoot()
   */
  private const TEMPSTORE_STAGING_ROOT_KEY = 'staging_root';

  /**
   * The tempstore key under which to store the time that ::apply() was called.
   *
   * @var string
   *
   * @see ::apply()
   * @see ::destroy()
   */
  private const TEMPSTORE_APPLY_TIME_KEY = 'apply_time';

  /**
   * The tempstore key for whether staged operations have been applied.
   *
   * @var string
   *
   * @see ::apply()
   * @see ::destroy()
   */
  private const TEMPSTORE_CHANGES_APPLIED = 'changes_applied';

  /**
   * The tempstore key for information about previously destroyed stages.
   *
   * @var string
   *
   * @see ::apply()
   * @see ::destroy()
   */
  private const TEMPSTORE_DESTROYED_STAGES_INFO_PREFIX = 'TEMPSTORE_DESTROYED_STAGES_INFO';

  /**
   * The regular expression to check if a package name is a platform package.
   *
   * @var string
   *
   * @see \Composer\Repository\PlatformRepository::PLATFORM_PACKAGE_REGEX
   * @see ::validateRequirements()
   */
  private const COMPOSER_PLATFORM_PACKAGE_REGEX = '{^(?:php(?:-64bit|-ipv6|-zts|-debug)?|hhvm|(?:ext|lib)-[a-z0-9](?:[_.-]?[a-z0-9]+)*|composer(?:-(?:plugin|runtime)-api)?)$}iD';

  /**
   * The regular expression to check if a package name is a regular package.
   *
   * If you try to require an invalid package name, this is the regular
   * expression that Composer will, at the command line, tell you to match.
   *
   * @var string
   *
   * @see \Composer\Package\Loader\ValidatingArrayLoader::hasPackageNamingError()
   * @see ::validateRequirements()
   */
  private const COMPOSER_PACKAGE_REGEX = '/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9](([_.]?|-{0,2})[a-z0-9]+)*$/';

  /**
   * The lock info for the stage.
   *
   * Consists of a unique random string and the current class name.
   *
   * @var string[]
   */
  private $lock;

  /**
   * The shared temp store.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected SharedTempStore $tempStore;

  /**
   * The stage type.
   *
   * To ensure that stage classes do not unintentionally use another stage's
   * type, all concrete subclasses MUST explicitly define this property.
   * The recommended pattern is `MODULE:TYPE`.
   *
   * @var string
   */
  protected string $type;

  public function __construct(
    protected readonly PathLocator $pathLocator,
    protected readonly BeginnerInterface $beginner,
    protected readonly StagerInterface $stager,
    protected readonly CommitterInterface $committer,
    protected readonly QueueFactory $queueFactory,
    protected EventDispatcherInterface $eventDispatcher,
    protected readonly SharedTempStoreFactory $tempStoreFactory,
    protected readonly TimeInterface $time,
    protected readonly PathFactoryInterface $pathFactory,
    protected readonly FailureMarker $failureMarker,
  ) {
    $this->tempStore = $tempStoreFactory->get('package_manager_stage');
  }

  /**
   * Gets the stage type.
   *
   * The stage type can be used by stage event subscribers to implement logic
   * specific to certain stages, without relying on the class name (which may
   * not be part of module's public API).
   *
   * @return string
   *   The stage type.
   *
   * @throws \LogicException
   *   Thrown if $this->type is not explicitly overridden.
   */
  final public function getType(): string {
    $reflector = new \ReflectionProperty($this, 'type');

    // The $type property must ALWAYS be overridden. This means that different
    // subclasses can return the same value (thus allowing one stage to
    // impersonate another one), but if that happens, it is intentional.
    if ($reflector->getDeclaringClass()->getName() === static::class) {
      return $this->type;
    }
    throw new \LogicException(static::class . ' must explicitly override the $type property.');
  }

  /**
   * Determines if the stage directory can be created.
   *
   * @return bool
   *   TRUE if the stage directory can be created, otherwise FALSE.
   */
  final public function isAvailable(): bool {
    return empty($this->tempStore->getMetadata(static::TEMPSTORE_LOCK_KEY));
  }

  /**
   * Returns a specific piece of metadata associated with this stage.
   *
   * Only the owner of the stage can access metadata, and the stage must either
   * be claimed by its owner, or created during the current request.
   *
   * @param string $key
   *   The metadata key.
   *
   * @return mixed
   *   The metadata value, or NULL if it is not set.
   */
  public function getMetadata(string $key) {
    $this->checkOwnership();

    $metadata = $this->tempStore->get(static::TEMPSTORE_METADATA_KEY) ?: [];
    return $metadata[$key] ?? NULL;
  }

  /**
   * Stores arbitrary metadata associated with this stage.
   *
   * Only the owner of the stage can set metadata, and the stage must either be
   * claimed by its owner, or created during the current request.
   *
   * @param string $key
   *   The key under which to store the metadata. To prevent conflicts, it is
   *   strongly recommended that this be prefixed with the name of the module
   *   storing the data.
   * @param mixed $data
   *   The metadata to store.
   */
  public function setMetadata(string $key, $data): void {
    $this->checkOwnership();

    $metadata = $this->tempStore->get(static::TEMPSTORE_METADATA_KEY);
    $metadata[$key] = $data;
    $this->tempStore->set(static::TEMPSTORE_METADATA_KEY, $metadata);
  }

  /**
   * Collects paths that Composer Stager should exclude.
   *
   * @return \PhpTuf\ComposerStager\API\Path\Value\PathListInterface
   *   A list of paths that Composer Stager should exclude when creating the
   *   stage directory and applying staged changes to the active directory.
   *
   * @throws \Drupal\package_manager\Exception\StageException
   *   Thrown if an exception occurs while collecting paths to exclude.
   *
   * @see ::create()
   * @see ::apply()
   */
  protected function getPathsToExclude(): PathListInterface {
    $event = new CollectPathsToExcludeEvent($this, $this->pathLocator, $this->pathFactory);
    try {
      return $this->eventDispatcher->dispatch($event);
    }
    catch (\Throwable $e) {
      $this->rethrowAsStageException($e);
    }
  }

  /**
   * Copies the active code base into the stage directory.
   *
   * This will automatically claim the stage, so external code does NOT need to
   * call ::claim(). However, if it was created during another request, the
   * stage must be claimed before operations can be performed on it.
   *
   * @param int|null $timeout
   *   (optional) How long to allow the file copying operation to run before
   *   timing out, in seconds, or NULL to never time out. Defaults to 300
   *   seconds.
   *
   * @return string
   *   Unique ID for the stage, which can be used to claim the stage before
   *   performing other operations on it. Calling code should store this ID for
   *   as long as the stage needs to exist.
   *
   * @throws \Drupal\package_manager\Exception\StageException
   *   Thrown if a stage directory already exists, or if an error occurs while
   *   creating the stage directory. In the latter situation, the stage
   *   directory will be destroyed.
   *
   * @see ::claim()
   */
  public function create(?int $timeout = 300): string {
    $this->failureMarker->assertNotExists();

    if (!$this->isAvailable()) {
      throw new StageException($this, 'Cannot create a new stage because one already exists.');
    }
    // Mark the stage as unavailable as early as possible, before dispatching
    // the pre-create event. The idea is to prevent a race condition if the
    // event subscribers take a while to finish, and two different users attempt
    // to create a stage directory at around the same time. If an error occurs
    // while the event is being processed, the stage is marked as available.
    // @see ::dispatch()
    // We specifically generate a random 32-character alphanumeric name in order
    // to guarantee that the stage ID won't start with -, which could cause it
    // to be interpreted as an option if it's used as a command-line argument.
    // (For example, \Drupal\Component\Utility\Crypt::randomBytesBase64() would
    // be vulnerable to this; the stage ID needs to be unique, but not
    // cryptographically so.)
    $id = (new Random())->name(32);
    // Re-acquire the tempstore to ensure that the lock is written by whoever is
    // actually logged in (or not) right now, since it's possible that the stage
    // was instantiated (i.e., __construct() was called) by a different session,
    // which would result in the lock having the wrong owner and the stage not
    // being claimable by whoever is actually creating it.
    $this->tempStore = $this->tempStoreFactory->get('package_manager_stage');
    // For the lock value, we use both the stage's class and its type in order
    // to prevent a stage from being manipulated by two different classes during
    // a single life cycle.
    $this->tempStore->set(static::TEMPSTORE_LOCK_KEY, [
      $id,
      static::class,
      $this->getType(),
    ]);
    $this->claim($id);

    $active_dir = $this->pathFactory->create($this->pathLocator->getProjectRoot());
    $stage_dir = $this->pathFactory->create($this->getStageDirectory());

    $excluded_paths = $this->getPathsToExclude();
    $event = new PreCreateEvent($this, $excluded_paths);
    // If an error occurs and we won't be able to create the stage, mark it as
    // available.
    $this->dispatch($event, [$this, 'markAsAvailable']);

    try {
      $this->beginner->begin($active_dir, $stage_dir, $excluded_paths, NULL, $timeout);
    }
    catch (\Throwable $error) {
      $this->destroy();
      $this->rethrowAsStageException($error);
    }
    $this->dispatch(new PostCreateEvent($this));
    return $id;
  }

  /**
   * Wraps an exception in a StageException and re-throws it.
   *
   * @param \Throwable $e
   *   The throwable to wrap.
   */
  private function rethrowAsStageException(\Throwable $e): never {
    throw new StageException($this, $e->getMessage(), $e->getCode(), $e);
  }

  /**
   * Adds or updates packages in the stage directory.
   *
   * @param string[] $runtime
   *   The packages to add as regular top-level dependencies, in the form
   *   'vendor/name' or 'vendor/name:version'.
   * @param string[] $dev
   *   (optional) The packages to add as dev dependencies, in the form
   *   'vendor/name' or 'vendor/name:version'. Defaults to an empty array.
   * @param int|null $timeout
   *   (optional) How long to allow the Composer operation to run before timing
   *   out, in seconds, or NULL to never time out. Defaults to 300 seconds.
   *
   * @throws \Drupal\package_manager\Exception\StageException
   *   Thrown if the Composer operation cannot be started, or if an error occurs
   *   during the operation. In the latter situation, the stage directory will
   *   be destroyed.
   */
  public function require(array $runtime, array $dev = [], ?int $timeout = 300): void {
    $this->checkOwnership();

    $this->dispatch(new PreRequireEvent($this, $runtime, $dev));

    // A helper function to execute a command in the stage, destroying it if an
    // exception occurs in the middle of a Composer operation.
    $do_stage = function (array $command) use ($timeout): void {
      $active_dir = $this->pathFactory->create($this->pathLocator->getProjectRoot());
      $stage_dir = $this->pathFactory->create($this->getStageDirectory());

      try {
        $this->stager->stage($command, $active_dir, $stage_dir, NULL, $timeout);
      }
      catch (\Throwable $e) {
        // If the caught exception isn't InvalidArgumentException or
        // PreconditionException, a Composer operation was actually attempted,
        // and failed. The stage should therefore be destroyed, because it's in
        // an indeterminate and possibly unrecoverable state.
        if (!$e instanceof InvalidArgumentException && !$e instanceof PreconditionException) {
          $this->destroy();
        }
        $this->rethrowAsStageException($e);
      }
    };

    // Change the runtime and dev requirements as needed, but don't update
    // the installed packages yet.
    if ($runtime) {
      self::validateRequirements($runtime);
      $command = array_merge(['require', '--no-update'], $runtime);
      $do_stage($command);
    }
    if ($dev) {
      self::validateRequirements($dev);
      $command = array_merge(['require', '--dev', '--no-update'], $dev);
      $do_stage($command);
    }

    // If constraints were changed, update those packages.
    if ($runtime || $dev) {
      $command = array_merge(['update', '--with-all-dependencies', '--optimize-autoloader'], $runtime, $dev);
      $do_stage($command);
    }
    $this->dispatch(new PostRequireEvent($this, $runtime, $dev));
  }

  /**
   * Applies staged changes to the active directory.
   *
   * After the staged changes are applied, the current request should be
   * terminated as soon as possible. This is because the code loaded into the
   * PHP runtime may no longer match the code that is physically present in the
   * active code base, which means that the current request is running in an
   * unreliable, inconsistent environment. In the next request,
   * ::postApply() should be called as early as possible after Drupal is
   * fully bootstrapped, to rebuild the service container, flush caches, and
   * dispatch the post-apply event.
   *
   * @param int|null $timeout
   *   (optional) How long to allow the file copying operation to run before
   *   timing out, in seconds, or NULL to never time out. Defaults to 600
   *   seconds.
   *
   * @throws \Drupal\package_manager\Exception\ApplyFailedException
   *   Thrown if there is an error calling Composer Stager, which may indicate
   *   a failed commit operation.
   */
  public function apply(?int $timeout = 600): void {
    $this->checkOwnership();

    $active_dir = $this->pathFactory->create($this->pathLocator->getProjectRoot());
    $stage_dir = $this->pathFactory->create($this->getStageDirectory());

    $excluded_paths = $this->getPathsToExclude();
    $event = new PreApplyEvent($this, $excluded_paths);
    // If an error occurs while dispatching the events, ensure that ::destroy()
    // doesn't think we're in the middle of applying the staged changes to the
    // active directory.
    $this->tempStore->set(self::TEMPSTORE_APPLY_TIME_KEY, $this->time->getRequestTime());
    $this->dispatch($event, $this->setNotApplying(...));

    // Create a marker file so that we can tell later on if the commit failed.
    $this->failureMarker->write($this, $this->getFailureMarkerMessage());

    try {
      $this->committer->commit($stage_dir, $active_dir, $excluded_paths, NULL, $timeout);
    }
    catch (InvalidArgumentException | PreconditionException $e) {
      // The commit operation has not started yet, so we can clear the failure
      // marker and release the flag that says we're applying.
      $this->setNotApplying();
      $this->failureMarker->clear();
      $this->rethrowAsStageException($e);
    }
    catch (\Throwable $throwable) {
      // The commit operation may have failed midway through, and the site code
      // is in an indeterminate state. Release the flag which says we're still
      // applying, because in this situation, the site owner should probably
      // restore everything from a backup.
      $this->setNotApplying();
      // Update the marker file with the information from the throwable.
      $this->failureMarker->write($this, $this->getFailureMarkerMessage(), $throwable);
      throw new ApplyFailedException($this, $this->failureMarker->getMessage(), $throwable->getCode(), $throwable);
    }
    $this->failureMarker->clear();
    $this->setMetadata(self::TEMPSTORE_CHANGES_APPLIED, TRUE);
  }

  /**
   * Returns a closure that marks this stage as no longer being applied.
   */
  private function setNotApplying(): void {
    $this->tempStore->delete(self::TEMPSTORE_APPLY_TIME_KEY);
  }

  /**
   * Performs post-apply tasks.
   *
   * This should be called as soon as possible after ::apply(), in a new
   * request.
   *
   * @see ::apply()
   */
  public function postApply(): void {
    $this->checkOwnership();

    if ($this->tempStore->get(self::TEMPSTORE_APPLY_TIME_KEY) === $this->time->getRequestTime()) {
      $this->logger?->warning('Post-apply tasks are running in the same request during which staged changes were applied to the active code base. This can result in unpredictable behavior.');
    }
    // Rebuild the container and clear all caches, to ensure that new services
    // are picked up.
    drupal_flush_all_caches();
    // Refresh the event dispatcher so that new or changed event subscribers
    // will be called. The other services we depend on are either stateless or
    // unlikely to call newly added code during the current request.
    $this->eventDispatcher = \Drupal::service('event_dispatcher');

    $release_apply = $this->setNotApplying(...);
    $this->dispatch(new PostApplyEvent($this), $release_apply);
    $release_apply();
  }

  /**
   * Deletes the stage directory.
   *
   * @param bool $force
   *   (optional) If TRUE, the stage directory will be destroyed even if it is
   *   not owned by the current user or session. Defaults to FALSE.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $message
   *   (optional) A message about why the stage was destroyed.
   *
   * @throws \Drupal\package_manager\Exception\StageException
   *   If the staged changes are being applied to the active directory.
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function destroy(bool $force = FALSE, ?TranslatableMarkup $message = NULL): void {
    if (!$force) {
      $this->checkOwnership();
    }
    if ($this->isApplying()) {
      throw new StageException($this, 'Cannot destroy the stage directory while it is being applied to the active directory.');
    }

    // If the stage directory exists, queue it to be automatically cleaned up
    // later by a queue (which may or may not happen during cron).
    // @see \Drupal\package_manager\Plugin\QueueWorker\Cleaner
    if ($this->stageDirectoryExists()) {
      $this->queueFactory->get('package_manager_cleanup')
        ->createItem($this->getStageDirectory());
    }

    $this->storeDestroyInfo($force, $message);
    $this->markAsAvailable();
  }

  /**
   * Marks the stage as available.
   */
  protected function markAsAvailable(): void {
    $this->tempStore->delete(static::TEMPSTORE_METADATA_KEY);
    $this->tempStore->delete(static::TEMPSTORE_LOCK_KEY);
    $this->tempStore->delete(self::TEMPSTORE_STAGING_ROOT_KEY);
    $this->lock = NULL;
  }

  /**
   * Dispatches an event and handles any errors that it collects.
   *
   * @param \Drupal\package_manager\Event\StageEvent $event
   *   The event object.
   * @param callable|null $on_error
   *   (optional) A callback function to call if an error occurs, before any
   *   exceptions are thrown.
   *
   * @throws \Drupal\package_manager\Exception\StageEventException
   *   If the event collects any validation errors.
   */
  protected function dispatch(StageEvent $event, ?callable $on_error = NULL): void {
    try {
      $this->eventDispatcher->dispatch($event);

      if ($event instanceof PreOperationStageEvent) {
        if ($event->getResults()) {
          $error = new StageEventException($event);
        }
      }
    }
    catch (\Throwable $error) {
      $error = new StageEventException($event, $error->getMessage(), $error->getCode(), $error);
    }

    if (isset($error)) {
      // Ensure the error is logged for post-mortem diagnostics.
      if ($this->logger) {
        Error::logException($this->logger, $error);
      }
      if ($on_error) {
        $on_error();
      }
      throw $error;
    }
  }

  /**
   * Attempts to claim the stage.
   *
   * Once a stage has been created, no operations can be performed on it until
   * it is claimed. This is to ensure that stage operations across multiple
   * requests are being done by the same code, running under the same user or
   * session that created the stage in the first place. To claim a stage, the
   * calling code must provide the unique identifier that was generated when the
   * stage was created.
   *
   * The stage is claimed when it is created, so external code does NOT need to
   * call this method after calling ::create() in the same request.
   *
   * @param string $unique_id
   *   The unique ID that was returned by ::create().
   *
   * @return $this
   *
   * @throws \Drupal\package_manager\Exception\StageOwnershipException
   *   If the stage cannot be claimed. This can happen if the current user or
   *   session did not originally create the stage, if $unique_id doesn't match
   *   the unique ID that was generated when the stage was created, or the
   *   current class is not the same one that was used to create the stage.
   *
   * @see ::create()
   */
  final public function claim(string $unique_id): self {
    $this->failureMarker->assertNotExists();

    if ($this->isAvailable()) {
      // phpcs:disable DrupalPractice.General.ExceptionT.ExceptionT
      // @see https://www.drupal.org/project/auto_updates/issues/3338651
      throw new StageException($this, $this->computeDestroyMessage(
        $unique_id,
        $this->t('Cannot claim the stage because no stage has been created.')
      )->render());
    }

    $stored_lock = $this->tempStore->getIfOwner(static::TEMPSTORE_LOCK_KEY);
    if (!$stored_lock) {
      throw new StageOwnershipException($this, $this->computeDestroyMessage(
        $unique_id,
        $this->t('Cannot claim the stage because it is not owned by the current user or session.')
      )->render());
    }

    if ($stored_lock === [$unique_id, static::class, $this->getType()]) {
      $this->lock = $stored_lock;
      return $this;
    }

    throw new StageOwnershipException($this, $this->computeDestroyMessage(
      $unique_id,
      $this->t('Cannot claim the stage because the current lock does not match the stored lock.')
    )->render());
    // phpcs:enable DrupalPractice.General.ExceptionT.ExceptionT
  }

  /**
   * Returns the specific destroy message for the ID.
   *
   * @param string $unique_id
   *   The unique ID that was returned by ::create().
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $fallback_message
   *   A fallback message, in case no specific message was stored.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A message describing why the stage with the given ID was destroyed, or if
   *   no message was associated with that destroyed stage, the provided
   *   fallback message.
   */
  private function computeDestroyMessage(string $unique_id, TranslatableMarkup $fallback_message): TranslatableMarkup {
    // Check to see if we have a specific message about a stage with a
    // specific ID that was given.
    return $this->tempStore->get(self::TEMPSTORE_DESTROYED_STAGES_INFO_PREFIX . $unique_id) ?? $fallback_message;
  }

  /**
   * Validates the ownership of stage directory.
   *
   * The stage is considered under valid ownership if it was created by current
   * user or session, using the current class.
   *
   * @throws \LogicException
   *   If ::claim() has not been previously called.
   * @throws \Drupal\package_manager\Exception\StageOwnershipException
   *   If the current user or session does not own the stage directory, or it
   *   was created by a different class.
   */
  final protected function checkOwnership(): void {
    if (empty($this->lock)) {
      throw new \LogicException('Stage must be claimed before performing any operations on it.');
    }

    $stored_lock = $this->tempStore->getIfOwner(static::TEMPSTORE_LOCK_KEY);
    if ($stored_lock !== $this->lock) {
      throw new StageOwnershipException($this, 'Stage is not owned by the current user or session.');
    }
  }

  /**
   * Returns the path of the directory where changes should be staged.
   *
   * @return string
   *   The absolute path of the directory where changes should be staged.
   *
   * @throws \LogicException
   *   If this method is called before the stage has been created or claimed.
   */
  public function getStageDirectory(): string {
    if (!$this->lock) {
      throw new \LogicException(__METHOD__ . '() cannot be called because the stage has not been created or claimed.');
    }
    return $this->getStagingRoot() . DIRECTORY_SEPARATOR . $this->lock[0];
  }

  /**
   * Returns the directory where stage directories will be created.
   *
   * @return string
   *   The absolute path of the directory containing the stage directories
   *   managed by this class.
   */
  private function getStagingRoot(): string {
    // Since the stage root can depend on site settings, store it so that
    // things won't break if the settings change during this stage's life
    // cycle.
    $dir = $this->tempStore->get(self::TEMPSTORE_STAGING_ROOT_KEY);
    if (empty($dir)) {
      $dir = $this->pathLocator->getStagingRoot();
      $this->tempStore->set(self::TEMPSTORE_STAGING_ROOT_KEY, $dir);
    }
    return $dir;
  }

  /**
   * Determines if the stage directory exists.
   *
   * @return bool
   *   TRUE if the directory exists, otherwise FALSE.
   */
  public function stageDirectoryExists(): bool {
    try {
      return is_dir($this->getStageDirectory());
    }
    catch (\LogicException) {
      return FALSE;
    }
  }

  /**
   * Checks if staged changes are being applied to the active directory.
   *
   * @return bool
   *   TRUE if the staged changes are being applied to the active directory, and
   *   it has been less than an hour since that operation began. If more than an
   *   hour has elapsed since the changes started to be applied, FALSE is
   *   returned even if the stage internally thinks that changes are still being
   *   applied.
   *
   * @see ::apply()
   */
  final public function isApplying(): bool {
    $apply_time = $this->tempStore->get(self::TEMPSTORE_APPLY_TIME_KEY);
    return isset($apply_time) && $this->time->getRequestTime() - $apply_time < 3600;
  }

  /**
   * Returns the failure marker message.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated failure marker message.
   */
  protected function getFailureMarkerMessage(): TranslatableMarkup {
    return $this->t('Staged changes failed to apply, and the site is in an indeterminate state. It is strongly recommended to restore the code and database from a backup.');
  }

  /**
   * Validates a set of package names.
   *
   * Package names are considered invalid if they look like Drupal project
   * names. The only exceptions to this are platform requirements, like `php`,
   * `composer`, or `ext-json`, which are legitimate to Composer.
   *
   * @param string[] $requirements
   *   A set of package names (with or without version constraints), as passed
   *   to ::require().
   *
   * @throws \InvalidArgumentException
   *   Thrown if any of the given package names fail basic validation.
   */
  protected static function validateRequirements(array $requirements): void {
    $version_parser = new VersionParser();

    foreach ($requirements as $requirement) {
      $parts = explode(':', $requirement, 2);
      $name = $parts[0];

      if (!preg_match(self::COMPOSER_PLATFORM_PACKAGE_REGEX, $name) && !preg_match(self::COMPOSER_PACKAGE_REGEX, $name)) {
        throw new \InvalidArgumentException("Invalid package name '$name'.");
      }
      if (count($parts) > 1) {
        $version_parser->parseConstraints($parts[1]);
      }
    }
  }

  /**
   * Stores information about the stage when it is destroyed.
   *
   * @param bool $force
   *   Whether the stage was force destroyed.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $message
   *   A message about why the stage was destroyed or null.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function storeDestroyInfo(bool $force, ?TranslatableMarkup $message): void {
    if (!$message) {
      if ($this->tempStore->get(self::TEMPSTORE_CHANGES_APPLIED) === TRUE) {
        $message = $this->t('This operation has already been applied.');
      }
      else {
        if ($force) {
          $message = $this->t('This operation was canceled by another user.');
        }
        else {
          $message = $this->t('This operation was already canceled.');
        }
      }
    }
    [$id] = $this->tempStore->get(static::TEMPSTORE_LOCK_KEY);
    $this->tempStore->set(self::TEMPSTORE_DESTROYED_STAGES_INFO_PREFIX . $id, $message);
  }

}
