<?php

namespace Drupal\rest\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for rest.
 */
class RestHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.rest':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The RESTful Web Services module provides a framework for exposing REST resources on your site. It provides support for content entity types such as the main site content, comments, content blocks, taxonomy terms, and user accounts, etc. (see the <a href=":field">Field module help page</a> for more information about entities). REST support for content items of the Node module is installed by default, and support for other types of content entities can be enabled. Other modules may add support for other types of REST resources. For more information, see the <a href=":rest">online documentation for the RESTful Web Services module</a>.', [
          ':rest' => 'https://www.drupal.org/documentation/modules/rest',
          ':field' => \Drupal::moduleHandler()->moduleExists('field') ? Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString() : '#',
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Installing supporting modules') . '</dt>';
        $output .= '<dd>' . $this->t('In order to use REST on a website, you need to install modules that provide serialization and authentication services. You can use the Core module <a href=":serialization">serialization</a> for serialization and <a href=":basic_auth">HTTP Basic Authentication</a> for authentication, or install a contributed or custom module.', [
          ':serialization' => \Drupal::moduleHandler()->moduleExists('serialization') ? Url::fromRoute('help.page', [
            'name' => 'serialization',
          ])->toString() : 'https://www.drupal.org/docs/8/core/modules/serialization/overview',
          ':basic_auth' => \Drupal::moduleHandler()->moduleExists('basic_auth') ? Url::fromRoute('help.page', [
            'name' => 'basic_auth',
          ])->toString() : 'https://www.drupal.org/docs/8/core/modules/basic_auth/overview',
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Enabling REST support for an entity type') . '</dt>';
        $output .= '<dd>' . $this->t('REST support for content types (provided by the <a href=":node">Node</a> module) is enabled by default. To enable support for other content entity types, you can use a <a href=":config" target="blank">process based on configuration editing</a> or the contributed <a href=":restui">REST UI module</a>.', [
          ':node' => \Drupal::moduleHandler()->moduleExists('node') ? Url::fromRoute('help.page', [
            'name' => 'node',
          ])->toString() : '#',
          ':config' => 'https://www.drupal.org/documentation/modules/rest',
          ':restui' => 'https://www.drupal.org/project/restui',
        ]) . '</dd>';
        $output .= '<dd>' . $this->t('You will also need to grant anonymous users permission to perform each of the REST operations you want to be available, and set up authentication properly to authorize web requests.') . '</dd>';
        $output .= '<dt>' . $this->t('General') . '</dt>';
        $output .= '<dd>' . $this->t('The <a href=":rest-docs">RESTful Web Services</a> and <a href=":jsonapi-docs">JSON:API</a> modules serve similar purposes. <a href=":comparison">Read the comparison of the RESTFul Web Services and JSON:API modules</a> to determine the best choice for your site.', [
          ':rest-docs' => 'https://www.drupal.org/docs/8/core/modules/rest',
          ':jsonapi-docs' => 'https://www.drupal.org/docs/8/modules/json-api',
          ':comparison' => 'https://www.drupal.org/docs/8/modules/jsonapi/jsonapi-vs-cores-rest-module',
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

}
