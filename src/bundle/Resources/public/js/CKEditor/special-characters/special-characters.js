import SpecialCharacters from '@ckeditor/ckeditor5-special-characters/src/specialcharacters';

export default class IbexaSpecialCharacters extends SpecialCharacters {
    _updateGrid(currentGroupName, gridView) {
        super._updateGrid(currentGroupName, gridView);

        gridView.tiles.forEach((tile) => {
            tile.on('mouseover', (event) => {
                event.source.element.removeAttribute('data-original-title');
                event.source.element.removeAttribute('data-bs-original-title');
            });
        });
    }
}
