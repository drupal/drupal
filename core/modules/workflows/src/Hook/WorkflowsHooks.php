<?php

namespace Drupal\workflows\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for workflows.
 */
class WorkflowsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.workflows':
        $content_moderation_url = NULL;
        if (\Drupal::moduleHandler()->moduleExists('content_moderation')) {
          $content_moderation_url = Url::fromRoute('help.page', ['name' => 'content_moderation'])->toString();
        }
        $output = '<h2>' . $this->t('About') . '</h2>';
        if ($content_moderation_url) {
          $output .= '<p>' . $this->t('The Workflows module provides an API and an interface to create workflows with transitions between different states (for example publication or user status). These have to be provided by other modules such as the <a href=":moderation">Content Moderation module</a>. For more information, see the <a href=":workflow">online documentation for the Workflows module</a>.', [
            ':moderation' => $content_moderation_url,
            ':workflow' => 'https://www.drupal.org/documentation/modules/workflows',
          ]) . '</p>';
        }
        else {
          $output .= '<p>' . $this->t('The Workflows module provides an API and an interface to create workflows with transitions between different states (for example publication or user status). These have to be provided by other modules such as the Content Moderation module. For more information, see the <a href=":workflow">online documentation for the Workflows module</a>.', [':workflow' => 'https://www.drupal.org/documentation/modules/workflows']) . '</p>';
        }
        $output .= '<h3>' . $this->t('Uses') . '</h3>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Adding workflows') . '</dt>';
        if ($content_moderation_url) {
          $output .= '<dd>' . $this->t('You can <em>only</em> add workflows on the <a href=":workflows">Workflows page</a>, after you have installed a module that leverages the API such as the <a href=":moderation">Content Moderation module</a>.', [
            ':moderation' => $content_moderation_url,
            ':workflows' => Url::fromRoute('entity.workflow.collection')->toString(),
          ]) . '</dd>';
        }
        else {
          $output .= '<dd>' . $this->t('You can <em>only</em> add workflows on the <a href=":workflows">Workflows page</a>, after you have installed a module that leverages the API such as the Content Moderation module.', [':workflow' => 'https://www.drupal.org/documentation/modules/workflows']) . '</dd>';
        }
        $output .= '<dt>' . $this->t('Adding states') . '<dt>';
        $output .= '<dd>' . $this->t('A workflow requires at least two states. States can be added when you add or edit a workflow on the <a href=":workflows">Workflows page</a>.', [
          ':workflows' => Url::fromRoute('entity.workflow.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Adding transitions') . '</dt>';
        $output .= '<dd>' . $this->t('A transition defines in which state an item can be save as next. It has one destination state, but can have several states <em>from</em> which the transition can be applied. Transitions can be added when you add or edit a workflow on the <a href=":workflows">Workflows page</a>.', [
          ':workflows' => Url::fromRoute('entity.workflow.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Configuring workflows further') . '</dt>';
        $output .= '<dd>' . $this->t('Depending on the installed workflow type, additional configuration can be available in the edit form of a workflow.') . '</dd>';
        $output .= '<dl>';
        return $output;
    }
    return NULL;
  }

}
