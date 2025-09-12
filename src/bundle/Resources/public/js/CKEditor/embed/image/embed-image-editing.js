import { Plugin, toWidget, Widget } from 'ckeditor5';

import IbexaEmbedImageCommand from './embed-image-command';

import { findContent } from '../../services/content-service';
import { getCustomClassesConfig, addPredefinedClassToConfig } from '../../custom-attributes/helpers/config-helper';

const CONTAINER_CLASS = 'ibexa-embed-type-image';

class IbexaEmbedImageEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    constructor(props) {
        super(props);

        this.loadImagePreview = this.loadImagePreview.bind(this);
        this.loadImageVariation = this.loadImageVariation.bind(this);
        this.getSetting = this.getSetting.bind(this);

        addPredefinedClassToConfig('embedImage', CONTAINER_CLASS);
    }

    loadImagePreview(modelElement) {
        const contentId = modelElement.getAttribute('contentId');
        const token = document.querySelector('meta[name="CSRF-Token"]').content;
        const siteaccess = document.querySelector('meta[name="SiteAccess"]').content;

        findContent({ token, siteaccess, contentId }, (contents) => {
            const fields = contents[0].CurrentVersion.Version.Fields.field;
            const fieldImage = fields.find((field) => field.fieldTypeIdentifier === 'ibexa_image');
            const size = modelElement.getAttribute('size');
            const variationHref = fieldImage.fieldValue.variations[size].href;

            this.loadImageVariation(modelElement, variationHref);
        });
    }

    loadImageVariation(modelElement, variationHref) {
        const token = document.querySelector('meta[name="CSRF-Token"]').content;
        const siteaccess = document.querySelector('meta[name="SiteAccess"]').content;
        const request = new Request(variationHref, {
            method: 'GET',
            headers: {
                Accept: 'application/vnd.ibexa.api.ContentImageVariation+json',
                'X-Siteaccess': siteaccess,
                'X-CSRF-Token': token,
            },
            credentials: 'same-origin',
            mode: 'same-origin',
        });

        fetch(request)
            .then((response) => response.json())
            .then((imageData) => {
                this.editor.model.change((writer) => {
                    writer.setAttribute('previewUrl', imageData.ContentImageVariation.uri, modelElement);
                });
            })
            .catch(window.ibexa.helpers.notification.showErrorNotification);
    }

    getSetting(viewElement, settingName) {
        const children = viewElement.getChildren();

        for (const child of children) {
            if (child.is('element') && child.getAttribute('data-ezelement') === settingName) {
                return child;
            }
        }
    }

    defineSchema() {
        const { schema } = this.editor.model;
        const customClassesConfig = getCustomClassesConfig();
        const allowedAttributes = ['contentId', 'size', 'ibexaLinkHref', 'ibexaLinkTitle', 'ibexaLinkTarget'];

        if (customClassesConfig.link) {
            allowedAttributes.push('ibexaLinkClasses');
        }

        schema.register('embedImage', {
            isObject: true,
            allowWhere: '$block',
            allowAttributes: allowedAttributes,
        });
    }

    defineConverters() {
        const { conversion } = this.editor;

        conversion
            .for('editingDowncast')
            .elementToElement({
                model: 'embedImage',
                view: (modelElement, { writer: downcastWriter }) => {
                    const container = downcastWriter.createContainerElement('div', {
                        'data-ezelement': 'ezembed',
                        'data-ezview': 'embed',
                        class: CONTAINER_CLASS,
                    });
                    const emdedItemsUpdateChannel = new BroadcastChannel('ibexa-emded-item-live-update');

                    this.loadImagePreview(modelElement);

                    emdedItemsUpdateChannel.addEventListener('message', () => {
                        this.loadImagePreview(modelElement);
                    });

                    return toWidget(container, downcastWriter);
                },
            })
            .add((dispatcher) =>
                dispatcher.on('attribute:previewUrl', (event, data, conversionApi) => {
                    const downcastWriter = conversionApi.writer;
                    const modelElement = data.item;

                    if (!modelElement.getAttribute('previewUrl')) {
                        return;
                    }

                    const viewElement = conversionApi.mapper.toViewElement(modelElement);
                    const preview = downcastWriter.createUIElement(
                        'img',
                        { src: modelElement.getAttribute('previewUrl') },
                        function (domDocument) {
                            const domElement = this.toDomElement(domDocument);

                            return domElement;
                        },
                    );

                    downcastWriter.remove(downcastWriter.createRangeIn(viewElement));
                    downcastWriter.insert(downcastWriter.createPositionAt(viewElement, 0), preview);
                }),
            )
            .add((dispatcher) =>
                dispatcher.on('attribute:size', (event, data) => {
                    const modelElement = data.item;

                    this.loadImagePreview(modelElement);
                }),
            );

        conversion.for('dataDowncast').elementToElement({
            model: 'embedImage',
            view: (modelElement, { writer: downcastWriter, consumable }) => {
                const container = downcastWriter.createContainerElement('div', {
                    'data-href': `ezcontent://${modelElement.getAttribute('contentId')}`,
                    'data-ezelement': 'ezembed',
                    'data-ezview': 'embed',
                    class: CONTAINER_CLASS,
                });
                const config = downcastWriter.createUIElement('span', { 'data-ezelement': 'ezconfig' }, function (domDocument) {
                    const domElement = this.toDomElement(domDocument);

                    // note: do not reformat - configuration value for image embeds cannot contain whitespaces

                    domElement.innerHTML = `<span data-ezelement="ezvalue" data-ezvalue-key="size">${modelElement.getAttribute('size')}</span>`; // prettier-ignore

                    return domElement;
                });
                const linkHref = modelElement.getAttribute('ibexaLinkHref');

                downcastWriter.remove(downcastWriter.createRangeIn(container));
                downcastWriter.insert(downcastWriter.createPositionAt(container, 0), config);

                if (linkHref) {
                    const linkClasses = modelElement.getAttribute('ibexaLinkClasses');
                    const linkAttributes = {
                        'data-ezelement': 'ezlink',
                        href: linkHref,
                        title: modelElement.getAttribute('ibexaLinkTitle'),
                        target: modelElement.getAttribute('ibexaLinkTarget'),
                    };

                    if (linkClasses) {
                        linkAttributes.class = linkClasses;
                    }

                    const link = downcastWriter.createUIElement('a', linkAttributes);

                    consumable.consume(modelElement, 'attribute:ibexaLinkHref');
                    consumable.consume(modelElement, 'attribute:ibexaLinkTitle');
                    consumable.consume(modelElement, 'attribute:ibexaLinkTarget');

                    if (linkClasses) {
                        consumable.consume(modelElement, 'attribute:ibexaLinkClasses');
                    }

                    downcastWriter.insert(downcastWriter.createPositionAt(container, 'end'), link);
                }

                return container;
            },
        });

        conversion.for('upcast').elementToElement({
            view: {
                name: 'div',
                attributes: {
                    'data-ezelement': 'ezembed',
                },
                classes: CONTAINER_CLASS,
            },
            model: (viewElement, { writer: upcastWriter }) => {
                const href = viewElement.getAttribute('data-href');
                const contentId = href.replace('ezcontent://', '');
                const size = this.getSetting(viewElement, 'ezconfig').getChild(0).getChild(0).data;
                const link = this.getSetting(viewElement, 'ezlink');
                const modelElement = upcastWriter.createElement('embedImage', { contentId, size });

                if (link?.is('element', 'a')) {
                    upcastWriter.setAttribute('ibexaLinkHref', link.getAttribute('href'), modelElement);
                    upcastWriter.setAttribute('ibexaLinkTitle', link.getAttribute('title') ?? '', modelElement);
                    upcastWriter.setAttribute('ibexaLinkTarget', link.getAttribute('target') ?? '', modelElement);

                    if (link.getAttribute('class')) {
                        upcastWriter.setAttribute('ibexaLinkClasses', link.getAttribute('class'), modelElement);
                    }
                }

                return modelElement;
            },
        });
    }

    init() {
        this.defineSchema();
        this.defineConverters();

        this.editor.commands.add('insertIbexaEmbedImage', new IbexaEmbedImageCommand(this.editor));
    }
}

export default IbexaEmbedImageEditing;
