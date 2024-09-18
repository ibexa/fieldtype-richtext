const setPanelContentMaxHeight = (balloonView) => {
    const HEADER_HEIGHT = 90;
    const MIN_HEIGHT_VALUE = 220;
    const { innerHeight: windowHeight } = window;
    const { top: panelTopPosition, element: panelNode } = balloonView;
    const panelHeader = panelNode.querySelector('.ibexa-custom-tag-panel-header');
    const panelContent = panelNode.querySelector('.ibexa-custom-tag-panel-content');
    const panelFooter = panelNode.querySelector('.ibexa-custom-tag-panel-footer');

    if (!panelContent) {
        return;
    }

    const panelHeaderHeight = panelHeader?.offsetHeight ?? 0;
    const panelFooterHeight = panelFooter?.offsetHeight ?? 0;
    const isPanelOverTopWindowEdge = panelTopPosition - HEADER_HEIGHT < 0;
    const maxHeightValue = isPanelOverTopWindowEdge
        ? panelContent.offsetHeight - Math.abs(panelTopPosition)
        : windowHeight - panelTopPosition - panelHeaderHeight - panelFooterHeight;
    const panelMaxHeight = maxHeightValue < MIN_HEIGHT_VALUE ? MIN_HEIGHT_VALUE : maxHeightValue;

    panelContent.style.maxHeight = `${panelMaxHeight}px`;
};

export { setPanelContentMaxHeight };
