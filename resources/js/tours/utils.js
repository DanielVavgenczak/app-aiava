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

export const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

export function isElementVisible(element) {
    if (!element || !element.isConnected) return false;

    if (typeof element.checkVisibility === 'function') {
        return element.checkVisibility({
            visibilityProperty: true,
            contentVisibilityAuto: true
        });
    }

    const style = window.getComputedStyle(element);
    return style.display !== 'none'
        && style.visibility !== 'hidden'
        && element.offsetParent !== null;
}

// Resolve os candidatos na ordem dos seletores (não na ordem do documento),
// para que uma lista de seletores possa expressar preferência.
export function getFirstVisible(selector) {
    return selector
        .split(',')
        .map((part) => part.trim())
        .filter(Boolean)
        .flatMap((part) => Array.from(document.querySelectorAll(part)))
        .find(isElementVisible) ?? null;
}

export function waitForElement(selector, { timeout = 10000, visible = true, root = document.body } = {}) {
    return new Promise((resolve, reject) => {
        const find = () => {
            const element = visible
                ? getFirstVisible(selector)
                : document.querySelector(selector);

            return element ?? null;
        };

        const found = find();
        if (found) return resolve(found);

        let timer = null;
        const observer = new MutationObserver(() => {
            const element = find();
            if (!element) return;

            cleanup();
            resolve(element);
        });

        const cleanup = () => {
            observer.disconnect();
            clearTimeout(timer);
        };

        observer.observe(root, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style', 'open', 'hidden']
        });

        timer = setTimeout(() => {
            cleanup();
            reject(new Error(`Elemento "${selector}" não encontrado após ${timeout}ms`));
        }, timeout);
    });
}

/**
 * Cria um botão de ação customizável dentro do popover do Driver.js.
 *
 * O callback recebe um objeto com os helpers do tour para que a lógica
 * customizada possa compor o comportamento (avançar, finalizar, recarregar)
 * em vez de reimplementar adestroy/avanço na mão.
 *
 * @param {object}  popover    Elemento do popover atual (via onPopoverRender).
 * @param {object}  driverObj  Instância do driver do tour.
 * @param {object}  options
 * @param {string}  options.label        Texto do botão.
 * @param {Function} options.onClick     Callback async. Recebe { next, prev, finish, popover, driver, button }.
 * @param {Function} options.onFinish    Executado ANTES de o tour ser destruído.
 * @param {boolean} options.reopenOptions Reabre o modal de opções após finalizar. Padrão: true.
 * @param {boolean} options.removeNav    Remove os botões Próximo/Anterior. Padrão: true.
 * @param {boolean} options.style        Aplica o estilo padrão (cinza). Padrão: true.
 * @returns {HTMLButtonElement}
 */
export function addTourAction(popover, driverObj, {
    label = 'Encerrar tour',
    onClick = null,
    onFinish = null,
    reopenOptions = true,
    removeNav = true,
    style = true,
} = {}) {
    if (removeNav) {
        popover.nextButton?.remove();
        popover.previousButton?.remove();
    }

    // finish() é a saída padrão: destroy + reabrir modal de opções.
    const finish = async () => {
        if (onFinish) {
            try {
                await onFinish({ popover, driver: driverObj });
            } catch (error) {
                console.error(`Falha em onFinish do botão "${label}":`, error);
            }
        }

        await driverObj.destroy();
        Alpine.store('tourguide').openMai = reopenOptions;
    };

    const handleClick = async (event) => {
        const button = event?.currentTarget ?? null;

        if (!onClick) {
            await finish();
            return;
        }

        try {
            await onClick({
                button,
                popover,
                driver: driverObj,
                next: () => driverObj.moveNext(),
                prev: () => driverObj.movePrevious(),
                finish,
            });
        } catch (error) {
            console.error(`Falha no onClick do botão "${label}":`, error);
        }
    };

    const button = AddButtonActions(popover, label, handleClick);

    if (!style) {
        button.style.cssText = button.style.cssText
            .replace('border: 1px solid #ccc', 'border: 1px solid #2563eb')
            .replace('color: #2d2d2d', 'color: #2563eb');
    }

    return button;
}

/**
 * Atalho para o botão padrão "encerrar tour".
 * Mantém compatibilidade com as chamadas existentes em todo o projeto,
 * mas também repassa onClick/onFinish/removeNav/style para addTourAction,
 * para que nenhuma opção passada aqui seja descartada silenciosamente.
 */
export function addTourFinishButton(popover, driverObj, {
    label = 'Encerrar tour',
    reopenOptions = true,
    onClick = null,
    onFinish = null,
    removeNav = true,
    style = true,
} = {}) {
    return addTourAction(popover, driverObj, {
        label,
        reopenOptions,
        onClick,
        onFinish,
        removeNav,
        style,
    });
}
