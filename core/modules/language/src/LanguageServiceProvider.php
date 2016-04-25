<?php

namespace Drupal\language;

use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the language_manager service to point to language's module one.
 */
class LanguageServiceProvider extends ServiceProviderBase {

  const CONFIG_PREFIX = 'language.entity.';

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // The following services are needed only on multilingual sites.
    if ($this->isMultilingual()) {
      $container->register('language_request_subscriber', 'Drupal\language\EventSubscriber\LanguageRequestSubscriber')
        ->addTag('event_subscriber')
        ->addArgument(new Reference('language_manager'))
        ->addArgument(new Reference('language_negotiator'))
        ->addArgument(new Reference('string_translation'))
        ->addArgument(new Reference('current_user'));

      $container->register('path_processor_language', 'Drupal\language\HttpKernel\PathProcessorLanguage')
        ->addTag('path_processor_inbound', array('priority' => 300))
        ->addTag('path_processor_outbound', array('priority' => 100))
        ->addArgument(new Reference('config.factory'))
        ->addArgument(new Reference('language_manager'))
        ->addArgument(new Reference('language_negotiator'))
        ->addArgument(new Reference('current_user'))
        ->addArgument(new Reference('language.config_subscriber'))
        ->addMethodCall('initConfigSubscriber');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('language_manager');
    $definition->setClass('Drupal\language\ConfigurableLanguageManager')
      ->addArgument(new Reference('config.factory'))
      ->addArgument(new Reference('module_handler'))
      ->addArgument(new Reference('language.config_factory_override'))
      ->addArgument(new Reference('request_stack'));
    if ($default_language_values = $this->getDefaultLanguageValues()) {
      $container->setParameter('language.default_values', $default_language_values);
    }

    // For monolingual sites, we explicitly set the default language for the
    // language config override service as there is no language negotiation.
    if (!$this->isMultilingual()) {
      $container->getDefinition('language.config_factory_override')
        ->addMethodCall('setLanguageFromDefault', array(new Reference('language.default')));
    }

  }

  /**
   * Checks whether the site is multilingual.
   *
   * @return bool
   *   TRUE if the site is multilingual, FALSE otherwise.
   */
  protected function isMultilingual() {
    // Assign the prefix to a local variable so it can be used in an anonymous
    // function.
    $prefix = static::CONFIG_PREFIX;
    // @todo Try to swap out for config.storage to take advantage of database
    //   and caching. This might prove difficult as this is called before the
    //   container has finished building.
    $config_storage = BootstrapConfigStorageFactory::get();
    $config_ids = array_filter($config_storage->listAll($prefix), function($config_id) use ($prefix) {
      return $config_id != $prefix . LanguageInterface::LANGCODE_NOT_SPECIFIED && $config_id != $prefix . LanguageInterface::LANGCODE_NOT_APPLICABLE;
    });
    return count($config_ids) > 1;
  }

  /**
   * Gets the default language values.
   *
   * @return array|bool
   *   Returns the default language values for the language configured in
   *   system.site:default_langcode if the corresponding configuration entity
   *   exists, otherwise FALSE.
   */
  protected function getDefaultLanguageValues() {
    $config_storage = BootstrapConfigStorageFactory::get();
    $system = $config_storage->read('system.site');
    $default_language = $config_storage->read(static::CONFIG_PREFIX . $system['default_langcode']);
    if (is_array($default_language)) {
      return $default_language;
    }
    return FALSE;
  }
}
