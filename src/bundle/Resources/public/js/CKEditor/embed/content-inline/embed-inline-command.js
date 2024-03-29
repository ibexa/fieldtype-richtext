import Command from '@ckeditor/ckeditor5-core/src/command';

class IbexaEmbedContentInlineCommand extends Command {
    execute(contentData) {
        this.editor.model.change((writer) => {
            this.editor.model.insertContent(this.createEmbed(writer, contentData));
        });
    }

    createEmbed(writer, { contentId, contentName, locationId, languageCodes }) {
        return writer.createElement('embedInline', { contentId, contentName, locationId, languageCodes });
    }
}

export default IbexaEmbedContentInlineCommand;
