import { Command } from 'ckeditor5';

const { ibexa } = window;

class IbexaCustomStyleInlineCommand extends Command {
    refresh() {
        const { selection } = this.editor.model.document;
        const { customStyles } = ibexa.richText;
        const selectedCustomStyle = Object.keys(customStyles).find(
            (customStyleIdentifier) => selection.hasAttribute(customStyleIdentifier) && customStyles[customStyleIdentifier].inline,
        );

        this.value = selectedCustomStyle;
    }
}

export default IbexaCustomStyleInlineCommand;
