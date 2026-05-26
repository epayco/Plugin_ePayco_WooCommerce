(function($) {
    $(document).ready(function() {
       console.log("epayco admin.")
        var modal = document.getElementById("myModal");
        var modalContent = document.getElementsByClassName("modal-content")[0];
        var span = document.getElementsByClassName("closeEpaycoModal")[0];
        var loader = document.getElementsByClassName("loader")[0];
        span.onclick = function() {
            modal.style.display = "none";
            modalContent.style.display = "none";
        }
        $(".validar").on("click", function() {
            loader.style.display = "block";
            modal.style.display = "block";
            var url_validate = $("#path_validate")[0].innerHTML.trim();
            var url_plugin = $("#path_plugin")[0].innerHTML.trim();
            const epayco_publickey = $("input:text[name=woocommerce_epayco_epayco_publickey]").val().replace(/\s/g,"");
            const epayco_privatey = $("input:text[name=woocommerce_epayco_epayco_privatekey]").val().replace(/\s/g,"");
            if (epayco_publickey !== "" &&
                epayco_privatey !== "") {
                var formData = new FormData();
                formData.append("epayco_publickey",epayco_publickey.replace(/\s/g,""));
                formData.append("epayco_privatey",epayco_privatey.replace(/\s/g,""));
                $.ajax({
                    url: url_validate,
                    type: "post",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success){
                                if(data.comercio_estado){
                                    //document.getElementById("woocommerce_woo-epaycosubscription_shop_name").value=data.comercio;
                                    //document.getElementById("woocommerce_woo-epaycosubscription_shop_icon").value=data.logo;
                                    //alert("validacion exitosa!");
                                    updateEpaycoModal(
                                         url_plugin+"check.png",
                                        "Llaves validadas correctamente",
                                        "Las llaves API fueron verificadas exitosamente.<br>Ya puedes usar ePayco con normalidad."
                                    );
                                }else{
                                    updateEpaycoModal(
                                         url_plugin+"check.png",
                                        "Comercio inactivo.",
                                        "Por favor contacte con soporte!"
                                    );
                                }
                                //modal.style.display = "none";
                                //modalContent.style.display = "none";
                            } else {
                                updateEpaycoModal(
                                    url_plugin+"logo_warning.png",
                                    "Oups!, Se proceso un error interno.",
                                    "Por favor contacte con soporte!"
                                );
                            }
                            loader.style.display = "none";
                            modalContent.style.display = "block";
                        } catch (error) {
                             modalContent.style.display = "block";
                             loader.style.display = "none";
                        }
                        
                    }
                });
            }else{
                updateEpaycoModal(
                    url_plugin+"logo_warnibg.png",
                    "Por favor, configure las credenciales",
                    "!"
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