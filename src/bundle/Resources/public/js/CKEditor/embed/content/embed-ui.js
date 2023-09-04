import IbexaEmbedBaseUI from '../embed-base-ui';

const { Translator } = window;

class IbexaEmbedContentUI extends IbexaEmbedBaseUI {
    constructor(props) {
        super(props);

        this.configName = 'richtext_embed';
        this.commandName = 'insertIbexaEmbed';
        this.buttonLabel = Translator.trans(/*@Desc("Embed")*/ 'embed_btn.label', {}, 'ck_editor');
        this.componentName = 'ibexaEmbed';
        this.icon = window.ibexa.helpers.icon.getIconPath('embed');
    }

    getCommandOptions(items) {
        const location = items[0];
        const content = location.ContentInfo.Content;

        return {
            contentId: content._id,
            contentName: content.TranslatedName,
            locationId: location.id,
            languageCodes: content.CurrentVersion.Version.VersionInfo.VersionTranslationInfo.Language.map(
                (language) => language.languageCode,
            ),
        };
    }
}

export default IbexaEmbedContentUI;
