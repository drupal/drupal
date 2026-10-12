<?php

namespace Drupal\Core\Extension\Install;

/**
 * Methods for adding or altering tasks during the installation of Drupal.
 *
 * Classes implementing this must be in the Install\Tasks namespace.
 * For example src/Install/Tasks/ModuleNameTasks.php.
 * If you want to inject dependencies then also implement
 * ContainerInjectionInterface. Only core services can safely be injected
 * since this runs before Drupal is fully installed.
 */
interface TasksInterface {

  /**
   * Returns an array of tasks to be performed by an installation profile.
   *
   * Any tasks you define here will be run, in order, after the installer has
   * finished the site configuration step but before it has moved on to the
   * final import of languages and the end of the installation. This is invoked
   * by install_tasks(). You can have any number of custom tasks to perform
   * during this phase.
   *
   * Each task you define here corresponds to a callback method which you must
   * separately define and which is called when your task is run. This method
   * will receive the global installation state variable, $install_state, as
   * input, and has the opportunity to access or modify any of its settings. See
   * the install_state_defaults() function in the installer for the list of
   * $install_state settings used by Drupal core.
   *
   * At the end of your task method, you can indicate that you want the
   * installer to pause and display a page to the user by returning any themed
   * output that should be displayed on that page (but see below for tasks that
   * use the form API or batch API; the return values of these task methods are
   * handled differently). You should also use #title within the task
   * callback method to set a custom page title. For some tasks, however, you
   * may want to only do some processing and pass control to the next task
   * without ending the page request; to indicate this, do not send back
   * a return value from your task method at all. This can be used, for
   * example, by installation profiles that need to configure certain site
   * settings in the database without obtaining any input from the user.
   *
   * The task method is treated specially if it defines a form or requires
   * batch processing; in that case, you should return either the form API
   * definition or batch API array, as appropriate. See below for more
   * information on the 'type' key that you must define in the task definition
   * to inform the installer that your task falls into one of those two
   * categories. It is important to use these APIs directly, since the installer
   * may be run non-interactively (for example, via a command line script), all
   * in one cli request; in that case, the installer will automatically take
   * care of submitting forms and processing batches correctly for both types of
   * installations. You can inspect the $install_state['interactive'] boolean to
   * see whether or not the current installation is interactive, if you need
   * access to this information.
   *
   * Remember that a user installing Drupal interactively will be able to reload
   * an installation page multiple times, so you should use \Drupal::state() to
   * store any data that you may need later in the installation process. Any
   * temporary state must be removed using \Drupal::state()->delete() before
   * your last task has completed and control is handed back to the installer.
   *
   * @param array $install_state
   *   An array of information about the current installation state.
   *
   * @return array
   *   A keyed array of tasks the profile will perform during the final stage of
   *   the installation. Each key represents the name of a method (usually a
   *   method defined by this profile, although that is not strictly required)
   *   that is called when that task is run. The values are associative arrays
   *   containing the following key-value pairs (all of which are optional):
   *   - display_name: The human-readable name of the task. This will be
   *     displayed to the user while the installer is running, along with a list
   *     of other tasks that are being run. Leave this unset to prevent the task
   *     from appearing in the list.
   *   - display: This is a boolean which can be used to provide finer-grained
   *     control over whether or not the task will display. This is mostly
   *     useful for tasks that are intended to display only under certain
   *     conditions; for these tasks, you can set 'display_name' to the name
   *     that you want to display, but then use this boolean to hide the task
   *     only when certain conditions apply.
   *   - type: A string representing the type of task. This parameter has three
   *     possible values:
   *     - normal: (default) This indicates that the task will be treated as a
   *       regular callback method, which does its processing and optionally
   *       returns HTML output.
   *     - batch: This indicates that the task method will return a batch API
   *       definition suitable for batch_set() or an array of batch definitions
   *       suitable for consecutive batch_set() calls. The installer will then
   *       take care of automatically running the task via batch processing.
   *     - form: This indicates that the task method will return a standard
   *       form API definition (and separately define validation and submit
   *       handlers, as appropriate). The installer will then take care of
   *       automatically directing the user through the form submission process.
   *   - run: A constant representing the manner in which the task will be run.
   *     This parameter has three possible values:
   *     - INSTALL_TASK_RUN_IF_NOT_COMPLETED: (default) This indicates that the
   *       task will run once during the installation of the profile.
   *     - INSTALL_TASK_SKIP: This indicates that the task will not run during
   *       the current installation page request. It can be used to skip running
   *       an installation task when certain conditions are met, even though the
   *       task may still show on the list of installation tasks presented to
   *       the user.
   *     - INSTALL_TASK_RUN_IF_REACHED: This indicates that the task will run on
   *       each installation page request that reaches it. This is rarely
   *       necessary for an installation profile to use; it is primarily used by
   *       the Drupal installer for bootstrap-related tasks.
   *   - function: Normally this does not need to be set, but it can be used to
   *     force the installer to call a different method when the task is run
   *     (rather than the method whose name is given by the array key). This
   *     could be used, for example, to allow the same method to be called by
   *     two different tasks.
   *
   * @see install_state_defaults()
   * @see batch_set()
   * @see install_tasks()
   * @see Drupal\Core\Extension\Install\TaskAlterInterface
   */
  public function getTasks(array &$install_state): array;

  /**
   * Alter the full list of installation tasks.
   *
   * You can use this hook to change or replace any part of the Drupal
   * installation process that occurs after the installation profile is
   * selected.
   *
   * This method is invoked on the install profile in install_tasks().
   *
   * @param string[] $tasks
   *   An array of all available installation tasks, including those provided by
   *   Drupal core. You can modify this array to change or replace individual
   *   steps within the installation process.
   * @param array $install_state
   *   An array of information about the current installation state.
   *
   * @see install_tasks()
   * @see Drupal\Core\Extension\Install\TasksInterface
   */
  public function alter(array &$tasks, array $install_state): void;

}
