import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import WidgetToolbarRepository from '@ckeditor/ckeditor5-widget/src/widgettoolbarrepository';

const { Translator } = window;

class IbexaCustomTagsToolbar extends Plugin {
    static get requires() {
        return [WidgetToolbarRepository];
    }

    getSelectedCustomTagWidget(selection) {
        const viewElement = selection.getSelectedElement();
        const isCustomTag = viewElement?.hasClass('ibexa-custom-tag') && viewElement?.getAttribute('data-ezelement') === 'eztemplate';

        return isCustomTag ? viewElement : null;
    }

    afterInit() {
        const { editor } = this;
        const widgetToolbarRepository = editor.plugins.get(WidgetToolbarRepository);

        widgetToolbarRepository.register('customTags', {
            ariaLabel: Translator.trans(/*@Desc("Custom tag toolbar")*/ 'custom_tag.toolbar.label', {}, 'ck_editor'),
            items: editor.config.get('customTag.toolbar') || [],
            getRelatedElement: this.getSelectedCustomTagWidget,
        });
    }
}

export default IbexaCustomTagsToolbar;
