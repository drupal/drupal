/**
 * @file
 * Renders BigPipe placeholders using Drupal's Ajax system.
 */

((Drupal, drupalSettings) => {
  /**
   * CSS selector for script elements to process on page load.
   *
   * @type {string}
   */
  const replacementsSelector = `script[data-big-pipe-replacement-for-placeholder-with-id]`;

  /**
   * Ajax object that will process all the BigPipe responses.
   *
   * Create a Drupal.Ajax object without associating an element, a progress
   * indicator or a URL.
   *
   * @type {Drupal.Ajax}
   */
  const ajaxObject = Drupal.ajax({
    url: '',
    base: false,
    element: false,
    progress: false,
  });

  /**
   * Maps textContent of <script type="application/vnd.drupal-ajax"> to an AJAX
   * response.
   *
   * @param {string} content
   *   The text content of a <script type="application/vnd.drupal-ajax"> DOM
   *   node.
   * @return {Array|boolean}
   *   The parsed Ajax response containing an array of Ajax commands, or false
   *   in case the DOM node hasn't fully arrived yet.
   */
  function mapTextContentToAjaxResponse(content) {
    if (content === '') {
      return false;
    }

    try {
      return JSON.parse(content);
    } catch (e) {
      return false;
    }
  }

  /**
   * Executes Ajax commands in <script type="application/vnd.drupal-ajax"> tag.
   *
   * These Ajax commands replace placeholders with HTML and load missing CSS/JS.
   *
   * @param {HTMLScriptElement} replacement
   *   Script tag created by BigPipe.
   */
  function processReplacement(replacement) {
    const id = replacement.dataset.bigPipeReplacementForPlaceholderWithId;
    // Because we use a mutation observer the content is guaranteed to be
    // complete at this point.
    const content = replacement.textContent.trim();

    // Ignore any placeholders that are not in the known placeholder list. Used
    // to avoid someone trying to XSS the site via the placeholdering mechanism.
    if (typeof drupalSettings.bigPipePlaceholderIds[id] === 'undefined') {
      return;
    }

    // Immediately remove the replacement to prevent it being processed twice.
    delete drupalSettings.bigPipePlaceholderIds[id];

    const response = mapTextContentToAjaxResponse(content);

    if (response === false) {
      return;
    }

    // Then, simulate an AJAX response having arrived, and let the Ajax system
    // handle it.
    ajaxObject.success(response, 'success');
  }

  /**
   * Check that the element is valid to process and process it.
   *
   * @param {HTMLElement} node
   *  The node added to the body element.
   */
  function checkMutationAndProcess(node) {
    if (
      node.nodeType === Node.ELEMENT_NODE &&
      node.nodeName === 'SCRIPT' &&
      node.dataset &&
      node.dataset.bigPipeReplacementForPlaceholderWithId
    ) {
      processReplacement(node);
    }
  }

  /**
   * Handles the mutation callback.
   *
   * @param {MutationRecord[]} mutations
   *  The list of mutations registered by the browser.
   */
  function processMutations(mutations) {
    mutations.forEach(({ addedNodes }) => {
      addedNodes.forEach(checkMutationAndProcess);
    });
  }

  const observer = new MutationObserver(processMutations);

  // Attach behaviors early, if possible.
  Drupal.attachBehaviors(document);

  // If loaded asynchronously there might already be replacement elements
  // in the DOM before the mutation observer is started.
  document.querySelectorAll(replacementsSelector).forEach(processReplacement);

  // Start observing the body element for new children.
  observer.observe(document.body, { childList: true });

  // As soon as the document is loaded, no more replacements will be added.
  // Immediately fetch and process all pending mutations and stop the observer.
  window.addEventListener('DOMContentLoaded', () => {
    const mutations = observer.takeRecords();
    observer.disconnect();
    if (mutations.length) {
      processMutations(mutations);
    }
    // No more mutations will be processed, remove the leftover Ajax object.
    Drupal.ajax.instances[ajaxObject.instanceIndex] = null;
  });
})(Drupal, drupalSettings);
