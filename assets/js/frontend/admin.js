(function($) {

    // Idioma del admin de WordPress (ej: "en-US", "es-CO")
    var lang = (document.documentElement.lang || "es").toLowerCase();
    var isSpanish = lang.indexOf("es") === 0;

    var messages = {
        es: {
            keysValidTitle: "Llaves validadas correctamente",
            keysValidDesc: "Las llaves API fueron verificadas exitosamente.<br>Ya puedes usar Davipay con normalidad.",
            inactiveTitle: "Comercio inactivo.",
            contactSupport: "¡Por favor contacte con soporte!",
            internalErrTitle: "¡Oups!, se procesó un error interno.",
            configureTitle: "Por favor, configure las credenciales",
            configureDesc: "Ingrese su Public Key y Private Key."
        },
        en: {
            keysValidTitle: "Keys validated successfully",
            keysValidDesc: "The API keys were successfully verified.<br>You can now use Davipay normally.",
            inactiveTitle: "Inactive merchant.",
            contactSupport: "Please contact support!",
            internalErrTitle: "Oops! An internal error occurred.",
            configureTitle: "Please configure your credentials",
            configureDesc: "Enter your Public Key and Private Key."
        }
    };

    var t = isSpanish ? messages.es : messages.en;

    $(document).ready(function() {
        console.log("epayco admin.");
        var modal = document.getElementById("myModal");
        var modalContent = document.getElementsByClassName("modal-content")[0];
        var span = document.getElementsByClassName("closeEpaycoModal")[0];
        var loader = document.getElementsByClassName("loader")[0];
        span.onclick = function() {
            modal.style.display = "none";
            modalContent.style.display = "none";
        };
        $(".validar").on("click", function() {
            loader.style.display = "block";
            modal.style.display = "block";
            var url_validate = $("#path_validate")[0].innerHTML.trim();
            var url_davipay_s3 = $("#path_davipay_s3")[0].innerHTML.trim();
            const epayco_publickey = $("input:text[name=woocommerce_epayco_epayco_publickey]").val().replace(/\s/g, "");
            const epayco_privatey = $("input:text[name=woocommerce_epayco_epayco_privatekey]").val().replace(/\s/g, "");
            if (epayco_publickey !== "" &&
                epayco_privatey !== "") {
                var formData = new FormData();
                formData.append("epayco_publickey", epayco_publickey.replace(/\s/g, ""));
                formData.append("epayco_privatey", epayco_privatey.replace(/\s/g, ""));
                $.ajax({
                    url: url_validate,
                    type: "post",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                if (data.comercio_estado) {
                                    updateEpaycoModal(
                                        url_davipay_s3 + "admin/credentials/logos/check/logo-credentials-check.png",
                                        t.keysValidTitle,
                                        t.keysValidDesc
                                    );
                                } else {
                                    updateEpaycoModal(
                                        url_davipay_s3 + "admin/credentials/logos/check/logo-credentials-check.png",
                                        t.inactiveTitle,
                                        t.contactSupport
                                    );
                                }
                            } else {
                                updateEpaycoModal(
                                    url_davipay_s3 + "admin/credentials/logos/warning/logo-credentials-warning.png",
                                    t.internalErrTitle,
                                    t.contactSupport
                                );
                            }
                            loader.style.display = "none";
                            modalContent.style.display = "block";
                        } catch (error) {
                            updateEpaycoModal(
                                url_davipay_s3 + "admin/credentials/logos/warning/logo-credentials-warning.png",
                                t.internalErrTitle,
                                t.contactSupport
                            );
                            modalContent.style.display = "block";
                            loader.style.display = "none";
                        }
                    }
                });
            } else {
                updateEpaycoModal(
                    url_davipay_s3 + "admin/credentials/logos/warning/logo-credentials-warning.png",
                    t.configureTitle,
                    t.configureDesc
                );
                loader.style.display = "none";
                modalContent.style.display = "block";
            }
        });
    });

    function updateEpaycoModal(newImg, newTitle, newDescription) {
        $("#epaycoModalImg").attr("src", newImg);
        $("#epaycoCredentialTittle").html("<strong>" + newTitle + "</strong>");
        $("#epaycoCredentialDescription").html(newDescription);
    }
}(jQuery));