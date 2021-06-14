/**
 * @file
 * Message API.
 */
((Drupal) => {
  /**
   * @typedef {class} Drupal.Message~messageDefinition
   */

  /**
   * Constructs a new instance of the Drupal.Message class.
   *
   * This provides a uniform interface for adding and removing messages to a
   * specific location on the page.
   *
   * @param {HTMLElement} messageWrapper
   *   The zone where to add messages. If no element is provided an attempt is
   *   made to determine a default location.
   *
   * @return {Drupal.Message~messageDefinition}
   *   Class to add and remove messages.
   */
  Drupal.Message = class {
    constructor(messageWrapper = null) {
      if (!messageWrapper) {
        this.messageWrapper = Drupal.Message.defaultWrapper();
      } else {
        this.messageWrapper = messageWrapper;
      }
    }

    /**
     * Attempt to determine the default location for
     * inserting JavaScript messages or create one if needed.
     *
     * @return {HTMLElement}
     *   The default destination for JavaScript messages.
     */
    static defaultWrapper() {
      let wrapper = document.querySelector('[data-drupal-messages]');
      if (!wrapper) {
        wrapper = document.querySelector('[data-drupal-messages-fallback]');
        wrapper.removeAttribute('data-drupal-messages-fallback');
        wrapper.setAttribute('data-drupal-messages', '');
        wrapper.classList.remove('hidden');
      }
      return wrapper.innerHTML === ''
        ? Drupal.Message.messageInternalWrapper(wrapper)
        : wrapper.firstElementChild;
    }

    /**
     * Provide an object containing the available message types.
     *
     * @return {Object}
     *   An object containing message type strings.
     */
    static getMessageTypeLabels() {
      return {
        status: Drupal.t('Status message'),
        error: Drupal.t('Error message'),
        warning: Drupal.t('Warning message'),
      };
    }

    /**
     * Sequentially adds a message to the message area.
     *
     * @name Drupal.Message~messageDefinition.add
     *
     * @param {string} message
     *   The message to display
     * @param {object} [options]
     *   The context of the message.
     * @param {string} [options.id]
     *   The message ID, it can be a simple value: `'filevalidationerror'`
     *   or several values separated by a space: `'mymodule formvalidation'`
     *   which can be used as an explicit selector for a message.
     * @param {string} [options.type=status]
     *   Message type, can be either 'status', 'error' or 'warning'.
     * @param {string} [options.announce]
     *   Screen-reader version of the message if necessary. To prevent a message
     *   being sent to Drupal.announce() this should be an empty string.
     * @param {string} [options.priority]
     *   Priority of the message for Drupal.announce().
     *
     * @return {string}
     *   ID of message.
     */
    add(message, options = {}) {
      if (!options.hasOwnProperty('type')) {
        options.type = 'status';
      }

      if (typeof message !== 'string') {
        throw new Error('Message must be a string.');
      }

      // Send message to screen reader.
      Drupal.Message.announce(message, options);
      /**
       * Use the provided index for the message or generate a pseudo-random key
       * to allow message deletion.
       */
      options.id = options.id
        ? String(options.id)
        : `${options.type}-${Math.random().toFixed(15).replace('0.', '')}`;

      // Throw an error if an unexpected message type is used.
      if (!Drupal.Message.getMessageTypeLabels().hasOwnProperty(options.type)) {
        const { type } = options;
        throw new Error(
          `The message type, ${type}, is not present in Drupal.Message.getMessageTypeLabels().`,
        );
      }

      this.messageWrapper.appendChild(
        Drupal.theme('message', { text: message }, options),
      );

      return options.id;
    }

    /**
     * Select a message based on id.
     *
     * @name Drupal.Message~messageDefinition.select
     *
     * @param {string} id
     *   The message id to delete from the area.
     *
     * @return {Element}
     *   Element found.
     */
    select(id) {
      return this.messageWrapper.querySelector(
        `[data-drupal-message-id^="${id}"]`,
      );
    }

    /**
     * Removes messages from the message area.
     *
     * @name Drupal.Message~messageDefinition.remove
     *
     * @param {string} id
     *   Index of the message to remove, as returned by
     *   {@link Drupal.Message~messageDefinition.add}.
     *
     * @return {number}
     *   Number of removed messages.
     */
    remove(id) {
      return this.messageWrapper.removeChild(this.select(id));
    }

    /**
     * Removes all messages from the message area.
     *
     * @name Drupal.Message~messageDefinition.clear
     */
    clear() {
      Array.prototype.forEach.call(
        this.messageWrapper.querySelectorAll('[data-drupal-message-id]'),
        (message) => {
          this.messageWrapper.removeChild(message);
        },
      );
    }

    /**
     * Helper to call Drupal.announce() with the right parameters.
     *
     * @param {string} message
     *   Displayed message.
     * @param {object} options
     *   Additional data.
     * @param {string} [options.announce]
     *   Screen-reader version of the message if necessary. To prevent a message
     *   being sent to Drupal.announce() this should be `''`.
     * @param {string} [options.priority]
     *   Priority of the message for Drupal.announce().
     * @param {string} [options.type]
     *   Message type, can be either 'status', 'error' or 'warning'.
     */
    static announce(message, options) {
      if (
        !options.priority &&
        (options.type === 'warning' || options.type === 'error')
      ) {
        options.priority = 'assertive';
      }
      /**
       * If screen reader message is not disabled announce screen reader
       * specific text or fallback to the displayed message.
       */
      if (options.announce !== '') {
        Drupal.announce(options.announce || message, options.priority);
      }
    }

    /**
     * Function for creating the internal message wrapper element.
     *
     * @param {HTMLElement} messageWrapper
     *   The message wrapper.
     *
     * @return {HTMLElement}
     *   The internal wrapper DOM element.
     */
    static messageInternalWrapper(messageWrapper) {
      const innerWrapper = document.createElement('div');
      innerWrapper.setAttribute('class', 'messages__wrapper');
      messageWrapper.insertAdjacentElement('afterbegin', innerWrapper);
      return innerWrapper;
    }
  };

  /**
   * Theme function for a message.
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
    messageWrapper.setAttribute('data-drupal-message-id', id);
    messageWrapper.setAttribute('data-drupal-message-type', type);

    messageWrapper.setAttribute('aria-label', messagesTypes[type]);

    messageWrapper.innerHTML = `${text}`;

    return messageWrapper;
  };
})(Drupal);
