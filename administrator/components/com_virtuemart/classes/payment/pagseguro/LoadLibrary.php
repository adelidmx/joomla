<?php

include (JPATH_COMPONENT_ADMINISTRATOR.'/classes/payment/pagseguro/PagSeguroLibrary/PagSeguroLibrary.php');

// Instantiate a new payment request
$paymentRequest = new PagSeguroPaymentRequest();
$pgs = new pgs();

// Sets the currency
$paymentRequest->setCurrency("BRL");

$db1 = new ps_DB();
$db1->query("SELECT * FROM #__vm_order_item WHERE order_id = '".$db->f('order_id')."'");

// Add an item for this payment request
$peso = 0.00;
$frete = 0.00;
if ($db->f("order_shipping")) {
    $frete = sprintf("%01.2f", $db->f("order_shipping"));
}


while ($db1->next_record()) {
    $paymentRequest->addItem(
        $db1->f('order_item_sku'), 
        strip_tags($db1->f('order_item_name') . ' - ' . ($db1->f('product_attribute'))), 
        $db1->f('product_quantity'), 
        sprintf("%01.2f", $db1->f('product_item_price')),
        $peso,
        $frete
    );
}

// Sets a reference code for this payment request, it's useful to identify this payment in future notifications.
$paymentRequest->setReference($db->f("order_id"));

// Sets shipping information for this payment request
$FREIGHT_CODE = PagSeguroShippingType::getCodeByType(PGS_TIPO_FRETE);

if( $FREIGHT_CODE ){ 

    $paymentRequest->setShippingType($FREIGHT_CODE);
    
    $street = explode(',', $user->address_1);            
    
    $street = array_slice(array_merge($street, array("", "", "")), 0, 3); 
    
    list($address, $number, $complement) = $street;
    list($prefix, $phone) = $pgs->splitPhone($user->phone_1);
    
    $paymentRequest->setShippingAddress(
        $user->zip, 
        $address, $number, $complement,
        $user->address_2,
        $user->city,
        $user->state,
        $user->country
    );
}

// Sets your customer information.
$paymentRequest->setSender(

    $user->first_name." ".$user->last_name, 

    $user->user_email, $prefix, $phone

);

$paymentRequest->setRedirectUrl("http://www.lojamodelo.com.br");

try {
	$credentials = new PagSeguroAccountCredentials(PGS_EMAIL, PGS_TOKEN);
	
	// Register this payment request in PagSeguro, to obtain the payment URL for redirect your customer.
	if ($url = $paymentRequest->register($credentials)) {
        // Payment Form
        $pgs->show_form(
            array(
                'payment_url' => $url
            )
        );
	}
} catch (PagSeguroServiceException $e) {
	die($e->getMessage());
}

class pgs {
    var $_config = array ();

    function pgs($args = array()) {
        
        $this->_config = $args;
    
    }
    
    /**
     *
     * show_form - imprime o formulário de envio de post do PagSeguro
     *
     * Após configurar o objeto, você pode usar este método para mostrando assim o
     * formulário com todos os inputs necessários para enviar ao pagseguro.
     *
     * <code>
     * array (
     *   'payment_url' => 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=988F7765S8705JDU8E7UF78BF6CDAEE9', // Url de pagamento    contendo um código de transação válido e único para cada transação.
     *   'img_button'  => 'imagem.jpg', // Usa a imagem (url) para formar o botão de submit
     *   'btn_submit'  => 1,            // Mostra um dos 5 botões do pagseguro no botão de submit
     * )
     * </code>
     *
     * @access public
     * @param array $args Array associativo contendo as configurações que você deseja alterar
     */
    function show_form($args = array()){
        $default = array (
            'payment_url' => '',
		    'img_button'  => false,
		    'btn_submit'  => false,
        );
        $args = $args+$default;
    
	    $_form = array();
	    $_form[] = sprintf('<form action="%s" target="pagseguro" id="pagseguro" method="post">', $args['payment_url']);
	    
	    if ($args['img_button']) {
		    $_form[] = sprintf('<input type="image" src="%s" name="submit" alt="Pague com o PagSeguro - &eacute; r&aacute;pido, gr&aacute;tis e seguro!" />', $args['img_button']);
	    } elseif ($args['btn_submit']) {
		    switch ($args['btn_submit']) {
			    case 1:  $btn = 'btnComprarBR.jpg';  break;
			    case 2:  $btn = 'btnPagarBR.jpg';    break;
			    case 3:  $btn = 'btnPagueComBR.jpg'; break;
			    case 4:  $btn = 'btnComprar.jpg';    break;
			    case 5:  $btn = 'btnPagar.jpg';      break;
			    default: $btn = 'btnComprarBR.jpg';
		    }
		    $_form[] = sprintf ('<input type="image" src="https://pagseguro.uol.com.br/Security/Imagens/%s"  name="submit" alt="Pague com o PagSeguro - &eacute; r&aacute;pido, gr&aacute;tis e seguro!" />', $btn);
	    } else {
		    $_form[] = '<input type="submit" value="Pague com o PagSeguro"  />';
	    }
	    
        $_form[] = '</form>';
        $return = implode("\n", $_form);
        print ($return);
    }

    function splitPhone($phone){
        $phone = preg_replace('/[a-w]+.*/', '', $phone);
        $numbers = preg_replace('/D/', '', $phone);
        $telephone = substr($numbers, sizeof($numbers) - 9);
        $prefix = substr($numbers, sizeof($numbers) - 11, 2);
        return array($prefix, $telephone);
    }
    	
}

?>
