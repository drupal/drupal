<?php

namespace Drupal\rest\Entity;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\rest\RestResourceConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Calculates rest resource config dependencies.
 */
class ConfigDependencies implements ContainerInjectionInterface {

  /**
   * The serialization format providers, keyed by format.
   *
   * @var string[]
   */
  protected $formatProviders;

  /**
   * The authentication providers, keyed by ID.
   *
   * @var string[]
   */
  protected $authProviders;

  /**
   * Creates a new ConfigDependencies instance.
   *
   * @param string[] $format_providers
   *   The serialization format providers, keyed by format.
   * @param string[] $auth_providers
   *   The authentication providers, keyed by ID.
   */
  public function __construct(array $format_providers, array $auth_providers) {
    $this->formatProviders = $format_providers;
    $this->authProviders = $auth_providers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->getParameter('serializer.format_providers'),
      $container->getParameter('authentication_providers')
    );
  }

  /**
   * Calculates dependencies of a specific rest resource configuration.
   *
   * @param \Drupal\rest\RestResourceConfigInterface $rest_config
   *   The rest configuration.
   *
   * @return string[][]
   *   Dependencies keyed by dependency type.
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::calculateDependencies()
   */
  public function calculateDependencies(RestResourceConfigInterface $rest_config) {
    $granularity = $rest_config->get('granularity');
    if ($granularity === RestResourceConfigInterface::METHOD_GRANULARITY) {
      return $this->calculateDependenciesForMethodGranularity($rest_config);
    }
    else {
      throw new \InvalidArgumentException("A different granularity then 'method' is not supported yet.");
      // @todo Add resource-level granularity support in https://www.drupal.org/node/2721595.
    }
  }

  /**
   * Calculates dependencies of a specific rest resource configuration.
   *
   * @param \Drupal\rest\RestResourceConfigInterface $rest_config
   *   The rest configuration.
   *
   * @return string[][]
   *   Dependencies keyed by dependency type.
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::calculateDependencies()
   */
  protected function calculateDependenciesForMethodGranularity(RestResourceConfigInterface $rest_config) {
    // The dependency lists for authentication providers and formats
    // generated on container build.
    $dependencies = [];
    foreach (array_keys($rest_config->get('configuration')) as $request_method) {
      // Add dependencies based on the supported authentication providers.
      foreach ($rest_config->getAuthenticationProviders($request_method) as $auth) {
        if (isset($this->authProviders[$auth])) {
          $module_name = $this->authProviders[$auth];
          $dependencies['module'][] = $module_name;
        }
      }
      // Add dependencies based on the supported authentication formats.
      foreach ($rest_config->getFormats($request_method) as $format) {
        if (isset($this->formatProviders[$format])) {
          $module_name = $this->formatProviders[$format];
          $dependencies['module'][] = $module_name;
        }
      }
    }

    return $dependencies;
  }

  /**
   * Informs the entity that entities it depends on will be deleted.
   *
   * @param \Drupal\rest\RestResourceConfigInterface $rest_config
   *   The rest configuration.
   * @param array $dependencies
   *   An array of dependencies that will be deleted keyed by dependency type.
   *   Dependency types are, for example, entity, module and theme.
   *
   * @return bool
   *   TRUE if the entity has been changed as a result, FALSE if not.
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::onDependencyRemoval()
   */
  public function onDependencyRemoval(RestResourceConfigInterface $rest_config, array $dependencies) {
    $granularity = $rest_config->get('granularity');
    if ($granularity === RestResourceConfigInterface::METHOD_GRANULARITY) {
      return $this->onDependencyRemovalForMethodGranularity($rest_config, $dependencies);
    }
    else {
      throw new \InvalidArgumentException("A different granularity then 'method' is not supported yet.");
      // @todo Add resource-level granularity support in https://www.drupal.org/node/2721595.
    }
  }

  /**
   * Informs the entity that entities it depends on will be deleted.
   *
   * @param \Drupal\rest\RestResourceConfigInterface $rest_config
   *   The rest configuration.
   * @param array $dependencies
   *   An array of dependencies that will be deleted keyed by dependency type.
   *   Dependency types are, for example, entity, module and theme.
   *
   * @return bool
   *   TRUE if the entity has been changed as a result, FALSE if not.
   */
  protected function onDependencyRemovalForMethodGranularity(RestResourceConfigInterface $rest_config, array $dependencies) {
    $changed = FALSE;
    // Only module-related dependencies can be fixed. All other types of
    // dependencies cannot, because they were not generated based on supported
    // authentication providers or formats.
    if (isset($dependencies['module'])) {
      // Try to fix dependencies.
      $removed_auth = array_keys(array_intersect($this->authProviders, $dependencies['module']));
      $removed_formats = array_keys(array_intersect($this->formatProviders, $dependencies['module']));
      $configuration_before = $configuration = $rest_config->get('configuration');
      if (!empty($removed_auth) || !empty($removed_formats)) {
        // Try to fix dependency problems by removing affected
        // authentication providers and formats.
        foreach (array_keys($rest_config->get('configuration')) as $request_method) {
          foreach ($removed_formats as $format) {
            if (in_array($format, $rest_config->getFormats($request_method))) {
              $configuration[$request_method]['supported_formats'] = array_diff($configuration[$request_method]['supported_formats'], $removed_formats);
            }
          }
          foreach ($removed_auth as $auth) {
            if (in_array($auth, $rest_config->getAuthenticationProviders($request_method))) {
              $configuration[$request_method]['supported_auth'] = array_diff($configuration[$request_method]['supported_auth'], $removed_auth);
            }
          }
          if (empty($configuration[$request_method]['supported_auth'])) {
            // Remove the key if there are no more authentication providers
            // supported by this request method.
            unset($configuration[$request_method]['supported_auth']);
          }
          if (empty($configuration[$request_method]['supported_formats'])) {
            // Remove the key if there are no more formats supported by this
            // request method.
            unset($configuration[$request_method]['supported_formats']);
          }
          if (empty($configuration[$request_method])) {
            // Remove the request method altogether if it no longer has any
            // supported authentication providers or formats.
            unset($configuration[$request_method]);
          }
        }
      }
      if (!empty($configuration_before != $configuration)) {
        $rest_config->set('configuration', $configuration);
        // Only mark the dependencies problems as fixed if there is any
        // configuration left.
        $changed = TRUE;
      }
    }
    // If the dependency problems are not marked as fixed at this point they
    // should be related to the resource plugin and the config entity should
    // be deleted.
    return $changed;
  }

}
