<?php

/**
 * @file
 * Definition of Drupal\comment\CommentFormController.
 */

namespace Drupal\comment;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;

/**
 * Base for controller for comment forms.
 */
class CommentFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $comment) {
    global $user;

    $node = node_load($comment->nid);
    $form_state['comment']['node'] = $node;

    // Use #comment-form as unique jump target, regardless of node type.
    $form['#id'] = drupal_html_id('comment_form');
    $form['#theme'] = array('comment_form__node_' . $node->type, 'comment_form');

    $anonymous_contact = variable_get('comment_anonymous_' . $node->type, COMMENT_ANONYMOUS_MAYNOT_CONTACT);
    $is_admin = (!empty($comment->cid) && user_access('administer comments'));

    if (!$user->uid && $anonymous_contact != COMMENT_ANONYMOUS_MAYNOT_CONTACT) {
      $form['#attached']['library'][] = array('system', 'jquery.cookie');
      $form['#attributes']['class'][] = 'user-info-from-cookie';
    }

    // If not replying to a comment, use our dedicated page callback for new
    // comments on nodes.
    if (empty($comment->cid) && empty($comment->pid)) {
      $form['#action'] = url('comment/reply/' . $comment->nid);
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
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
    }

    // Prepare default values for form elements.
    if ($is_admin) {
      $author = (!$comment->uid && $comment->name ? $comment->name : $comment->registered_name);
      $status = (isset($comment->status) ? $comment->status : COMMENT_NOT_PUBLISHED);
      $date = (!empty($comment->date) ? $comment->date : format_date($comment->created, 'custom', 'Y-m-d H:i O'));
    }
    else {
      if ($user->uid) {
        $author = $user->name;
      }
      else {
        $author = ($comment->name ? $comment->name : '');
      }
      $status = (user_access('skip comment approval') ? COMMENT_PUBLISHED : COMMENT_NOT_PUBLISHED);
      $date = '';
    }

    // Add the author name field depending on the current user.
    if ($is_admin) {
      $form['author']['name'] = array(
        '#type' => 'textfield',
        '#title' => t('Authored by'),
        '#default_value' => $author,
        '#maxlength' => 60,
        '#size' => 30,
        '#description' => t('Leave blank for %anonymous.', array('%anonymous' => config('user.settings')->get('anonymous'))),
        '#autocomplete_path' => 'user/autocomplete',
      );
    }
    elseif ($user->uid) {
      $form['author']['_author'] = array(
        '#type' => 'item',
        '#title' => t('Your name'),
        '#markup' => theme('username', array('account' => $user)),
      );

      $form['author']['name'] = array(
        '#type' => 'value',
        '#value' => $author,
      );
    }
    else {
      $form['author']['name'] = array(
        '#type' => 'textfield',
        '#title' => t('Your name'),
        '#default_value' => $author,
        '#required' => (!$user->uid && $anonymous_contact == COMMENT_ANONYMOUS_MUST_CONTACT),
        '#maxlength' => 60,
        '#size' => 30,
      );
    }

    // Add author e-mail and homepage fields depending on the current user.
    $form['author']['mail'] = array(
      '#type' => 'email',
      '#title' => t('E-mail'),
      '#default_value' => $comment->mail,
      '#required' => (!$user->uid && $anonymous_contact == COMMENT_ANONYMOUS_MUST_CONTACT),
      '#maxlength' => 64,
      '#size' => 30,
      '#description' => t('The content of this field is kept private and will not be shown publicly.'),
      '#access' => $is_admin || (!$user->uid && $anonymous_contact != COMMENT_ANONYMOUS_MAYNOT_CONTACT),
    );

    $form['author']['homepage'] = array(
      '#type' => 'url',
      '#title' => t('Homepage'),
      '#default_value' => $comment->homepage,
      '#maxlength' => 255,
      '#size' => 30,
      '#access' => $is_admin || (!$user->uid && $anonymous_contact != COMMENT_ANONYMOUS_MAYNOT_CONTACT),
    );

    // Add administrative comment publishing options.
    $form['author']['date'] = array(
      '#type' => 'textfield',
      '#title' => t('Authored on'),
      '#default_value' => $date,
      '#maxlength' => 25,
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
      '#default_value' => $comment->subject,
      '#access' => variable_get('comment_subject_field_' . $node->type, 1) == 1,
    );

    // Used for conditional validation of author fields.
    $form['is_anonymous'] = array(
      '#type' => 'value',
      '#value' => ($comment->cid ? !$comment->uid : !$user->uid),
    );

    // Add internal comment properties.
    foreach (array('cid', 'pid', 'nid', 'uid') as $key) {
      $form[$key] = array('#type' => 'value', '#value' => $comment->$key);
    }
    $form['node_type'] = array('#type' => 'value', '#value' => 'comment_node_' . $node->type);

    // Make the comment inherit the current content language unless specifically
    // set.
    if ($comment->isNew()) {
      $language_content = language(LANGUAGE_TYPE_CONTENT);
      $comment->langcode = $language_content->langcode;
    }

    $form['langcode'] = array(
      '#type' => 'value',
      '#value' => $comment->langcode,
    );

    // Attach fields.
    $comment->node_type = 'comment_node_' . $node->type;

    return parent::form($form, $form_state, $comment);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $element = parent::actions($form, $form_state);
    $comment = $this->getEntity($form_state);
    $node = $form_state['comment']['node'];
    $preview_mode = variable_get('comment_preview_' . $node->type, DRUPAL_OPTIONAL);

    // No delete action on the comment form.
    unset($element['delete']);

    // Mark the submit action as the primary action, when it appears.
    $element['submit']['#button_type'] = 'primary';

    // Only show the save button if comment previews are optional or if we are
    // already previewing the submission.
    $element['submit']['#access'] = ($comment->cid && user_access('administer comments')) || $preview_mode != DRUPAL_REQUIRED || isset($form_state['comment_preview']);

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
      $form_state['values']['uid'] = $account ? $account->uid : 0;

      $date = new DrupalDateTime($form_state['values']['date']);
      if ($date->hasErrors()) {
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
   * Overrides Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    $comment = parent::submit($form, $form_state);

    if (empty($comment->date)) {
      $comment->date = 'now';
    }
    $date = new DrupalDateTime($comment->date);
    $comment->created = $date->getTimestamp();
    $comment->changed = REQUEST_TIME;

    // If the comment was posted by a registered user, assign the author's ID.
    // @todo Too fragile. Should be prepared and stored in comment_form()
    // already.
    if (!$comment->is_anonymous && !empty($comment->name) && ($account = user_load_by_name($comment->name))) {
      $comment->uid = $account->uid;
    }
    // If the comment was posted by an anonymous user and no author name was
    // required, use "Anonymous" by default.
    if ($comment->is_anonymous && (!isset($comment->name) || $comment->name === '')) {
      $comment->name = config('user.settings')->get('anonymous');
    }

    // Validate the comment's subject. If not specified, extract from comment
    // body.
    if (trim($comment->subject) == '') {
      // The body may be in any format, so:
      // 1) Filter it into HTML
      // 2) Strip out all HTML tags
      // 3) Convert entities back to plain-text.
      $field = field_info_field('comment_body');
      $langcode = field_is_translatable('comment', $field) ? $this->getFormLangcode($form_state) : LANGUAGE_NOT_SPECIFIED;
      $comment_body = $comment->comment_body[$langcode][0];
      if (isset($comment_body['format'])) {
        $comment_text = check_markup($comment_body['value'], $comment_body['format']);
      }
      else {
        $comment_text = check_plain($comment_body['value']);
      }
      $comment->subject = truncate_utf8(trim(decode_entities(strip_tags($comment_text))), 29, TRUE);
      // Edge cases where the comment body is populated only by HTML tags will
      // require a default subject.
      if ($comment->subject == '') {
        $comment->subject = t('(No subject)');
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
    $comment = $this->getEntity($form_state);
    drupal_set_title(t('Preview comment'), PASS_THROUGH);
    $form_state['comment_preview'] = comment_preview($comment);
    $form_state['rebuild'] = TRUE;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $node = node_load($form_state['values']['nid']);
    $comment = $this->getEntity($form_state);

    if (user_access('post comments') && (user_access('administer comments') || $node->comment == COMMENT_NODE_OPEN)) {
      // Save the anonymous user information to a cookie for reuse.
      if (user_is_anonymous()) {
        user_cookie_save(array_intersect_key($form_state['values'], array_flip(array('name', 'mail', 'homepage'))));
      }

      comment_save($comment);
      $form_state['values']['cid'] = $comment->cid;

      // Add an entry to the watchdog log.
      watchdog('content', 'Comment posted: %subject.', array('%subject' => $comment->subject), WATCHDOG_NOTICE, l(t('view'), 'comment/' . $comment->cid, array('fragment' => 'comment-' . $comment->cid)));

      // Explain the approval queue if necessary.
      if ($comment->status == COMMENT_NOT_PUBLISHED) {
        if (!user_access('administer comments')) {
          drupal_set_message(t('Your comment has been queued for review by site administrators and will be published after approval.'));
        }
      }
      else {
        drupal_set_message(t('Your comment has been posted.'));
      }
      $query = array();
      // Find the current display page for this comment.
      $page = comment_get_display_page($comment->cid, $node->type);
      if ($page > 0) {
        $query['page'] = $page;
      }
      // Redirect to the newly posted comment.
      $redirect = array('node/' . $node->nid, array('query' => $query, 'fragment' => 'comment-' . $comment->cid));
    }
    else {
      watchdog('content', 'Comment: unauthorized comment submitted or comment submitted to a closed post %subject.', array('%subject' => $comment->subject), WATCHDOG_WARNING);
      drupal_set_message(t('Comment: unauthorized comment submitted or comment submitted to a closed post %subject.', array('%subject' => $comment->subject)), 'error');
      // Redirect the user to the node they are commenting on.
      $redirect = 'node/' . $node->nid;
    }
    $form_state['redirect'] = $redirect;
    // Clear the block and page caches so that anonymous users see the comment
    // they have posted.
    cache_invalidate_tags(array('content' => TRUE));
  }
}
