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
