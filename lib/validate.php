<?php
if (is_array($_REQUEST) && count($_REQUEST) > 0) {
    $username=$_REQUEST['epayco_publickey'];
    $password=$_REQUEST['epayco_privatey'];
    $data = array(
        'public_key' => $username,
        'private_key' => $password
    );
    
    $content = json_encode($data);
    $header = array(
        "Authorization: Basic " . base64_encode("$username:$password")."\r\n".
        "Content-Type: application/json"
    );
    $options = array(
        'http' => array(
            'method' => 'POST',
            'content' => $content,
            'header' => "Authorization: Basic " . base64_encode("$username:$password")."\r\n".
            "Content-Type: application/json\r\n"
        )
        
    );    
    $response =file_get_contents('https://apify.epayco.co/login', false, stream_context_create($options));
    $data = json_decode($response);
    if($data->token){
        echo "suscces";
    }else{
        echo 0;
    }
}else{
    echo 0;
}