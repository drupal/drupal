# Drupal Release Schedule

[View it in GitLab](https://git.drupalcode.org/project/drupal/-/blob/11.x/RELEASES.md)

```mermaid
---
displayMode: compact
---
%%{
  init: {
    'theme': 'base',
    'themeVariables': {
      'primaryColor': '#0077C0',
      'primaryTextColor': '#FFFFFF',
      'tertiaryColor': '#81CEFF',
      'tertiaryTextColor': '#000000',
      'doneTaskBkgColor': '#7cbc48',
      'textColor': '#000000'
    }
  }
}%%
gantt
    title Drupal Release Schedule
    dateFormat  YYYY-MM
    axisFormat %Y %b
    section Drupal 10.2
    10.2 Security   :active, 102sec, 2024-06, 6M
    section Drupal 10.3
    10.3 Support    :103sup, 2024-06, 6M
    10.3 Security   :active, 103sec, after 103sup,6M
    section Drupal 10.4
    10.4 Maint      :done, 104mnt, after 103sup, 6M
    10.4 Security   :active, 104sec, after 104mnt, 6M
    section Drupal 10.5
    10.5 Maint      :done, 105mnt, after 104mnt, 6M
    10.5 Security   :active, 105sec, after 105mnt, 6M
    section Drupal 11.0
    11.0 Support    :110sup, 2024-06  , 6M
    11.0 Security   :active, 110sec, after 110sup, 6M
    section Drupal 11.1
    11.1 Support    :111sup, after 110sup, 6M
    11.1 Security   :active, 111sec, after 111sup, 6M
    section Drupal 11.2
    11.2 Support    :112sup, after 111sup, 6M
    11.2 Security   :active, 112sec, after 112sup, 6M
    section Drupal 11.3
    11.3 Support    :113sup, after 112sup, 6M
    11.3 Security   :active, 113sec, after 113sup, 6M
    section Drupal 11.4
    11.4 Support    :114sup, after 113sup, 6M
    11.4 Security   :active, 114sec, after 114sup, 6M
    section Drupal 11.5
    11.5 Maint      :done, 115mnt, after 114sup, 6M
    11.5 Security   :active, 115sec, after 115mnt, 6M
    section Drupal 11.6
    11.6 Maint      :done, 116mnt, after 115mnt, 6M
    section Drupal 12.0
    12.0 Support    :120sup, after 113sup, 6M
    12.0 Security   :active, 120sec, after 120sup, 6M
    section Drupal 12.1
    12.1 Support    :121sup, after 120sup, 6M
    12.1 Security   :active, after 121sup, 6M
```

---

### Export graph as image

Copy the mermaid markdown code above:
- Use the `Copy to Clipboard` button when viewing the file via GitLab UI
- Or if you are editing code, copy the mermaid markdown (do not include the wrapping backticks)

Then go to the [Mermaid Live](https://mermaid.live) editor, and paste the code you just copied.
You can see the `Actions` dropdown, and you can click on the `PNG` button to download an export of the graph in PNG format.
