/**
 * @file
 * Customization of comments.
 */

((Drupal, once) => {
  /**
   * Initialize show/hide button for the comments.
   *
   * @param {Element} comments
   *   The comment wrapper element.
   */
  function init(comments) {
    comments
      .querySelectorAll('[data-drupal-selector="comment"]')
      .forEach((comment) => {
        if (comment.nextElementSibling?.matches('.indented')) {
          comment.classList.add('has-children');
        }
      });

    comments.querySelectorAll('.indented').forEach((commentGroup) => {
      const showHideWrapper = document.createElement('div');
      showHideWrapper.setAttribute('class', 'show-hide-wrapper');

      const toggleCommentsBtn = document.createElement('button');
      toggleCommentsBtn.setAttribute('type', 'button');
      toggleCommentsBtn.setAttribute('aria-expanded', 'true');
      toggleCommentsBtn.setAttribute('class', 'show-hide-btn');
      toggleCommentsBtn.innerText = Drupal.t('Replies');

      commentGroup.parentNode.insertBefore(showHideWrapper, commentGroup);
      showHideWrapper.appendChild(toggleCommentsBtn);

      toggleCommentsBtn.addEventListener('click', (e) => {
        commentGroup.classList.toggle('hidden');
        e.currentTarget.setAttribute(
          'aria-expanded',
          commentGroup.classList.contains('hidden') ? 'false' : 'true',
        );
      });
    });
  }

  /**
   * Attaches the comment behavior to comments.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the show/hide behavior for indented comments.
   */
  Drupal.behaviors.comments = {
    attach(context) {
      once('comments', '[data-drupal-selector="comments"]', context).forEach(
        init,
      );
    },
  };
})(Drupal, once);
