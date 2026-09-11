<?php

namespace Drupal\Core\Update;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Update\Attribute\MarkFutureUpdateEquivalent;
use Drupal\Core\Utility\Error;

/**
 * Drupal database update API.
 *
 * This class provides methods for executing database updates within the
 * context of a Drupal installation.
 */
class DatabaseUpdate {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  public function __construct(
    protected UpdateHookRegistry $updateRegistry,
    protected ModuleHandlerInterface $moduleHandler,
    protected MessengerInterface $messenger,
    protected LoggerChannelFactoryInterface $loggerChannelFactory,
  ) {}

  /**
   * Returns whether the minimum schema requirement has been satisfied.
   *
   * @return array<string, array{'title': \Drupal\Core\StringTranslation\TranslatableMarkup, 'value': mixed, description: \Drupal\Core\StringTranslation\TranslatableMarkup, 'severity': \Drupal\Core\Extension\Requirement\RequirementSeverity}>
   *   An array of requirements, in the same format as is returned by
   *   hook_requirements().
   *
   * @see hook_requirements()
   * @see getRequirements()
   */
  public function systemSchemaRequirements(): array {
    $requirements = [];

    $system_schema = $this->updateRegistry->getInstalledVersion('system');

    $requirements['minimum schema']['title'] = 'Minimum schema version';
    if ($system_schema >= \Drupal::CORE_MINIMUM_SCHEMA_VERSION) {
      $requirements['minimum schema'] += [
        'value' => 'The installed schema version meets the minimum.',
        'description' => 'Schema version: ' . $system_schema,
      ];
    }
    else {
      $requirements['minimum schema'] += [
        'value' => 'The installed schema version does not meet the minimum.',
        'severity' => RequirementSeverity::Error,
        'description' => 'Your system schema version is ' . $system_schema . '. Updating directly from a schema version prior to 8000 is not supported. You must upgrade your site to Drupal 8 (or later), see https://www.drupal.org/docs/upgrading-drupal.',
      ];
    }

    return $requirements;
  }

  /**
   * Checks update requirements and reports errors and (optionally) warnings.
   *
   * @return array<string, array{'title': \Drupal\Core\StringTranslation\TranslatableMarkup, 'value': mixed, description: \Drupal\Core\StringTranslation\TranslatableMarkup, 'severity': \Drupal\Core\Extension\Requirement\RequirementSeverity}>
   *   An array of requirements, in the same format as is returned by
   *   hook_requirements().
   *
   * @see hook_requirements()
   */
  public function getRequirements(): array {
    // Because this is one of the earliest points in the update process,
    // detect and fix missing schema versions for modules here to ensure
    // it runs on all update code paths.
    $this->fixMissingSchema();

    // Check requirements of all loaded modules.
    $requirements = array_merge(
      $this->moduleHandler->invokeAll('requirements', ['update']),
      $this->moduleHandler->invokeAll('update_requirements'),
    );
    $this->moduleHandler->alter('requirements', $requirements);
    $this->moduleHandler->alter('update_requirements', $requirements);
    $requirements += $this->systemSchemaRequirements();
    return $requirements;
  }

  /**
   * Detect and fix 'missing' schema information with the helper.
   *
   * Repairs the case where a module has no schema version recorded.
   * This has to be done prior to updates being run, otherwise the update
   * system would detect and attempt to run all historical updates for a
   * module.
   *
   * @todo remove in a major version after
   *   https://www.drupal.org/project/drupal/issues/3130037 has been fixed.
   *
   * @internal
   */
  public function fixMissingSchema(): void {
    $versions = $this->updateRegistry->getAllInstalledVersions();
    $enabled_modules = $this->moduleHandler->getModuleList();

    foreach (array_keys($enabled_modules) as $module) {
      // All modules should have a recorded schema version, but when they don't,
      // detect and fix the problem.
      if (!isset($versions[$module])) {
        // Ensure the .install file is loaded.
        $this->moduleHandler->loadInclude($module, 'install');
        $all_updates = $this->updateRegistry->getAvailableUpdates($module);
        // If the schema version of a module hasn't been recorded, we cannot
        // know the actual schema version a module is at, because no updates
        // will ever have been run on the site and it was not set correctly when
        // the module was installed, so instead set it to the same as the last
        // update. This means that updates will proceed again the next time the
        // module is updated and a new update is added. Updates added in between
        // the module being installed and the schema version being fixed here
        // (if any have been added) will never be run, but we have no way to
        // identify which updates these are.
        if ($all_updates) {
          $last_update = max($all_updates);
        }
        else {
          $last_update = \Drupal::CORE_MINIMUM_SCHEMA_VERSION;
        }
        // If the module implements hook_update_last_removed() use the value of
        // that if it's higher than the schema versions found so far. The hook
        // is procedural only and is not collected by the hook system, so call
        // the function directly.
        $function = $module . '_update_last_removed';
        if (function_exists($function) && ($last_removed = $function())) {
          $last_update = max($last_update, $last_removed);
        }
        $this->updateRegistry->setInstalledVersion($module, $last_update);
        $args = ['%module' => $module, '%last_update_hook' => $module . '_update_' . $last_update . '()'];
        $this->messenger->addWarning($this->t('Schema information for module %module was missing from the database. You should manually review the module updates and your database to check if any updates have been skipped up to, and including, %last_update_hook.', $args));
        $this->loggerChannelFactory->get('update')->warning('Schema information for module %module was missing from the database. You should manually review the module updates and your database to check if any updates have been skipped up to, and including, %last_update_hook.', $args);
      }
    }
  }

  /**
   * Performs one update and stores the results to display on the results page.
   *
   * If an update function completes successfully, it should return a message
   * as a string indicating success, for example:
   * @code
   * return t('New index added successfully.');
   * @endcode
   *
   * Alternatively, it may return nothing. In that case, no message
   * will be displayed at all.
   *
   * If it fails for whatever reason, it should throw an instance of
   * Drupal\Core\Utility\UpdateException with an appropriate error message, for
   * example:
   * @code
   * use Drupal\Core\Utility\UpdateException;
   * throw new UpdateException('Description of what went wrong');
   * @endcode
   *
   * If an exception is thrown, the current update and all updates that depend
   * on it will be aborted. The schema version will not be updated in this case,
   * and all the aborted updates will continue to appear on update.php as
   * updates that have not yet been run.
   *
   * If an update function needs to be re-run as part of a batch process, it
   * should accept the $sandbox array by reference as its first parameter and
   * set the #finished property to the percentage completed that it is, as a
   * fraction of 1.
   *
   * @param string $module
   *   The module whose update will be run.
   * @param int $number
   *   The update number to run.
   * @param array $dependency_map
   *   An array whose keys are the names of all update functions that will be
   *   performed during this batch process, and whose values are arrays of other
   *   update functions that each one depends on.
   * @param array $context
   *   The batch context array.
   *
   * @see callback_batch_operation()
   */
  public function doOne(string $module, int $number, array $dependency_map, array &$context): void {
    $function = $module . '_update_' . $number;

    // If this update was aborted in a previous step, or has a dependency that
    // was aborted in a previous step, go no further.
    if (!empty($context['results']['#abort']) && array_intersect($context['results']['#abort'], array_merge($dependency_map, [$function]))) {
      return;
    }

    $ret = [];
    $equivalent_update = $this->updateRegistry->getEquivalentUpdate($module, $number);
    if ($equivalent_update instanceof EquivalentUpdate) {
      $ret['results']['query'] = $equivalent_update->toSkipMessage();
      $ret['results']['success'] = TRUE;
      $context['sandbox']['#finished'] = TRUE;
    }
    elseif (function_exists($function)) {
      try {
        // Check for the MarkFutureUpdateEquivalent attribute on the update
        // function to register a future equivalent update.
        $attributes = (new \ReflectionFunction($function))->getAttributes(MarkFutureUpdateEquivalent::class);
        foreach ($attributes as $attribute) {
          /** @var \Drupal\Core\Update\Attribute\MarkFutureUpdateEquivalent $instance */
          $instance = $attribute->newInstance();
          $this->updateRegistry->markFutureUpdateEquivalent($instance->futureUpdateNumber, $instance->futureVersionString, $module, $number);
        }

        $ret['results']['query'] = $function($context['sandbox']);
        $ret['results']['success'] = TRUE;
      }
      // @todo We may want to do different error handling for different
      // exception types, but for now we'll just log the exception and
      // return the message for printing.
      // @see https://www.drupal.org/node/2564311
      catch (\Exception $e) {
        $variables = Error::decodeException($e);
        $this->loggerChannelFactory->get('update')->error(Error::DEFAULT_ERROR_MESSAGE, $variables);

        unset($variables['backtrace'], $variables['exception'], $variables['severity_level']);
        // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
        $ret['#abort'] = ['success' => FALSE, 'query' => $this->t(Error::DEFAULT_ERROR_MESSAGE, $variables)];
      }
    }

    if (isset($context['sandbox']['#finished'])) {
      $context['finished'] = $context['sandbox']['#finished'];
      unset($context['sandbox']['#finished']);
    }

    if (!isset($context['results'][$module])) {
      $context['results'][$module] = [];
    }
    if (!isset($context['results'][$module][$number])) {
      $context['results'][$module][$number] = [];
    }
    $context['results'][$module][$number] = array_merge($context['results'][$module][$number], $ret);

    if (!empty($ret['#abort'])) {
      // Record this function in the list of updates that were aborted.
      $context['results']['#abort'][] = $function;
    }

    // Record the schema update if it was completed successfully.
    if ($context['finished'] == 1 && empty($ret['#abort'])) {
      $this->updateRegistry->setInstalledVersion($module, $number);
    }

    $context['message'] = $this->t('Updating @module', ['@module' => $module]);
  }

}
