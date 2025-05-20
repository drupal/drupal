<?php

/**
 * @file
 * Documentation related to Package Manager.
 */

/**
 * @defgroup package_manager_architecture Package Manager architecture
 * @{
 *
 * @section sec_overview Overview
 * Package Manager is an API-only module which provides the scaffolding and
 * functionality needed for Drupal to make changes to its own running code base
 * via Composer. It doesn't have a user interface.
 *
 * @see https://getcomposer.org/
 * @see https://github.com/php-tuf/composer-stager
 *
 * @section sec_concepts Concepts
 * At the center of Package Manager is the concept of a stage directory. A
 * stage directory is a complete copy of the active Drupal code base, created
 * in a temporary directory that isn't accessible over the web. The stage
 * directory doesn't have any site-specific assets like settings.php, uploaded
 * files, or SQLite databases.
 *
 * Only one stage directory can exist at any given time, and it is "owned" by
 * the user or session that originally created it. Only the owner can perform
 * operations on the stage directory, and only using the same class (i.e.,
 * \Drupal\package_manager\StageBase or a subclass) they used to create it.
 *
 * Package Manager can run Composer commands in the stage directory to require
 * or update packages in it, and then copy those changes back into the live,
 * running code base (which is referred to as the "active directory"). The
 * stage directory can then be safely deleted. Four distinct operations:
 * create, require, apply, and destroy. They comprise the "stage life cycle."
 *
 * Package Manager's \Drupal\package_manager\StageBase controls the stage life
 * cycle and is an abstract class that must be subclassed. Most of the time,
 * there should be little need to heavily customize a StageBase subclass;
 * custom code should generally use the event system to interact with the stage.
 *
 * @see sec_stage_events Stage API: Events
 * Events are dispatched before and after each operation in the stage life
 * cycle. There are two types of events: pre-operation and post-operation.
 * Pre-operation event subscribers can analyze the state of the stage directory,
 * or the system at large, and flag errors if they detect any problems. If
 * errors are flagged, the operation is prevented. Therefore, pre-operation
 * events are helpful to ensure that the stage directory is in a valid state.
 * Post-operation events are simple triggers allowing custom code to react when
 * an operation is successfully completed. They cannot flag errors to block
 * stage operations (although they can use the core messenger and logging
 * systems as needed).
 *
 * All stage events extend \Drupal\package_manager\Event\StageEvent, and all
 * pre-operation events extend
 * \Drupal\package_manager\Event\PreOperationStageEvent. All events have a
 * $stage property which allows access to the stage object itself.
 *
 * The stage dispatches the following events during its life cycle:
 *
 * - \Drupal\package_manager\Event\PreCreateEvent
 *   Dispatched before the stage directory is created. At this point, the
 *   stage will have recorded which user or session owns it, so another stage
 *   directory cannot be created until the current one is destroyed. If
 *   subscribers flag errors during this event, the stage will release its
 *   ownership. This is the earliest possible time to detect problems that might
 *   prevent the stage from completing its life cycle successfully. This event
 *   is dispatched only once during the stage life cycle.
 *   @see sec_stage_exceptions
 *
 * - \Drupal\package_manager\Event\PostCreateEvent
 *   Dispatched after the stage directory has been created, which means that the
 *   running Drupal code base has been copied into a separate, temporary
 *   location. This event is dispatched only once during the stage life cycle.
 *
 * - \Drupal\package_manager\Event\PreRequireEvent
 *   Dispatched before one or more Composer packages are required into the
 *   stage directory. This event may be dispatched multiple times during the
 *   stage life cycle, and receives a list of the packages which are about to
 *   be required into the stage directory. The list of packages CANNOT be
 *   altered by subscribers.
 *
 * - \Drupal\package_manager\Event\PostRequireEvent
 *   Dispatched after one or more Composer packages have been added to the
 *   stage directory. This event may be dispatched multiple times during the
 *   stage life cycle, and receives a list of the packages which were required
 *   into the stage directory. (Note that this is a list of packages which
 *   were specifically *asked for*, not the full list of packages and
 *   dependencies that was actually installed.)
 *
 * - \Drupal\package_manager\Event\PreApplyEvent
 *   Dispatched before changes in the stage directory (i.e., new and/or updated
 *   packages) are copied to the active directory. This is the final opportunity
 *   for event subscribers to flag errors before the active directory is
 *   modified, because once that has happened, the changes cannot be undone.
 *   This event may be dispatched multiple times during the stage life cycle.
 *   Note that this event is NOT dispatched when the sandbox manager is
 *   operating in direct-write mode.
 *
 * - \Drupal\package_manager\Event\PostApplyEvent
 *   Dispatched after changes in the stage directory have been copied to the
 *   active directory. It should only be used for cleaning up after other
 *   operations that happened during the stage life cycle. For example, a
 *   PostCreateEvent subscriber might have set a state value which is no longer
 *   needed once the stage has been applied to the active directory -- in such a
 *   case, a PostApplyEvent subscriber should delete that value.
 *   `drupal_flush_all_caches()` is called just before this event is dispatched,
 *   so subscribers shouldn't need to flush any caches or rebuild the service
 *   container. This event may be dispatched multiple times during the stage
 *   life cycle, and should *never* be used for schema changes (i.e., operations
 *   that should happen in `hook_update_N()` or a post-update function).
 *
 * Since the apply events are not dispatched in direct-write mode, event
 * subscribers that want to prevent a sandbox from moving through its life cycle
 * in direct-write mode should do it by subscribing to PreCreateEvent or
 * StatusCheckEvent.
 *
 * @section sec_stage_api Stage API: Public methods
 * The public API of any stage consists of the following methods:
 *
 * - \Drupal\package_manager\StageBase::create()
 *   Creates the stage directory, records ownership, and dispatches pre- and
 *   post-create events. Returns a unique token which calling code must use to
 *   verify stage ownership before performing operations on the stage
 *   directory in subsequent requests (when the stage directory is created,
 *   its ownership is automatically verified for the duration of the current
 *   request). See \Drupal\package_manager\StageBase::claim() for more
 *   information.
 *
 * - \Drupal\package_manager\StageBase::require()
 *   Adds and/or updates packages in the stage directory and dispatches pre-
 *   and post-require events. The stage must be claimed by its owner to call
 *   this method.
 *
 * - \Drupal\package_manager\StageBase::apply()
 *   Copies changes from the stage directory into the active directory, and
 *   dispatches the pre-apply event. The stage must be claimed by its owner to
 *   call this method.
 *
 * - \Drupal\package_manager\StageBase::postApply()
 *   Performs post-apply tasks after changes have been copied from the stage
 *   directory. This method should be called as soon as possible in a new
 *   request because the code on disk may no longer match what has been loaded
 *   into PHP's runtime memory. This method clears all Drupal caches, rebuilds
 *   the service container, and dispatches the post-apply event. The stage must
 *   be claimed by its owner to call this method.
 *
 * - \Drupal\package_manager\StageBase::destroy()
 *   Destroys the stage directory, and releases ownership. It is possible to
 *   destroy the stage without having claimed it first, but this shouldn't be
 *   done unless absolutely necessary.
 *
 * - \Drupal\package_manager\StageBase::stageDirectoryExists()
 *   Determines if the stage directory exists and returns a boolean accordingly.
 *   This allows validators to directly know if the stage directory exists
 *   without using \Drupal\package_manager\StageBase::getStageDirectory(), which
 *   throws an exception if the stage directory does not exist.
 *
 * - \Drupal\package_manager\StageBase::getStageDirectory()
 *   Returns the absolute path of the directory where changes should be staged.
 *   It throws an exception if the stage hasn't been created or claimed yet.
 *
 * - \Drupal\package_manager\StageBase::isApplying()
 *   Determines if the staged changes are being applied to the active directory.
 *   It will return FALSE if more than an hour has passed since the apply
 *   operation began.
 *
 * - \Drupal\package_manager\StageBase::isAvailable()
 *   Determines if a stage directory can be created.
 *
 * @section sec_stage_exceptions Stage life cycle exceptions
 * If problems occur during any point of the stage life cycle, a
 * \Drupal\package_manager\Exception\StageException is thrown. If problems are
 * detected during one of the "pre" operations, a subclass of that is thrown:
 * \Drupal\package_manager\Exception\StageEventException. This will contain
 * \Drupal\package_manager\ValidationResult objects.
 *
 * Package Manager does not catch or handle these exceptions: they provide a
 * framework for other modules to build user experiences for installing,
 * updating, and removing packages.
 *
 * @section sec_validators_status_checks API: Validators and status checks
 * Package Manager requires certain conditions in order to function properly.
 * Event subscribers which check such conditions should ensure that they run
 * before \Drupal\package_manager\Validator\BaseRequirementsFulfilledValidator,
 * by using a priority higher than BaseRequirementsFulfilledValidator::PRIORITY.
 * BaseRequirementsFulfilledValidator will stop event propagation if any errors
 * have been flagged by the subscribers that ran before it.
 *
 * The following base requirements are checked by Package Manager:
 *
 * - Package Manager has not been explicitly disabled in the current
 *   environment.
 * - The Composer executable is available.
 * - The detected version of Composer is supported.
 * - composer.json and composer.lock exist in the project root, and are valid
 *   according to the "composer validate" command.
 * - The stage directory is not a subdirectory of the active directory.
 * - There is enough free disk space to do stage operations.
 * - The Drupal site root and vendor directory are writable.
 * - The current site is not part of a multisite.
 * - The project root and stage directory don't contain any unsupported links.
 *   See https://github.com/php-tuf/composer-stager/tree/develop/src/Domain/Service/Precondition#symlinks
 *   for information about which types of symlinks are supported.
 *
 * Apart from base requirements, Package Manager also enforces certain
 * constraints at various points of the stage life cycle (typically
 * \Drupal\package_manager\Event\PreCreateEvent and/or
 * \Drupal\package_manager\Event\PreApplyEvent), to ensure that both the active
 * directory and stage directory are kept in a safe, consistent state:
 *
 * - If the composer.lock file is changed (e.g., by installing or updating a
 *   package) in the active directory after a stage directory has been created,
 *   Package Manager will refuse to make any further changes to the stage
 *   directory or apply the staged changes to the active directory.
 * - Composer plugins are able to perform arbitrary file system operations, and
 *   hence could perform actions that make it impossible for Package Manager to
 *   guarantee the Drupal site will continue to work correctly. For that reason,
 *   Package Manager will refuse to make any further changes if untrusted
 *   Composer plugins are installed or staged. If you know what you are doing,
 *   it is possible to trust additional Composer plugins by modifying
 *   package_manager.settings's "additional_trusted_composer_plugins" setting.
 * - The Drupal site must not have any pending database updates (i.e.,
 *   update.php needs to be run).
 * - Composer must use HTTPS to download packages and metadata (i.e., Composer's
 *   secure-http configuration option must be enabled). This is the default
 *   behavior.
 *
 * Package Manager also assumes certain things that it does not explicitly
 * enforce or check:
 *
 * - Only Composer operations should be performed on the stage directory. If
 *   other file operations were performed, any newly created files might not
 *   be copied back to the active site because of
 *   \Drupal\package_manager\PathExcluder\UnknownPathExcluder.
 *
 * Event subscribers which enforce these and other constraints are referred to
 * as validators.
 *
 * \Drupal\package_manager\Event\StatusCheckEvent may be dispatched at any time
 * to check the status of the Drupal site and whether Package Manager can
 * function properly. Package Manager does NOT dispatch this event on its own
 * because it doesn't have a UI; it is meant for modules that build on top of
 * Package Manager to ensure they will work correctly before they try to do any
 * stage operations, and present errors however they want in their own UIs.
 * Status checks can be dispatched irrespective of whether a stage directory has
 * actually been created.
 *
 * In general, validators should always listen to
 * \Drupal\package_manager\Event\StatusCheckEvent,
 * \Drupal\package_manager\Event\PreCreateEvent, and
 * \Drupal\package_manager\Event\PreApplyEvent. If they detect any errors,
 * they should call the event's ::addError() method to prevent the stage life
 * cycle from proceeding any further. If a validator encounters an exception,
 * it can use ::addErrorFromThrowable() instead of ::addError(). During status
 * checks, validators can call ::addWarning() for less severe problems --
 * warnings will NOT stop the stage life cycle. All three are convenience
 * methods for equivalent \Drupal\package_manager\ValidationResult constructors,
 * which can then be added to the event using ::addResult().
 *
 * @see \Drupal\package_manager\ValidationResult
 * @see \Drupal\package_manager\Event\SandboxValidationEvent::addError()
 * @see \Drupal\package_manager\Event\SandboxValidationEvent::addErrorFromThrowable()
 * @see \Drupal\package_manager\Event\StatusCheckEvent::addWarning()
 * @see \Drupal\package_manager\Event\SandboxValidationEvent::addResult()
 *
 * @section sec_excluded_paths Excluding files from stage operations
 * Certain files are never copied into the stage directory because they are
 * irrelevant to Composer or Package Manager. Examples include settings.php
 * and related files, public and private files, SQLite databases, and git
 * repositories. Custom code can subscribe to
 * Drupal\package_manager\Event\CollectPathsToExcludeEvent to flag paths which
 * should never be copied into the stage directory from the active directory or
 * vice versa.
 *
 * @see \Drupal\package_manager\Event\CollectPathsToExcludeEvent
 *
 * @section sec_services Useful services
 * The following services are especially useful to validators:
 * - \Drupal\package_manager\PathLocator looks up certain important paths in the
 *   active directory, such as the vendor directory, the project root and the
 *   web root.
 * - \Drupal\package_manager\ComposerInspector is a wrapper to interact with
 *   Composer at the command line and get information from it about the
 *   project's `composer.json`, which packages are installed, etc.
 *
 * @section sec_package_manager_failure_marker Package Manager failure marker
 * A file PACKAGE_MANAGER_FAILURE.yml is placed in the active directory while
 * staged code is copied back into it, and then removed after the copying is
 * finished. If this file exists, it means that the staged changes failed to be
 * applied to the active directory (for example: a file system error, or the
 * copying process was interrupted), and the site is therefore in an
 * indeterminate state. The only thing you can do is to restore the code and
 * database from a backup.
 * @see \Drupal\package_manager\FailureMarker
 *
 * @}
 */
