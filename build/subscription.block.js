(() => {
    "use strict";
    const e = window.React,
        t = window.wc.wcBlocksRegistry,
        o = window.wc.wcSettings,
        a = window.wp.element,
        c = window.wp.htmlEntities,
        n = "epayco_blocks_update_cart";
    var r;
    const m = "mp_checkout_blocks", d = "woo-epayco-subscription",
        i = (0, o.getSetting)("woo-epayco-subscription_data", {}),
        p = (0, c.decodeEntities)(i.title) || "Checkout Subscription", u = t => {
            (e => {
                const {extensionCartUpdate: t} = wc.blocksCheckout, {
                    eventRegistration: o,
                    emitResponse: c
                } = e, {onPaymentSetup: r, onCheckoutSuccess: i, onCheckoutFail: p} = o;
                (0, a.useEffect)((() => {
                    ((e, t) => {
                        e({namespace: n, data: {action: "add", gateway: t}})
                    })(t, d);
                    const e = r((() => ({type: c.responseTypes.SUCCESS})));
                    return () => (((e, t) => {
                        e({namespace: n, data: {action: "remove", gateway: t}})
                    })(t, d), e())
                }), [r]), (0, a.useEffect)((() => {
                    const e = i((async e => {
                        const t = e.processingResponse;
                        return {
                            type: c.responseTypes.SUCCESS,
                            messageContext: c.noticeContexts.PAYMENTS,
                            message: t.paymentDetails.message
                        }
                    }));
                    return () => e()
                }), [i]), (0, a.useEffect)((() => {
                    const e = p((e => {
                        const t = e.processingResponse;
                        return {
                            type: c.responseTypes.FAIL,
                            messageContext: c.noticeContexts.PAYMENTS,
                            message: t.paymentDetails.message
                        }
                    }));
                    return () => e()
                }), [p])
            })(t);
            const M = (0, a.useRef)(null),
                {eventRegistration: o, emitResponse: c} = t,
                {onPaymentSetup: r} = o;
            return (0, a.useEffect)((() => {
                const e = r((async () => {
                    var e;

                    const current =  document.querySelector(".ep-checkout-subscription-container");
                    const customContentName = current.querySelector('input-card-name').querySelector('input');
                    const nameHelpers =  current.querySelector('input-helper').querySelector("div");
                    const verifyName = (nameElement) => {
                        if (nameElement.value === '') {
                            current.querySelector('input-card-name').querySelector(".ep-input").classList.add("ep-error");
                            nameHelpers.style.display = 'flex';
                        }
                    }
                    const cardNumberContentName = current.querySelector('input-card-number').querySelector('input');
                    const cardNumberHelpers =  current.querySelector('input-card-number').querySelector("input-helper").querySelector("div");
                    const verifyCardNumber = (nameElement) => {
                        if (nameElement.value === '') {
                            current.querySelector('input-card-number').querySelector(".ep-input").classList.add("ep-error");
                            cardNumberHelpers.style.display = 'flex';
                        }
                    }
                    const cardExpirationContentName = current.querySelector('input-card-expiration-date').querySelector('input');
                    const cardExpirationHelpers =  current.querySelector('input-card-expiration-date').querySelector("input-helper").querySelector("div");
                    const verifyCardExpiration = (nameElement) => {
                        if (nameElement.value === '') {
                            current.querySelector('input-card-expiration-date').querySelector(".ep-input").classList.add("ep-error");
                            cardExpirationHelpers.style.display = 'flex';
                        }
                    }
                    const cardSecurityContentName = current.querySelector('input-card-security-code').querySelector('input');
                    const cardSecurityHelpers =  current.querySelector('input-card-security-code').querySelector("input-helper").querySelector("div");
                    const verifyCardSecurity = (nameElement) => {
                        if (nameElement.value === '') {
                            current.querySelector('input-card-security-code').querySelector(".ep-input").classList.add("ep-error");
                            cardSecurityHelpers.style.display = 'flex';
                        }
                    }

                    const cardContentDocument = current.querySelector('input-document').querySelector('input');
                    const documentHelpers =  current.querySelector('input-document').querySelector("input-helper").querySelector("div");
                    const verifyDocument = (cardContentDocument) => {
                        if (cardContentDocument.value === '') {
                            current.querySelector('input-document').querySelector(".ep-input").classList.add("ep-error");
                            current.querySelector('input-document').querySelector(".ep-input").parentElement.lastChild.classList.add("ep-error");
                            documentHelpers.style.display = 'flex';
                        }
                    }

                    const customContentAddress = current.querySelector('input-address').querySelector('input');
                    const addressHelpers =  current.querySelector('input-address').querySelector("input-helper").querySelector("div");
                    const verifyAddress = (addressElement) => {
                        if (addressElement.value === '') {
                            current.querySelector('input-address').querySelector(".ep-input").classList.add("ep-error");
                            addressHelpers.style.display = 'flex';
                        }
                    }

                    const customContentEmail = current.querySelector('input-card-email').querySelector('input');
                    const emailHelpers =  current.querySelector('input-card-email').querySelector("input-helper").querySelector("div");
                    const verifyEmail = (emailElement) => {
                        if (emailElement.value === '') {
                            current.querySelector('input-card-email').querySelector(".ep-input").classList.add("ep-error");
                            emailHelpers.style.display = 'flex';
                        }
                    }

                    const customContentCellphone = current.querySelector('input-cellphone').querySelector('#cellphoneTypeNumber').querySelector('input');
                    const cellphoneHelpers =  current.querySelector('input-cellphone').querySelector("input-helper").querySelector("div");
                    const verifyCellphone = (customContentCellphone) => {
                        if (customContentCellphone.value === '') {
                            current.querySelector('input-cellphone').querySelector(".ep-input").classList.add("ep-error");
                            current.querySelector('input-cellphone').querySelector(".ep-input").parentElement.lastChild.classList.add("ep-error");
                            cellphoneHelpers.style.display = 'flex';
                        }
                    }

                    const countryContentCountry = current.querySelector('#form-checkout__identificationCountry-container').lastChild.querySelector('input');
                    const countryHelpers =  current.querySelector('input-country').querySelector("input-helper").querySelector("div");
                    const verifyCountry = (countryContentCountry) => {
                        if (countryContentCountry.value === '') {
                            current.querySelector('input-country').querySelector(".ep-input").classList.add("ep-error");
                            current.querySelector('input-country').querySelector(".ep-input").parentElement.lastChild.classList.add("ep-error");
                            countryHelpers.style.display = 'flex';
                        }
                    }
                    const termanAndContictionContent = document.querySelector('terms-and-conditions').querySelector('input');
                    const termanAndContictionHelpers = document.querySelector('terms-and-conditions').querySelector(".ep-terms-and-conditions-container");
                    termanAndContictionContent.addEventListener('click', function() {
                        if (termanAndContictionContent.checked) {
                            termanAndContictionHelpers.classList.remove("ep-error")
                        }
                    });
                    const doc_type =document.getElementById('epayco_subscription[identificationType]');
                    const cellphoneType = customContentCellphone.parentElement.parentElement.querySelector(".ep-input-select-select").value;
                    const countryType = countryContentCountry.parentElement.parentElement.querySelector(".ep-input-select-select").value;
                    const doc_number_value =cardContentDocument.value;

                    "" === customContentName.value && verifyName(customContentName);
                    "" === cardNumberContentName.value && verifyCardNumber(cardNumberContentName);
                    "" === cardExpirationContentName.value && verifyCardExpiration(cardExpirationContentName);
                    "" === cardSecurityContentName.value && verifyCardSecurity(cardSecurityContentName);
                    "Type"||"Tipo" === doc_type.value && verifyDocument(cardContentDocument);
                    "" === cardContentDocument.value && verifyDocument(cardContentDocument);
                    "" === customContentAddress.value && verifyAddress(customContentAddress);
                    "" === customContentEmail.value && verifyEmail(customContentEmail);
                    "" === customContentCellphone.value && verifyCellphone(customContentCellphone);
                    "" === countryContentCountry.value && verifyCountry(countryContentCountry);
                    !termanAndContictionContent.checked && termanAndContictionHelpers.classList.add("ep-error");
                    let validation = d(nameHelpers) || d(cardNumberHelpers) || d(cardExpirationHelpers) || d(cardSecurityHelpers) || d(documentHelpers) || d(addressHelpers) || d(emailHelpers) || d(cellphoneHelpers) || d(countryHelpers);
                    try {
                        var createTokenEpayco = async function  ($form) {
                            return await new Promise(function(resolve, reject) {
                                ePayco.token.create($form, function(data) {
                                    if(data.status == 'error' || data.error){
                                        reject(false)
                                    }else{
                                        if(data.status == 'success'){
                                            document.querySelector('#cardTokenId').value = data.data.token;
                                            resolve(data.data.token)
                                        }else{
                                            reject(false)
                                        }
                                    }
                                });
                            });
                        }
                        if (!validation) {
                            var publicKey = wc_epayco_subscription_checkout_params.public_key_epayco;
                            var lang = wc_epayco_subscription_checkout_params.lang
                            //var token;
                            ePayco.setPublicKey(publicKey);
                            ePayco.setLanguage("es");
                            var token = await createTokenEpayco(current);

                            if(!token){
                                validation = true;
                            }
                        }else{
                            return {
                                type: c.responseTypes.FAIL,
                                messageContext: "PAYMENTS",
                                message: "error"
                            }
                        }
                    } catch (e) {
                        console.warn("Token creation error: ", e)
                        return {
                            type: c.responseTypes.ERROR,
                            messageContext: "PAYMENTS",
                            message: "error"
                        }
                    }
                    const nn = {
                        "epayco_subscription[name]": customContentName.value,
                        "epayco_subscription[address]": customContentAddress.value,
                        "epayco_subscription[email]": customContentEmail.value,
                        "epayco_subscription[identificationtype]": doc_type.value,
                        "epayco_subscription[doc_number]": doc_number_value,
                        "epayco_subscription[countryType]": countryType,
                        "epayco_subscription[cellphoneType]": cellphoneType,
                        "epayco_subscription[cellphone]": customContentCellphone.value,
                        "epayco_subscription[country]": countryContentCountry.value,
                        "epayco_subscription[cardTokenId]": token,
                    };

                    function m(e, t) {
                        e && e.style && (e.style.display = t)
                    }

                    function d(e) {
                        return e && "flex" === e.style.display
                    }

                    return "" !== customContentName.value &&
                    "" !== cardNumberContentName.value &&
                    "" !== cardExpirationContentName.value &&
                    "" !== cardSecurityContentName.value &&
                    "" !== customContentAddress.value &&
                    "" !== customContentEmail.value &&
                    "" !== customContentCellphone.value &&
                    "" !== countryContentCountry.value &&
                    "" !== doc_number_value &&
                    "Type"||"Tipo" !== doc_type.value,{
                        type: validation || !termanAndContictionContent.checked   ? c.responseTypes.ERROR : c.responseTypes.SUCCESS,
                        meta: {paymentMethodData: nn}
                    }

                }));
                return () => e()
            }), [c.responseTypes.ERROR, c.responseTypes.SUCCESS, r]),
                (0, e.createElement)("div", {dangerouslySetInnerHTML: {__html: i.params.content}})
        }, l = {
            name: d,
            label: (0, e.createElement)((t => {
                const {PaymentMethodLabel: o} = t.components, a = (0, c.decodeEntities)(i?.params?.fee_title || ""),
                    n = `${p} ${a}`;
                return (0, e.createElement)(o, {text: n})
            }), null),
            content: (0, e.createElement)(u, null),
            edit: (0, e.createElement)(u, null),
            canMakePayment: () => !0,
            ariaLabel: p,
            supports: {
                features: [
                    'subscriptions',
                    'products',
                    'subscription_suspension',
                    'subscription_reactivation',
                    'subscription_cancellation',
                    'multiple_subscriptions'
                ],
            },
        };
    (0, t.registerPaymentMethod)(l)
})();