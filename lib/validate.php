<?php
if (is_array($_REQUEST) && count($_REQUEST) > 0) {

    $data = array(
        'public_key' => $_REQUEST['epayco_publickey'],
        'private_key' => $_REQUEST['epayco_privatey']
    );
    if (function_exists('curl_init')) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.secure.payco.co/v1/auth/login',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);
        if ($response === false) {
            return array('curl_error' => curl_error($curl), 'curerrno' => curl_errno($curl));
        }
        curl_close($curl);
    }else{

        $content = json_encode($data);
		$header = array(
			"Content-Type: application/json"
		);
		$options = array(
			'http' => array(
				'method' => 'POST',
				'content' => $content,
				'header' => implode("\r\n", $header)
			)
			
		);
        $response =file_get_contents('https://api.secure.payco.co/v1/auth/login', false, stream_context_create($options));
    }
    $data = json_decode($response);
    $status =  $data->status;
    echo $status;
}else{
    echo 0;
}