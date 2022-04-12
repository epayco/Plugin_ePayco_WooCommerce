<?php
if (is_array($_FILES) && count($_FILES) > 0) {
    if (($_FILES["file"]["type"] == "image/pjpeg")
        || ($_FILES["file"]["type"] == "image/jpeg")
        || ($_FILES["file"]["type"] == "image/png")
        || ($_FILES["file"]["type"] == "image/gif")) {

        $nombre = $_FILES['file']['name'];
        $strpos = strpos($nombre, '.');
        $strlen = strlen($nombre);
        $posicion = $strlen - $strpos;
        $typeImage = substr($nombre, -$posicion);
        $typeImage = '.png';
        $oldImageName = stristr($nombre, $typeImage,$posicion);
        $newImageName = 'epayco'.$typeImage;

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
                    }
                }
            }
        }

        if (move_uploaded_file($_FILES["file"]["tmp_name"], $newPath."/".$newImageName)) {
            $url  = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
            $newPath = str_replace('upload.php',$newImageName,$url);
            echo $newPath;
        } else {
            echo 0;
        }
    } else {
        echo 0;
    }
} else {
    echo 0;
}