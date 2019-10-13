/**
 * @file
 * Message template overrides.
 */

(Drupal => {
  /**
   * Override Drupal.Message.defaultWrapper() because it prevents adding classes
   * to the wrapper.
   *
   *  @return {HTMLElement}
   *   The default destination for JavaScript messages.
   *
   * @todo Revisit this after https://www.drupal.org/node/3086723 has been
   *   resolved.
   */
  Drupal.Message.defaultWrapper = () => {
    let wrapper = document.querySelector('[data-drupal-messages]');
    if (!wrapper) {
      wrapper = document.querySelector('[data-drupal-messages-fallback]');
      wrapper.removeAttribute('data-drupal-messages-fallback');
      wrapper.setAttribute('data-drupal-messages', '');
      wrapper.classList.remove('hidden');
      wrapper.classList.add('messages-list');
    }
    return wrapper.innerHTML === ''
      ? Drupal.Message.messageInternalWrapper(wrapper)
      : wrapper.firstElementChild;
  };

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

    messageWrapper.setAttribute('class', `messages messages--${type}`);
    messageWrapper.setAttribute(
      'role',
      type === 'error' || type === 'warning' ? 'alert' : 'status',
    );
    messageWrapper.setAttribute('aria-labelledby', `${id}-title`);
    messageWrapper.setAttribute('data-drupal-message-id', id);
    messageWrapper.setAttribute('data-drupal-message-type', type);

    messageWrapper.innerHTML = `
    <div class="messages__header">
      <h2 id="${id}-title" class="messages__title">
        ${messagesTypes[type]}
      </h2>
    </div>
    <div class="messages__content">
      ${text}
    </div>
  `;

    return messageWrapper;
  };
})(Drupal);
