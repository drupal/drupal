<?php

namespace Drupal\comment\Plugin\views\row;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsRow;
use Drupal\views\Plugin\views\row\RssPluginBase;

/**
 * Plugin which formats the comments as RSS items.
 */
#[ViewsRow(
  id: "comment_rss",
  title: new TranslatableMarkup("Comment"),
  help: new TranslatableMarkup("Display the comment as RSS."),
  theme: "views_view_row_rss",
  register_theme: FALSE,
  base: ["comment_field_data"],
  display_types: ["feed"]
)]
class Rss extends RssPluginBase {

  /**
   * {@inheritdoc}
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  protected $base_table = 'comment_field_data';

  /**
   * {@inheritdoc}
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  public string $base_field = 'cid';

  /**
   * The field alias.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  public string $field_alias;

  /**
   * @var \Drupal\comment\CommentInterface[]
   */
  protected $comments;

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'comment';

  /**
   * {@inheritdoc}
   */
  public function preRender($result) {
    $cids = [];

    foreach ($result as $row) {
      $cids[] = $row->cid;
    }

    $this->comments = $this->entityTypeManager->getStorage('comment')->loadMultiple($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm_summary_options() {
    $options = parent::buildOptionsForm_summary_options();
    $options['title'] = $this->t('Title only');
    $options['default'] = $this->t('Use site default RSS settings');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    global $base_url;

    $cid = $row->{$this->field_alias};
    if (!is_numeric($cid)) {
      return;
    }

    $view_mode = $this->options['view_mode'];
    if ($view_mode == 'default') {
      $view_mode = \Drupal::config('system.rss')->get('items.view_mode');
    }

    // Load the specified comment and its associated node:
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->comments[$cid];
    if (empty($comment)) {
      return;
    }

    $comment->rss_namespaces = [];
    $comment->rss_elements = [
      [
        'key' => 'pubDate',
        'value' => gmdate('r', $comment->getCreatedTime()),
      ],
      [
        'key' => 'dc:creator',
        'value' => $comment->getAuthorName(),
      ],
      [
        'key' => 'guid',
        'value' => 'comment ' . $comment->id() . ' at ' . $base_url,
        'attributes' => ['isPermaLink' => 'false'],
      ],
    ];

    // The comment gets built and modules add to or modify
    // $comment->rss_elements and $comment->rss_namespaces.
    $build = $this->entityTypeManager->getViewBuilder('comment')->view($comment, 'rss');
    unset($build['#theme']);

    if (!empty($comment->rss_namespaces)) {
      $this->view->style_plugin->namespaces = array_merge($this->view->style_plugin->namespaces, $comment->rss_namespaces);
    }

    $item = new \stdClass();
    if ($view_mode != 'title') {
      // We render comment contents.
      $item->description = $build;
    }
    $item->title = $comment->label();
    $item->link = $comment->toUrl('canonical', ['absolute' => TRUE])->toString();
    // Provide a reference so that the render call in
    // template_preprocess_views_view_row_rss() can still access it.
    $item->elements = &$comment->rss_elements;
    $item->cid = $comment->id();

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
    ];
    return $build;
  }

}
