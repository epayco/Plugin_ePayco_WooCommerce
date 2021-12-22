window.onload = function() {
    document.addEventListener("contextmenu", function(e){
        e.preventDefault();
    }, false);
} 
                            
var controlprecionado = 0;
var altprecionado = 0;
function desactivarCrlAlt(teclaactual){
    var desactivar = false;
    //Ctrl + 
    if (controlprecionado==17){
        if (teclaactual==78 || teclaactual==85 ){
        //alert("Ctrl+N y Ctrl+U deshabilitado");
            desactivar=true;
        }         
        if (teclaactual==82){
            //alert("Ctrl+R deshabilitado");
            desactivar=true;
        }             
        if (teclaactual==116){
            //alert("Ctrl+F5 deshabilitado");
            desactivar=true;
        }          
        if (teclaactual==114){
            //alert("Ctrl+F3 deshabilitado");
            desactivar=true;
        } 
        if (teclaactual==67){
            //alert("c deshabilitado");
            desactivar=true;
        }
        if (teclaactual==123){
            //alert("Ctrl+f12 deshabilitado");
            desactivar=true;
        }
    }
        if (teclaactual==123){
            //alert("Ctrl+f12 deshabilitado");
            desactivar=true;
        }
    //Alt +
    if (altprecionado==18){
        if (teclaactual==37){
        //alert("Alt+ [<-] deshabilitado");
            desactivar=true;
        } 
        if (teclaactual==39){
        //alert("Alt+ [->] deshabilitado");
        desactivar=true;
        }     
    }
    if (teclaactual==17)controlprecionado=teclaactual;
    if (teclaactual==18)altprecionado=teclaactual;  
        return desactivar;
}
                         
document.onkeyup = function(){   
    if(window.event.keyCode == 123){    
            return false; 
    }
    if (window.event && window.event.keyCode==17){
        controlprecionado = 0;
    }
    if (window.event && window.event.keyCode==18){
        altprecionado = 0;
    }  
    if (window.event && window.event.keyCode==123){
        altprecionado = 0;
    }
}
                        
document.onkeydown = function(){ 
    //https://keycode.info/ 
    if(window.event.keyCode == 123){    
            return false; 
    }
    if (window.event && desactivarCrlAlt(window.event.keyCode)){
        return false;
    }
    
    if (window.event && (window.event.keyCode == 122 || 
        window.event.keyCode == 116 || 
        window.event.keyCode == 114 || 
        window.event.keyCode == 117))
        {    
            window.event.keyCode = 505; 
        }
    if (window.event.keyCode == 505){                       
        return false; 
    } 
    if (window.event && (window.event.keyCode == 8)){                       
        valor = document.activeElement.value;
        if (valor===undefined) {
            //Evita Back en página.
            return false; 
        } 
    }
} 