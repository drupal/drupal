<?php

declare(strict_types=1);

namespace Drupal\views\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\Routing\Route;

/**
 * Checks access to view routes based on argument configuration.
 *
 * This is primarily used to avoid displaying links to views that will return a
 * 403 response.
 */
class ValidArgumentAccessCheck implements AccessInterface {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ViewExecutableFactory $executableFactory,
    protected readonly AccountInterface $currentUser,
  ) {}

  /**
   * Checks whether a NULL argument will return a 403 response.
   *
   * @param array $options
   *   The argument handler options.
   *
   * @return bool
   *   Whether a NULL argument will return a 403 response.
   */
  public static function requiresValue(array $options): bool {
    return ($options['default_action'] ?? '') === 'access denied';
  }

  /**
   * Checks whether an invalid argument will return a 403 response.
   *
   * @param array $options
   *   The argument handler options.
   *
   * @return bool
   *   Whether an invalid argument will return a 403 response.
   */
  public static function requiresValidation(array $options): bool {
    if (!isset($options['validate']['type']) || $options['validate']['type'] === 'none') {
      return FALSE;
    }
    return ($options['validate']['fail'] ?? '') === 'access denied';
  }

  /**
   * Gets the arguments to check from the route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to get the arguments from.
   *
   * @return array
   *   The arguments to check.
   */
  protected function getArgumentsToCheck(Route $route): array {
    if (!$route->hasRequirement('_views_argument_access')) {
      return [];
    }

    $argument_list = $route->getRequirement('_views_argument_access');
    if ($argument_list === '') {
      return [];
    }

    return explode(',', $argument_list);
  }

  /**
   * Checks access result for a view based on argument configuration.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parameterized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param string $view_id
   *   The view ID.
   * @param string $display_id
   *   The display ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, string $view_id, string $display_id): AccessResultInterface {
    $argument_map = $route->getOption('_view_argument_map') ?? [];
    if ($argument_map === []) {
      return AccessResult::allowed();
    }

    $arguments_to_check = $this->getArgumentsToCheck($route);
    if ($arguments_to_check === []) {
      return AccessResult::allowed();
    }

    $view = $this->loadViewExecutable($view_id, $display_id);
    $cacheability = CacheableMetadata::createFromObject($view);

    $argument_counter = 0;
    foreach ($view->getDisplay()->getHandlers('argument') as $name => $argument) {
      assert($argument instanceof ArgumentPluginBase);
      $argument_id = 'arg_' . $argument_counter++;

      if (!in_array($name, $arguments_to_check, TRUE)) {
        continue;
      }

      $parameter_name = $argument_map[$argument_id] ?? FALSE;
      if ($parameter_name === FALSE) {
        continue;
      }

      $argument_value = $route_match->getRawParameter($parameter_name);
      if (!$this->argumentValueIsValid($argument, $argument_value, $account, $cacheability)) {
        return AccessResult::forbidden()->addCacheableDependency($cacheability);
      }
    }

    return AccessResult::allowed()->addCacheableDependency($cacheability);
  }

  /**
   * Checks whether a views argument value is valid.
   *
   * @param \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument
   *   The views argument plugin.
   * @param mixed $value
   *   The views argument value.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheability
   *   The cacheable metadata to add to.
   *
   * @return bool
   *   Whether the views argument value is valid.
   */
  protected function argumentValueIsValid(ArgumentPluginBase $argument, mixed $value, AccountInterface $account, RefinableCacheableDependencyInterface $cacheability): bool {
    $argument_options = $argument->options ?? [];
    $requires_value = static::requiresValue($argument_options);
    $requires_validation = static::requiresValidation($argument_options);
    if (!$requires_value && !$requires_validation) {
      return TRUE;
    }

    if ($requires_value && $value === NULL) {
      return FALSE;
    }

    if ($argument->broken() || $argument->isException($value)) {
      return TRUE;
    }

    // Validators can only check the current user.
    if ($requires_validation && $account->id() != $this->currentUser->id()) {
      $cacheability->mergeCacheMaxAge(0);
      return TRUE;
    }

    if ($requires_validation && !$argument->getPlugin('argument_validator')->validateArgument($value, $cacheability)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Loads a view executable with a display.
   *
   * @param string $view_id
   *   The view ID.
   * @param string $display_id
   *   The display ID.
   *
   * @return \Drupal\views\ViewExecutable
   *   The view executable with the display set.
   */
  protected function loadViewExecutable(string $view_id, string $display_id): ViewExecutable {
    $view = $this->entityTypeManager->getStorage('view')->load($view_id);
    assert($view instanceof ViewEntityInterface);
    $executable = $this->executableFactory->get($view);
    assert($executable instanceof ViewExecutable);
    $executable->setDisplay($display_id);
    return $executable;
  }

}
