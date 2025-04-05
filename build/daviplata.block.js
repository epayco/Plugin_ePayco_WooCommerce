(() => {
    "use strict";
    const e = window.React,
        t = window.wc.wcBlocksRegistry,
        o = window.wc.wcSettings,
        a = window.wp.element,
        c = window.wp.htmlEntities,
        n = "epayco_blocks_update_cart";
    var r;
    const m = "mp_checkout_blocks", d = "woo-epayco-daviplata",
        i = (0, o.getSetting)("woo-epayco-daviplata_data", {}),
        p = (0, c.decodeEntities)(i.title) || "Checkout daviplata", u = t => {
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
                {onPaymentSetup: r} = o,
                p = ["address_city", "address_federal_unit", "address_zip_code", "address_street_name", "address_street_number", "address_neighborhood", "address_complement"];
            return (0, a.useEffect)((() => {
                const e = r((async () => {
                    var e;
                    const daviplataName = document.getElementsByName('epayco_daviplata[name]')[0]??document.getElementsByName('epayco_daviplata[nameError]')[0];
                    const daviplataContentName = daviplataName.value;
                    const nameHelpers =  document.querySelector('input-helper-epayco').querySelector("div");
                    const verifyName = (nameElement) => {
                        if (nameElement === '') {
                            document.querySelector('input-name-epayco').querySelector(".ep-input").classList.add("ep-error");
                            nameHelpers.style.display = 'flex';
                        }
                    }
                    /*const daviplataContentAddress = document.getElementsByName('epayco_daviplata[address]')[0].value;
                    const addressHelpers =  document.querySelector('input-address').querySelector("input-helper-epayco").querySelector("div");
                    const verifyAddress = (addressElement) => {
                        if (addressElement === '') {
                            document.querySelector('input-address').querySelector(".ep-input").classList.add("ep-error");
                            addressHelpers.style.display = 'flex';
                        }
                    }*/
                    const daviplataContentEmail = document.getElementsByName('epayco_daviplata[email]')[0].value;
                    const emailHelpers =  document.querySelector('input-email-epayco').querySelector("input-helper-epayco").querySelector("div");
                    const verifyEmail = (emailElement) => {
                        if (emailElement === '') {
                            document.querySelector('input-email-epayco').querySelector(".ep-input").classList.add("ep-error");
                            emailHelpers.style.display = 'flex';
                        }
                    }

                    const cellphoneType = document.getElementsByName('epayco_daviplata[cellphone]')[0].value;
                    const daviplataContentCellphone = document.getElementsByName('epayco_daviplata[cellphoneType]')[0].value;
                    const cellphoneHelpers =  document.querySelector('input-cellphone-epayco').querySelector("input-helper-epayco").querySelector("div");
                    const verifyCellphone = (cellphone) => {
                        if (cellphone === '') {
                            document.querySelector('input-cellphone-epayco').querySelector(".ep-input").classList.add("ep-error");
                            document.querySelector('input-cellphone-epayco').querySelector(".ep-input").parentElement.lastChild.classList.add("ep-error");
                            cellphoneHelpers.style.display = 'flex';
                        }
                    }

                    //const person_type_value = document.getElementsByName('epayco_daviplata[person_type]')[1].value;
                    const doc_type = document.getElementsByName('epayco_daviplata[documentType]')[0].value;
                    const documentHelpers =  document.querySelector('input-document-epayco').querySelector("input-helper-epayco").querySelector("div");
                    const verifyDocument = (daviplataContentDocument) => {
                        if (daviplataContentDocument === '') {
                            document.querySelector('input-document-epayco').querySelector(".ep-input").classList.add("ep-error");
                            document.querySelector('input-document-epayco').querySelector(".ep-input").parentElement.lastChild.classList.add("ep-error");
                            documentHelpers.style.display = 'flex';
                        }
                    }
                    const doc_number = document.getElementsByName('epayco_daviplata[document]').length>0?document.getElementsByName('epayco_daviplata[document]'):document.getElementsByName('documentTypeError');
                    const doc_number_value = doc_number[0].value;
                    /*const countryType = document.getElementsByName('epayco_daviplata[countryType]')[0].value;
                    const daviplataContentCountry = document.getElementsByName('epayco_daviplata[country]')[0].value;
                    const countryHelpers =  document.querySelector('input-country').querySelector("input-helper-epayco").querySelector("div");
                    const verifyCountry = (daviplataContentCountry) => {
                        if (daviplataContentCountry === '') {
                            document.querySelector('input-country').querySelector(".ep-input").classList.add("ep-error");
                            document.querySelector('input-country').querySelector(".ep-input").parentElement.lastChild.classList.add("ep-error");
                            countryHelpers.style.display = 'flex';
                        }
                    }*/

                    const termanAndContictionContent = document.querySelector('terms-and-conditions').querySelector('input');
                    const termanAndContictionHelpers = document.querySelector('terms-and-conditions').querySelector(".ep-terms-and-conditions-container");
                    termanAndContictionContent.addEventListener('click', function() {
                        if (termanAndContictionContent.checked) {
                            termanAndContictionHelpers.classList.remove("ep-error")
                        }
                    });


                    const nn = {
                        "epayco_daviplata[name]": daviplataContentName,
                        //"epayco_daviplata[address]": daviplataContentAddress,
                        "epayco_daviplata[email]": daviplataContentEmail,
                        "epayco_daviplata[cellphoneType]": cellphoneType,
                        "epayco_daviplata[cellphone]": daviplataContentCellphone,
                        //"epayco_daviplata[person_type]": person_type_value,
                        "epayco_daviplata[identificationtype]": doc_type,
                        "epayco_daviplata[doc_number]": doc_number_value,
                        //"epayco_daviplata[countryType]": countryType,
                        //"epayco_daviplata[country]": daviplataContentCountry
                    };

                    "" === daviplataContentName && verifyName(daviplataContentName);
                    "" === daviplataContentEmail && verifyEmail(daviplataContentEmail);
                   // "" === daviplataContentAddress && verifyAddress(daviplataContentAddress);
                    "" === cellphoneType && verifyCellphone(cellphoneType);
                    "Type"||"Tipo" === doc_type && verifyDocument(doc_number_value);
                    "" === doc_number_value && verifyDocument(doc_number_value);
                   // "" === daviplataContentCountry && verifyCountry(daviplataContentCountry);
                    !termanAndContictionContent.checked && termanAndContictionHelpers.classList.add("ep-error");
                    let validation = d(nameHelpers)|| d(emailHelpers)|| d(cellphoneHelpers)||d(documentHelpers)||d(termanAndContictionContent)
                    function m(e, t) {
                        e && e.style && (e.style.display = t)
                    }

                    function d(e) {

                        return e && "flex" === e.style.display
                    }

                    return "" !== daviplataContentName &&
                    //"" !== daviplataContentAddress &&
                    "" !==  daviplataContentEmail &&
                    "" !== daviplataContentCellphone &&
                    "" !== doc_number_value &&
                    //"" !== daviplataContentCountry &&
                    "Type"||"Tipo" !== doc_type,{
                        type:validation || !termanAndContictionContent.checked  ? c.responseTypes.ERROR : c.responseTypes.SUCCESS,
                        meta: {paymentMethodData: nn}
                    }
                }));
                return () => e()
            }), [c.responseTypes.ERROR, c.responseTypes.SUCCESS, r]), (0, e.createElement)("div", {dangerouslySetInnerHTML: {__html: i.params.content}})
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
            supports: {features: null !== (r = i?.supports) && void 0 !== r ? r : []}
        };
    (0, t.registerPaymentMethod)(l)
})();