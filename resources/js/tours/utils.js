// Função para adicionar botões personalizados no popover
export const configDriver = {
    animate: true,
    opacity: 0.75,
    padding: 10,
    nextBtnText: "Próximo",
    prevBtnText: "Anterior",
    doneBtnText:"Concluir",
    allowClose: false,
}
export const defaultDriverConfig = {
    animate: true,
    opacity: 0.75,
    padding: 10,
    allowClose: true,
    nextBtnText: 'Próximo',
    prevBtnText: 'Anterior',
    doneBtnText: 'Concluir',
    closeBtnText: 'Fechar',
    showProgress: false
};
export function AddButtonActions(popover, title, callback) {
    const button = document.createElement('button');
    button.innerText = title;
    button.style.cssText = `
        float: left;
        padding: 10px;
        border-radius: 7px;
        border: 1px solid #ccc;
        color: #2d2d2d;
        text-transform: capitalize;
        font-weight: 400;
        text-shadow: none;
    `;
    if (popover.footerButtons.firstChild) {
        popover.footerButtons.insertBefore(button, popover.footerButtons.firstChild);
    } else {
        popover.footerButtons.appendChild(button);
    }
    if (callback) button.addEventListener('click', callback);
    return button;
}
export function startDriverTour(steps, customConfig = {}) {
    const driverObj = drivertour({
        ...defaultDriverConfig,
        ...customConfig,
        steps: steps.map(step => ({
            ...step,
            showButtons: step.onHighlighted ? ['close'] : ['next', 'prev', 'close']
        }))
    });
    driverObj.drive();
    return driverObj;
}
