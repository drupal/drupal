For some reason the link in the core file https://modernizr.com/download/?-details-inputtypes-addtest-mq-prefixed-prefixes-setclasses-teststyles
always produces a 3.6.0 version when using the `Build` option.

Browse to the same URL and use the `Command Line Config` option to `Download` the config JSON file `modernizr-config.json`.

Following the instructions here https://modernizr.com/docs

```
sudo npm install -g npm
npm install -g modernizr
modernizr -c modernizr-config.json // This is the file downloaded from http://modernizr.com/download
```
This produces a `modernizr.js` file that should be renamed to `modernizr.min.js` and copied to `core/assets/vendor/modernizr/modernizr.min.js`.

Please also remember to update the version number of Modernizer in `core/core.libraries.yml`.
