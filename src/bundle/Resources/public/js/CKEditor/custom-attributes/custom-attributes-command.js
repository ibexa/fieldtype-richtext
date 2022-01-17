import Command from '@ckeditor/ckeditor5-core/src/command';

class IbexaCustomAttributesCommand extends Command {
    refresh() {
        const parentElement = this.editor.model.document.selection.getFirstPosition().parent;
        const isEnabled =
            window.ibexa.richText.alloyEditor.attributes[parentElement.name] || window.ibexa.richText.alloyEditor.classes[parentElement.name];

        this.isEnabled = !!isEnabled;
    }
}

export default IbexaCustomAttributesCommand;
