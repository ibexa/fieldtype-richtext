const Encore = require('@symfony/webpack-encore');
const path = require('path');
const ibexaConfigManager = require(path.resolve('./ibexa.webpack.config.manager.js'));
const configManagers = require(path.resolve('./var/encore/ibexa.richtext.config.manager.js'));

Encore.reset();
Encore.setOutputPath('public/assets/richtext/build').setPublicPath('/assets/richtext/build').enableSassLoader().disableSingleRuntimeChunk();

Encore.addEntry('ibexa-richtext-onlineeditor-js', [path.resolve(__dirname, '../public/js/CKEditor/core/base-ckeditor.js')]).addStyleEntry(
    'ibexa-richtext-onlineeditor-css',
    [
        path.resolve('./public/bundles/ibexaadminuiassets/vendors/ckeditor5/dist/ckeditor5.css'),
        path.resolve(__dirname, '../public/scss/ckeditor.scss'),
    ],
);

Encore.addAliases({
    '@ckeditor': path.resolve('./public/bundles/ibexaadminuiassets/vendors/@ckeditor'),
    ckeditor5: path.resolve('./public/bundles/ibexaadminuiassets/vendors/ckeditor5'),
    '@fieldtype-richtext': path.resolve('./vendor/ibexa/fieldtype-richtext'),
    '@ibexa-admin-ui': path.resolve('./vendor/ibexa/admin-ui'),
});

const customConfig = Encore.getWebpackConfig();

customConfig.name = 'richtext';

customConfig.module.rules[4].oneOf[1].use[1].options.url = false;
customConfig.module.rules[1].oneOf[1].use[1].options.url = false;

configManagers.forEach((configManagerPath) => {
    const configManager = require(path.resolve(configManagerPath));

    configManager(customConfig, ibexaConfigManager);
});

module.exports = customConfig;
