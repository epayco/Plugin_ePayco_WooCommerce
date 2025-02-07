(()=>{
    "use strict";
    const e=window.wp.element,
        t=window.wp.i18n,
        n=window.wc.wcBlocksRegistry,
        s=window.wp.htmlEntities,
        a=window.wc.wcSettings,
        l=(0,a.getSetting)("epayco_data",{}),
        o=(0,t.__)("Epayco","woo-epayco-gateway"),
        c=(0,s.decodeEntities)(l.title)||o,
        w=()=>(0,s.decodeEntities)(l.description||""),
        y={
            name:"epayco",
            label:(0,e.createElement)(
                (t=> {const{PaymentMethodLabel:n}=t.components;
                        return(0,e.createElement)(n,{text:c})
                }),null),
                content:(0,e.createElement)(w,null),
                edit:(0,e.createElement)(w,null),
                canMakePayment:()=>!0,
                ariaLabel:c,
                supports:{features:l.supports}
        };
        (0,n.registerPaymentMethod)(y)
})();