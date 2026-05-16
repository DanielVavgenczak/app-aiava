import { configDriver, AddButtonActions } from "../../utils";

export async function ContinueUploadBlock(modules, dataId) {
  // Função utilitária para esperar um elemento estar presente no DOM
  const waitForElement = (selector, timeout = 5000) => {
    return new Promise((resolve, reject) => {
      const element = document.querySelector(selector);
      if (element) {
        return resolve(element);
      }

      const observer = new MutationObserver((mutations, obs) => {
        const element = document.querySelector(selector);
        if (element) {
          obs.disconnect();
          resolve(element);
        }
      });

      observer.observe(document.body, {
        childList: true,
        subtree: true
      });

      // Timeout de segurança
      setTimeout(() => {
        observer.disconnect();
        reject(new Error(`Elemento ${selector} não encontrado após ${timeout}ms`));
      }, timeout);
    });
  };

  const steps = [
    {
      element: `#accordion-module-${dataId}`,
      popover: {
        description: `Vou abrir essa aula`,
        side: "bottom",
        align: "start",
        showButtons: [""],
      },
      onHighlighted: async (el) => {
        await new Promise((resolve) => setTimeout(resolve, 1000));
        await updateOnedDriverObj.moveNext();
      },
    },
    {
      element: `#open-accordion-block-${dataId}`,
      popover: {
        description: `Vou clicar e abrir para listar os blocos`,
        side: "bottom",
        align: "start",
        showButtons: [""],
      },
      onHighlighted: async (el) => {
        await new Promise((resolve) => setTimeout(resolve, 1000));
        const btnOpenAccordion = document.querySelector(`#open-accordion-block-${dataId}`);
        await btnOpenAccordion.click();

        // Aguarda o accordion expandir
        await new Promise((resolve) => setTimeout(resolve, 500));
        await updateOnedDriverObj.moveNext();
      },
    },
    {
      element: `#block-open-new-block-${dataId}`,
      popover: {
        description: `Vou clicar aqui para abrir a opção de adicionar um bloco`,
        side: "right",
        align: "center",
        showButtons: [""],
      },
      onHighlighted: async (el) => {
        await new Promise((resolve) => setTimeout(resolve, 1000));
        await el.click();

        // Aguarda o menu de opções abrir
        await new Promise((resolve) => setTimeout(resolve, 500));
        await updateOnedDriverObj.moveNext();
      },
    },
    {
      element: `#browser-file-${dataId}`,
      popover: {
        description: `Clique no ícone de vídeo para adicionar um novo bloco`,
        side: "top",
        align: "start",
        // showButtons: [""],
        onPopoverRender: (popover, { config, state }) => {
              const actions = AddButtonActions(
                popover,
                "Voltar ao início",
              );
              actions.addEventListener("click", () => {
                Livewire.dispatch("open-modal-module", {
                  open: false,
                });
                driverObj.destroy();
                store.openMai = true;
              });
            }
      },
      onHighlighted: async (el) => {
        await new Promise((resolve) => setTimeout(resolve, 1500));

        // Clica no ícone de vídeo
        await el.click();

        // 🔍 VALIDAÇÃO: Aguarda o modal de upload ser renderizado
        try {
          console.log("Aguardando modal de upload renderizar...");

          // Espera pelo elemento do modal ou container de upload
          const modalSelector = `#upload-video-modal-${dataId}`; // Ajuste para o seletor correto
          const uploadContainerSelector = `#video-upload-container-${dataId}`; // Ajuste conforme necessário

          // Tenta encontrar qualquer um dos elementos que indica que o modal está pronto
          const modalElement = await Promise.race([
            waitForElement(modalSelector, 8000),
            waitForElement(uploadContainerSelector, 8000),
            waitForElement(`#temp-video-preview-${dataId}`, 8000)
          ]);

          console.log("Modal renderizado com sucesso:", modalElement);

          // Aguarda um pouco mais para garantir que todo o conteúdo do modal carregou
          await new Promise((resolve) => setTimeout(resolve, 1000));

          // Verifica se o elemento de preview existe
          const videoElement = document.querySelector(`#temp-video-preview-${dataId}`);

          if (!videoElement) {
            console.error("Elemento de preview não encontrado mesmo após modal renderizar");
            // Fallback: tenta avançar mesmo assim
            await updateOnedDriverObj.moveNext();
            return;
          }

          // Verifica se já tem src definido
          if (videoElement.src && videoElement.src !== "") {
            console.log("Vídeo já selecionado - avançando");
            await updateOnedDriverObj.moveNext();
            return;
          }

          // Cria um observador para detectar quando o src for alterado
          const observer = new MutationObserver(async (mutations) => {
            for (const mutation of mutations) {
              if (
                mutation.type === "attributes" &&
                mutation.attributeName === "src"
              ) {
                if (
                  videoElement.src &&
                  videoElement.src !== ""
                ) {
                  console.log("Vídeo selecionado detectado - avançando");
                  observer.disconnect();
                  await updateOnedDriverObj.moveNext();
                }
              }
            }
          });

          // Configura o observador
          observer.observe(videoElement, {
            attributes: true,
            attributeFilter: ["src"],
          });

          // Timeout de segurança
          setTimeout(() => {
            observer.disconnect();
            console.warn("Timeout aguardando seleção de vídeo");
          }, 30000);

        } catch (error) {
          console.error("Erro ao aguardar modal:", error);
          // Fallback: tenta avançar após timeout
          await updateOnedDriverObj.moveNext();
        }
      },
    },
    {
      element: `#temp-video-preview-${dataId}`,
      popover: {
        description: `Caso o vídeo esteja correto, clique em "Próximo"`,
        side: "top",
        align: "start",
        onPopoverRender: (popover, { config, state }) => {
          const actions = AddButtonActions(popover, "Voltar ao início");
          actions.addEventListener("click", async () => {
            await updateOnedDriverObj.destroy();
            store.openMai = true;
          });
        },
      },
      onNextClick: async () => {
        console.log("Avançando para enviar vídeo");
      },
    },
    {
      element: `#temp-preview-actions-${dataId}`,
      popover: {
        description: `Clique em 'Enviar' para enviar o vídeo`,
        side: "top",
        align: "start",
        showButtons: [""]
      },
      onHighlighted: async (el) => {
        const btnSendVideoBlock = el.querySelector(
          `#lesson-send-video-block-content-${dataId}`
        );
        if (!btnSendVideoBlock) return;

        btnSendVideoBlock.addEventListener("click", async () => {
          await updateOnedDriverObj.moveNext();
        });
      },
    },
    {
      element: `#progress-bar-container-${dataId}`,
      popover: {
        description: "Aguarde enquanto o vídeo é enviado",
        side: "top",
        align: "start",
        showButtons: [""]
      },
      onHighlighted: async (el) => {
        await new Promise((resolve) => setTimeout(resolve, 2000));

        let successMessage = document.getElementById(`success-message-${dataId}`);

        // Aguarda o elemento de sucesso aparecer (caso ainda não exista)
        if (!successMessage) {
          try {
            successMessage = await waitForElement(`#success-message-${dataId}`, 10000);
          } catch (error) {
            console.error("Elemento de sucesso não encontrado:", error);
            // Se não encontrar o elemento de sucesso, aguarda um tempo e finaliza
            await new Promise((resolve) => setTimeout(resolve, 3000));
            await updateOnedDriverObj.destroy();
            return;
          }
        }

        await new Promise((resolve) => setTimeout(resolve, 2000));

        const observer = new MutationObserver((mutations) => {
          mutations.forEach((mutation) => {
            if (successMessage.style.display !== 'none') {
              if (updateOnedDriverObj) {
                updateOnedDriverObj.destroy();
              }
              observer.disconnect();
            }
          });
        });

        observer.observe(successMessage, {
          attributes: true,
          attributeFilter: ['style', 'class'],
          childList: true,
          subtree: true
        });
      },
    },
  ];

  // Cria um novo tour com os passos atualizados
  const updateOnedDriverObj = drivertour({
    ...configDriver,
    steps: [...steps],
  });

  await updateOnedDriverObj.drive();
  return;
}
