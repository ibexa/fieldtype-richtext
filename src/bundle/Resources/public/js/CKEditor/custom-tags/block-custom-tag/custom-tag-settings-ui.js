import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import IbexaButtonView from '../../common/button-view/button-view';

import IbexaCustomTagSettingsCommand from './custom-tag-settings-command';

const { ibexa, Translator } = window;

class IbexaCustomTagSettingsUI extends Plugin {
    constructor(props) {
        super(props);

        this.showSettings = this.showSettings.bind(this);
    }

    showSettings() {
        this.editor.editing.view.document.fire('ibexa-show-custom-tag-settings');
    }

    init() {
        const ibexaCustomTagSettingsCommand = new IbexaCustomTagSettingsCommand(this.editor);

        this.editor.ui.componentFactory.add('ibexaCustomTagSettings', (locale) => {
            const buttonView = new IbexaButtonView(locale);

            buttonView.set({
                label: Translator.trans(/*@Desc("Settings")*/ 'custom_tag.settings.label', {}, 'ck_editor'),
                icon: ibexa.helpers.icon.getIconPath('settings-block'),
                tooltip: true,
            });

            this.listenTo(buttonView, 'execute', this.showSettings);

            buttonView.bind('isEnabled').to(ibexaCustomTagSettingsCommand);

            return buttonView;
        });

        this.editor.commands.add('ibexaCustomTagSettings', ibexaCustomTagSettingsCommand);
    }
}

export default IbexaCustomTagSettingsUI;
