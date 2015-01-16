<?php

/**
 * @file
 * Contains \Drupal\Core\Form\ConfigFormBaseTrait.
 */

namespace Drupal\Core\Form;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides access to configuration for forms.
 *
 * This trait provides a config() method that returns override free and mutable
 * config objects if the configuration name is in the array returned by the
 * getEditableConfigNames() implementation.
 *
 * Forms that present configuration to the user have to take care not to save
 * configuration overrides to the stored configuration since overrides are often
 * environment specific. Default values of form elements should be obtained from
 * override free configuration objects. However, if a form reacts to
 * configuration in any way, for example sends an email to the system.site:mail
 * address, then it is important that the value comes from a configuration
 * object with overrides. Therefore, override free and editable configuration
 * objects are limited to those listed by the getEditableConfigNames() method.
 */
trait ConfigFormBaseTrait {

  /**
   * Retrieves a configuration object.
   *
   * Objects that use the trait need to implement the
   * \Drupal\Core\Form\ConfigFormBaseTrait::getEditableConfigNames() to declare
   * which configuration objects this method returns override free and mutable.
   * This ensures that overrides do not pollute saved configuration.
   *
   * @param string $name
   *   The name of the configuration object to retrieve. The name corresponds to
   *   a configuration file. For @code \Drupal::config('book.admin') @endcode,
   *   the config object returned will contain the contents of book.admin
   *   configuration file.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   A configuration object.
   */
  protected function config($name) {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    if (method_exists($this, 'configFactory')) {
      $config_factory = $this->configFactory();
    }
    elseif (property_exists($this, 'configFactory')) {
      $config_factory = $this->configFactory;
    }
    if (!isset($config_factory) || !($config_factory instanceof ConfigFactoryInterface)) {
      throw new \LogicException('No config factory available for ConfigFormBaseTrait');
    }
    if (in_array($name, $this->getEditableConfigNames())) {
      // Get a mutable object from the factory.
      $config = $config_factory->getEditable($name);
    }
    else {
      $config = $config_factory->get($name);
    }
    return $config;
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  abstract protected function getEditableConfigNames();

}
