<?php

namespace Drupal\views_test_data\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsAccess;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Tests a static access plugin.
 */
#[ViewsAccess(
  id: 'test_static',
  title: new TranslatableMarkup('Static test access plugin'),
  help: new TranslatableMarkup('Provides a static test access plugin.'),
)]
class StaticTest extends AccessPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['access'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return !empty($this->options['access']);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    if (!empty($this->options['access'])) {
      $route->setRequirement('_access', 'TRUE');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [
      'content' => ['StaticTest'],
    ];
  }

}
