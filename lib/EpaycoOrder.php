<?php

/**
 * Clase en donde se guardan las transacciones
 */
class EpaycoOrder{

    public $id;

    public $id_payco;

    public $order_id;

    public $order_stock_restore;

    public $order_stock_discount;

    public $order_status;


    /**
     * Guarda el registro de una oden
     * @param int $orderId
     * @param array $stock
     */
    public static function create($orderId, $stock)
    {

        global $wpdb;

        $table_name = $wpdb->prefix . "epayco_order";

        $result = $wpdb->insert( $table_name,

            array(
                'order_id' => $orderId,
                'order_stock_restore' => $stock
            )
        );

        return $result;
    }


    /**
     * Consultar si existe el registro de una oden
     * @param int $orderId
     */
    public static function ifExist($orderId)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "epayco_order";

        $results = $wpdb->get_row( "SELECT * FROM $table_name WHERE order_id = $orderId" );

        if ($results)
            return true;

        return false;

    }


    /**
     * Consultar si a una orden ya se le descconto el stock
     * @param int $orderId
     */
    public static function ifStockDiscount($orderId)
    {

        global $wpdb;

        $table_name = $wpdb->prefix . "epayco_order";

        $result = $wpdb->get_row( "SELECT * FROM $table_name WHERE order_id = $orderId" );
        if (is_null($result))
            return false;

        return intval($result->order_stock_discount) != 0 ? true : false;

    }


    /**
     * Actualizar que ya se le descont贸 el stock a una orden
     * @param int $orderId
     */
    public static function updateStockDiscount($orderId)
    {

        global $wpdb;

        $table_name = $wpdb->prefix . "epayco_order";

        $result = $wpdb->update( $table_name, array('order_stock_discount'=>1), array('order_id'=>(int)$orderId) );

        return (int)$result == 1;

    }



    /**
     * Crear la tabla en la base de datos.
     * @return true or false
     */
    public static function setup()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "epayco_order";

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT  EXISTS $table_name (

		    id INT NOT NULL AUTO_INCREMENT,

		    id_payco INT NULL,

		    order_id INT NULL,

		    order_stock_restore INT NULL,

		    order_stock_discount INT NULL,

		    order_status TEXT NULL,

		    PRIMARY KEY (id)

	  	) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);

    }

    /**
     * Borra la tabla en la base de datos.
     * @return true or false
     */
    public static function remove(){

        $sql = array(
            'DROP TABLE IF EXISTS '._DB_PREFIX_.'payco'
        );

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

    }

}