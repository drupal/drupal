<?php

namespace Drupal\Core\Ajax;

/**
 * Ajax command for updating the form build ID.
 *
 * Used for updating the value of a hidden form_build_id input element on a
 * form. It requires the form passed in to have keys for both the old build ID
 * in #build_id_old and the new build ID in #build_id.
 *
 * The primary use case for this Ajax command is to serve a new build ID to a
 * form served from the cache to an anonymous user, preventing one anonymous
 * user from accessing the form state of another anonymous user on Ajax enabled
 * forms.
 *
 * This command is implemented by
 * Drupal.AjaxCommands.prototype.update_build_id() defined in misc/ajax.js.
 *
 * @ingroup ajax
 */
class UpdateBuildIdCommand implements CommandInterface {

  /**
   * Old build id.
   *
   * @var string
   */
  protected $old;

  /**
   * New build id.
   *
   * @var string
   */
  protected $new;

  /**
   * Constructs an UpdateBuildIdCommand object.
   *
   * @param string $old
   *   The old build_id.
   * @param string $new
   *   The new build_id.
   */
  public function __construct($old, $new) {
    $this->old = $old;
    $this->new = $new;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'update_build_id',
      'old' => $this->old,
      'new' => $this->new,
    ];
  }

}
