<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\argument_default\User.
 */

namespace Drupal\user\Plugin\views\argument_default;

use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;

/**
 * Default argument plugin to extract a user from request.
 *
 * @ViewsArgumentDefault(
 *   id = "user",
 *   title = @Translation("User ID from route context")
 * )
 */
class User extends ArgumentDefaultPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['user'] = array('default' => '', 'bool' => TRUE, 'translatable' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['user'] = array(
      '#type' => 'checkbox',
      '#title' => t('Also look for a node and use the node author'),
      '#default_value' => $this->options['user'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {

    // If there is a user object in the current route.
    if ($this->view->getRequest()->attributes->has('user')) {
      $user = $this->view->getRequest()->attributes->get('user');
      if ($user instanceof UserInterface) {
        return $user->id();
      }
    }

    // If option to use node author; and node in current route.
    if (!empty($this->options['user']) && $this->view->getRequest()->attributes->has('node')) {
      $node = $this->view->getRequest()->attributes->get('node');
      if ($node instanceof NodeInterface) {
        return $node->getOwnerId();
      }
    }

    // If the current page is a view that takes uid as an argument.
    $view = views_get_page_view();

    if ($view && isset($view->argument['uid'])) {
      return $view->argument['uid']->argument;
    }
  }

}
