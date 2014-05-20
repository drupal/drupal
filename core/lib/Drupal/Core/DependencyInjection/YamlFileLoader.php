<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\YamlFileLoader.
 */

namespace Drupal\Core\DependencyInjection;

use Drupal\Component\Serialization\Yaml;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * YamlFileLoader loads YAML files service definitions.
 *
 * Drupal does not use Symfony's Config component, so this is a partial copy
 * of \Symfony\Component\DependencyInjection\Loader\YamlFileLoader class not
 * depending on the Config component.
 */
class YamlFileLoader {

  /**
   * Statically cached yaml files.
   *
   * Especially during tests, yaml files are re-parsed often.
   *
   * @var array
   */
  static protected $yaml = array();

  /**
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   */
  protected $container;

  public function __construct(ContainerBuilder $container) {
    $this->container = $container;
  }

  /**
   * Load a YAML file containing service definitions and kernel parameters.
   *
   * string $filename
   *   The name of the file to load.
   */
  public function load($filename) {
    if (!isset(static::$yaml[$filename])) {
      static::$yaml[$filename] = $this->loadFile($filename);
    }
    $content = static::$yaml[$filename];
    $content += array('parameters' => array(), 'services' => array());
    // parameters
    foreach ($content['parameters'] as $key => $value) {
      $this->container->setParameter($key, $this->resolveServices($value));
    }
    // services
    foreach ($content['services'] as $id => $service) {
      $this->parseDefinition($id, $service, $filename);
    }
  }

  /**
   * Parses a definition.
   *
   * Copied from \Symfony\Component\DependencyInjection\Loader\YamlFileLoader::parseDefinition().
   *
   * @param string $id
   *   The id of the service.
   * @param string|array $service
   *   Either a string starting with a @ meaning this service is an alias or
   *   the array defining the service.
   * @param string $filename
   *   The name of the file, only used in error messages.
   *
   * @throws \InvalidArgumentException When tags are invalid
   */
  protected function parseDefinition($id, $service, $filename) {
    if (is_string($service) && 0 === strpos($service, '@')) {
      $this->container->setAlias($id, substr($service, 1));
      return;
    }
    elseif (isset($service['alias'])) {
      $public = !array_key_exists('public', $service) || (Boolean) $service['public'];
      $this->container->setAlias($id, new Alias($service['alias'], $public));
      return;
    }
    if (isset($service['parent'])) {
      $definition = new DefinitionDecorator($service['parent']);
    }
    else {
      $definition = new Definition();
    }

    if (isset($service['class'])) {
      $definition->setClass($service['class']);
    }

    if (isset($service['scope'])) {
      $definition->setScope($service['scope']);
    }

    if (isset($service['synthetic'])) {
      $definition->setSynthetic($service['synthetic']);
    }

    if (isset($service['synchronized'])) {
      $definition->setSynchronized($service['synchronized']);
    }

    if (isset($service['public'])) {
      $definition->setPublic($service['public']);
    }

    if (isset($service['abstract'])) {
      $definition->setAbstract($service['abstract']);
    }

    if (isset($service['factory_class'])) {
      $definition->setFactoryClass($service['factory_class']);
    }

    if (isset($service['factory_method'])) {
      $definition->setFactoryMethod($service['factory_method']);
    }

    if (isset($service['factory_service'])) {
      $definition->setFactoryService($service['factory_service']);
    }

    if (isset($service['file'])) {
      $definition->setFile($service['file']);
    }

    if (isset($service['arguments'])) {
      $definition->setArguments($this->resolveServices($service['arguments']));
    }

    if (isset($service['properties'])) {
      $definition->setProperties($this->resolveServices($service['properties']));
    }

    if (isset($service['configurator'])) {
      if (is_string($service['configurator'])) {
        $definition->setConfigurator($service['configurator']);
      }
      else {
        $definition->setConfigurator(array($this->resolveServices($service['configurator'][0]), $service['configurator'][1]));
      }
    }

    if (isset($service['calls'])) {
      foreach ($service['calls'] as $call) {
        $args = isset($call[1]) ? $this->resolveServices($call[1]) : array();
        $definition->addMethodCall($call[0], $args);
      }
    }

    if (isset($service['tags'])) {
      if (!is_array($service['tags'])) {
        throw new \InvalidArgumentException(sprintf('Parameter "tags" must be an array for service "%s" in %s.', $id, $filename));
      }

      foreach ($service['tags'] as $tag) {
        if (!isset($tag['name'])) {
          throw new \InvalidArgumentException(sprintf('A "tags" entry is missing a "name" key for service "%s" in %s.', $id, $filename));
        }

        $name = $tag['name'];
        unset($tag['name']);

        foreach ($tag as $value) {
          if (!is_scalar($value)) {
            throw new \InvalidArgumentException(sprintf('A "tags" attribute must be of a scalar-type for service "%s", tag "%s" in %s.', $id, $name, $filename));
          }
        }

        $definition->addTag($name, $tag);
      }
    }

    $this->container->setDefinition($id, $definition);
  }

  /**
   * Loads a YAML file.
   *
   * @param string $filename
   *
   * @return array
   *   The file content.
   */
  protected function loadFile($filename) {
    return $this->validate(Yaml::decode(file_get_contents($filename)), $filename);
  }

  /**
   * Validates a YAML file.
   *
   * @param mixed $content
   *   The parsed YAML file.
   * @param string $filename
   *   The name of the file, only used for error messages.
   *
   * @return array
   *   The $content unchanged returned to allow for chaining this method.
   *
   * @throws \InvalidArgumentException When service file is not valid
   */
  protected function validate($content, $filename) {
    if (NULL === $content) {
      return $content;
    }

    if (!is_array($content)) {
      throw new \InvalidArgumentException(sprintf('The service file "%s" is not valid: it is not an array.', $filename));
    }
    if ($keys = array_diff_key($content, array('parameters' => TRUE, 'services' => TRUE))) {
      $invalid_keys = htmlspecialchars(implode(', ', $keys), ENT_QUOTES, 'UTF-8');
      throw new \InvalidArgumentException(sprintf('The service file "%s" is not valid: it contains invalid keys %s. Services have to be added under "services" and Parameters under "parameters".', $filename, $invalid_keys));
    }

    return $content;
  }

  /**
   * Resolves services.
   *
   * Copied from \Symfony\Component\DependencyInjection\Loader\YamlFileLoader::parseDefinition().
   *
   * @param mixed $value
   *   If a string, then it is either a plain string (for example a class
   *   name) or a reference to a service. If it's an array then it's a list of
   *   such strings.
   *
   * @return string|\Symfony\Component\DependencyInjection\Reference
   *   Either the string unchanged or the Reference object.
   */
  protected function resolveServices($value) {
    if (is_array($value)) {
      $value = array_map(array($this, 'resolveServices'), $value);
    }
    elseif (is_string($value) && 0 === strpos($value, '@')) {
      if (0 === strpos($value, '@?')) {
        $value = substr($value, 2);
        $invalidBehavior = ContainerInterface::IGNORE_ON_INVALID_REFERENCE;
      }
      else {
        $value = substr($value, 1);
        $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
      }

      if ('=' === substr($value, -1)) {
        $value = substr($value, 0, -1);
        $strict = FALSE;
      }
      else {
        $strict = TRUE;
      }

      $value = new Reference($value, $invalidBehavior, $strict);
    }

    return $value;
  }

}
