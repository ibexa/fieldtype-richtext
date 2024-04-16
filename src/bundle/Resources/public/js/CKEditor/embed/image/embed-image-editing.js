import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import { toWidget } from '@ckeditor/ckeditor5-widget/src/utils';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';

import IbexaEmbedImageCommand from './embed-image-command';

import { findContent } from '../../services/content-service';

class IbexaEmbedImageEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    constructor(props) {
        super(props);

        this.loadImagePreview = this.loadImagePreview.bind(this);
        this.loadImageVariation = this.loadImageVariation.bind(this);
        this.getSetting = this.getSetting.bind(this);
    }

    loadImagePreview(modelElement) {
        const contentId = modelElement.getAttribute('contentId');
        const token = document.querySelector('meta[name="CSRF-Token"]').content;
        const siteaccess = document.querySelector('meta[name="SiteAccess"]').content;

        findContent({ token, siteaccess, contentId }, (contents) => {
            const fields = contents[0].CurrentVersion.Version.Fields.field;
            const fieldImage = fields.find((field) => field.fieldTypeIdentifier === 'ezimage');
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

        schema.register('embedImage', {
            isObject: true,
            allowWhere: '$block',
            allowAttributes: ['contentId', 'size', 'ibexaLinkHref', 'ibexaLinkTitle', 'ibexaLinkTarget'],
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
                        class: 'ibexa-embed-type-image',
                    });

                    this.loadImagePreview(modelElement);

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
                    class: 'ibexa-embed-type-image',
                });
                const config = downcastWriter.createUIElement('span', { 'data-ezelement': 'ezconfig' }, function (domDocument) {
                    const domElement = this.toDomElement(domDocument);

                    // note: do not reformat - configuration value for image embeds cannot contain whitespaces
                    // eslint-disable-next-line
                    domElement.innerHTML = `<span data-ezelement="ezvalue" data-ezvalue-key="size">${modelElement.getAttribute('size')}</span>`; // prettier-ignore

                    return domElement;
                });
                const linkHref = modelElement.getAttribute('ibexaLinkHref');

                downcastWriter.remove(downcastWriter.createRangeIn(container));
                downcastWriter.insert(downcastWriter.createPositionAt(container, 0), config);

                if (linkHref) {
                    const link = downcastWriter.createUIElement('a', {
                        'data-ezelement': 'ezlink',
                        href: linkHref,
                        title: modelElement.getAttribute('ibexaLinkTitle'),
                        target: modelElement.getAttribute('ibexaLinkTarget'),
                    });

                    consumable.consume(modelElement, 'attribute:ibexaLinkHref');
                    consumable.consume(modelElement, 'attribute:ibexaLinkTitle');
                    consumable.consume(modelElement, 'attribute:ibexaLinkTarget');

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
                classes: 'ibexa-embed-type-image',
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
