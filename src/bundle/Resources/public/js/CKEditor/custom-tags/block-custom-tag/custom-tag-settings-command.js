import Command from '@ckeditor/ckeditor5-core/src/command';

const { ibexa } = window;

class IbexaCustomTagSettingsCommand extends Command {
    refresh() {
        const modelElement = this.editor.model.document.selection.getSelectedElement();
        const isCustomTag = modelElement?.name === 'customTag';
        const isEnabled =
            isCustomTag && !!Object.keys(ibexa.richText.customTags[modelElement.getAttribute('customTagName')].attributes).length;

        this.isEnabled = isEnabled;
    }
}

export default IbexaCustomTagSettingsCommand;
