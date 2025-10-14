<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Utility\Xss;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Help hook implementation for node.
 */
class NodeHelpHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    // Remind site administrators about the {node_access} table being flagged
    // for rebuild. We don't need to issue the message on the confirm form, or
    // while the rebuild is being processed.
    if ($route_name != 'node.configure_rebuild_confirm' && $route_name != 'system.batch_page.html' && $route_name != 'help.page.node' && $route_name != 'help.main' && \Drupal::currentUser()->hasPermission('rebuild node access permissions') && node_access_needs_rebuild()) {
      if ($route_name == 'system.status') {
        $message = $this->t('The content access permissions need to be rebuilt.');
      }
      else {
        $message = $this->t('The content access permissions need to be rebuilt. <a href=":node_access_rebuild">Rebuild permissions</a>.', [
          ':node_access_rebuild' => Url::fromRoute('node.configure_rebuild_confirm')->toString(),
        ]);
      }
      \Drupal::messenger()->addError($message);
    }
    switch ($route_name) {
      case 'help.page.node':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Node module manages the creation, editing, deletion, settings, and display of the main site content. Content items managed by the Node module are typically displayed as pages on your site, and include a title, some meta-data (author, creation time, content type, etc.), and optional fields containing text or other data (fields are managed by the <a href=":field">Field module</a>). For more information, see the <a href=":node">online documentation for the Node module</a>.', [
          ':node' => 'https://www.drupal.org/docs/core-modules-and-themes/core-modules/node-module',
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Creating content') . '</dt>';
        $output .= '<dd>' . $this->t('When new content is created, the Node module records basic information about the content, including the author, date of creation, and the <a href=":content-type">Content type</a>. It also manages the <em>publishing options</em>, which define whether or not the content is published, promoted to the front page of the site, and/or sticky at the top of content lists. Default settings can be configured for each <a href=":content-type">type of content</a> on your site.', [
          ':content-type' => Url::fromRoute('entity.node_type.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Creating custom content types') . '</dt>';
        $output .= '<dd>' . $this->t('The Node module gives users with the <em>Administer content types</em> permission the ability to <a href=":content-new">create new content types</a> in addition to the default ones already configured. Creating custom content types gives you the flexibility to add <a href=":field">fields</a> and configure default settings that suit the differing needs of various site content.', [
          ':content-new' => Url::fromRoute('node.type_add')->toString(),
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Administering content') . '</dt>';
        $output .= '<dd>' . $this->t('The <a href=":content">Content</a> page lists your content, allowing you add new content, filter, edit or delete existing content, or perform bulk operations on existing content.', [':content' => Url::fromRoute('system.admin_content')->toString()]) . '</dd>';
        $output .= '<dt>' . $this->t('Creating revisions') . '</dt>';
        $output .= '<dd>' . $this->t('The Node module also enables you to create multiple versions of any content, and revert to older versions using the <em>Revision information</em> settings.') . '</dd>';
        $output .= '<dt>' . $this->t('User permissions') . '</dt>';
        $output .= '<dd>' . $this->t('The Node module makes a number of permissions available for each content type, which can be set by role on the <a href=":permissions">permissions page</a>.', [
          ':permissions' => Url::fromRoute('user.admin_permissions.module', [
            'modules' => 'node',
          ])->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'node.type_add':
        return '<p>' . $this->t('Individual content types can have different fields, behaviors, and permissions assigned to them.') . '</p>';

      case 'entity.entity_form_display.node.default':
      case 'entity.entity_form_display.node.form_mode':
        $type = $route_match->getParameter('node_type');
        return '<p>' . $this->t('Content items can be edited using different form modes. Here, you can define which fields are shown and hidden when %type content is edited in each form mode, and define how the field form widgets are displayed in each form mode.', ['%type' => $type->label()]) . '</p>';

      case 'entity.entity_view_display.node.default':
      case 'entity.entity_view_display.node.view_mode':
        $type = $route_match->getParameter('node_type');
        return '<p>' . $this->t('Content items can be displayed using different view modes: Teaser, Full content, Print, RSS, etc. <em>Teaser</em> is a short format that is typically used in lists of multiple content items. <em>Full content</em> is typically used when the content is displayed on its own page.') . '</p><p>' . $this->t('Here, you can define which fields are shown and hidden when %type content is displayed in each view mode, and define how the fields are displayed in each view mode.', ['%type' => $type->label()]) . '</p>';

      case 'entity.node.version_history':
        return '<p>' . $this->t('Revisions allow you to track differences between multiple versions of your content, and revert to older versions.') . '</p>';

      case 'entity.node.edit_form':
        $node = $route_match->getParameter('node');
        $type = NodeType::load($node->getType());
        $help = $type->getHelp();
        return !empty($help) ? Xss::filterAdmin($help) : '';

      case 'node.add':
        $type = $route_match->getParameter('node_type');
        $help = $type->getHelp();
        return !empty($help) ? Xss::filterAdmin($help) : '';
    }
    return NULL;
  }

}
