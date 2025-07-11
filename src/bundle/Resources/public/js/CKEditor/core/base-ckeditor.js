import 'regenerator-runtime';

import IbexaCharacterCounter from '../plugins/character-counter';
import IbexaElementsPath from '../plugins/elements-path';
import IbexaEmbed from '../embed/embed';
import IbexaCustomTags from '../custom-tags/custom-tags';
import IbexaCustomStylesInline from '../custom-styles/inline/custom-styles-inline';
import IbexaCustomAttributes from '../custom-attributes/custom-attributes';
import IbexaLink from '../link/link';
import IbexaAnchor from '../anchor/anchor';
import IbexaFormatted from '../formatted/formatted';
import IbexaMove from '../move/move';
import IbexaRemoveElement from '../remove-element/remove-element';
import IbexaBlockAlignment from '../block-alignment/block-alignment';
import IbexaUploadImage from '../upload-image/upload-image';

import {
    InlineEditor,
    Essentials,
    Alignment,
    Heading,
    ListProperties,
    Table,
    TableToolbar,
    Bold,
    Italic,
    Underline,
    Subscript,
    Superscript,
    Strikethrough,
    BlockQuote,
    ContextualBalloon,
} from 'ckeditor5';

const VIEWPORT_TOP_OFFSET = 102;
const VIEWPORT_TOP_OFFSET_DISTRACTION_FREE_MODE = 0;

(function (global, doc, ibexa) {
    class BaseRichText {
        constructor(config) {
            this.ezNamespace = 'http://ibexa.co/namespaces/ezpublish5/xhtml5/edit';
            this.xhtmlNamespace = 'http://www.w3.org/1999/xhtml';

            this.editor = null;
            this.viewportTopOffset = config?.viewportTopOffset ?? VIEWPORT_TOP_OFFSET;

            this.xhtmlify = this.xhtmlify.bind(this);
            this.getData = this.getData.bind(this);
        }

        getData() {
            const notTrimmedData = this.editor.getData({ trim: 'none' });
            const isDataEmpty = notTrimmedData === '<p>&nbsp;</p>';

            return isDataEmpty ? this.editor.getData() : notTrimmedData;
        }

        getHTMLDocumentFragment(data) {
            const fragment = doc.createDocumentFragment();
            const div = fragment.ownerDocument.createElement('div');
            const parsedHTML = new DOMParser().parseFromString(data, 'text/xml');
            const importChildNodes = (parent, element, skipElement) => {
                let i;
                let newElement;

                if (skipElement) {
                    newElement = parent;
                } else {
                    if (element.nodeType === Node.ELEMENT_NODE) {
                        newElement = parent.ownerDocument.createElement(element.localName);

                        for (i = 0; i !== element.attributes.length; i++) {
                            importChildNodes(newElement, element.attributes[i], false);
                        }

                        if (element.localName === 'a' && parent.dataset.ezelement === 'ezembed') {
                            element.setAttribute('data-cke-survive', '1');
                        }

                        parent.appendChild(newElement);
                    } else if (element.nodeType === Node.TEXT_NODE) {
                        parent.appendChild(parent.ownerDocument.createTextNode(element.nodeValue));

                        return;
                    } else if (element.nodeType === Node.ATTRIBUTE_NODE) {
                        parent.setAttribute(element.localName, element.value);

                        return;
                    } else {
                        return;
                    }
                }

                for (i = 0; i !== element.childNodes.length; i++) {
                    importChildNodes(newElement, element.childNodes[i], false);
                }
            };

            if (!parsedHTML || !parsedHTML.documentElement || parsedHTML.querySelector('parsererror')) {
                console.warn('Unable to parse the content of the RichText field');

                return fragment;
            }

            fragment.appendChild(div);

            importChildNodes(div, parsedHTML.documentElement, true);

            return fragment;
        }

        xhtmlify(data) {
            const xmlDocument = doc.implementation.createDocument(this.xhtmlNamespace, 'html', null);
            const htmlDoc = doc.implementation.createHTMLDocument('');
            const section = htmlDoc.createElement('section');
            let body = htmlDoc.createElement('body');

            section.innerHTML = data;
            body.appendChild(section);
            body = xmlDocument.importNode(body, true);
            xmlDocument.documentElement.appendChild(body);

            return body.innerHTML;
        }

        init(container) {
            const wrapper = this.getHTMLDocumentFragment(container.closest('.ibexa-data-source').querySelector('textarea').value);
            const section = wrapper.childNodes[0];
            const { toolbar, extraPlugins = [], extraConfig = {} } = window.ibexa.richText.CKEditor;
            let locale;
            try {
                locale = new Intl.Locale(doc.querySelector('meta[name="LanguageCode"]').content);
            } catch (e) {
                console.warn(
                    `Unsupported LanguageCode '${doc.querySelector('meta[name="LanguageCode"]').content}' - using fallback 'eng-GB'.`,
                );
                locale = new Intl.Locale('eng-GB');
            }
            const blockCustomStyles = Object.entries(ibexa.richText.customStyles)
                .filter(([, customStyleConfig]) => !customStyleConfig.inline)
                .map(([customStyleName, customStyleConfig]) => {
                    return {
                        model: customStyleName,
                        view: {
                            name: 'div',
                            attributes: {
                                'data-ezelement': 'eztemplate',
                                'data-eztype': 'style',
                                'data-ezname': customStyleName,
                            },
                        },
                        title: customStyleConfig.label,
                    };
                });

            if (!section.hasChildNodes()) {
                section.appendChild(doc.createElement('p'));
            }

            const config = {
                licenseKey: 'GPL',
                initialData: section.innerHTML,
                plugins: [
                    Essentials,
                    Heading,
                    Alignment,
                    ListProperties,
                    Table,
                    TableToolbar,
                    Bold,
                    Italic,
                    Underline,
                    Subscript,
                    Superscript,
                    Strikethrough,
                    BlockQuote,
                    ContextualBalloon,
                    IbexaCharacterCounter,
                    IbexaElementsPath,
                    IbexaEmbed,
                    IbexaCustomTags,
                    IbexaFormatted,
                    IbexaCustomStylesInline,
                    IbexaCustomAttributes,
                    IbexaLink,
                    IbexaAnchor,
                    IbexaMove,
                    IbexaRemoveElement,
                    IbexaBlockAlignment,
                    IbexaUploadImage,
                    ...extraPlugins,
                ],
                toolbar: {
                    items: toolbar,
                    shouldNotGroupWhenFull: true,
                },
                ui: {
                    viewportOffset: {
                        top: this.viewportTopOffset,
                    },
                },
                embedImage: {
                    toolbar: [
                        'imageVarations',
                        'ibexaBlockLeftAlignment',
                        'ibexaBlockCenterAlignment',
                        'ibexaBlockRightAlignment',
                        'ibexaRemoveElement',
                    ],
                },
                customTag: {
                    toolbar: [
                        'ibexaBlockLeftAlignment',
                        'ibexaBlockCenterAlignment',
                        'ibexaBlockRightAlignment',
                        '|',
                        'ibexaCustomTagSettings',
                        'ibexaRemoveElement',
                    ],
                },
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: { name: 'h1' }, title: 'Heading 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: { name: 'h2' }, title: 'Heading 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: { name: 'h3' }, title: 'Heading 3', class: 'ck-heading_heading3' },
                        { model: 'heading4', view: { name: 'h4' }, title: 'Heading 4', class: 'ck-heading_heading4' },
                        { model: 'heading5', view: { name: 'h5' }, title: 'Heading 5', class: 'ck-heading_heading5' },
                        { model: 'heading6', view: { name: 'h6' }, title: 'Heading 6', class: 'ck-heading_heading6' },
                        ...blockCustomStyles,
                    ],
                },
                table: {
                    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
                },
                language: {
                    content: locale.language,
                },
                ...extraConfig,
            };

            const customEvent = new CustomEvent('ibexa-ckeditor:configure', {
                detail: { container, config },
            });

            doc.body.dispatchEvent(customEvent);

            InlineEditor.create(container, config).then((editor) => {
                this.editor = editor;

                const editableElement = this.editor.editing.view.getDomRoot();
                const editorToolbarPanelInstance = this.editor.ui.view.panel;
                const initialData = this.getData();
                const updateInput = (data, shouldFireInputEvent = true) => {
                    const textarea = container.closest('.ibexa-data-source').querySelector('textarea');

                    textarea.value = this.xhtmlify(data).replace(this.xhtmlNamespace, this.ezNamespace);

                    if (shouldFireInputEvent) {
                        textarea.dispatchEvent(new Event('input'));
                    }
                };
                const setDataSourceHeight = (toolbarNode, fieldEditNode) => {
                    const dataSourceNode = fieldEditNode.querySelector('.ibexa-data-source');
                    const { height: toolbarHeight } = toolbarNode.getBoundingClientRect();
                    const { top: dataSourceTop } = dataSourceNode.getBoundingClientRect();

                    if (toolbarHeight > dataSourceTop) {
                        const positionDiff = toolbarHeight - dataSourceTop;

                        dataSourceNode.style.height = `calc(100% - ${positionDiff}px)`;
                        dataSourceNode.style.marginTop = `${positionDiff}px`;
                    }
                };
                const clearDataSourceHeight = () => {
                    const fieldEditNode = editableElement.closest('.ibexa-field-edit');
                    const dataSourceNode = fieldEditNode.querySelector('.ibexa-data-source');

                    dataSourceNode.style.removeProperty('height');
                    dataSourceNode.style.removeProperty('margin-top');
                };
                const setToolbarMaxWidth = (toolbarNode, fieldEditNode) => {
                    const distractionFreeModeControlNodeBtn = fieldEditNode.querySelector(
                        '.ibexa-field-edit__distraction-free-mode-control-container .ibexa-field-edit__distraction-free-mode-btns',
                    );

                    const dataSourceNode = fieldEditNode.querySelector('.ibexa-data-source');
                    const { offsetWidth: dataSourceNodeWidth } = dataSourceNode;
                    let toolbarNodeMaxWidth = dataSourceNodeWidth;

                    if (distractionFreeModeControlNodeBtn !== null) {
                        const { offsetWidth: distractionFreeModeControlNodeBtnWidth } = distractionFreeModeControlNodeBtn;

                        toolbarNodeMaxWidth = dataSourceNodeWidth - distractionFreeModeControlNodeBtnWidth;
                    }

                    toolbarNode.style.maxWidth = `${toolbarNodeMaxWidth}px`;
                };

                updateInput(initialData, false);

                this.editor.model.document.on('change:data', () => {
                    const data = this.getData();

                    updateInput(data);
                });

                this.editor.on('set:distractionFreeModeActive', ({ source: eventEditorInstance }, name, value) => {
                    const { ui: eventEditorUiInstance } = eventEditorInstance;
                    const { panel: eventEditorToolbarPanelInstance } = eventEditorUiInstance.view;
                    const toolbarPanelNode = eventEditorToolbarPanelInstance.element;
                    const toolbarPanelsContainer = toolbarPanelNode.closest('.ck-body');

                    eventEditorUiInstance.viewportOffset = {
                        top: value ? VIEWPORT_TOP_OFFSET_DISTRACTION_FREE_MODE : this.viewportTopOffset,
                    };

                    toolbarPanelsContainer.classList.toggle('ck-body--distraction-free-mode-active');

                    if (!value) {
                        eventEditorToolbarPanelInstance.hide();
                        clearDataSourceHeight();
                    }
                });

                editorToolbarPanelInstance.on('change:isVisible', ({ source: eventBalloonPanelViewInstance }) => {
                    const fieldEditNode = editableElement.closest('.ibexa-field-edit');

                    setToolbarMaxWidth(eventBalloonPanelViewInstance.element, fieldEditNode);

                    if (editor?.distractionFreeModeActive) {
                        setDataSourceHeight(eventBalloonPanelViewInstance.element, fieldEditNode);
                    }
                });
            });
        }
    }

    ibexa.BaseRichText = BaseRichText;
})(window, window.document, window.ibexa);
