The API of Single Directory Components includes:

  - The component plugin manager (the service with name plugin.manager.sdc).
    This service will be needed by modules that need to find and instantiate
    components.
  - The exceptions. Code using Single Directory Components can rely, and extend,
    the exceptions provided by the experimental module.
  - The folder structure of a component and the naming conventions of the files
    in it.
  - The structure of the component metadata (the my-component.component.yml).
    Note that the metadata of the component is described, and optionally
    validated, by the schema in metadata.schema.json (this file is for internal validation and not part of the API).
  - The render element and its class \Drupal\Core\Render\Element\ComponentElement.
  - The naming convention for the ID when using Twig's include, embed, and
    extends. This naming convention is [module/theme]:[component machine name].
    See the example below.

{% embed 'my-theme:my-component' with { prop1: content.field_for_prop1 } %}
  {% block slot1 %}
    {{ content|without('field_for_prop1') }}
  {% endblock %}
{% endembed %}

This way  of specifying the component for Twig's include, embed, and
extends('my-theme:my-component' in the example) will not change, as it is
considered an API.
