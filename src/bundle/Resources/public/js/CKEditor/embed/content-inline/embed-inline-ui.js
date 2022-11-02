import IbexaEmbedBaseUI from '../embed-base-ui';

const { Translator } = window;

class IbexaEmbedContentInlineUI extends IbexaEmbedBaseUI {
    constructor(props) {
        super(props);

        this.configName = 'richtext_embed';
        this.commandName = 'insertIbexaEmbedInline';
        this.buttonLabel = Translator.trans(/*@Desc("Embed inline")*/ 'embed_inline_btn.label', {}, 'ck_editor');
        this.componentName = 'ibexaEmbedInline';
        this.icon = window.ibexa.helpers.icon.getIconPath('embed-inline');
    }

    getCommandOptions(items) {
        return {
            contentId: items[0].ContentInfo.Content._id,
            contentName: items[0].ContentInfo.Content.TranslatedName,
        };
    }
}

export default IbexaEmbedContentInlineUI;
