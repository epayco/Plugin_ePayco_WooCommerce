(function($) {
    $(document).ready(function() {
       console.log("epayco admin.")
        var modal = document.getElementById("myModal");
        var span = document.getElementsByClassName("close")[0];
        span.onclick = function() {
            modal.style.display = "none";
        }
        $(".validar").on("click", function() {
            var modal = document.getElementById("myModal");
            var url_validate = $("#path_validate")[0].innerHTML.trim();
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
                        debugger
                        if (response == "success") {
                            alert("validacion exitosa!");
                        } else {
                            modal.style.display = "block";
                        }
                    }
                });
            }else{
                modal.style.display = "block";
            }
        });
    });
}(jQuery));