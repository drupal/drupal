/**
 * @file
 * Adds assets the current page requires.
 *
 * This script fires a custom `htmx:drupal:load` event when the request has
 * settled and all script and css files have been successfully loaded on the
 * page.
 */

((Drupal, drupalSettings, htmx) => {
  /**
   * Processes the response text: merges Drupal settings, loads assets, and
   * returns cleaned HTML with asset tags removed.
   *
   * Called by wrapping ctx.response.raw.text so htmx awaits it before swap.
   *
   * @param {string} text
   *   Raw response HTML.
   * @return {Promise<string>}
   *   Cleaned HTML with assets and settings elements removed.
   */
  async function processResponseText(text) {
    const doc = Document.parseHTMLUnsafe(text);

    // Remove noscript elements.
    doc.querySelectorAll('noscript').forEach((el) => el.remove());

    // 1. Extract and merge Drupal settings.
    const settingsEl = doc.querySelector(
      ':is(head, body) > script[type="application/json"][data-drupal-selector="drupal-settings-json"]',
    );
    if (settingsEl) {
      Drupal.htmx.mergeSettings(
        drupalSettings,
        JSON.parse(settingsEl.textContent),
      );
      settingsEl.remove();
    }

    // 2. Extract assets from head and body.
    const assets = doc.querySelectorAll(
      'link[rel="stylesheet"][href], script[src]',
    );
    if (assets.length) {
      const assetData = Array.from(assets).map(({ attributes }) => {
        const attrs = {};
        Object.values(attributes).forEach(({ name, value }) => {
          attrs[name] = value;
        });
        return attrs;
      });
      assets.forEach((el) => el.remove());

      await Drupal.htmx.addAssets(assetData);
    }

    // 3. Return cleaned HTML.
    return doc.documentElement.outerHTML;
  }

  /**
   * Determine a context for behavior processing.
   *
   * Drupal behavior processing operates on a context.  Drupal's once()
   * function is often used within that context to filter elements which are
   * within, that are children of the context.
   *
   * @param element
   *   The target or inserted element from htmx.
   * @return {*|HTMLElement}
   *   The parent element or body element for behavior processing.
   */
  function behaviorTarget(element) {
    let processTarget = element.parentElement;
    if (element === document.body || processTarget === null) {
      // The default context for behavior processing is the document.
      processTarget = document;
    }
    return processTarget;
  }

  htmx.registerExtension('drupal-assets', {
    htmx_config_request(elt, detail) {
      const { ctx } = detail;
      const url = new URL(ctx.request.action, document.location.href);
      const drupalIdentifier = (element) => {
        if (element?.name) {
          return `${element.tagName.toLowerCase()}[name="${encodeURI(element.name)}"]`;
        }
        if (element?.dataset.drupalSelector !== undefined) {
          return `${element.tagName.toLowerCase()}[data-drupal-selector="${encodeURI(element.dataset.drupalSelector)}"]`;
        }
        return `${element.tagName.toLowerCase()}${element.id ? `#${encodeURI(element.id)}` : ''}`;
      };

      if (!Drupal.url.isLocal(url.toString())) return;

      // Send current page state for differential asset loading.
      const pageState = drupalSettings.ajaxPageState;
      ctx.request.body.set('ajax_page_state[theme]', pageState.theme);
      ctx.request.body.set(
        'ajax_page_state[theme_token]',
        pageState.theme_token,
      );
      ctx.request.body.set('ajax_page_state[libraries]', pageState.libraries);

      // Add _wrapper_format query parameter for all non-full-page requests.
      if (ctx.sourceElement.hasAttribute('data-hx-drupal-only-main-content')) {
        // Drupal expects this parameter to be in the query string, not the post
        // values.
        url.searchParams.set('_wrapper_format', 'drupal_htmx');
        ctx.request.action = url.pathname + url.search;
      }

      // Add element metadata for Drupal forms.
      // @see core/assets/vendor/htmx/htmx.js:Htmx.#createCoreHeaders
      ctx.request.headers['HX-Source'] = drupalIdentifier(ctx.sourceElement);
      ctx.request.headers['HX-Target'] = drupalIdentifier(ctx.target);
      if (ctx.sourceElement?.name) {
        ctx.request.body.set(
          '_triggering_element_name',
          ctx.sourceElement.name,
        );
      }
    },

    htmx_before_response(elt, { ctx }) {
      // Wrap text() so htmx awaits asset loading before swap.
      const realText = ctx.response.raw.text.bind(ctx.response.raw);
      ctx.response.raw.text = async () => {
        const text = await realText();
        return processResponseText(text);
      };
    },

    htmx_before_history_update(elt, { history }) {
      const url = new URL(history.path, window.location);
      [
        '_wrapper_format',
        'ajax_page_state[theme]',
        'ajax_page_state[theme_token]',
        'ajax_page_state[libraries]',
        '_triggering_element_name',
        '_triggering_element_value',
      ].forEach((key) => {
        url.searchParams.delete(key);
      });
      history.path = url.toString();
    },

    htmx_before_swap(elt, detail) {
      // Get a set of unique targets for behavior processing.
      const processTargets = new Set();
      // eslint-disable-next-line no-restricted-syntax
      for (const task of detail.tasks) {
        // task data structure:
        // • type: main, partial, or oob
        // • fragment: the DocumentFragment to insert
        // • target: the resolved target element
        // • swapSpec: parsed swap settings including a `style` value.
        // • sourceElement: the request-originating element
        // • transition: (optional) applicable transition state
        if (
          task.swapSpec.style === 'none' ||
          task.swapSpec.style.includes('after') ||
          task.swapSpec.style.includes('before')
        ) {
          // Swap style does not remove elements
          continue;
        }
        processTargets.add(behaviorTarget(task.target));
      }
      processTargets.forEach((target) => {
        htmx.trigger(target, 'htmx:drupal:unload');
      });
    },

    htmx_after_settle(elt, detail) {
      // Attach Drupal behaviors to Element objects in newContent.
      const elements = detail.newContent.filter(
        (value) => value instanceof Element,
      );
      // Get a set of unique targets for behavior processing.
      const processTargets = new Set();
      elements.forEach((element) => {
        processTargets.add(behaviorTarget(element));
      });
      processTargets.forEach((target) => {
        htmx.trigger(target, 'htmx:drupal:load');
      });
    },
  });
})(Drupal, drupalSettings, htmx);
