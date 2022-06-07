<?php
$newPath = __DIR__;
$gestor  = opendir($newPath);
if($gestor){
    while (($image = readdir($gestor)) !== false){
        if($image != '.' && $image != '..'){
            $strpos_image = strpos($image, '.');
            $strlen_image = strlen($image);
            $posicion_image = $strlen_image - $strpos_image;
            $type_image = substr($image, - $posicion_image);
            $name_image = substr($image,  0,$strpos_image);
            if($name_image == "epayco"){
                unlink($newPath."/".$image);
                echo "el logo se elimino con exito";
            }
        }
    }
}
?>