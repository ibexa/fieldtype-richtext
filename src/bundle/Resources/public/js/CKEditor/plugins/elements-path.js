import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

const { Translator } = window;
class IbexaElementsPath extends Plugin {
    constructor(props) {
        super(props);

        this.elementsPathWrapper = null;

        this.updatePath = this.updatePath.bind(this);
    }

    addListItem(element) {
        const label = Translator.trans(/*@Desc("list")*/ 'elements_path.list.label', {}, 'ck_editor');
        const pathItem = `<li class="ibexa-elements-path__item">${label}</li>`;
        const container = document.createElement('ul');

        container.insertAdjacentHTML('beforeend', pathItem);

        const listItemNode = container.querySelector('li');

        listItemNode.addEventListener(
            'click',
            () => {
                let firstElement = element;
                let lastElement = element;

                while (firstElement?.previousSibling?.name === 'listItem') {
                    firstElement = firstElement.previousSibling;
                }

                while (lastElement?.nextSibling?.name === 'listItem') {
                    lastElement = lastElement.nextSibling;
                }

                const range = this.editor.model.createRange(
                    this.editor.model.createPositionBefore(firstElement),
                    this.editor.model.createPositionAfter(lastElement),
                );

                this.editor.isListSelected = true;
                this.editor.model.change((writer) => writer.setSelection(range));
                this.editor.focus();

                this.editor.model.document.selection.once('change', () => {
                    this.editor.isListSelected = false;
                });
            },
            false,
        );

        this.elementsPathWrapper.append(listItemNode);
    }

    updatePath(element) {
        if (element.name === '$root') {
            return;
        }

        if (element.name === 'listItem') {
            this.addListItem(element);
        }

        const pathItem = `<li class="ibexa-elements-path__item">${element.name}</li>`;
        const container = document.createElement('ul');

        container.insertAdjacentHTML('beforeend', pathItem);

        const listItemNode = container.querySelector('li');

        listItemNode.addEventListener(
            'click',
            () => {
                this.editor.model.change((writer) => writer.setSelection(element, 'in'));
                this.editor.focus();
            },
            false,
        );

        this.elementsPathWrapper.append(listItemNode);
    }

    init() {
        this.elementsPathWrapper = this.editor.sourceElement.parentElement.querySelector('.ibexa-elements-path');

        this.editor.model.document.selection.on('change:range', () => {
            this.elementsPathWrapper.innerHTML = '';

            this.editor.model.document.selection.getFirstPosition().getAncestors().forEach(this.updatePath);
        });
    }
}

export default IbexaElementsPath;
