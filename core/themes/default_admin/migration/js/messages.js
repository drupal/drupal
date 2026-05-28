/**
 * @file
 *   Main JavaScript file for Dismiss module
 */

/* eslint-disable func-names, no-mutable-exports, comma-dangle, strict */

((Drupal, once) => {
  /**
   * Overrides message theme function.
   *
   * @param {object} message
   *   The message object.
   * @param {string} message.text
   *   The message text.
   * @param {object} options
   *   The message context.
   * @param {string} options.type
   *   The message type.
   * @param {string} options.id
   *   ID of the message, for reference.
   *
   * @return {HTMLElement}
   *   A DOM Node.
   */
  Drupal.theme.message = ({ text }, { type, id }) => {
    const messagesTypes = Drupal.Message.getMessageTypeLabels();
    const messageWrapper = document.createElement('div');

    messageWrapper.setAttribute('class', `messages-list__item messages messages--${type}`);
    messageWrapper.setAttribute(
      'role',
      type === 'error' || type === 'warning' ? 'alert' : 'status',
    );
    messageWrapper.setAttribute('data-drupal-message-id', id);
    messageWrapper.setAttribute('data-drupal-message-type', type);

    messageWrapper.setAttribute('aria-label', messagesTypes[type]);

    messageWrapper.innerHTML = `
    <div class="messages__header">
      <h2 id="${id}-title" class="messages__title">
        ${messagesTypes[type]}
      </h2>
    </div>
    <div class="messages__content">
      ${text}
    </div>
    <button type="button" class="button button--dismiss js-message-button-hide" title="${Drupal.t('Hide')}">
      <span class="icon-close"></span>
      ${Drupal.t('Hide')}
    </button>
  `;

    // Attach event listener
    Drupal.ginMessages.dismissMessages(messageWrapper);

    return messageWrapper;
  };

  Drupal.behaviors.ginMessages = {
    attach: (context) => {
      Drupal.ginMessages.dismissMessages(context);
    }
  };

  Drupal.ginMessages = {
    dismissMessages: (context = document) => {
      once('gin-messages-dismiss', '.js-message-button-hide', context).forEach(dismissButton => {
        dismissButton.addEventListener('click', e => {
          e.preventDefault();
          const message = dismissButton.parentNode;

          if (message.classList.contains('messages-list__item')) {
            message.style.opacity = 0;
            message.classList.add('visually-hidden');
          }
        });
      });
    },

  };
})(Drupal, once);
