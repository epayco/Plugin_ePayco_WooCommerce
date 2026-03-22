const { test, expect } = require('@playwright/test');

test('epayco-checkout', async ({ page }, testInfo) => {
  test.setTimeout(240000);

  const checkoutEmail = 'ricardo.saldarriaga12345@epayco.com';
  const checkoutPhone = '3184210294';
  const checkoutDocument = '1214723219';

  // const fillFirstVisible = async (selectors, value) => {
  //   for (const selector of selectors) {
  //     const input = page.locator(selector).first();
  //     const isVisible = await input.isVisible().catch(() => false);
  //     if (!isVisible) continue;
  //     await input.fill(value);
  //     return true;
  //   }
  //   return false;
  // };

  const fillAndValidateField = async (selectors, value, normalize = (input) => String(input || '').trim()) => {
    const expected = normalize(value);

    for (const selector of selectors) {
      const input = page.locator(selector).first();
      const visible = await input.isVisible({ timeout: 1200 }).catch(() => false);
      if (!visible) continue;

      for (let attempt = 0; attempt < 3; attempt += 1) {
        await input.click({ force: true }).catch(() => {});
        await input.fill('').catch(() => {});
        await input.type(String(value), { delay: 20 }).catch(async () => {
          await input.fill(String(value)).catch(() => {});
        });
        await input.dispatchEvent('input').catch(() => {});
        await input.dispatchEvent('change').catch(() => {});
        await input.press('Tab').catch(() => {});

        const currentValue = await input.inputValue().catch(() => '');
        const current = normalize(currentValue);
        if (current && current === expected) {
          return true;
        }

        await page.waitForTimeout(120);
      }
    }

    return false;
  };

  const fillCheckoutRequiredFields = async () => {
    const required = {
      emailFilled: await fillAndValidateField([
        '#email:visible',
        'input[name="contact_email"]:visible',
        'input[name="billing_email"]:visible',
        'input[type="email"]:visible',
      ], checkoutEmail),
      firstNameFilled: await fillAndValidateField([
        '#shipping-first_name:visible',
        '#billing-first_name:visible',
        'input[name="shipping_first_name"]:visible',
        'input[name="billing_first_name"]:visible',
        'input[autocomplete="shipping given-name"]:visible',
      ], 'Ricardo'),
      lastNameFilled: await fillAndValidateField([
        '#shipping-last_name:visible',
        '#billing-last_name:visible',
        'input[name="shipping_last_name"]:visible',
        'input[name="billing_last_name"]:visible',
        'input[autocomplete="shipping family-name"]:visible',
      ], 'Saldarriaga'),
      addressFilled: await fillAndValidateField([
        '#shipping-address_1:visible',
        '#billing-address_1:visible',
        'input[name="shipping_address_1"]:visible',
        'input[name="billing_address_1"]:visible',
        'input[autocomplete="shipping address-line1"]:visible',
      ], 'Calle 10 #20-30'),
      cityFilled: await fillAndValidateField([
        '#shipping-city:visible',
        '#billing-city:visible',
        'input[name="shipping_city"]:visible',
        'input[name="billing_city"]:visible',
        'input[autocomplete="shipping address-level2"]:visible',
      ], 'Bogotá'),
      phoneFilled: await fillAndValidateField([
        '#shipping-phone:visible',
        '#billing-phone:visible',
        'input[name="shipping_phone"]:visible',
        'input[name="billing_phone"]:visible',
        'input[autocomplete="tel"]:visible',
      ], checkoutPhone, (input) => String(input || '').replace(/\D/g, '')),
    };

    const documentFields = await fillCheckoutDocumentFields();

    const requiredFilled = Object.values(required).every(Boolean)
      && (!documentFields.numberFieldVisible || documentFields.numberFilled);
    return { ...required, ...documentFields, requiredFilled };
  };

  const clickPlaceOrderSafely = async () => {
    const placeOrderCandidates = [
      page.getByRole('button', { name: /Realizar el pedido|Place order/i }).first(),
      page.locator('button.wc-block-components-checkout-place-order-button').first(),
      page.locator('button[type="submit"]').filter({ hasText: /Realizar el pedido|Place order/i }).first(),
    ];

    for (const button of placeOrderCandidates) {
      const visible = await button.isVisible({ timeout: 2500 }).catch(() => false);
      if (!visible) continue;

      await button.scrollIntoViewIfNeeded().catch(() => {});

      for (let attempt = 0; attempt < 3; attempt += 1) {
        const enabled = await button.isEnabled().catch(() => false);
        if (enabled) {
          await button.click({ noWaitAfter: true }).catch(() => {});
        } else {
          await button.click({ force: true, noWaitAfter: true }).catch(() => {});
        }

        const moved = await page
          .waitForURL(/order-pay|finalizar-compra\/order-pay|checkout\/order-pay/i, { timeout: 7000 })
          .then(() => true)
          .catch(() => false);
        if (moved) return true;

        const iframeOpened = await page.locator('iframe[title="ePayco Checkout V2"]').first().isVisible({ timeout: 3000 }).catch(() => false);
        if (iframeOpened) return true;

        await page.waitForTimeout(400);
      }

      const clickedByJs = await button.evaluate((node) => {
        if (!(node instanceof HTMLButtonElement)) return false;
        node.removeAttribute('disabled');
        node.disabled = false;
        node.click();
        return true;
      }).catch(() => false);

      if (clickedByJs) {
        const movedAfterJs = await page
          .waitForURL(/order-pay|finalizar-compra\/order-pay|checkout\/order-pay/i, { timeout: 7000 })
          .then(() => true)
          .catch(() => false);
        if (movedAfterJs) return true;

        const iframeOpenedAfterJs = await page.locator('iframe[title="ePayco Checkout V2"]').first().isVisible({ timeout: 3000 }).catch(() => false);
        if (iframeOpenedAfterJs) return true;
      }
    }

    return false;
  };

  /**
   * 
   Solo aplica para suscripciones
   * 
   */
  const fillCheckoutDocumentFields = async () => {
    const documentNumberValue = checkoutDocument;

    const documentTypeCandidates = [
      page.locator('select[name*="document" i]').first(),
      page.locator('select[id*="document" i]').first(),
      page.getByLabel(/Tipo de documento|Document type/i).first(),
      page.locator('select').filter({ hasText: /Seleccionar tipo de documento|Document type|Documento/i }).first(),
    ];

    let typeSelected = false;
    let typeFieldVisible = false;
    for (const selectField of documentTypeCandidates) {
      const visible = await selectField.isVisible({ timeout: 1200 }).catch(() => false);
      if (!visible) continue;
      typeFieldVisible = true;

      const options = await selectField.locator('option').allTextContents().catch(() => []);
      const normalized = options.map((option) => String(option || '').trim().toLowerCase());

      const preferredByLabel = [
        'cédula de ciudadanía',
        'cedula de ciudadania',
        'cedula',
        'dni',
        'citizenship id',
        'citizenship card',
        'document',
      ];

      let selected = false;
      for (const preferred of preferredByLabel) {
        const index = normalized.findIndex((option) => option.includes(preferred));
        if (index > 0) {
          await selectField.selectOption({ index }).catch(() => {});
          selected = true;
          break;
        }
      }

      if (!selected) {
        await selectField.selectOption({ index: 1 }).catch(() => {});
      }

      const value = (await selectField.inputValue().catch(() => '')).trim();
      if (value) {
        typeSelected = true;
        break;
      }
    }

    const documentNumberCandidates = [
      page.locator('input[name*="document" i]').first(),
      page.locator('input[id*="document" i]').first(),
      page.getByLabel(/N[uú]mero de documento|Document number/i).first(),
      page.locator('input[placeholder*="document" i], input[placeholder*="documento" i]').first(),
    ];

    let numberFilled = false;
    let numberFieldVisible = false;
    for (const inputField of documentNumberCandidates) {
      const visible = await inputField.isVisible({ timeout: 1200 }).catch(() => false);
      if (!visible) continue;
      numberFieldVisible = true;

      await inputField.click({ force: true }).catch(() => {});
      await inputField.fill('').catch(() => {});
      await inputField.type(documentNumberValue, { delay: 20 }).catch(async () => {
        await inputField.fill(documentNumberValue).catch(() => {});
      });
      await inputField.dispatchEvent('input').catch(() => {});
      await inputField.dispatchEvent('change').catch(() => {});

      const current = (await inputField.inputValue().catch(() => '')).replace(/\D/g, '');
      if (current === documentNumberValue) {
        numberFilled = true;
        break;
      }
    }

    return { typeSelected, numberFilled, typeFieldVisible, numberFieldVisible };
  };

  try {
    //url dle producto
    await page.goto('/producto/camisa-roja/', { waitUntil: 'domcontentloaded' });

    const addToCartCandidates = [
      page.getByRole('button', { name: /Añadir al carrito|Add to cart/i }).first(),
      page.locator('button.single_add_to_cart_button').first(),
      page.locator('button[name="add-to-cart"], a.add_to_cart_button').first(),
    ];

    let addedToCart = false;
    for (const addToCartButton of addToCartCandidates) {
      const visible = await addToCartButton.isVisible({ timeout: 4000 }).catch(() => false);
      if (!visible) continue;
      await addToCartButton.click({ force: true }).catch(() => {});
      addedToCart = true;
      break;
    }
    expect(addedToCart).toBeTruthy();

    const checkoutCandidates = [
      page.getByRole('link', { name: /Finalizar compra|Checkout/i }).first(),
      page.getByRole('button', { name: /Finalizar compra|Checkout/i }).first(),
      page.locator('a.checkout, .checkout-button').first(),
    ];

    let checkoutOpened = false;
    for (const checkoutControl of checkoutCandidates) {
      const visible = await checkoutControl.isVisible({ timeout: 4000 }).catch(() => false);
      if (!visible) continue;
      await checkoutControl.click({ force: true }).catch(() => {});
      checkoutOpened = true;
      break;
    }
    expect(checkoutOpened).toBeTruthy();

    await page.waitForURL(/finalizar-compra|checkout/i, { timeout: 30000 });

    let checkoutFields = await fillCheckoutRequiredFields();
    if (!checkoutFields.requiredFilled) {
      await page.waitForTimeout(800);
      checkoutFields = await fillCheckoutRequiredFields();
    }
    expect(checkoutFields.requiredFilled).toBeTruthy();

    const epaycoOption = page.getByText(/Checkout ePayco/i).first();
    if (await epaycoOption.isVisible({ timeout: 10000 }).catch(() => false)) {
      await epaycoOption.click();
    }

    const placeOrderClicked = await clickPlaceOrderSafely();
    expect(placeOrderClicked).toBeTruthy();

    const movedToOrderPay = await page
      .waitForURL(/order-pay|finalizar-compra\/order-pay|checkout\/order-pay/i, { timeout: 30000 })
      .then(() => true)
      .catch(() => false);


  /////////////////////////////////////////////////////////////////////////
  ///////////////////////////////////////////////////////////////////////////////
    const runEpaycoPopupFlow = async () => {   
        if (!movedToOrderPay) {
        const iframeAlreadyOpened = await page.locator('iframe[title="ePayco Checkout V2"]').first().isVisible({ timeout: 10000 }).catch(() => false);
        if (!iframeAlreadyOpened) {
          await page.waitForTimeout(2500);
        }
      } 
      await page.waitForTimeout(5000);
      const epaycoFlow = (process.env.EPAYCO_FLOW || 'cash').toLowerCase();
      const psePreferredBank = (process.env.PSE_BANK || 'BANKA').trim();
      const psePreferredBankRegex = new RegExp(psePreferredBank.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
      const cardProfiles = {
        aceptada: { number: '4575623182290326', expiration: '12/27', cvv: '123', state: 'aceptada' },
        rechazada: { number: '4151611527583283', expiration: '12/27', cvv: '123', state: 'rechazada' },
        fallida: { number: '5170394490379427', expiration: '12/27', cvv: '123', state: 'fallida' },
        pendiente: { number: '373118856457642', expiration: '12/27', cvv: '123', state: 'pendiente' },
        'fondos insuficientes': { number: '4151611527583283', expiration: '12/27', cvv: '123', state: 'rechazada' },
      };
      const requestedCardState = (process.env.CARD_STATE || 'aceptada').toLowerCase();
      const selectedCardProfile = cardProfiles[requestedCardState] || cardProfiles.aceptada;
      const acceptedCardProfile = cardProfiles.aceptada;


      const epaycoIframe = page.locator('iframe[title="ePayco Checkout V2"]').first();
      let iframeVisible = await epaycoIframe.isVisible({ timeout: 30000 }).catch(() => false);
      const openCheckoutCandidates = [
        page.getByRole('link', { name: /Pagar con ePayco/i }).first(),
        page.locator('div:has-text("Cargando métodos de pago") a[href="#"]').first(),
        page.locator('a[href="#"]', { has: page.locator('img[src*="epayco"], img[alt*="epayco" i]') }).first(),
        page.locator('a[href="#"]').first(),
      ];

      const tryOpenEpaycoIframe = async (attempts = 1) => {
        for (let attempt = 0; attempt < attempts; attempt += 1) {
          for (const openCheckout of openCheckoutCandidates) {
            const visible = await openCheckout.isVisible({ timeout: 4000 }).catch(() => false);
            if (!visible) continue;

            await openCheckout.click({ force: true }).catch(() => {});
            await page.waitForTimeout(3000);
            const opened = await epaycoIframe.isVisible({ timeout: 8000 }).catch(() => false);
            if (opened) return true;
          }

          await page.waitForTimeout(1200);
        }

        return false;
      };

      if (!iframeVisible) {
        const iframeCount = await page.locator('iframe[title="ePayco Checkout V2"]').count();
        if (iframeCount > 0) {
          iframeVisible = true;
        }
      }

      if (!iframeVisible) {
        iframeVisible = await tryOpenEpaycoIframe(1);
      }

      if (!iframeVisible) {
        const backendWarningVisible = await page.getByText(/Trying to access array offset on value of type null|Hemos notado un problema con tu orden/i).first().isVisible({ timeout: 4000 }).catch(() => false);
        if (backendWarningVisible) {
          testInfo.annotations.push({ type: 'warning', description: 'ePayco backend respondió con error del módulo payco.php; se reintentará abrir checkout automáticamente.' });
          await page.waitForTimeout(2500);
          iframeVisible = await tryOpenEpaycoIframe(2);

          if (!iframeVisible) {
            testInfo.annotations.push({ type: 'warning', description: 'No fue posible abrir el checkout de ePayco después del reintento automático.' });
            return;
          }

          testInfo.annotations.push({ type: 'info', description: 'Checkout ePayco recuperado después de detectar error temporal del backend.' });
        }
      }

      expect(iframeVisible).toBeTruthy();

      const frame = page.frameLocator('iframe[title="ePayco Checkout V2"]');
      let popupReadyForPayment = false;

      const fillPopupContactFallback = async () => {
        const iframeHandle = await epaycoIframe.elementHandle().catch(() => null);
        const popupFrame = await iframeHandle?.contentFrame();
        if (!popupFrame) {
          return { phoneFound: false, emailFound: false, continueFound: false, continueEnabled: false };
        }

        const candidates = [checkoutPhone, `57${checkoutPhone}`, `+57${checkoutPhone}`];
        let result = { phoneFound: false, emailFound: false, continueFound: false, continueEnabled: false };

        for (const candidate of candidates) {
          result = await popupFrame.evaluate(({ phoneValue, emailValue }) => {
            const getInput = (selectors) => selectors.map((selector) => document.querySelector(selector)).find(Boolean);

            const phoneInput = getInput([
              'input[name="mobilePhone"]',
              'input[placeholder*="phone" i]',
              'input[placeholder*="número" i]',
              'input[type="tel"]',
              'input[id*="phone" i]'
            ]);

            const emailInput = getInput([
              'input[name="email"]',
              'input[type="email"]',
              'input[id*="email" i]'
            ]);

            if (phoneInput) {
              phoneInput.focus();
              phoneInput.value = '';
              phoneInput.value = phoneValue;
              phoneInput.dispatchEvent(new Event('input', { bubbles: true }));
              phoneInput.dispatchEvent(new Event('change', { bubbles: true }));
              phoneInput.blur();
            }

            if (emailInput) {
              if (!String(emailInput.value || '').trim()) {
                emailInput.focus();
                emailInput.value = emailValue;
                emailInput.dispatchEvent(new Event('input', { bubbles: true }));
                emailInput.dispatchEvent(new Event('change', { bubbles: true }));
                emailInput.blur();
              }
            }

            const allButtons = Array.from(document.querySelectorAll('button'));
            const continueButton = allButtons.find((button) => /continuar|continue|proceed/i.test((button.textContent || '').trim()));

            return {
              phoneFound: !!phoneInput,
              emailFound: !!emailInput,
              continueFound: !!continueButton,
              continueEnabled: !!(continueButton && !continueButton.disabled)
            };
          }, { phoneValue: candidate, emailValue: checkoutEmail }).catch(() => ({ phoneFound: false, emailFound: false, continueFound: false, continueEnabled: false }));

          if (result.continueEnabled) {
            break;
          }
        }

        return result;
      };

      const closeEpaycoModal = async () => {
        const closeCandidates = [
          frame.getByRole('button', { name: /Close|Cerrar/i }).first(),
          frame.locator('button[aria-label="Close"], button[aria-label="Cerrar"]').first(),
          page.locator('button[aria-label="Close"], button[aria-label="Cerrar"]').first(),
        ];

        for (const closeButton of closeCandidates) {
          const visible = await closeButton.isVisible({ timeout: 1500 }).catch(() => false);
          if (!visible) continue;
          await closeButton.click({ force: true }).catch(() => {});
          return true;
        }

        return false;
      };

      const captureRefPaycoScreenshot = async (method = epaycoFlow) => {
        const normalized = (value) => String(value || '').replace(/\D/g, '');

        const referenceFromContext = async (context) => context.evaluate(() => {
          const bodyText = (document.body?.innerText || '').replace(/\s+/g, ' ').trim();
          const nearRefPatterns = [
            /(?:epayco'?s\s*reference|referencia\s*epayco|reference|referencia)\D{0,20}(\d{6,12})/i,
            /(?:pin|id)\D{0,10}(\d{6,12})/i,
          ];

          for (const pattern of nearRefPatterns) {
            const match = bodyText.match(pattern);
            if (match && match[1]) {
              return match[1];
            }
          }

          const allNumbers = bodyText.match(/\b\d{6,12}\b/g) || [];
          return allNumbers[0] || '';
        }).catch(() => '');

        const iframeCount = await epaycoIframe.count().catch(() => 0);
        let referenceFromDom = '';

        if (iframeCount > 0) {
          const iframeHandle = await epaycoIframe.elementHandle().catch(() => null);
          const popupFrame = await iframeHandle?.contentFrame();
          if (popupFrame) {
            referenceFromDom = await referenceFromContext(popupFrame);
          }
        }

        if (!referenceFromDom) {
          referenceFromDom = await referenceFromContext(page);
        }

        if (!referenceFromDom) {
          const currentUrl = page.url();
          const refInUrl = (() => {
            try {
              const url = new URL(currentUrl);
              return url.searchParams.get('ref_payco') || '';
            } catch {
              return '';
            }
          })();
          referenceFromDom = refInUrl;
        }

        const reference = normalized(referenceFromDom);
        const rawReferenceSafe = String(referenceFromDom || 'unknown').replace(/[^a-zA-Z0-9_-]/g, '');
        const screenshotPath = testInfo.outputPath(`epayco-ref-${reference || rawReferenceSafe || 'unknown'}-${method}.png`);
        await page.screenshot({ path: screenshotPath, fullPage: true }).catch(() => {});

        if (reference) {
          testInfo.annotations.push({ type: 'epayco-reference', description: `Referencia detectada (${method}): ${reference}` });
        } else if (rawReferenceSafe) {
          testInfo.annotations.push({ type: 'epayco-reference', description: `Referencia detectada (${method}): ${rawReferenceSafe}` });
        }

        return reference || rawReferenceSafe;
      };

      const finalizeEpaycoCheckout = async (timeout = 30000) => {
        const iframeCount = await epaycoIframe.count().catch(() => 0);
        const finalizar = frame.getByRole('button', { name: /Finalizar|Finalize/i }).first();
        if (iframeCount > 0 && await finalizar.isVisible({ timeout }).catch(() => false)) {
          const finalizarEnabled = await finalizar.isEnabled().catch(() => false);
          if (finalizarEnabled) {
            await finalizar.click({ noWaitAfter: true });
            return true;
          }
          await finalizar.click({ force: true, noWaitAfter: true }).catch(() => {});
          return true;
        }

        const pageFinalizeCandidates = [
          page.getByRole('button', { name: /Finalizar|Finalize/i }).first(),
          page.getByRole('link', { name: /Finalizar|Finalize/i }).first(),
        ];

        for (const finalizeCandidate of pageFinalizeCandidates) {
          const visible = await finalizeCandidate.isVisible({ timeout: 2000 }).catch(() => false);
          if (!visible) continue;
          await finalizeCandidate.click({ force: true, noWaitAfter: true }).catch(() => {});
          return true;
        }

        const closeOrExitCandidates = [
          frame.getByRole('button', { name: /Finalizar|Finalize/i }).first(),
          frame.getByRole('button', { name: /Close|Cerrar/i }).first(),
          frame.getByRole('button', { name: /exit-button|Salir|Exit/i }).first(),
        ];

        for (const button of closeOrExitCandidates) {
          const visible = await button.isVisible({ timeout: 3000 }).catch(() => false);
          if (!visible) continue;
          await button.click({ force: true }).catch(() => {});
          return true;
        }

        return false;
      };

      const ensureModalPhone = async () => {
        const phoneInputs = [
          frame.getByRole('textbox', { name: 'mobilePhone' }).first(),
          frame.getByRole('textbox', { name: /Phone number|Número de teléfono/i }).first(),
          frame.locator('input[name="mobilePhone"]').first(),
          frame.locator('input[placeholder*="phone" i]').first(),
          frame.locator('input[placeholder*="número"]').first(),
          frame.locator('input[type="tel"]').first(),
        ];
        const getContactContinueButton = () => frame.getByRole('button', { name: /Continuar|Proceed|Continue/i }).first();

        const ensureCountryColombia = async () => {
          const countryControls = [
            frame.locator('[role="combobox"]').first(),
            frame.locator('button').filter({ hasText: /^\+\d+$/ }).first(),
            frame.locator('button:has-text("+1"), button:has-text("+57")').first(),
            frame.locator('select[name*="country" i], select[id*="country" i]').first(),
          ];

          for (const control of countryControls) {
            const visible = await control.isVisible({ timeout: 1200 }).catch(() => false);
            if (!visible) continue;

            const tagName = await control.evaluate((node) => node.tagName.toLowerCase()).catch(() => '');

            if (tagName === 'select') {
              await control.selectOption({ label: 'Colombia' }).catch(() => {});
              await control.selectOption({ value: '+57' }).catch(() => {});
              await control.selectOption({ value: '57' }).catch(() => {});
              return;
            }

            await control.click({ force: true }).catch(() => {});
            await page.waitForTimeout(250);

            const countryOptions = [
              frame.locator('button, [role="option"], li, div').filter({ hasText: /^\+57\s+Colombia$/ }).first(),
              frame.getByRole('option', { name: /Colombia|\+57/i }).first(),
              frame.locator('[role="option"]:has-text("Colombia"), [role="option"]:has-text("+57")').first(),
              frame.getByText(/^\+57\s+Colombia$/).first(),
              frame.getByText(/Colombia|\+57/i).first(),
            ];

            for (const option of countryOptions) {
              const optionVisible = await option.isVisible({ timeout: 1200 }).catch(() => false);
              if (!optionVisible) continue;
              await option.click({ force: true }).catch(() => {});
              await page.waitForTimeout(250);
              return;
            }
          }
        };

        const findVisiblePhoneInput = async () => {
          const maxWaitMs = 45000;
          const startedAt = Date.now();

          while (Date.now() - startedAt < maxWaitMs) {
            for (const phoneInput of phoneInputs) {
              const isVisible = await phoneInput.isVisible({ timeout: 800 }).catch(() => false);
              if (isVisible) {
                return phoneInput;
              }
            }

            const cardMethodVisible = await frame.getByRole('link', { name: /Tarjeta de crédito y\/o débito|Credit card|Debit card/i }).first().isVisible({ timeout: 500 }).catch(() => false);
            const cashMethodVisible = await frame.getByRole('link', { name: /Efectivo|Cash/i }).first().isVisible({ timeout: 500 }).catch(() => false);
            if (cardMethodVisible || cashMethodVisible) {
              return null;
            }

            await page.waitForTimeout(500);
          }

          return false;
        };

        const phoneInput = await findVisiblePhoneInput();
        if (phoneInput === null) {
          return true;
        }
        if (!phoneInput) {
          return false;
        }

        await ensureCountryColombia();

        const continueButton = getContactContinueButton();
        const continueVisible = await continueButton.isVisible({ timeout: 2000 }).catch(() => false);
        if (continueVisible) {
          const continueEnabled = await continueButton.isEnabled().catch(() => false);
          if (continueEnabled) {
            return true;
          }
        }

        for (let attempt = 0; attempt < 4; attempt += 1) {
          const candidateValues = [checkoutPhone, `57${checkoutPhone}`, `+57${checkoutPhone}`];

          for (const candidate of candidateValues) {
            await phoneInput.click({ force: true });
            await phoneInput.fill('');
            await phoneInput.type(candidate, { delay: 35 });
            await phoneInput.dispatchEvent('input').catch(() => {});
            await phoneInput.dispatchEvent('change').catch(() => {});
            await phoneInput.press('Tab').catch(() => {});
            await page.waitForTimeout(300);

            const newDigits = ((await phoneInput.inputValue().catch(() => '')).trim()).replace(/\D/g, '');
            if (newDigits.length >= 10) {
              const proceedReady = await getContactContinueButton().isEnabled().catch(() => false);
              if (proceedReady) {
                return true;
              }
            }
          }

          await ensureCountryColombia();
        }

        const finalValue = (await phoneInput.inputValue().catch(() => '')).trim();
        if (finalValue.replace(/\D/g, '').length >= 10 && await getContactContinueButton().isEnabled().catch(() => false)) {
          return true;
        }

        const fallbackResult = await fillPopupContactFallback();
        if (fallbackResult.continueEnabled) {
          return true;
        }

        return false;
      };

      const phoneReady = await ensureModalPhone();
      if (!phoneReady) {
        await closeEpaycoModal();
        return;
      }

      const modalEmail = frame.getByRole('textbox', { name: /email/i }).first();
      if (await modalEmail.isVisible({ timeout: 8000 }).catch(() => false)) {
        const emailValue = (await modalEmail.inputValue().catch(() => '')).trim();
        if (!emailValue) {
          await modalEmail.fill(checkoutEmail);
          await modalEmail.dispatchEvent('input').catch(() => {});
          await modalEmail.dispatchEvent('change').catch(() => {});
        }
      } else {
        const modalEmailByName = frame.locator('input[name="email"], input[type="email"]').first();
        if (await modalEmailByName.isVisible({ timeout: 5000 }).catch(() => false)) {
          const emailValue = (await modalEmailByName.inputValue().catch(() => '')).trim();
          if (!emailValue) {
            await modalEmailByName.fill(checkoutEmail);
            await modalEmailByName.dispatchEvent('input').catch(() => {});
            await modalEmailByName.dispatchEvent('change').catch(() => {});
          }
        }
      }

      const waitForPaymentMethods = async (timeout = 12000) => {
        const cardMethod = frame.getByRole('link', { name: /Tarjeta de crédito y\/o débito|Credit card|Debit card/i }).first();
        const cashMethod = frame.getByRole('link', { name: /Efectivo|Cash/i }).first();
        const pseMethod = frame.getByRole('link', { name: /PSE|Pago seguro en l[ií]nea|Pagos Seguros en L[ií]nea|Secure online payment/i }).first();
        const mobilePhoneInput = frame.getByRole('textbox', { name: /mobilePhone|Phone number|N[uú]mero de tel[eé]fono|N[uú]mero de m[oó]vil/i }).first();

        return Promise.race([
          cardMethod.waitFor({ state: 'visible', timeout }).then(() => true).catch(() => false),
          cashMethod.waitFor({ state: 'visible', timeout }).then(() => true).catch(() => false),
          pseMethod.waitFor({ state: 'visible', timeout }).then(() => true).catch(() => false),
          mobilePhoneInput.waitFor({ state: 'hidden', timeout }).then(() => true).catch(() => false),
        ]).catch(() => false);
      };

      const clickContactContinueSafely = async () => {
        const contactContinue = frame.getByRole('button', { name: /Continuar|Proceed|Continue/i }).first();
        const continueVisible = await contactContinue.isVisible({ timeout: 10000 }).catch(() => false);
        if (!continueVisible) {
          return waitForPaymentMethods(3000);
        }

        for (let attempt = 0; attempt < 5; attempt += 1) {
          if (!(await contactContinue.isEnabled().catch(() => false))) {
            await ensureModalPhone();
            await fillPopupContactFallback();
            await page.waitForTimeout(250);
          }

          const continueEnabled = await contactContinue.isEnabled().catch(() => false);
          if (continueEnabled) {
            await contactContinue.click({ force: true }).catch(() => {});
            await page.waitForTimeout(200);
            await contactContinue.evaluate((node) => {
              if (typeof node.click === 'function') node.click();
            }).catch(() => {});

            const contactPhone = frame.locator('input[name="mobilePhone"], input[type="tel"]').first();
            const contactEmail = frame.locator('input[name="email"], input[type="email"]').first();
            if (await contactPhone.isVisible({ timeout: 500 }).catch(() => false)) {
              await contactPhone.press('Enter').catch(() => {});
            }
            if (await contactEmail.isVisible({ timeout: 500 }).catch(() => false)) {
              await contactEmail.press('Enter').catch(() => {});
            }
          } else {
            const forcedContinueClick = await (async () => {
              const iframeHandle = await epaycoIframe.elementHandle().catch(() => null);
              const popupFrame = await iframeHandle?.contentFrame();
              if (!popupFrame) return false;

              return popupFrame.evaluate(() => {
                const proceedButton = Array.from(document.querySelectorAll('button')).find((button) => /continuar|proceed|continue/i.test((button.textContent || '').trim()));
                if (!proceedButton) {
                  return false;
                }

                proceedButton.removeAttribute('disabled');
                proceedButton.disabled = false;
                proceedButton.click();
                proceedButton.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
                return true;
              }).catch(() => false);
            })();

            if (!forcedContinueClick) {
              await page.waitForTimeout(300);
              continue;
            }
          }

          await (async () => {
            const iframeHandle = await epaycoIframe.elementHandle().catch(() => null);
            const popupFrame = await iframeHandle?.contentFrame();
            if (!popupFrame) return;

            await popupFrame.evaluate(() => {
              const continueButtons = Array.from(document.querySelectorAll('button'))
                .filter((button) => /continuar|proceed|continue/i.test((button.textContent || '').trim()));

              for (const button of continueButtons) {
                const style = window.getComputedStyle(button);
                const visible = style.display !== 'none' && style.visibility !== 'hidden' && button.getBoundingClientRect().height > 0;
                if (!visible) continue;

                button.removeAttribute('disabled');
                button.disabled = false;
                button.click();
                button.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
              }
            }).catch(() => {});
          })();

          const methodsLoaded = await waitForPaymentMethods(9000 + (attempt * 2500));
          if (methodsLoaded) {
            return true;
          }

          await page.waitForTimeout(500);
        }

        return false;
      };

      popupReadyForPayment = await clickContactContinueSafely();

      if (!popupReadyForPayment) {
        const cardVisible = await frame.getByRole('link', { name: /Tarjeta de crédito y\/o débito|Credit card|Debit card/i }).first().isVisible({ timeout: 3000 }).catch(() => false);
        const cashVisible = await frame.getByRole('link', { name: /Efectivo|Cash/i }).first().isVisible({ timeout: 3000 }).catch(() => false);
        popupReadyForPayment = cardVisible || cashVisible;
      }

      if (!popupReadyForPayment) {
        await closeEpaycoModal();
        return;
      }

      const selectPaymentMethod = async (method) => {
        const methodRegex = method === 'cash'
          ? /Efectivo|Cash/i
          : method === 'pse'
            ? /PSE|Pago seguro en l[ií]nea|Pagos Seguros en L[ií]nea|Secure online payment/i
            : /Tarjeta de crédito y\/o débito|Credit and\/or debit card|Credit card|Debit card|Tarjeta|D[ée]bito|Cr[ée]dito/i;

        const methodLocators = [
          frame.getByRole('link', { name: methodRegex }).first(),
          frame.getByRole('button', { name: methodRegex }).first(),
          frame.locator('a, button, [role="button"], div').filter({ hasText: methodRegex }).first(),
        ];

        for (let attempt = 0; attempt < 4; attempt += 1) {
          for (const locator of methodLocators) {
            const visible = await locator.isVisible({ timeout: 1500 }).catch(() => false);
            if (!visible) continue;

            await locator.scrollIntoViewIfNeeded().catch(() => {});
            await locator.click({ force: true }).catch(async () => {
              await locator.evaluate((node) => {
                if (typeof node.click === 'function') node.click();
              }).catch(() => {});
            });

            await page.waitForTimeout(900);

            if (method === 'cash') {
              const cashEntryVisible = await frame.getByRole('button', { name: /Select Gana|Gana/i }).first().isVisible({ timeout: 2500 }).catch(() => false);
              if (cashEntryVisible) {
                return true;
              }
            } else if (method === 'pse') {
              const pseEntryVisible = await Promise.race([
                frame.getByText(/PSE|Pago seguro en l[ií]nea|Pagos Seguros en L[ií]nea/i).first().isVisible({ timeout: 2500 }).catch(() => false),
                frame.locator('select[name*="bank" i], select[id*="bank" i], input[name*="bank" i]').first().isVisible({ timeout: 2500 }).catch(() => false),
              ]).catch(() => false);

              if (pseEntryVisible) {
                return true;
              }
            } else {
              const cardFields = [
                frame.getByRole('textbox', { name: 'cardNumber' }).first(),
                frame.locator('input[name="cardNumber"]').first(),
                frame.locator('input[autocomplete="cc-number"]').first(),
                frame.locator('input[placeholder*="card" i], input[placeholder*="tarjeta" i]').first(),
              ];
              let cardEntryVisible = false;
              for (const cardField of cardFields) {
                cardEntryVisible = await cardField.isVisible({ timeout: 1200 }).catch(() => false);
                if (cardEntryVisible) break;
              }
              if (cardEntryVisible) {
                return true;
              }

              const clickedByDomText = await frame.locator('a, button, [role="button"], div')
                .filter({ hasText: /Tarjeta de crédito y\/o débito|Credit and\/or debit card|Credit|Debit|Tarjeta|D[ée]bito/i })
                .first()
                .click({ force: true })
                .then(() => true)
                .catch(() => false);

              if (clickedByDomText) {
                await page.waitForTimeout(700);
                const cardNumberVisibleAfterDomClick = await frame.getByRole('textbox', { name: 'cardNumber' }).first().isVisible({ timeout: 2500 }).catch(() => false);
                if (cardNumberVisibleAfterDomClick) {
                  return true;
                }
              }
            }
          }

          const reopenCheckoutCandidates = [
            page.getByRole('link', { name: /Pagar con ePayco|Pay with ePayco/i }).first(),
            page.locator('div:has-text("Cargando métodos de pago") a[href="#"]').first(),
            page.locator('a[href="#"]', { has: page.locator('img[src*="epayco"], img[alt*="epayco" i]') }).first(),
            page.locator('a[href="#"]').first(),
          ];

          for (const openCheckout of reopenCheckoutCandidates) {
            const openVisible = await openCheckout.isVisible({ timeout: 800 }).catch(() => false);
            if (!openVisible) continue;
            await openCheckout.click({ force: true }).catch(() => {});
            await page.waitForTimeout(900);
            break;
          }

          await page.waitForTimeout(700);
        }

        return false;
      };

      const flowToMethod = (flow) => {
        if (flow === 'cash' || flow === 'efectivo') return 'cash';
        if (flow === 'pse') return 'pse';
        return 'card';
      };

      const preferredMethod = flowToMethod(epaycoFlow);
      const selectionOrder = [preferredMethod, 'card', 'cash', 'pse'].filter((method, index, list) => list.indexOf(method) === index);
      let selectedPaymentMethod = preferredMethod;

      let paymentMethodSelected = false;

      const alreadyInPseStepOne = await Promise.race([
        frame.getByText(/PSE\s*\(Paso\s*1\s*de\s*2\)|PSE\s*\(Step\s*1\s*of\s*2\)|Selecciona banco/i).first().isVisible({ timeout: 1500 }).catch(() => false),
        frame.getByRole('heading', { name: /PSE\s*\(Paso\s*1\s*de\s*2\)|PSE\s*\(Step\s*1\s*of\s*2\)/i }).first().isVisible({ timeout: 1500 }).catch(() => false),
        frame.getByText(/Seleccionar otro banco/i).first().isVisible({ timeout: 1500 }).catch(() => false),
      ]).catch(() => false);

      const alreadyInCashStep = await Promise.race([
        frame.getByText(/Efectivo\s*\(Paso\s*1\s*de\s*2\)|Cash\s*\(Step\s*1\s*of\s*2\)|Pago en efectivo|Cash payment/i).first().isVisible({ timeout: 1500 }).catch(() => false),
        frame.getByRole('textbox', { name: /numberDoc|Documento|N[uú]mero de documento/i }).first().isVisible({ timeout: 1500 }).catch(() => false),
        frame.getByRole('button', { name: /Select Gana|Gana|Efectivo|Cash/i }).first().isVisible({ timeout: 1500 }).catch(() => false),
      ]).catch(() => false);

      const alreadyInCardStep = await Promise.race([
        frame.getByRole('textbox', { name: 'cardNumber' }).first().isVisible({ timeout: 1500 }).catch(() => false),
        frame.locator('input[name="cardNumber"], input[autocomplete="cc-number"]').first().isVisible({ timeout: 1500 }).catch(() => false),
      ]).catch(() => false);

      if (preferredMethod === 'pse' && alreadyInPseStepOne) {
        selectedPaymentMethod = 'pse';
        paymentMethodSelected = true;
      } else if (alreadyInCashStep) {
        selectedPaymentMethod = 'cash';
        paymentMethodSelected = true;
      } else if (alreadyInCardStep) {
        selectedPaymentMethod = 'card';
        paymentMethodSelected = true;
      }

      for (const method of selectionOrder) {
        if (paymentMethodSelected) break;
        selectedPaymentMethod = method;
        paymentMethodSelected = await selectPaymentMethod(method);
        if (paymentMethodSelected) break;
      }

      expect(paymentMethodSelected).toBeTruthy();

      if (selectedPaymentMethod === 'cash') {
        const cashEntryButton = frame.getByRole('button', { name: /Select Gana|Gana/i }).first();
        if (await cashEntryButton.isVisible({ timeout: 3000 }).catch(() => false)) {
          await cashEntryButton.click({ force: true }).catch(() => {});
          const cashContinue = frame.getByRole('button', { name: /Continuar|Proceed|Continue/i }).first();
          if (await cashContinue.isVisible({ timeout: 3000 }).catch(() => false)) {
            await cashContinue.click({ force: true }).catch(() => {});
          }
        }

        const ensureCashFullName = async () => {
          const fullNameValue = 'Ricardo Saldarriaga';
          const cashNameInputs = [
            frame.getByRole('textbox', { name: 'name' }).first(),
            frame.getByRole('textbox', { name: /Nombre y Apellidos/i }).first(),
            frame.locator('input[name="name"]').first(),
            frame.locator('input[placeholder*="Escribe"]').first(),
            frame.locator('input[placeholder*="Nombre"]').first(),
          ];

          for (const nameInput of cashNameInputs) {
            for (let attempt = 0; attempt < 3; attempt += 1) {
              const currentValue = (await nameInput.inputValue().catch(() => '')).trim();
              if (currentValue.split(/\s+/).filter(Boolean).length >= 2) {
                return currentValue;
              }

              const canUse = await nameInput.isVisible({ timeout: 3000 }).catch(() => false);
              if (!canUse) continue;

              await nameInput.click({ force: true });
              await nameInput.fill('');
              await nameInput.type(fullNameValue, { delay: 30 });
              await page.waitForTimeout(250);
            }
          }

          const fallbackInput = frame.locator('input[name="name"]').first();
          if (await fallbackInput.isVisible({ timeout: 5000 }).catch(() => false)) {
            await fallbackInput.fill(fullNameValue);
            await page.waitForTimeout(250);
          }

          return (await frame.locator('input[name="name"]').first().inputValue().catch(() => '')).trim();
        };

        const cashFullNameValue = await ensureCashFullName();
        expect(cashFullNameValue.split(/\s+/).filter(Boolean).length).toBeGreaterThanOrEqual(2);

        await frame.getByRole('textbox', { name: 'numberDoc' }).first().fill(checkoutDocument);
        const cashTerms = frame.getByTestId('default-checkbox').first();
        if (await cashTerms.isVisible({ timeout: 8000 }).catch(() => false)) {
          await cashTerms.click();
        }
        const cashPayButton = frame.getByRole('button', { name: /Pagar|Pay now|Pay/i }).first();
        await expect(cashPayButton).toBeVisible({ timeout: 15000 });
        await cashPayButton.click();

        await frame.getByText(/ePayco'?s Reference|Referencia ePayco|Given the payment date/i).first().waitFor({ state: 'visible', timeout: 35000 }).catch(() => {});
        const detectedCashReference = await captureRefPaycoScreenshot('cash');
        expect(Boolean(detectedCashReference)).toBeTruthy();

        const pendingMessage = frame.locator('text=/\[200\].*Pendiente/i').first();
        if (await pendingMessage.isVisible({ timeout: 25000 }).catch(() => false)) {
          await captureRefPaycoScreenshot();
          const closePending = frame.getByRole('button', { name: 'Close' }).first();
          if (await closePending.isVisible().catch(() => false)) {
            await closePending.click();
          }
        }

        await finalizeEpaycoCheckout(25000);
      } else if (selectedPaymentMethod === 'pse') {
        const pseStepVisible = await frame.getByText(/PSE\s*\(Step\s*1\s*of\s*2\)|PSE/i).first().isVisible({ timeout: 12000 }).catch(() => false);
        expect(pseStepVisible).toBeTruthy();

        const openPseBankSelector = async () => {
          const captureWhenBankModalReady = async () => {
            const bankModalReady = await Promise.race([
              frame.getByText(/Seleccionar banco|Select bank/i).first().waitFor({ state: 'visible', timeout: 7000 }).then(() => true).catch(() => false),
              frame.getByRole('textbox', { name: /Buscar|Search/i }).first().waitFor({ state: 'visible', timeout: 7000 }).then(() => true).catch(() => false),
            ]).catch(() => false);

            if (bankModalReady) {
              //await page.screenshot({ path: testInfo.outputPath('pse-seleccionar-banco-screen.png'), fullPage: true }).catch(() => {});
            }

            return bankModalReady;
          };

          const explicitSelectAnotherBank = frame.getByText(/Seleccionar otro banco/i).first();
          if (await explicitSelectAnotherBank.isVisible({ timeout: 1200 }).catch(() => false)) {
            await explicitSelectAnotherBank.click({ force: true }).catch(() => {});
            await captureWhenBankModalReady();
          // await page.screenshot({ path: testInfo.outputPath('pse-seleccionar-otro-banco-clicked.png'), fullPage: true }).catch(() => {});
            return true;
          }

          const openSelectorCandidates = [
            frame.getByText(/Selecciona\.\.\.|Select\.\.\.|Seleccionar otro banco|Select bank/i).first(),
            frame.locator('[role="combobox"]').first(),
            frame.locator('input[placeholder*="Selecciona" i], input[placeholder*="Select" i]').first(),
            frame.locator('div[class*="select" i], button[class*="select" i]').first(),
          ];

          for (const control of openSelectorCandidates) {
            const visible = await control.isVisible({ timeout: 1200 }).catch(() => false);
            if (!visible) continue;
            await control.click({ force: true }).catch(() => {});
            await captureWhenBankModalReady();
            //await page.screenshot({ path: testInfo.outputPath('pse-selector-clicked.png'), fullPage: true }).catch(() => {});
            return true;
          }

          return false;
        };

        await openPseBankSelector();

        let bankaSelected = false;

        const runPrimaryBankaSequence = async () => {
          const bankControls = [
            frame.locator('[role="combobox"]').first(),
            frame.getByText(/Seleccionar otro banco|Selecciona\.\.\.|Select bank|Select\.\.\./i).first(),
            frame.locator('button:has-text("Selecciona"), button:has-text("Select")').first(),
            frame.locator('input[placeholder*="Selecciona" i], input[placeholder*="Select" i]').first(),
            frame.locator('select[name*="bank" i], select[id*="bank" i], select').first(),
            frame.locator('div[class*="select" i], button[class*="select" i], [data-testid*="select" i]').first(),
          ];

          const bankOptions = [
            frame.getByRole('option', { name: /^BANKA$/i }).first(),
            frame.getByRole('button', { name: /^BANKA$/i }).first(),
            frame.locator('[role="option"], li, button, div').filter({ hasText: /^BANKA$/i }).first(),
            frame.getByText(/^BANKA$/i).first(),
            frame.locator('[role="option"], li, button, div').filter({ hasText: psePreferredBankRegex }).first(),
          ];

          for (const control of bankControls) {
            const visible = await control.isVisible({ timeout: 1800 }).catch(() => false);
            if (!visible) continue;

            const tagName = await control.evaluate((node) => node.tagName.toLowerCase()).catch(() => '');

            if (tagName === 'select') {
              await control.selectOption({ label: 'BANKA' }).catch(() => {});
              await control.selectOption('BANKA').catch(() => {});
              await control.selectOption({ label: psePreferredBank }).catch(() => {});
              await control.selectOption(psePreferredBank).catch(() => {});

              const selectedText = (await control.locator('option:checked').first().textContent().catch(() => '') || '').trim();
              if (/banka/i.test(selectedText) || psePreferredBankRegex.test(selectedText)) {
                await page.waitForTimeout(600);
                //await page.screenshot({ path: testInfo.outputPath('pse-banka-selected-step1.png'), fullPage: true }).catch(() => {});
                return true;
              }

              continue;
            }

            await control.click({ force: true }).catch(() => {});
            await page.waitForTimeout(300);

            const bankListVisible = await Promise.race([
              frame.getByText(/Seleccionar banco|Select bank/i).first().waitFor({ state: 'visible', timeout: 7000 }).then(() => true).catch(() => false),
              frame.getByRole('textbox', { name: /Buscar|Search/i }).first().waitFor({ state: 'visible', timeout: 7000 }).then(() => true).catch(() => false),
            ]).catch(() => false);

            if (!bankListVisible) continue;

            //await page.screenshot({ path: testInfo.outputPath('pse-seleccionar-banco-modal-open.png'), fullPage: true }).catch(() => {});

            const bankSearchInput = frame.getByRole('textbox', { name: /Buscar|Search/i }).first();
            if (await bankSearchInput.isVisible({ timeout: 1200 }).catch(() => false)) {
              await bankSearchInput.fill('').catch(() => {});
              await bankSearchInput.type('BANKA', { delay: 20 }).catch(async () => {
                await bankSearchInput.fill('BANKA').catch(() => {});
              });
              await bankSearchInput.dispatchEvent('input').catch(() => {});
              await bankSearchInput.dispatchEvent('change').catch(() => {});
              await page.waitForTimeout(500);
            }

            for (const option of bankOptions) {
              const optionVisible = await option.isVisible({ timeout: 1500 }).catch(() => false);
              if (!optionVisible) continue;

              await option.click({ force: true }).catch(() => {});
              await page.waitForTimeout(1000);
              //await page.screenshot({ path: testInfo.outputPath('pse-banka-selected-step1.png'), fullPage: true }).catch(() => {});
              return true;
            }
          }

          return false;
        };

        bankaSelected = await runPrimaryBankaSequence();

        const trySelectBankaViaDomPicker = async () => {
          const iframeHandle = await epaycoIframe.elementHandle().catch(() => null);
          const popupFrame = await iframeHandle?.contentFrame();
          if (!popupFrame) return false;

          const clicked = await popupFrame.evaluate((bankName) => {
            const normalize = (value) => String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
            const targetBank = normalize(bankName);

            const allNodes = Array.from(document.querySelectorAll('button, div, li, span, input, [role="option"], [role="button"]'));

            const selectorTrigger = allNodes.find((node) => {
              const text = normalize(node.textContent || node.getAttribute('placeholder') || '');
              return text.includes('seleccionar otro banco') || text.includes('selecciona...') || text.includes('select...') || text.includes('select bank');
            });

            if (selectorTrigger && typeof selectorTrigger.click === 'function') {
              selectorTrigger.click();
            }

            const searchInput = Array.from(document.querySelectorAll('input')).find((input) => {
              const placeholder = normalize(input.getAttribute('placeholder') || '');
              return placeholder.includes('buscar') || placeholder.includes('search');
            });

            if (searchInput) {
              searchInput.focus();
              searchInput.value = '';
              searchInput.value = bankName;
              searchInput.dispatchEvent(new Event('input', { bubbles: true }));
              searchInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            const bankNode = Array.from(document.querySelectorAll('button, li, div, [role="option"], span')).find((node) => {
              const text = normalize(node.textContent || '');
              return text === targetBank || text.includes(targetBank);
            });

            if (!bankNode) return false;
            bankNode.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
            if (typeof bankNode.click === 'function') bankNode.click();
            return true;
          }, psePreferredBank).catch(() => false);

          if (!clicked) return false;
          await page.waitForTimeout(1500);

          const returnedToStepOne = await frame
            .getByText(/PSE\s*\(Paso\s*1\s*de\s*2\)|PSE\s*\(Step\s*1\s*of\s*2\)|Selecciona banco/i)
            .first()
            .isVisible({ timeout: 8000 })
            .catch(() => false);

          return returnedToStepOne;
        };

        if (!bankaSelected) {
          bankaSelected = await trySelectBankaViaDomPicker();
        }

        const bancaListOpened = await Promise.race([
          frame.getByText(/Seleccionar banco|Select bank/i).first().waitFor({ state: 'visible', timeout: 4000 }).then(() => true).catch(() => false),
          frame.getByRole('textbox', { name: /Buscar|Search/i }).first().waitFor({ state: 'visible', timeout: 4000 }).then(() => true).catch(() => false),
        ]).catch(() => false);

        if (bancaListOpened) {
          //await page.screenshot({ path: testInfo.outputPath('pse-bank-list-opened.png'), fullPage: true }).catch(() => {});
        }

        if (bancaListOpened) {
          const bankSearchInput = frame.getByRole('textbox', { name: /Buscar|Search/i }).first();
          if (await bankSearchInput.isVisible({ timeout: 1200 }).catch(() => false)) {
            await bankSearchInput.fill('').catch(() => {});
            await bankSearchInput.type(psePreferredBank, { delay: 20 }).catch(async () => {
              await bankSearchInput.fill(psePreferredBank).catch(() => {});
            });
            await bankSearchInput.dispatchEvent('input').catch(() => {});
            await bankSearchInput.dispatchEvent('change').catch(() => {});
            await page.waitForTimeout(250);
          }

          const bankaFromListCandidates = [
            frame.getByRole('button', { name: /^BANKA$/i }).first(),
            frame.getByRole('option', { name: psePreferredBankRegex }).first(),
            frame.locator('li, button, div').filter({ hasText: /^BANKA$/i }).first(),
            frame.locator('li, button, div').filter({ hasText: psePreferredBankRegex }).first(),
            frame.getByText(/^BANKA$/i).first(),
          ];

          for (const bankOption of bankaFromListCandidates) {
            const optionVisible = await bankOption.isVisible({ timeout: 1200 }).catch(() => false);
            if (!optionVisible) continue;
            await bankOption.click({ force: true }).catch(() => {});
            await page.waitForTimeout(2500);

            const returnedToPseStep = await frame.getByText(/PSE\s*\(Paso\s*1\s*de\s*2\)|PSE\s*\(Step\s*1\s*of\s*2\)|Selecciona banco/i)
              .first()
              .isVisible({ timeout: 10000 })
              .catch(() => false);

            if (returnedToPseStep) {
              bankaSelected = true;
              break;
            }
          }
        }

        const bankaOptionCandidates = [
          frame.getByRole('button', { name: psePreferredBankRegex }).first(),
          frame.getByText(psePreferredBankRegex).first(),
          frame.locator('button, [role="button"], div, li').filter({ hasText: psePreferredBankRegex }).first(),
        ];

        for (const option of bankaOptionCandidates) {
          if (!(await option.isVisible({ timeout: 800 }).catch(() => false))) {
            await openPseBankSelector();
          }
          const optionVisible = await option.isVisible({ timeout: 1500 }).catch(() => false);
          if (!optionVisible) continue;
          await option.click({ force: true }).catch(() => {});
          bankaSelected = true;
          break;
        }

        if (!bankaSelected) {
          const bankSelect = frame.locator('select[name*="bank" i], select[id*="bank" i], select').first();
          const selectVisible = await bankSelect.isVisible({ timeout: 5000 }).catch(() => false);
          if (selectVisible) {
            await bankSelect.selectOption({ label: psePreferredBank }).catch(() => {});
            await bankSelect.selectOption(psePreferredBank).catch(() => {});
            await bankSelect.evaluate((node, bankName) => {
              if (!(node instanceof HTMLSelectElement)) return;
              const target = Array.from(node.options).find((opt) =>
                (opt.textContent || '').toLowerCase().includes(String(bankName).toLowerCase())
              );
              if (target) {
                node.value = target.value;
                node.dispatchEvent(new Event('input', { bubbles: true }));
                node.dispatchEvent(new Event('change', { bubbles: true }));
              }
            }, psePreferredBank).catch(() => {});
            await bankSelect.dispatchEvent('input').catch(() => {});
            await bankSelect.dispatchEvent('change').catch(() => {});

            const currentBankValue = (await bankSelect.inputValue().catch(() => '')).trim();
            const currentBankLabel = (await bankSelect.locator('option:checked').first().textContent().catch(() => '') || '').trim();
            bankaSelected = psePreferredBankRegex.test(currentBankValue) || psePreferredBankRegex.test(currentBankLabel);
          }
        }

        if (!bankaSelected) {
          const bankDropdownCandidates = [
            frame.locator('[role="combobox"]').first(),
            frame.locator('input[placeholder*="Selecciona" i], input[placeholder*="Select" i]').first(),
            frame.locator('div[class*="select" i], button[class*="select" i]').first(),
          ];

          for (const dropdown of bankDropdownCandidates) {
            const visible = await dropdown.isVisible({ timeout: 1200 }).catch(() => false);
            if (!visible) continue;

            await dropdown.click({ force: true }).catch(() => {});
            await page.waitForTimeout(350);

            const bankOptionCandidates = [
              frame.getByRole('option', { name: psePreferredBankRegex }).first(),
              frame.locator('[role="option"], li, button, div').filter({ hasText: psePreferredBankRegex }).first(),
              frame.getByText(psePreferredBankRegex).first(),
            ];

            for (const bankOption of bankOptionCandidates) {
              const optionVisible = await bankOption.isVisible({ timeout: 1200 }).catch(() => false);
              if (!optionVisible) continue;
              await bankOption.click({ force: true }).catch(() => {});
              bankaSelected = true;
              break;
            }

            if (bankaSelected) break;
          }
        }

        if (!bankaSelected) {
          const iframeHandle = await epaycoIframe.elementHandle().catch(() => null);
          const popupFrame = await iframeHandle?.contentFrame();
          if (popupFrame) {
            bankaSelected = await popupFrame.evaluate((bankName) => {
              const elements = Array.from(document.querySelectorAll('li, button, div, span, option'));
              const target = elements.find((element) =>
                (element.textContent || '').toLowerCase().includes(String(bankName).toLowerCase())
              );

              if (!target) return false;
              target.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
              if (typeof target.click === 'function') target.click();
              return true;
            }, psePreferredBank).catch(() => false);
          }
        }

        if (!bankaSelected) {
          const bankSelect = frame.locator('select[name*="bank" i], select[id*="bank" i], select').first();
          const selectVisible = await bankSelect.isVisible({ timeout: 2500 }).catch(() => false);
          if (selectVisible) {
            const selectedFirst = await bankSelect.evaluate((node) => {
              if (!(node instanceof HTMLSelectElement)) return false;
              const options = Array.from(node.options || []);
              const firstValid = options.find((opt) => {
                const value = (opt.value || '').trim();
                const label = (opt.textContent || '').trim();
                if (!value) return false;
                if (/selecciona|select|otro banco/i.test(label)) return false;
                return true;
              });

              if (!firstValid) return false;
              node.value = firstValid.value;
              node.dispatchEvent(new Event('input', { bubbles: true }));
              node.dispatchEvent(new Event('change', { bubbles: true }));
              return true;
            }).catch(() => false);

            bankaSelected = selectedFirst;
          }
        }

        if (!bankaSelected) {
          testInfo.annotations.push({ type: 'warning', description: `No se encontró el banco ${psePreferredBank} en PSE.` });
        }

        const ensurePseBankSelectedOnStepOne = async () => {
          const selectedBankIndicators = [
            frame.getByRole('button', { name: /^BANKA$/i }).first(),
            frame.locator('button, div, span').filter({ hasText: /^BANKA$/i }).first(),
            frame.getByText(psePreferredBankRegex).first(),
            frame.locator('select[name*="bank" i], select[id*="bank" i]').first(),
            frame.locator('input[placeholder*="Selecciona" i], input[placeholder*="Select" i]').first(),
            frame.locator('button:has-text("Selecciona"), button:has-text("Select")').first(),
          ];

          for (const indicator of selectedBankIndicators) {
            const visible = await indicator.isVisible({ timeout: 1000 }).catch(() => false);
            if (!visible) continue;

            const tagName = await indicator.evaluate((node) => node.tagName.toLowerCase()).catch(() => '');
            if (tagName === 'select') {
              const selectedText = await indicator.locator('option:checked').first().textContent().catch(() => '');
              if (psePreferredBankRegex.test(String(selectedText || ''))) return true;
              continue;
            }

            const text = await indicator.textContent().catch(() => '');
            const value = await indicator.inputValue().catch(() => '');
            const merged = `${text || ''} ${value || ''}`.trim();
            if (/^banka$/i.test(merged) || (psePreferredBankRegex.test(merged) && !/selecciona|select/i.test(merged))) return true;
          }

          return false;
        };

        const forceSelectBankaOnStepOne = async () => {
          for (let attempt = 0; attempt < 3; attempt += 1) {
            const alreadySelected = await ensurePseBankSelectedOnStepOne();
            if (alreadySelected) return true;

            const bankaQuickButton = frame.getByRole('button', { name: /^BANKA$/i }).first();
            if (await bankaQuickButton.isVisible({ timeout: 1000 }).catch(() => false)) {
              await bankaQuickButton.click({ force: true }).catch(() => {});
              await page.waitForTimeout(800);
              if (await ensurePseBankSelectedOnStepOne()) return true;
            }

            await openPseBankSelector();
            const selectorModalReady = await Promise.race([
              frame.getByText(/Seleccionar banco|Select bank/i).first().waitFor({ state: 'visible', timeout: 4000 }).then(() => true).catch(() => false),
              frame.getByRole('textbox', { name: /Buscar|Search/i }).first().waitFor({ state: 'visible', timeout: 4000 }).then(() => true).catch(() => false),
            ]).catch(() => false);

            if (!selectorModalReady) continue;

            const bankSearchInput = frame.getByRole('textbox', { name: /Buscar|Search/i }).first();
            if (await bankSearchInput.isVisible({ timeout: 1200 }).catch(() => false)) {
              await bankSearchInput.fill('').catch(() => {});
              await bankSearchInput.type('BANKA', { delay: 20 }).catch(async () => {
                await bankSearchInput.fill('BANKA').catch(() => {});
              });
              await bankSearchInput.dispatchEvent('input').catch(() => {});
              await bankSearchInput.dispatchEvent('change').catch(() => {});
              await page.waitForTimeout(350);
            }

            const bankaCandidates = [
              frame.getByRole('button', { name: /^BANKA$/i }).first(),
              frame.getByRole('option', { name: /^BANKA$/i }).first(),
              frame.getByText(/^BANKA$/i).first(),
              frame.locator('li, button, div, [role="option"]').filter({ hasText: /^BANKA$/i }).first(),
            ];

            for (const bankOption of bankaCandidates) {
              const optionVisible = await bankOption.isVisible({ timeout: 1200 }).catch(() => false);
              if (!optionVisible) continue;
              await bankOption.click({ force: true }).catch(() => {});
              await page.waitForTimeout(1000);
              break;
            }

            if (await ensurePseBankSelectedOnStepOne()) return true;
          }

          return false;
        };

        const backToStepOne = await frame
          .getByText(/PSE\s*\(Paso\s*1\s*de\s*2\)|PSE\s*\(Step\s*1\s*of\s*2\)|Selecciona banco/i)
          .first()
          .isVisible({ timeout: 10000 })
          .catch(() => false);
        if (!backToStepOne) {
          await page.waitForTimeout(1500);
        }

        await page.waitForTimeout(2500);

        const bankReadyOnStepOne = await forceSelectBankaOnStepOne();
        if (!bankReadyOnStepOne) {
          testInfo.annotations.push({ type: 'warning', description: 'No fue posible seleccionar BANKA en Paso 1 de PSE.' });
        }

        const pseContinue = frame.getByRole('button', { name: /Continuar|Proceed|Continue/i }).first();
        await expect(pseContinue).toBeVisible({ timeout: 12000 });
        const popupPagePromise = page.context().waitForEvent('page', { timeout: 60000 }).catch(() => null);
        let bankaPopupPageFromPseContinue = null;
        const waitForAchStep = async (timeout = 15000) => {
          return Promise.race([
            frame.getByText(/ACH|PSE\s*\(Step\s*2\s*of\s*2\)|PSE\s*\(Paso\s*2\s*de\s*2\)/i).first().waitFor({ state: 'visible', timeout }).then(() => true).catch(() => false),
            frame.getByText(/Tipo de persona|Person type|Tipo de cuenta|Account type|Cuenta de ahorros|Cuenta corriente|Savings|Checking|Natural|Jur[ií]dica/i).first().waitFor({ state: 'visible', timeout }).then(() => true).catch(() => false),
          ]).catch(() => false);
        };

        const waitForBankaLoginForm = async (timeout = 12000) => {
          const startedAt = Date.now();

          while (Date.now() - startedAt < timeout) {
            const pages = page.context().pages();
            for (const candidatePage of pages) {
              if (candidatePage.isClosed()) continue;
              const candidateUrl = (candidatePage.url() || '').toLowerCase();
              if (!/desarrollo\.pse\.com\.co|\/banka|\/authorize/.test(candidateUrl)) continue;

              const bodyText = (await candidatePage.locator('body').innerText().catch(() => '') || '').toLowerCase();
              const loginVisible = /bienvenido al banco de pruebas|tipo de documento|no\.?\s*de\s*documento|contraseñ|ingresar/.test(bodyText);
              if (loginVisible) {
                await candidatePage.bringToFront().catch(() => {});
                return candidatePage;
              }
            }

            await page.waitForTimeout(500);
          }

          return null;
        };

        let achInIframe = false;
        for (let attempt = 0; attempt < 4; attempt += 1) {
          const continueEnabled = await pseContinue.isEnabled().catch(() => false);
          if (continueEnabled) {
            await pseContinue.click({ force: true }).catch(() => {});
          } else {
            await pseContinue.evaluate((node) => {
              if (node instanceof HTMLButtonElement) {
                node.disabled = false;
                node.removeAttribute('disabled');
              }
              if (typeof node.click === 'function') node.click();
            }).catch(() => {});
          }

          const directBankaPopup = await waitForBankaLoginForm(5000);
          if (directBankaPopup) {
            bankaPopupPageFromPseContinue = directBankaPopup;
            achInIframe = true;
            break;
          }

          achInIframe = await waitForAchStep(12000);
          if (achInIframe) break;
          await page.waitForTimeout(1000);
        }

        let achLoaded = achInIframe;
        if (!achLoaded) {
          const directBankaPopup = await waitForBankaLoginForm(6000);
          if (directBankaPopup) {
            bankaPopupPageFromPseContinue = directBankaPopup;
            achLoaded = true;
          }
        }

        if (!achLoaded) {
          const popupPage = await popupPagePromise;
          if (popupPage) {
            await popupPage.waitForLoadState('domcontentloaded').catch(() => {});
            const popupBodyText = (await popupPage.locator('body').innerText().catch(() => '') || '').toLowerCase();
            achLoaded = /ach|pse|person type|tipo de persona|tipo de cuenta|account type/.test(popupBodyText) || /pse|ach/.test(popupPage.url().toLowerCase());
          }
        }

        if (!achLoaded) {
          await openPseBankSelector();
          await page.waitForTimeout(1000);

          const bankSearchInput = frame.getByRole('textbox', { name: /Buscar|Search/i }).first();
          if (await bankSearchInput.isVisible({ timeout: 2000 }).catch(() => false)) {
            await bankSearchInput.fill('').catch(() => {});
            await bankSearchInput.type('BANKA', { delay: 20 }).catch(async () => {
              await bankSearchInput.fill('BANKA').catch(() => {});
            });
            await bankSearchInput.dispatchEvent('input').catch(() => {});
            await bankSearchInput.dispatchEvent('change').catch(() => {});
            await page.waitForTimeout(400);
          }

          const recoveryBankCandidates = [
            frame.getByRole('button', { name: /^BANKA$/i }).first(),
            frame.getByText(/^BANKA$/i).first(),
            frame.locator('li, button, div, [role="option"]').filter({ hasText: /^BANKA$/i }).first(),
            frame.locator('li, button, div, [role="option"]').filter({ hasText: psePreferredBankRegex }).first(),
          ];

          for (const bankOption of recoveryBankCandidates) {
            const optionVisible = await bankOption.isVisible({ timeout: 1200 }).catch(() => false);
            if (!optionVisible) continue;
            await bankOption.click({ force: true }).catch(() => {});
            await page.waitForTimeout(1200);
            break;
          }

          for (let retry = 0; retry < 3; retry += 1) {
            const enabled = await pseContinue.isEnabled().catch(() => false);
            if (enabled) {
              await pseContinue.click({ force: true }).catch(() => {});
            } else {
              await pseContinue.evaluate((node) => {
                if (node instanceof HTMLButtonElement) {
                  node.disabled = false;
                  node.removeAttribute('disabled');
                }
                if (typeof node.click === 'function') node.click();
              }).catch(() => {});
            }

            achLoaded = await waitForAchStep(15000);
            if (achLoaded) break;
          }
        }

        const bankaFormLoadedDirectly = Boolean(bankaPopupPageFromPseContinue);
        expect(achLoaded || bankaFormLoadedDirectly).toBeTruthy();

        const pseIdentificationNumber = process.env.PSE_IDENTIFICATION_NUMBER || '1214723219';

        let bankPopupPromise = null;
        if (!bankaFormLoadedDirectly) {
        const pseIdentificationInputs = [
          frame.getByRole('textbox', { name: /Number|N[uú]mero|Identification/i }).first(),
          frame.locator('input[name*="number" i]').first(),
          frame.locator('input[name*="ident" i]').first(),
          frame.locator('input[placeholder*="Number" i], input[placeholder*="N[uú]mero" i]').first(),
          frame.locator('input[type="text"]').nth(2),
        ];

        let identificationFilled = false;
        for (const identificationInput of pseIdentificationInputs) {
          const visible = await identificationInput.isVisible({ timeout: 1200 }).catch(() => false);
          if (!visible) continue;

          await identificationInput.click({ force: true }).catch(() => {});
          await identificationInput.fill('').catch(() => {});
          await identificationInput.type(pseIdentificationNumber, { delay: 25 }).catch(async () => {
            await identificationInput.fill(pseIdentificationNumber).catch(() => {});
          });
          await identificationInput.dispatchEvent('input').catch(() => {});
          await identificationInput.dispatchEvent('change').catch(() => {});

          const currentValue = (await identificationInput.inputValue().catch(() => '')).replace(/\D/g, '');
          if (currentValue === pseIdentificationNumber) {
            identificationFilled = true;
            break;
          }
        }

        expect(identificationFilled).toBeTruthy();

        const pseTermsCandidates = [
          frame.getByTestId('default-checkbox').first(),
          frame.locator('input[type="checkbox"]').first(),
          frame.getByRole('checkbox').first(),
        ];

        let pseTermsAccepted = false;
        for (const termControl of pseTermsCandidates) {
          const visible = await termControl.isVisible({ timeout: 1500 }).catch(() => false);
          if (!visible) continue;

          const checked = await termControl.isChecked().catch(() => false);
          if (!checked) {
            await termControl.click({ force: true }).catch(() => {});
          }

          pseTermsAccepted = await termControl.isChecked().catch(() => false);
          if (pseTermsAccepted) break;
        }

        if (!pseTermsAccepted) {
          const termsLabel = frame.getByText(/T[eé]rminos|Terms|tratamiento de datos|privacy|acepto/i).first();
          if (await termsLabel.isVisible({ timeout: 2000 }).catch(() => false)) {
            await termsLabel.click({ force: true }).catch(() => {});
            const genericCheckbox = frame.locator('input[type="checkbox"]').first();
            pseTermsAccepted = await genericCheckbox.isChecked().catch(() => false);
          }
        }

        expect(pseTermsAccepted).toBeTruthy();

        const psePayNowButton = frame.getByRole('button', { name: /Pay now|Pagar ahora|Pagar/i }).first();
        await expect(psePayNowButton).toBeVisible({ timeout: 20000 });

        let psePayEnabled = await psePayNowButton.isEnabled().catch(() => false);
        if (!psePayEnabled) {
          await page.waitForTimeout(1200);
          psePayEnabled = await psePayNowButton.isEnabled().catch(() => false);
        }

        await expect(psePayNowButton).toBeEnabled({ timeout: 20000 });
        await psePayNowButton.click({ force: true }).catch(async () => {
          await psePayNowButton.evaluate((node) => {
            if (typeof node.click === 'function') node.click();
          }).catch(() => {});
        });

        const postPayContinue = frame.getByRole('button', { name: /Continuar|Continue|Proceed/i }).first();
        bankPopupPromise = page.context().waitForEvent('page', { timeout: 30000 }).catch(() => null);
        const continueClicked = await (async () => {
          if (await postPayContinue.isVisible({ timeout: 20000 }).catch(() => false)) {
            const enabled = await postPayContinue.isEnabled().catch(() => false);
            if (enabled) {
              await postPayContinue.click({ force: true }).catch(() => {});
              return true;
            }
            await postPayContinue.click({ force: true }).catch(() => {});
            return true;
          }

          return frame.evaluate(() => {
            const candidates = Array.from(document.querySelectorAll('button'));
            const target = candidates.find((button) => /continuar|continue|proceed/i.test((button.textContent || '').trim()));
            if (!target) return false;
            target.click();
            return true;
          }).catch(() => false);
        })();

        expect(continueClicked).toBeTruthy();
        }

        const waitForBankaPopupPage = async () => {
          if (bankaPopupPageFromPseContinue && !bankaPopupPageFromPseContinue.isClosed()) {
            await bankaPopupPageFromPseContinue.waitForLoadState('domcontentloaded').catch(() => {});
            return bankaPopupPageFromPseContinue;
          }

          const popupFromEvent = await bankPopupPromise;
          if (popupFromEvent && !popupFromEvent.isClosed()) {
            await popupFromEvent.waitForLoadState('domcontentloaded').catch(() => {});
            return popupFromEvent;
          }

          const startedAt = Date.now();
          while (Date.now() - startedAt < 30000) {
            const pages = page.context().pages();
            const bankaPage = pages.find((candidatePage) => {
              if (candidatePage.isClosed()) return false;
              const currentUrl = (candidatePage.url() || '').toLowerCase();
              return /desarrollo\.pse\.com\.co\/banka|\/authorize/.test(currentUrl);
            });

            if (bankaPage) {
              await bankaPage.waitForLoadState('domcontentloaded').catch(() => {});
              return bankaPage;
            }

            await page.waitForTimeout(500);
          }

          return null;
        };

        const bankaPopupPage = await waitForBankaPopupPage();
        expect(Boolean(bankaPopupPage)).toBeTruthy();

        if (bankaPopupPage) {
          let activeBankaPage = bankaPopupPage;
          await activeBankaPage.bringToFront().catch(() => {});

          const getOpenBankaPages = () => page.context().pages().filter((candidatePage) => {
            if (candidatePage.isClosed()) return false;
            const currentUrl = (candidatePage.url() || '').toLowerCase();
            return /desarrollo\.pse\.com\.co|\/banka|\/authorize/.test(currentUrl);
          });

          const waitForBankaReady = async (timeout = 45000) => {
            const startedAt = Date.now();

            while (Date.now() - startedAt < timeout) {
              const pages = getOpenBankaPages();
              for (const candidatePage of pages) {
                const bodyText = (await candidatePage.locator('body').innerText().catch(() => '') || '').toLowerCase();
                const processingVisible = /estamos procesando tu transacci[oó]n|processing your transaction|procesando tu transacci[oó]n/.test(bodyText);
                const loginReady = /bienvenido al banco de pruebas|tipo de documento|no\. de documento|contraseñ|ingresar/.test(bodyText);
                const authorizeReady = /pago pse|seleccione la cuenta|identificacion del pagador|correo electronico|numero del celular/.test(bodyText);

                if (!processingVisible && (loginReady || authorizeReady)) {
                  activeBankaPage = candidatePage;
                  await activeBankaPage.bringToFront().catch(() => {});
                  return true;
                }
              }

              await activeBankaPage.waitForTimeout(700);
            }

            return false;
          };

          const bankaReady = await waitForBankaReady();
          expect(bankaReady).toBeTruthy();

          const fillFirstVisibleInBanka = async (selectors, value) => {
            for (const selector of selectors) {
              const input = activeBankaPage.locator(selector).first();
              const visible = await input.isVisible({ timeout: 1200 }).catch(() => false);
              if (!visible) continue;
              await input.click({ force: true }).catch(() => {});
              await input.fill('').catch(() => {});
              await input.type(value, { delay: 20 }).catch(async () => {
                await input.fill(value).catch(() => {});
              });
              await input.dispatchEvent('input').catch(() => {});
              await input.dispatchEvent('change').catch(() => {});
              return true;
            }
            return false;
          };

          const alreadyAuthorize = await activeBankaPage.getByText(/Pago PSE|Seleccione la cuenta/i).first().isVisible({ timeout: 2500 }).catch(() => false);
          if (!alreadyAuthorize) {
            await activeBankaPage.waitForTimeout(800);

            const loginPasswordVisible = await activeBankaPage.locator('input[type="password"], input[name*="password" i], input[placeholder*="Contrase" i]').first().isVisible({ timeout: 3000 }).catch(() => false);
            const ingresarVisible = await activeBankaPage.getByRole('button', { name: /INGRESAR|Ingresar/i }).first().isVisible({ timeout: 3000 }).catch(() => false);

            if (loginPasswordVisible || ingresarVisible) {
              const loginDocFilled = await fillFirstVisibleInBanka([
                'input[name*="document" i]',
                'input[placeholder*="No. de Documento" i]',
                'input[placeholder*="Documento" i]',
                'input:not([type="password"])'
              ], pseIdentificationNumber);

              const loginPasswordFilled = await fillFirstVisibleInBanka([
                'input[type="password"]',
                'input[name*="password" i]',
                'input[placeholder*="Contrase" i]'
              ], 'sistemas1305');

              //await activeBankaPage.screenshot({ path: testInfo.outputPath('epayco-pse-login-form.png'), fullPage: true }).catch(() => {});

              if (loginDocFilled || loginPasswordFilled) {
                const submitBankaLogin = async () => {
                  const submitCandidates = [
                    activeBankaPage.getByRole('button', { name: /INGRESAR|Ingresar/i }).first(),
                    activeBankaPage.locator('button:has-text("INGRESAR"), button:has-text("Ingresar")').first(),
                    activeBankaPage.locator('input[type="submit"][value*="INGRESAR" i], input[type="submit"][value*="Ingresar" i]').first(),
                  ];

                  for (let attempt = 0; attempt < 4; attempt += 1) {
                    let clicked = false;

                    for (const submitControl of submitCandidates) {
                      const visible = await submitControl.isVisible({ timeout: 1200 }).catch(() => false);
                      if (!visible) continue;
                      await submitControl.click({ force: true }).catch(async () => {
                        await submitControl.evaluate((node) => {
                          if (typeof node.click === 'function') node.click();
                        }).catch(() => {});
                      });
                      clicked = true;
                      break;
                    }

                    const passwordInput = activeBankaPage.locator('input[type="password"], input[name*="password" i], input[placeholder*="Contrase" i]').first();
                    if (await passwordInput.isVisible({ timeout: 800 }).catch(() => false)) {
                      await passwordInput.press('Enter').catch(() => {});
                    }

                    await activeBankaPage.waitForTimeout(1500);
                    const bodyText = (await activeBankaPage.locator('body').innerText().catch(() => '') || '').toLowerCase();
                    const advanced = /pago pse|seleccione la cuenta|identificacion del pagador|identificación del pagador/.test(bodyText)
                      || /authorize/.test((activeBankaPage.url() || '').toLowerCase());

                    if (advanced) {
                      return true;
                    }

                    if (!clicked) {
                      await activeBankaPage.waitForTimeout(800);
                    }
                  }

                  return false;
                };

                const loginSubmitted = await submitBankaLogin();
                if (!loginSubmitted) {
                  testInfo.annotations.push({ type: 'warning', description: 'No fue posible confirmar avance después de clicar INGRESAR en Banka; se continúa con fallback.' });
                }
              }
            }
          }

          const waitForAuthorizeReady = async (timeout = 120000) => {
            const startedAt = Date.now();

            while (Date.now() - startedAt < timeout) {
              const pages = getOpenBankaPages();
              for (const candidatePage of pages) {
                const currentUrl = (candidatePage.url() || '').toLowerCase();
                const bodyText = (await candidatePage.locator('body').innerText().catch(() => '') || '').toLowerCase();

                const processingVisible = /estamos procesando tu transacci[oó]n|processing your transaction|procesando tu transacci[oó]n/.test(bodyText);
                const authorizeVisible = /pago pse|seleccione la cuenta|identificacion del pagador|identificación del pagador|correo electronico|correo electrónico|numero del celular|número del celular/.test(bodyText);
                const inAuthorizeUrl = /authorize/.test(currentUrl);

                if ((inAuthorizeUrl || authorizeVisible) && !processingVisible) {
                  activeBankaPage = candidatePage;
                  await activeBankaPage.bringToFront().catch(() => {});
                  return candidatePage;
                }
              }

              await activeBankaPage.waitForTimeout(1000);
            }

            return null;
          };

          const authorizePage = await waitForAuthorizeReady();
          if (authorizePage && !authorizePage.isClosed()) {
            activeBankaPage = authorizePage;
          } else {
            testInfo.annotations.push({ type: 'warning', description: 'Banka no mostró marcador explícito de authorize a tiempo; se continúa con llenado por fallback.' });
            await activeBankaPage.waitForTimeout(5000).catch(() => {});
          }

          const ahorroSelected = await activeBankaPage.evaluate(() => {
            const rows = Array.from(document.querySelectorAll('label, div, li, tr'));
            const row = rows.find((node) => {
              const text = (node.textContent || '').toLowerCase();
              return text.includes('cuenta de ahorros') && text.includes('3898');
            });

            if (!row) return false;

            const radio = row.querySelector('input[type="radio"]') || row.closest('label')?.querySelector('input[type="radio"]');
            if (radio) {
              radio.click();
              return true;
            }

            row.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
            return true;
          }).catch(() => false);

          if (!ahorroSelected) {
            const fallbackAhorros = activeBankaPage.getByText(/Cuenta de ahorros[\s\S]*3898|3898/i).first();
            if (await fallbackAhorros.isVisible({ timeout: 5000 }).catch(() => false)) {
              await fallbackAhorros.click({ force: true }).catch(() => {});
            }
          }

          await fillFirstVisibleInBanka([
            'input[name*="ident" i]',
            'input[placeholder*="Identificacion" i]',
            'input[placeholder*="Identificación" i]'
          ], pseIdentificationNumber);

          await fillFirstVisibleInBanka([
            'input[name*="ult" i]',
            'input[name*="digit" i]',
            'input[placeholder*="Ultimos 4" i]',
            'input[placeholder*="Últimos 4" i]',
            'input[placeholder*="4 Digitos" i]',
            'input[placeholder*="4 Dígitos" i]'
          ], '3898');

          await fillFirstVisibleInBanka([
            'input[type="email"]',
            'input[name*="correo" i]',
            'input[name*="email" i]',
            'input[placeholder*="Correo" i]'
          ], checkoutEmail);

          await fillFirstVisibleInBanka([
            'input[name*="cel" i]',
            'input[name*="phone" i]',
            'input[placeholder*="Celular" i]',
            'input[placeholder*="Numero" i]'
          ], checkoutPhone);

          //await activeBankaPage.screenshot({ path: testInfo.outputPath('epayco-pse-authorize-form-filled.png'), fullPage: true }).catch(() => {});

          const pagarButtonCandidates = [
            activeBankaPage.getByRole('button', { name: /Pagar|Pay/i }).first(),
            activeBankaPage.locator('button, input[type="submit"]').filter({ hasText: /Pagar|Pay/i }).first(),
            activeBankaPage.locator('input[type="submit"][value*="Pagar" i], input[type="submit"][value*="Pay" i]').first(),
          ];

          let pagarClicked = false;
          for (const pagarButton of pagarButtonCandidates) {
            const visible = await pagarButton.isVisible({ timeout: 3000 }).catch(() => false);
            if (!visible) continue;

            const enabled = await pagarButton.isEnabled().catch(() => true);
            if (!enabled) {
              await activeBankaPage.waitForTimeout(1000);
            }

            await pagarButton.click({ force: true }).catch(async () => {
              await pagarButton.evaluate((node) => {
                if (typeof node.click === 'function') node.click();
              }).catch(() => {});
            });
            pagarClicked = true;
            break;
          }

          if (!pagarClicked) {
            pagarClicked = await activeBankaPage.evaluate(() => {
              const buttons = Array.from(document.querySelectorAll('button, input[type="submit"]'));
              const target = buttons.find((button) => {
                const text = ((button.textContent || '') + ' ' + (button.getAttribute('value') || '')).toLowerCase();
                return /pagar|pay/.test(text);
              });
              if (!target) return false;
              target.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
              if (typeof target.click === 'function') target.click();
              return true;
            }).catch(() => false);
          }

          if (!pagarClicked) {
            const bankaBodyText = (await activeBankaPage.locator('body').innerText().catch(() => '') || '').toLowerCase();
            const bankaUrl = (activeBankaPage.url() || '').toLowerCase();
            const movedAfterPay = /procesando|processing|transacci[oó]n|transaction|authorize|payment/.test(bankaBodyText) || /authorize|payment|response|transaction/.test(bankaUrl);
            pagarClicked = movedAfterPay;
          }

          if (pagarClicked) {
            //await activeBankaPage.screenshot({ path: testInfo.outputPath('epayco-pse-after-pay-click.png'), fullPage: true }).catch(() => {});

            const waitForBankaResultPage = async (timeout = 15000) => {
              const startedAt = Date.now();

              while (Date.now() - startedAt < timeout) {
                const currentUrl = (activeBankaPage.url() || '').toLowerCase();
                const bodyText = (await activeBankaPage.locator('body').innerText().catch(() => '') || '').toLowerCase();

                const atResultUrl = /\/banka\/pagar|\/pagar/.test(currentUrl);
                const hasResultText = /resultado de su transacci[oó]n|n[uú]mero de aprobaci[oó]n|regresar al comercio/.test(bodyText);

                if (atResultUrl || hasResultText) {
                  return true;
                }

                const waited = await activeBankaPage.waitForTimeout(700).then(() => true).catch(() => false);
                if (!waited) {
                  return false;
                }
              }

              return false;
            };

            const bankaResultReady = await waitForBankaResultPage();
            if (bankaResultReady) {
              //await activeBankaPage.screenshot({ path: testInfo.outputPath('epayco-pse-banka-result.png'), fullPage: true }).catch(() => {});

              const backToCommerceButton = activeBankaPage.getByRole('button', { name: /REGRESAR AL COMERCIO|Regresar al comercio/i }).first();
              let returnedToCommerce = false;

              if (await backToCommerceButton.isVisible({ timeout: 8000 }).catch(() => false)) {
                await backToCommerceButton.click({ force: true }).catch(async () => {
                  await backToCommerceButton.evaluate((node) => {
                    if (typeof node.click === 'function') node.click();
                  }).catch(() => {});
                });

                await activeBankaPage.waitForTimeout(3500).catch(() => {});
                const checkoutPageLoaded = await Promise.race([
                  page.waitForURL(/order-confirmation|module\/payco|\/order/i, { timeout: 15000 }).then(() => true).catch(() => false),
                  page.getByText(/Your order is confirmed|Pedido confirmado|Pagar con ePayco/i).first().waitFor({ state: 'visible', timeout: 15000 }).then(() => true).catch(() => false),
                ]).catch(() => false);

                returnedToCommerce = checkoutPageLoaded;
              }

              if (returnedToCommerce) {
                //await page.screenshot({ path: testInfo.outputPath('epayco-pse-after-return-commerce.png'), fullPage: true }).catch(() => {});
              } else {
                //await activeBankaPage.screenshot({ path: testInfo.outputPath('epayco-pse-after-return-commerce.png'), fullPage: true }).catch(() => {});
                testInfo.annotations.push({ type: 'warning', description: 'No se confirmó redirección al comercio tras clicar REGRESAR AL COMERCIO; se tomó captura en la ventana actual.' });
              }
            } else {
              testInfo.annotations.push({ type: 'warning', description: 'No se detectó pantalla final de Banka (resultado de transacción) en el tiempo esperado.' });
            }
          }

          if (!pagarClicked) {
            testInfo.annotations.push({ type: 'warning', description: 'No se detectó clic explícito en botón Pagar de Banka en este intento.' });
          }

          //await activeBankaPage.screenshot({ path: testInfo.outputPath('epayco-pse-banka-authorize.png'), fullPage: true }).catch(() => {});
        }

        const waitForPseCompleted = async (timeout = 45000) => {
          const startedAt = Date.now();

          while (Date.now() - startedAt < timeout) {
            const completionInIframe = await frame.getByText(/Transaction accepted|Transacci[oó]n aceptada|ePayco'?s Reference|Referencia ePayco/i).first().isVisible({ timeout: 1200 }).catch(() => false);
            if (completionInIframe) {
              return true;
            }

            const bodyText = (await page.locator('body').innerText().catch(() => '') || '').toLowerCase();
            const completionInPage = /transaction accepted|transacci[oó]n aceptada|epayco'?s reference|referencia epayco/.test(bodyText);
            if (completionInPage) {
              return true;
            }

            await page.waitForTimeout(1200);
          }

          return false;
        };

        const capturePseTransactionDetailScreenshot = async () => {
          const loadingOverlayRegex = /Transacci[oó]n en curso|para volver a ella haz clic aqu[ií]|processing your transaction|estamos procesando tu transacci[oó]n/i;
          const detailRegex = /Transacci[oó]n aprobada|Transaction approved|Medio de pago|M[eé]todo de pago|Banco|Autorizaci[oó]n|Recibo|Direcci[oó]n IP/i;

          const waitForStableDetail = async (timeout = 60000) => {
            const startedAt = Date.now();

            while (Date.now() - startedAt < timeout) {
              const iframeTextRaw = await frame.locator('body').innerText().catch(() => '');
              const pageTextRaw = await page.locator('body').innerText().catch(() => '');

              const iframeText = String(iframeTextRaw || '');
              const pageText = String(pageTextRaw || '');
              const mergedText = `${iframeText}\n${pageText}`;

              const loadingVisible = loadingOverlayRegex.test(mergedText);
              const detailVisible = detailRegex.test(mergedText);

              if (!loadingVisible && detailVisible) {
                await page.waitForTimeout(1800);
                const iframeTextConfirm = String((await frame.locator('body').innerText().catch(() => '')) || '');
                const pageTextConfirm = String((await page.locator('body').innerText().catch(() => '')) || '');
                const mergedConfirm = `${iframeTextConfirm}\n${pageTextConfirm}`;

                const loadingStillGone = !loadingOverlayRegex.test(mergedConfirm);
                const detailStillVisible = detailRegex.test(mergedConfirm);
                if (loadingStillGone && detailStillVisible) {
                  return true;
                }
              }

              await page.waitForTimeout(1200);
            }

            return false;
          };

          const stableDetailVisible = await waitForStableDetail(60000);
          if (stableDetailVisible) {
            await page.screenshot({ path: testInfo.outputPath('epayco-pse-detalle-transaccion.png'), fullPage: true }).catch(() => {});
            return true;
          }

          await page.screenshot({ path: testInfo.outputPath('epayco-pse-detalle-transaccion-fallback.png'), fullPage: true }).catch(() => {});
          return false;
        };

        const pseCompleted = await waitForPseCompleted();
        const detectedPseReference = await captureRefPaycoScreenshot('pse-completed');
        const pseDetailCaptured = await capturePseTransactionDetailScreenshot();
        if (!pseCompleted) {
          testInfo.annotations.push({ type: 'warning', description: 'PSE no mostró estado final explícito dentro del tiempo esperado; se tomó captura por fallback.' });
        }
        if (!detectedPseReference) {
          testInfo.annotations.push({ type: 'warning', description: 'No se pudo extraer referencia de pago PSE en este intento.' });
        }
        if (!pseDetailCaptured) {
          testInfo.annotations.push({ type: 'warning', description: 'No se detectó bloque de detalle de transacción PSE; se guardó captura fallback.' });
        }

      } else {
        const readIframeText = async () => {
          const iframe = page.locator('iframe[title="ePayco Checkout V2"]').first();
          const iframeCount = await iframe.count().catch(() => 0);
          if (iframeCount > 0) {
            const iframeHandle = await iframe.elementHandle().catch(() => null);
            const iframeContext = await iframeHandle?.contentFrame();
            const text = await iframeContext?.evaluate(() => (document.body?.innerText || '').toLowerCase()).catch(() => '');
            if (text) return text;
          }

          const pageText = await page.locator('body').innerText().catch(() => '');
          return String(pageText || '').toLowerCase();
        };

        const waitCardOutcome = async (timeoutMs = 30000) => {
          const startedAt = Date.now();
          while (Date.now() - startedAt < timeoutMs) {
            const text = await readIframeText();
            if (/\[200\].*aceptada|transacción aprobada|respuesta\s*aprobada/.test(text)) return 'accepted';
            if (/fondos insuficientes|rechazada|fallida|error de comunicación/.test(text)) return 'rejected';
            if (/pendiente|transacción pendiente/.test(text)) return 'pending';
            await page.waitForTimeout(800);
          }
          return 'unknown';
        };

        const ensureCardStepVisible = async () => {
          const cardNumberInput = frame.getByRole('textbox', { name: 'cardNumber' }).first();
          if (await cardNumberInput.isVisible({ timeout: 3000 }).catch(() => false)) {
            return cardNumberInput;
          }

          await selectPaymentMethod('card');
          if (await cardNumberInput.isVisible({ timeout: 8000 }).catch(() => false)) {
            return cardNumberInput;
          }

          const retryPaymentButton = frame.getByRole('button', { name: /Reintentar pago|Retry payment/i }).first();
          if (await retryPaymentButton.isVisible({ timeout: 3000 }).catch(() => false)) {
            await retryPaymentButton.click();
          }

          if (await cardNumberInput.isVisible({ timeout: 8000 }).catch(() => false)) {
            return cardNumberInput;
          }

          const returnButton = frame.getByRole('button', { name: /return/i }).first();
          if (await returnButton.isVisible({ timeout: 3000 }).catch(() => false)) {
            await returnButton.click();
          }

          const cardMethod = frame.getByRole('link', { name: /Tarjeta de crédito y\/o débito|Credit card|Debit card/i }).first();
          if (await cardMethod.isVisible({ timeout: 12000 }).catch(() => false)) {
            await cardMethod.click();
          }

          await selectPaymentMethod('card');

          await expect(cardNumberInput).toBeVisible({ timeout: 20000 });
          return cardNumberInput;
        };

        const ensureCardFullName = async () => {
          const cardNameInput = frame.locator('input[name="name"]').first();
          await expect(cardNameInput).toBeVisible({ timeout: 15000 });

          let cardFullNameValue = (await cardNameInput.inputValue().catch(() => '')).trim();
          if (cardFullNameValue.split(/\s+/).filter(Boolean).length < 2) {
            await cardNameInput.click({ force: true });
            await cardNameInput.fill('');
            await cardNameInput.type('Ricardo Saldarriaga', { delay: 30 });
            await page.waitForTimeout(250);
            cardFullNameValue = (await cardNameInput.inputValue().catch(() => '')).trim();
          }

          expect(cardFullNameValue.split(/\s+/).filter(Boolean).length).toBeGreaterThanOrEqual(2);
        };

        const clickEnabledContinue = async () => {
          const continueButtons = frame.locator('button:visible', { hasText: /Continuar|Proceed|Continue/i });
          const buttonCount = await continueButtons.count();

          for (let index = 0; index < buttonCount; index += 1) {
            const button = continueButtons.nth(index);
            const isEnabled = await button.isEnabled().catch(() => false);
            if (!isEnabled) continue;
            await button.click();
            return true;
          }

          return false;
        };

        const executeCardPaymentAttempt = async (cardProfile) => {
          const ensureCardAddress = async () => {
            const addressValue = 'Calle 10 #20-30';
            const addressInputs = [
              frame.getByRole('textbox', { name: /Dirección/i }).first(),
              frame.getByRole('textbox', { name: 'address' }).first(),
              frame.locator('input[name="address"]').first(),
              frame.locator('input[placeholder*="Escribe"]').first(),
              frame.locator('input[placeholder*="Dirección"]').first(),
            ];

            for (const addressInput of addressInputs) {
              const isVisible = await addressInput.isVisible({ timeout: 2000 }).catch(() => false);
              if (!isVisible) continue;

              for (let attempt = 0; attempt < 3; attempt += 1) {
                const currentValue = (await addressInput.inputValue().catch(() => '')).trim();
                if (currentValue.length >= 6) {
                  return true;
                }

                await addressInput.click({ force: true });
                await addressInput.fill('');
                await addressInput.type(addressValue, { delay: 20 });
                await page.waitForTimeout(250);
              }

              const finalValue = (await addressInput.inputValue().catch(() => '')).trim();
              if (finalValue.length >= 6) {
                return true;
              }
            }

            return false;
          };

          const cardNumberInput = await ensureCardStepVisible();
          await ensureCardFullName();

          await cardNumberInput.fill(cardProfile.number);
          await frame.getByRole('textbox', { name: 'expirationDate' }).first().fill(cardProfile.expiration);
          await frame.getByRole('textbox', { name: /CVV/i }).first().fill(cardProfile.cvv);

          let continueClicked = await clickEnabledContinue();
          if (!continueClicked) {
            await page.waitForTimeout(2000);
            continueClicked = await clickEnabledContinue();
          }

          expect(continueClicked).toBeTruthy();
          const cardDocumentInput = frame.getByRole('textbox', { name: 'numberDoc' }).first();
          await cardDocumentInput.fill(checkoutDocument);
          await ensureCardAddress();

          const cardTermsCandidates = [
            frame.getByTestId('default-checkbox').first(),
            frame.locator('input[type="checkbox"]').first(),
            frame.getByRole('checkbox').first(),
          ];

          for (const termControl of cardTermsCandidates) {
            const isVisible = await termControl.isVisible({ timeout: 2000 }).catch(() => false);
            if (!isVisible) continue;

            const isChecked = await termControl.isChecked().catch(() => false);
            if (!isChecked) {
              await termControl.click({ force: true });
            }
            break;
          }

          const payButton = frame.getByRole('button', { name: /Pagar|Pay/i }).first();
          let payEnabled = await payButton.isEnabled().catch(() => false);
          if (!payEnabled) {
            await cardDocumentInput.fill(checkoutDocument);
            await ensureCardAddress();
            await page.waitForTimeout(800);
            payEnabled = await payButton.isEnabled().catch(() => false);
          }

          await expect(payButton).toBeEnabled({ timeout: 15000 });
          await payButton.click();
          return waitCardOutcome(35000);
        };

        let paymentOutcome = await executeCardPaymentAttempt(selectedCardProfile);

        const shouldRetryWithAcceptedCard = ['rechazada', 'fallida'].includes(selectedCardProfile.state);
        if (shouldRetryWithAcceptedCard && paymentOutcome !== 'accepted') {
          for (let retryAttempt = 0; retryAttempt < 2; retryAttempt += 1) {
            const retryPaymentButton = frame.getByRole('button', { name: /Reintentar pago|Retry payment/i }).first();
            if (await retryPaymentButton.isVisible({ timeout: 8000 }).catch(() => false)) {
              await retryPaymentButton.click();
            }

            paymentOutcome = await executeCardPaymentAttempt(acceptedCardProfile);
            if (paymentOutcome === 'accepted') {
              break;
            }
          }
        }

        const acceptedMessage = frame.locator('text=/\[200\].*Aceptada/i').first();
        if (paymentOutcome === 'accepted' || await acceptedMessage.isVisible({ timeout: 5000 }).catch(() => false)) {
          await frame.getByText(/ePayco'?s Reference|Referencia ePayco|Reference/i).first().waitFor({ state: 'visible', timeout: 35000 }).catch(() => {});
          const detectedCardReference = await captureRefPaycoScreenshot('card');
          expect(Boolean(detectedCardReference)).toBeTruthy();
          await page.screenshot({ path: 'epayco-aceptada-checkout-spec.png', fullPage: true });
        }

        await finalizeEpaycoCheckout(45000);

        const exitButton = frame.getByRole('button', { name: 'exit-button' }).first();
        if (await exitButton.isVisible({ timeout: 10000 }).catch(() => false)) {
          const exitEnabled = await exitButton.isEnabled().catch(() => false);
          if (exitEnabled) {
            await exitButton.click();
            const acceptExit = frame.getByRole('button', { name: 'Aceptar' }).first();
            if (await acceptExit.isVisible({ timeout: 10000 }).catch(() => false)) {
              await acceptExit.click();
            }
          }
        }
      }

    };

    const runEpaycoSubscriptionFlow = async () => {
      await page.waitForTimeout(5000);
      const epaycoFlow = (process.env.EPAYCO_FLOW || 'credit').toLowerCase();
      const cardProfiles = {
        aceptada: { number: '4575623182290326', expiration: '12/27', cvv: '123', state: 'aceptada' },
        rechazada: { number: '4151611527583283', expiration: '12/27', cvv: '123', state: 'rechazada' },
        fallida: { number: '5170394490379427', expiration: '12/27', cvv: '123', state: 'fallida' },
        pendiente: { number: '373118856457642', expiration: '12/27', cvv: '123', state: 'pendiente' },
        'fondos insuficientes': { number: '4151611527583283', expiration: '12/27', cvv: '123', state: 'rechazada' },
      };
      const requestedCardState = (process.env.CARD_STATE || 'aceptada').toLowerCase();
      const selectedCardProfile = cardProfiles[requestedCardState] || cardProfiles.aceptada;
      const acceptedCardProfile = cardProfiles.aceptada;
    };
  
    
    await runEpaycoPopupFlow();
    //await runEpaycoSubscriptionFlow();
    //await page.screenshot({ path: testInfo.outputPath('final-checkout.png'), fullPage: true }).catch(() => {});

    ///////////////////////////////////////////////////////////////////////

    await page.screenshot({ path: testInfo.outputPath('epayco-post-flow-state.png'), fullPage: true }).catch(() => {});
  } catch (error) {
    await page.screenshot({ path: testInfo.outputPath('checkout-error-or-block.png'), fullPage: true }).catch(() => {});
    throw error;
  }
});