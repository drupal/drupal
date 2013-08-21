<?php

/**
 * @file
 * Definition of Drupal\comment\CommentFormController.
 */

namespace Drupal\comment;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormControllerNG;
use Drupal\Core\Language\Language;

/**
 * Base for controller for comment forms.
 */
class CommentFormController extends EntityFormControllerNG {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    global $user;
    $comment = $this->entity;
    $node = $comment->nid->entity;

    // Use #comment-form as unique jump target, regardless of node type.
    $form['#id'] = drupal_html_id('comment_form');
    $form['#theme'] = array('comment_form__node_' . $node->getType(), 'comment_form');

    $anonymous_contact = variable_get('comment_anonymous_' . $node->getType(), COMMENT_ANONYMOUS_MAYNOT_CONTACT);
    $is_admin = $comment->id() && user_access('administer comments');

    if (!$user->isAuthenticated() && $anonymous_contact != COMMENT_ANONYMOUS_MAYNOT_CONTACT) {
      $form['#attached']['library'][] = array('system', 'jquery.cookie');
      $form['#attributes']['class'][] = 'user-info-from-cookie';
    }

    // If not replying to a comment, use our dedicated page callback for new
    // comments on nodes.
    if (!$comment->id() && !$comment->pid->target_id) {
      $form['#action'] = url('comment/reply/' . $comment->nid->target_id);
    }

    if (isset($form_state['comment_preview'])) {
      $form += $form_state['comment_preview'];
    }

    $form['author'] = array(
      '#weight' => 10,
    );
    // Display author information in a details element for comment moderators.
    if ($is_admin) {
      $form['author'] += array(
        '#type' => 'details',
        '#title' => t('Administration'),
        '#collapsed' => TRUE,
      );
    }

    // Prepare default values for form elements.
    if ($is_admin) {
      $author = $comment->name->value;
      $status = (isset($comment->status->value) ? $comment->status->value : COMMENT_NOT_PUBLISHED);
      $date = (!empty($comment->date) ? $comment->date : DrupalDateTime::createFromTimestamp($comment->created->value));
    }
    else {
      if ($user->isAuthenticated()) {
        $author = $user->getUsername();
      }
      else {
        $author = ($comment->name->value ? $comment->name->value : '');
      }
      $status = (user_access('skip comment approval') ? COMMENT_PUBLISHED : COMMENT_NOT_PUBLISHED);
      $date = '';
    }

    // Add the author name field depending on the current user.
    $form['author']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Your name'),
      '#default_value' => $author,
      '#required' => ($user->isAnonymous() && $anonymous_contact == COMMENT_ANONYMOUS_MUST_CONTACT),
      '#maxlength' => 60,
      '#size' => 30,
    );
    if ($is_admin) {
      $form['author']['name']['#title'] = t('Authored by');
      $form['author']['name']['#description'] = t('Leave blank for %anonymous.', array('%anonymous' => \Drupal::config('user.settings')->get('anonymous')));
      $form['author']['name']['#autocomplete_route_name'] = 'user_autocomplete';
    }
    elseif ($user->isAuthenticated()) {
      $form['author']['name']['#type'] = 'item';
      $form['author']['name']['#value'] = $form['author']['name']['#default_value'];
      $username = array(
        '#theme' => 'username',
        '#account' => $user,
      );
      $form['author']['name']['#markup'] = drupal_render($username);
    }

    // Add author e-mail and homepage fields depending on the current user.
    $form['author']['mail'] = array(
      '#type' => 'email',
      '#title' => t('E-mail'),
      '#default_value' => $comment->mail->value,
      '#required' => ($user->isAnonymous() && $anonymous_contact == COMMENT_ANONYMOUS_MUST_CONTACT),
      '#maxlength' => 64,
      '#size' => 30,
      '#description' => t('The content of this field is kept private and will not be shown publicly.'),
      '#access' => $is_admin || ($user->isAnonymous() && $anonymous_contact != COMMENT_ANONYMOUS_MAYNOT_CONTACT),
    );

    $form['author']['homepage'] = array(
      '#type' => 'url',
      '#title' => t('Homepage'),
      '#default_value' => $comment->homepage->value,
      '#maxlength' => 255,
      '#size' => 30,
      '#access' => $is_admin || ($user->isAnonymous() && $anonymous_contact != COMMENT_ANONYMOUS_MAYNOT_CONTACT),
    );

    // Add administrative comment publishing options.
    $form['author']['date'] = array(
      '#type' => 'datetime',
      '#title' => t('Authored on'),
      '#default_value' => $date,
      '#size' => 20,
      '#access' => $is_admin,
    );

    $form['author']['status'] = array(
      '#type' => 'radios',
      '#title' => t('Status'),
      '#default_value' => $status,
      '#options' => array(
        COMMENT_PUBLISHED => t('Published'),
        COMMENT_NOT_PUBLISHED => t('Not published'),
      ),
      '#access' => $is_admin,
    );

    $form['subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#maxlength' => 64,
      '#default_value' => $comment->subject->value,
      '#access' => variable_get('comment_subject_field_' . $node->getType(), 1) == 1,
    );

    // Used for conditional validation of author fields.
    $form['is_anonymous'] = array(
      '#type' => 'value',
      '#value' => ($comment->id() ? !$comment->uid->target_id : $user->isAnonymous()),
    );

    // Make the comment inherit the current content language unless specifically
    // set.
    if ($comment->isNew()) {
      $language_content = language(Language::TYPE_CONTENT);
      $comment->langcode->value = $language_content->id;
    }

    // Add internal comment properties.
    $original = $comment->getUntranslated();
    foreach (array('cid', 'pid', 'nid', 'uid', 'node_type', 'langcode') as $key) {
      $key_name = key($comment->$key->offsetGet(0)->getPropertyDefinitions());
      $form[$key] = array('#type' => 'value', '#value' => $original->$key->{$key_name});
    }

    return parent::form($form, $form_state, $comment);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $element = parent::actions($form, $form_state);
    $comment = $this->entity;
    $node = $comment->nid->entity;
    $preview_mode = variable_get('comment_preview_' . $node->getType(), DRUPAL_OPTIONAL);

    // No delete action on the comment form.
    unset($element['delete']);

    // Mark the submit action as the primary action, when it appears.
    $element['submit']['#button_type'] = 'primary';

    // Only show the save button if comment previews are optional or if we are
    // already previewing the submission.
    $element['submit']['#access'] = ($comment->id() && user_access('administer comments')) || $preview_mode != DRUPAL_REQUIRED || isset($form_state['comment_preview']);

    $element['preview'] = array(
      '#type' => 'submit',
      '#value' => t('Preview'),
      '#access' => $preview_mode != DRUPAL_DISABLED,
      '#validate' => array(
        array($this, 'validate'),
      ),
      '#submit' => array(
        array($this, 'submit'),
        array($this, 'preview'),
      ),
    );

    return $element;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);

    if (!empty($form_state['values']['cid'])) {
      // Verify the name in case it is being changed from being anonymous.
      $account = user_load_by_name($form_state['values']['name']);
      $form_state['values']['uid'] = $account ? $account->id() : 0;

      $date = $form_state['values']['date'];
      if ($date instanceOf DrupalDateTime && $date->hasErrors()) {
        form_set_error('date', t('You have to specify a valid date.'));
      }
      if ($form_state['values']['name'] && !$form_state['values']['is_anonymous'] && !$account) {
        form_set_error('name', t('You have to specify a valid author.'));
      }
    }
    elseif ($form_state['values']['is_anonymous']) {
      // Validate anonymous comment author fields (if given). If the (original)
      // author of this comment was an anonymous user, verify that no registered
      // user with this name exists.
      if ($form_state['values']['name']) {
        $query = db_select('users', 'u');
        $query->addField('u', 'uid', 'uid');
        $taken = $query
          ->condition('name', db_like($form_state['values']['name']), 'LIKE')
          ->countQuery()
          ->execute()
          ->fetchField();
        if ($taken) {
          form_set_error('name', t('The name you used belongs to a registered user.'));
        }
      }
    }
  }

  /**
   * Overrides EntityFormController::buildEntity().
   */
  public function buildEntity(array $form, array &$form_state) {
    $comment = parent::buildEntity($form, $form_state);
    if (!empty($form_state['values']['date']) && $form_state['values']['date'] instanceOf DrupalDateTime) {
      $comment->created->value = $form_state['values']['date']->getTimestamp();
    }
    else {
      $comment->created->value = REQUEST_TIME;
    }
    $comment->changed->value = REQUEST_TIME;
    return $comment;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    $comment = parent::submit($form, $form_state);

    // If the comment was posted by a registered user, assign the author's ID.
    // @todo Too fragile. Should be prepared and stored in comment_form()
    // already.
    if (!$comment->is_anonymous && !empty($comment->name->value) && ($account = user_load_by_name($comment->name->value))) {
      $comment->uid->target_id = $account->id();
    }
    // If the comment was posted by an anonymous user and no author name was
    // required, use "Anonymous" by default.
    if ($comment->is_anonymous && (!isset($comment->name->value) || $comment->name->value === '')) {
      $comment->name->value = \Drupal::config('user.settings')->get('anonymous');
    }

    // Validate the comment's subject. If not specified, extract from comment
    // body.
    if (trim($comment->subject->value) == '') {
      // The body may be in any format, so:
      // 1) Filter it into HTML
      // 2) Strip out all HTML tags
      // 3) Convert entities back to plain-text.
      $comment_text = $comment->comment_body->processed;
      $comment->subject = truncate_utf8(trim(decode_entities(strip_tags($comment_text))), 29, TRUE);
      // Edge cases where the comment body is populated only by HTML tags will
      // require a default subject.
      if ($comment->subject->value == '') {
        $comment->subject->value = t('(No subject)');
      }
    }

    return $comment;
  }

  /**
   * Form submission handler for the 'preview' action.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function preview(array $form, array &$form_state) {
    $comment = $this->entity;
    drupal_set_title(t('Preview comment'), PASS_THROUGH);
    $form_state['comment_preview'] = comment_preview($comment);
    $form_state['rebuild'] = TRUE;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $node = node_load($form_state['values']['nid']);
    $comment = $this->entity;

    if (user_access('post comments') && (user_access('administer comments') || $node->comment == COMMENT_NODE_OPEN)) {
      // Save the anonymous user information to a cookie for reuse.
      if (user_is_anonymous()) {
        user_cookie_save(array_intersect_key($form_state['values'], array_flip(array('name', 'mail', 'homepage'))));
      }

      $comment->save();
      $form_state['values']['cid'] = $comment->id();

      // Add an entry to the watchdog log.
      watchdog('content', 'Comment posted: %subject.', array('%subject' => $comment->subject->value), WATCHDOG_NOTICE, l(t('view'), 'comment/' . $comment->id(), array('fragment' => 'comment-' . $comment->id())));

      // Explain the approval queue if necessary.
      if ($comment->status->value == COMMENT_NOT_PUBLISHED) {
        if (!user_access('administer comments')) {
          drupal_set_message(t('Your comment has been queued for review by site administrators and will be published after approval.'));
        }
      }
      else {
        drupal_set_message(t('Your comment has been posted.'));
      }
      $query = array();
      // Find the current display page for this comment.
      $page = comment_get_display_page($comment->id(), $node->getType());
      if ($page > 0) {
        $query['page'] = $page;
      }
      // Redirect to the newly posted comment.
      $redirect = array('node/' . $node->id(), array('query' => $query, 'fragment' => 'comment-' . $comment->id()));
    }
    else {
      watchdog('content', 'Comment: unauthorized comment submitted or comment submitted to a closed post %subject.', array('%subject' => $comment->subject->value), WATCHDOG_WARNING);
      drupal_set_message(t('Comment: unauthorized comment submitted or comment submitted to a closed post %subject.', array('%subject' => $comment->subject->value)), 'error');
      // Redirect the user to the node they are commenting on.
      $redirect = 'node/' . $node->id();
    }
    $form_state['redirect'] = $redirect;
    // Clear the block and page caches so that anonymous users see the comment
    // they have posted.
    cache_invalidate_tags(array('content' => TRUE));
  }
}
