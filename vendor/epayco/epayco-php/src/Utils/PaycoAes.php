<?php

namespace Epayco\Utils;

use Epayco\Utils\McryptEncrypt;
use Epayco\Utils\OpensslEncrypt;

/**
 * Epayco library encrypt based in AES
 */
if (function_exists('mcrypt_get_iv_size')) {

	class PaycoAes extends McryptEncrypt {}
}else{
	
	class PaycoAes extends OpensslEncrypt {}
}

?>