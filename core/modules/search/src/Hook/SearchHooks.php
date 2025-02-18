<?php

namespace Drupal\search\Hook;

use Drupal\block\BlockInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for search.
 */
class SearchHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.search':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Search module provides the ability to set up search pages based on plugins provided by other modules. In Drupal core, there are two page-type plugins: the Content page type provides keyword searching for content managed by the Node module, and the Users page type provides keyword searching for registered users. Contributed modules may provide other page-type plugins. For more information, see the <a href=":search-module">online documentation for the Search module</a>.', [':search-module' => 'https://www.drupal.org/documentation/modules/search']) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Configuring search pages') . '</dt>';
        $output .= '<dd>' . $this->t('To configure search pages, visit the <a href=":search-settings">Search pages page</a>. In the Search pages section, you can add a new search page, edit the configuration of existing search pages, enable and disable search pages, and choose the default search page. Each enabled search page has a URL path starting with <em>search</em>, and each will appear as a tab or local task link on the <a href=":search-url">search page</a>; you can configure the text that is shown in the tab. In addition, some search page plugins have additional settings that you can configure for each search page.', [
          ':search-settings' => Url::fromRoute('entity.search_page.collection')->toString(),
          ':search-url' => Url::fromRoute('search.view')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Managing the search index') . '</dt>';
        $output .= '<dd>' . $this->t('Some search page plugins, such as the core Content search page, index searchable text using the Drupal core search index, and will not work unless content is indexed. Indexing is done during <em>cron</em> runs, so it requires a <a href=":cron">cron maintenance task</a> to be set up. There are also several settings affecting indexing that can be configured on the <a href=":search-settings">Search pages page</a>: the number of items to index per cron run, the minimum word length to index, and how to handle Chinese, Japanese, and Korean characters.', [
          ':cron' => Url::fromRoute('system.cron_settings')->toString(),
          ':search-settings' => Url::fromRoute('entity.search_page.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dd>' . $this->t('Modules providing search page plugins generally ensure that content-related actions on your site (creating, editing, or deleting content and comments) automatically cause affected content items to be marked for indexing or reindexing at the next cron run. When content is marked for reindexing, the previous content remains in the index until cron runs, at which time it is replaced by the new content. However, there are some actions related to the structure of your site that do not cause affected content to be marked for reindexing. Examples of structure-related actions that affect content include deleting or editing taxonomy terms, installing or uninstalling modules that add text to content (such as Taxonomy, Comment, and field-providing modules), and modifying the fields or display parameters of your content types. If you take one of these actions and you want to ensure that the search index is updated to reflect your changed site structure, you can mark all content for reindexing by clicking the "Re-index site" button on the <a href=":search-settings">Search pages page</a>. If you have a lot of content on your site, it may take several cron runs for the content to be reindexed.', [
          ':search-settings' => Url::fromRoute('entity.search_page.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Displaying the Search block') . '</dt>';
        $output .= '<dd>' . $this->t('The Search module includes a block, which can be enabled and configured on the <a href=":blocks">Block layout page</a>, if you have the Block module installed; the default block title is Search, and it is the Search form block in the Forms category, if you wish to add another instance. The block is available to users with the <a href=":search_permission">Use search</a> permission, and it performs a search using the configured default search page.', [
          ':blocks' => \Drupal::moduleHandler()->moduleExists('block') ? Url::fromRoute('block.admin_display')->toString() : '#',
          ':search_permission' => Url::fromRoute('user.admin_permissions.module', [
            'modules' => 'search',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Searching your site') . '</dt>';
        $output .= '<dd>' . $this->t('Users with <a href=":search_permission">Use search</a> permission can use the Search block and <a href=":search">Search page</a>. Users with the <a href=":node_permission">View published content</a> permission can use configured search pages of type <em>Content</em> to search for content containing exact keywords; in addition, users with <a href=":search_permission">Use advanced search</a> permission can use more complex search filtering. Users with the <a href=":user_permission">View user information</a> permission can use configured search pages of type <em>Users</em> to search for active users containing the keyword anywhere in the username, and users with the <a href=":user_permission">Administer users</a> permission can search for active and blocked users, by email address or username keyword.', [
          ':search' => Url::fromRoute('search.view')->toString(),
          ':search_permission' => Url::fromRoute('user.admin_permissions.module', [
            'modules' => 'search',
          ])->toString(),
          ':node_permission' => Url::fromRoute('user.admin_permissions.module', [
            'modules' => 'node',
          ])->toString(),
          ':user_permission' => Url::fromRoute('user.admin_permissions.module', [
            'modules' => 'user',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Extending the Search module') . '</dt>';
        $output .= '<dd>' . $this->t('By default, the Search module only supports exact keyword matching in content searches. You can modify this behavior by installing a language-specific stemming module for your language (such as <a href=":porterstemmer_url">Porter Stemmer</a> for American English), which allows words such as walk, walking, and walked to be matched in the Search module. Another approach is to use a third-party search technology with stemming or partial word matching features built in, such as <a href=":solr_url">Apache Solr</a> or <a href=":sphinx_url">Sphinx</a>. There are also contributed modules that provide additional search pages. These and other <a href=":contrib-search">search-related contributed modules</a> can be downloaded by visiting Drupal.org.', [
          ':contrib-search' => 'https://www.drupal.org/project/project_module?f[2]=im_vid_3%3A105',
          ':porterstemmer_url' => 'https://www.drupal.org/project/porterstemmer',
          ':solr_url' => 'https://www.drupal.org/project/apachesolr',
          ':sphinx_url' => 'https://www.drupal.org/project/sphinx',
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'search_result' => [
        'variables' => [
          'result' => NULL,
          'plugin_id' => NULL,
        ],
        'file' => 'search.pages.inc',
      ],
    ];
  }

  /**
   * Implements hook_cron().
   *
   * Fires updateIndex() in the plugins for all indexable active search pages,
   * and cleans up dirty words.
   */
  #[Hook('cron')]
  public function cron(): void {
    /** @var \Drupal\search\SearchPageRepositoryInterface $search_page_repository */
    $search_page_repository = \Drupal::service('search.search_page_repository');
    foreach ($search_page_repository->getIndexableSearchPages() as $entity) {
      $entity->getPlugin()->updateIndex();
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for the search_block_form form.
   *
   * Since the exposed form is a GET form, we don't want it to send the form
   * tokens. However, you cannot make this happen in the form builder function
   * itself, because the tokens are added to the form after the builder function
   * is called. So, we have to do it in a form_alter.
   *
   * @see \Drupal\search\Form\SearchBlockForm
   */
  #[Hook('form_search_block_form_alter')]
  public function formSearchBlockFormAlter(&$form, FormStateInterface $form_state) : void {
    $form['form_build_id']['#access'] = FALSE;
    $form['form_token']['#access'] = FALSE;
    $form['form_id']['#access'] = FALSE;
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for block entities.
   */
  #[Hook('block_presave')]
  public function blockPresave(BlockInterface $block): void {
    // @see \Drupal\search\Plugin\Block\SearchBlock
    if ($block->getPluginId() === 'search_form_block') {
      $settings = $block->get('settings');
      if ($settings['page_id'] === '') {
        @trigger_error('Saving a search block with an empty page ID is deprecated in drupal:11.1.0 and removed in drupal:12.0.0. To use the default search page, use NULL. See https://www.drupal.org/node/3463132', E_USER_DEPRECATED);
        $settings['page_id'] = NULL;
        $block->set('settings', $settings);
      }
    }
  }

}
