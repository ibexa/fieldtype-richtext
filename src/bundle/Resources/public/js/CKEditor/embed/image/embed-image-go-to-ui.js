import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaButtonView from '../../common/button-view/button-view';

const { Translator, Routing, ibexa } = window;
const metaLanguageCode = document.querySelector('meta[name="LanguageCode"]')?.content;
const previewLanguageCode = metaLanguageCode ?? ibexa.adminUiConfig.languages.priority[0];

class IbexaEmbedImageGoToUI extends Plugin {
    constructor(props) {
        super(props);
    }

    init() {
        this.editor.ui.componentFactory.add('imageGoTo', (locale) => {
            const buttonView = new IbexaButtonView(locale);

            buttonView.set({
                label: Translator.trans(/*@Desc("Go to image")*/ 'image_btn.go_to.label', {}, 'ck_editor'),
                icon: ibexa.helpers.icon.getIconPath('file-arrow'),
                tooltip: true,
            });

            this.listenTo(buttonView, 'execute', () => {
                const modelElement = this.editor.model.document.selection.getSelectedElement();

                const route = Routing.generate('ibexa.content.translation.view', {
                    contentId: modelElement.getAttribute('contentId'),
                    languageCode: previewLanguageCode,
                });

                window.open(route);
            });

            return buttonView;
        });
    }
}

export default IbexaEmbedImageGoToUI;
