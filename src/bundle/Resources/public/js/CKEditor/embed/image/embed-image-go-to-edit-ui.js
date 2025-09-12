import { Plugin, createDropdown, addListToDropdown, Model, Collection } from 'ckeditor5';

import IbexaButtonView from '../../common/button-view/button-view';
import { findContent } from '../../services/content-service';

const { Translator, ibexa } = window;

class IbexaEmbedImageGoToEditUI extends Plugin {
    constructor(props) {
        super(props);
    }

    editContent({ contentId, languageCode }) {
        const editEmbeddedItemForm = document.querySelector('[name="embedded_item_edit"]');

        const contentInfoInput = editEmbeddedItemForm.querySelector('[name="embedded_item_edit[content_info]"]');
        const languageInput = editEmbeddedItemForm.querySelector(`[name="embedded_item_edit[language]"][value="${languageCode}"]`);

        contentInfoInput.value = contentId;
        languageInput.click();

        editEmbeddedItemForm.submit();
    }

    init() {
        this.editor.ui.componentFactory.add('imageGoToEdit', (locale) => {
            const dropdownView = createDropdown(locale, IbexaButtonView);
            const { buttonView } = dropdownView;

            buttonView.set({
                label: Translator.trans(/*@Desc("Edit")*/ 'image_btn.edit.label', {}, 'ck_editor'),
                icon: ibexa.helpers.icon.getIconPath('edit'),
                tooltip: true,
                isEnabled: true,
            });

            this.listenTo(buttonView, 'execute', () => {
                const selectedElement = this.editor.model.document.selection.getSelectedElement();
                const contentId = selectedElement.getAttribute('contentId');
                const token = document.querySelector('meta[name="CSRF-Token"]').content;
                const siteaccess = document.querySelector('meta[name="SiteAccess"]').content;

                findContent({ token, siteaccess, contentId }, (contents) => {
                    const languages = contents[0].CurrentVersion.Version.VersionInfo.VersionTranslationInfo.Language;
                    const itemDefinitions = new Collection();

                    languages.forEach((language) => {
                        itemDefinitions.add({
                            type: 'button',
                            model: new Model({
                                label: window.ibexa.adminUiConfig.languages.mappings[language.languageCode].name,
                                value: language.languageCode,
                                withText: true,
                            }),
                        });
                    });

                    if (languages.length === 1) {
                        this.editContent({ contentId, languageCode: languages[0].languageCode });
                    } else {
                        dropdownView.panelView.children.clear();

                        addListToDropdown(dropdownView, itemDefinitions);

                        this.listenTo(dropdownView, 'execute', (event) => {
                            const languageCode = event.source.value;

                            this.editContent({ contentId, languageCode });
                        });

                        dropdownView.isOpen = true;
                    }
                });
            });

            return dropdownView;
        });
    }
}

export default IbexaEmbedImageGoToEditUI;
