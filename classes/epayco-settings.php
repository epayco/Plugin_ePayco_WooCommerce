<?php
defined('ABSPATH') || exit;

return apply_filters(
    'wc_epayco_settings',
    array(
        'enabled'          => array(
            'title'   => __('Habilitar/Deshabilitar', 'woo-epayco-gateway'),
            'type'    => 'checkbox',
            'label'   => __('Habilitar ePayco', 'woo-epayco-gateway'),
            'default' => 'yes',
        ),
        'title'            => array(
            'title'       => __('<span class="epayco-required">Título</span>', 'woo-epayco-gateway'),
            'type'        => 'text',
            'description' => __('Corresponde al título que el usuario ve durante el Checkout.', 'woo-epayco-gateway'),
            'default'     => __('Checkout ePayco (Tarjetas de crédito,debito,efectivo)', 'woo-epayco-gateway'),
            'desc_tip'    => true,
        ),
        'description'      => array(
            'title'       => __('<span class="epayco-required">Descripción</span>', 'woo-epayco-gateway'),
            'type'        => 'textarea',
            'description' => __('Corresponde a la descripción que verá el usuario durante el Checkout', 'woo-epayco-gateway'),
            'default'     => __('Checkout ePayco (Tarjetas de crédito,débito,efectivo)', 'woo-epayco-gateway'),
        ),
        'epayco_customerid' => array(
            'title'       => __('<span class="epayco-required">P_CUST_ID_CLIENTE</span>', 'woo-epayco-gateway'),
            'type'        => 'text',
            'description' => __('ID de cliente que lo identifica en ePayco. Lo puede encontrar en su panel de clientes en la opción configuración', 'woo-epayco-gateway'),
            'default' => '',
            //'desc_tip' => true,
            'placeholder' => '',
        ),
        'epayco_secretkey' => array(
            'title'       => __('<span class="epayco-required">P_KEY</span>', 'woo-epayco-gateway'),
            'type'        => 'text',
            'description' => __('LLave para firmar la información enviada y recibida de ePayco. Lo puede encontrar en su panel de clientes en la opción configuración', 'woo-epayco-gateway'),
            'default' => '',
            //'desc_tip' => true,
            'placeholder' => '',
        ),
        'epayco_publickey' => array(
            'title'       => __('<span class="epayco-required">PUBLIC_KEY</span>', 'woo-epayco-gateway'),
            'type'        => 'text',
            'description' => __('LLave para autenticar y consumir los servicios de ePayco, Proporcionado en su panel de clientes en la opción configuración', 'woo-epayco-gateway'),
            'default' => '',
            //'desc_tip' => true,
            'placeholder' => '',
        ),
        'epayco_privatekey' => array(
            'title'       => __('<span class="epayco-required">PRIVATE_KEY</span>', 'woo-epayco-gateway'),
            'type'        => 'text',
            'description' => __('LLave para autenticar y consumir los servicios de ePayco, Proporcionado en su panel de clientes en la opción configuración', 'woo-epayco-gateway'),
            'default' => '',
            //'desc_tip' => true,
            'placeholder' => '',
        ),
        'epayco_testmode' => array(
            'title'       => __('Sitio en pruebas', 'woo-epayco-gateway'),
            'type'        => 'checkbox',
            'label' => __('Habilitar el modo de pruebas', 'woo-epayco-gateway'),
            'description' => __('Habilite para realizar pruebas', 'woo-epayco-gateway'),
            'default' => 'no',
        ),
        'epayco_type_checkout'         => array(
            'title'       => __('Tipo Checkout', 'woo-epayco-gateway'),
            'type'        => 'select',
            'css' => 'line-height: inherit',
            'label' => __('Seleccione un tipo de Checkout:', 'woo-epayco-gateway'),
            'description' => __('(Onpage Checkout, el usuario al pagar permanece en el sitio) ó (Standard Checkout, el usario al pagar es redireccionado a la pasarela de ePayco)', 'woo-epayco-gateway'),
            'options' => array('false' => "Onpage Checkout", "true" => "Standard Checkout"),
        ),
        'epayco_endorder_state'     => array(
            'title'       => __('Estado Final del Pedido', 'woo-epayco-gateway'),
            'type' => 'select',
            'css' => 'line-height: inherit',
            'description' => __('Seleccione el estado del pedido que se aplicará a la hora de aceptar y confirmar el pago de la orden', 'woo-epayco-gateway'),
            'options' => array(
                'epayco-processing' => __('ePayco Procesando Pago', 'woo-epayco-gateway'),
                "epayco-completed" => __('ePayco Pago Completado', 'woo-epayco-gateway'),
                'processing' => __('Procesando', 'woo-epayco-gateway'),
                "completed" => __('Completado', 'woo-epayco-gateway')
            ),
        ),
        'epayco_cancelled_endorder_state'         => array(
            'title'       => __('Estado Cancelado del Pedido', 'woo-epayco-gateway'),
            'type' => 'select',
            'css' => 'line-height: inherit',
            'description' => __('Seleccione el estado del pedido que se aplicará cuando la transacciónes Cancelada o Rechazada', 'woo-epayco-gateway'),
            'options' => array(
                'epayco-cancelled' => __('ePayco Pago Cancelado', 'woo-epayco-gateway'),
                "epayco-failed" => __('ePayco Pago Fallido', 'woo-epayco-gateway'),
                'cancelled' => __('Cancelado', 'woo-epayco-gateway'),
                "failed" => __('Fallido', 'woo-epayco-gateway')
            ),
        ),
        'epayco_url_response'          => array(
            'title'       => __('Página de Respuesta', 'woo-epayco-gateway'),
            'type'        => 'select',
            'css' => 'line-height: inherit',
            'description' => __('Url de la tienda donde se redirecciona al usuario luego de pagar el pedido', 'woo-epayco-gateway'),
            'options'       => $this->get_pages(__('Seleccionar pagina', 'woo-epayco-gateway')),
        ),
        'epayco_url_confirmation'          => array(
            'title'       => __('Página de Confirmación', 'woo-epayco-gateway'),
            'type'        => 'select',
            'css' => 'line-height: inherit',
            'description' => __('Url de la tienda donde ePayco confirma el pago', 'woo-epayco-gateway'),
            'options'       => $this->get_pages(__('Seleccionar pagina', 'woo-epayco-gateway')),
        ),
        'epayco_reduce_stock_pending'    => array(
            'title'       => __('Reducir el stock en transacciones pendientes', 'woo-epayco-gateway'),
            'type'        => 'checkbox',
            'css' => 'line-height: inherit',
            'default'     => 'yes',
            'description' => sprintf(__('Habilite para reducir el stock en transacciones pendientes', 'woo-epayco-gateway')),
        ),
        'epayco_lang'          => array(
            'title'       => __('Idioma del Checkout', 'woo-epayco-gateway'),
            'type'        => 'select',
            'css' => 'line-height: inherit',
            'description' => __('Seleccione el idioma del checkout', 'woo-epayco-gateway'),
            'default'     => 'es',
            'options'     => array(),
        ),
        'response_data'     => array(
            'title'       => __('Habilitar envió de atributos a través de la URL de respuesta', 'woo-epayco-gateway'),
            'type' => 'checkbox',
            'label' => __('Habilitar el modo redirección con data', 'woo-epayco-gateway'),
            'description' => __('Al habilitar esta opción puede exponer información sensible de sus clientes, el uso de esta opción es bajo su responsabilidad, conozca esta información en el siguiente  <a href="https://docs.epayco.co/payments/checkout#scroll-response-p" target="_blank">link.</a>', 'woo-epayco-gateway'),
            'default'     => 'no',
        ),
        'cron_data'     => array(
            'title'       => __('Rastreo de orden ', 'woo-epayco-gateway'),
            'type' => 'checkbox',
            'label' => __('Habilitar el rastreo de orden ', 'woo-epayco-gateway'),
            'description' => __('Mantendremos tus pedidos actualizados cada hora. Recomendamos activar esta opción sólo en caso de fallos en la actualización automática de pedidos. ', 'woo-epayco-gateway'),
            'default'     => 'no',
        ),
    )
);
