window.onload = function() {
    document.addEventListener("contextmenu", function(e){
        e.preventDefault();
    }, false);
}

jQuery( document ).ready( function( $ ) {
    $(document).keydown(function (event) {
        if (event.keyCode == 123) {
            return false;
        } else if (event.ctrlKey && event.shiftKey && event.keyCode == 73) {
            return false;
        }
    });
})

var openChekout = function () {
    handler.open(data);
    console.log("epayco")
}
setTimeout(openChekout, 2000)
var bntPagar = document.getElementById("btn_epayco");
bntPagar.addEventListener("click", openChekout);
