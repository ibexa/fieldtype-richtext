const path = require('path');
const ibexaConfigManager = require('@ibexa/frontend-config/webpack-config/manager');
const configManagers = require(path.resolve('./var/encore/ibexa.richtext.config.manager.js'));

module.exports = (Encore) => {
    Encore.reset();
    Encore.setOutputPath('public/assets/richtext/build')
        .setPublicPath('/assets/richtext/build')
        .enableSassLoader()
        .disableSingleRuntimeChunk()
        .configureBabelPresetEnv((config) => {
            config.useBuiltIns = false;
            config.corejs = false;
        });

    Encore.addAliases({
        '@ckeditor': path.resolve('./public/bundles/ibexaadminuiassets/vendors/@ckeditor'),
        ckeditor5: path.resolve('./public/bundles/ibexaadminuiassets/vendors/ckeditor5'),
        '@fieldtype-richtext': path.resolve('./vendor/ibexa/fieldtype-richtext'),
        '@ibexa-admin-ui': path.resolve('./vendor/ibexa/admin-ui'),
    });

    Encore.addEntry('ibexa-richtext-onlineeditor-js', [
        path.resolve(__dirname, '../public/js/CKEditor/core/base-ckeditor.js'),
    ]).addStyleEntry('ibexa-richtext-onlineeditor-css', [
        path.resolve('./public/bundles/ibexaadminuiassets/vendors/ckeditor5/dist/ckeditor5.css'),
        path.resolve(__dirname, '../public/scss/ckeditor.scss'),
    ]);

    const customConfig = Encore.getWebpackConfig();

    customConfig.name = 'richtext';

    customConfig.module.rules[4].oneOf[1].use[1].options.url = false;
    customConfig.module.rules[1].oneOf[1].use[1].options.url = false;

    configManagers.forEach((configManagerPath) => {
        const configManager = require(path.resolve(configManagerPath));

        configManager(customConfig, ibexaConfigManager);
    });

    Encore.reset();

    return customConfig;
};
