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

        $url = __DIR__;
        $strpos_url = strpos($url, 'wp-content');
        $strlen_url = strlen($url);
        $posicion_url = $strlen_url - $strpos_url;
        $typeUrl = substr($url, -$posicion_url);
        $newPath = str_replace($typeUrl,'wp-admin/images/',$url);


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
                        unlink($newPath.$image);
                    }
                }
            }
        }

        if (move_uploaded_file($_FILES["file"]["tmp_name"], $newPath.$newImageName)) {
            //more code here...
            $url  = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
            $strpos_url = strpos($url, 'wp-content');
            $strlen_url = strlen($url);
            $posicion_url = $strlen_url - $strpos_url;
            $typeUrl = substr($url, -$posicion_url);
            $newPath = str_replace($typeUrl,'wp-admin/images/',$url);
            echo $newPath.$newImageName;
        } else {
            echo 0;
        }
    } else {
        echo 0;
    }
} else {
    echo 0;
}