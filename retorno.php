<?php
define( '_JEXEC', 1 );
define('JPATH_BASE', dirname(__FILE__) );
define( 'DS', DIRECTORY_SEPARATOR );

require_once ( JPATH_BASE .DS.'includes'.DS.'defines.php' );
require_once ( JPATH_BASE .DS.'includes'.DS.'framework.php' );

JDEBUG ? $_PROFILER->mark( 'afterLoad' ) : null;

$mainframe =& JFactory::getApplication('site');

/**
 * Aqui começa o código do PagSeguro de verdade
 */
function add2Log($text)
{
    file_put_contents('pagseguro.log', $text."\n", FILE_APPEND); // Para sua seguraça, altere este nome de arquivo
}

function retorno_automatico(
   $VendedorEmail, $TransacaoID, $Referencia, $TipoFrete, $ValorFrete,
   $Anotacao, $DataTransacao, $TipoPagamento, $StatusTransacao, $CliNome, 
   $CliEmail, $CliEndereco, $CliNumero, $CliComplemento, $CliBairro, 
   $CliCidade, $CliEstado, $CliCEP, $CliTelefone, $produtos, $NumItens)
{
    add2Log('------- Ok, informações verificadas --------');
    $db    =& JFactory::getDBO();
    $query =  'SELECT '.$db->nameQuote('order_total').', '.$db->nameQuote('order_id').
              ' FROM '.$db->nameQuote('#__vm_orders').
              ' WHERE '.$db->nameQuote('order_number').' = '.$db->quote($Referencia);
    $db->setQuery($query);
    $order = $db->loadObject();
    add2log("Verificando se existe a `order` $Referencia");
    if (!$order) {
        add2log('Referencia não existe, parece que alguem tentou sabotar o sistema, verifique no seu banco de dados...');
        return false;
    }
    add2log('Vamos ver se efetuamos a compra');
    if (!in_array(strtolower(trim($StatusTransacao)), array('aprovado', 'completo'))) {
        add2Log('Nao! Nao era aprovado nem completo...');
        if ( strtolower(trim($StatusTransacao)) == 'cancelado' ) {
            add2Log('Mas era Cancelado, parece que ele deixou de pagar.');
            $query = 'UPDATE '.$db->nameQuote('#__vm_orders').
                     ' SET order_status = '.$db->quote('X').
                     ' WHERE '.$db->nameQuote('order_id').' = '.$db->quote($order->order_id);
            $db->setQuery($query);
            $db->query();
        }
        return false;
    }
    add2Log('Sim! Vamos comecar a transacao!');
    $pg_valor=0;
    foreach ($produtos as $item) {
        $pg_valor += ($item['ProdValor'] * $item['ProdQuantidade']) + $item['ProdFrete'] + $item['ProdExtras'];
    }
    $order->order_total = number_format($order->order_total, 2);
    $pg_valor = number_format($pg_valor, 2);
    add2log("Existe! Verificando o valor: `{$order->order_total}` do banco com o `{$pg_valor}` do PagSeguro");
    if ($pg_valor != $order->order_total) {
        add2log('Oops! Os dados nao conferem, parece que alguem tentou burlar o PagSeguro com o uso da Firebug, verifique no seu Log de produtos se não há algo de errado...');
        return false;
    }
    $query = 'UPDATE '.$db->nameQuote('#__vm_orders').
             ' SET order_status = '.$db->quote('C').
             ' WHERE '.$db->nameQuote('order_id').' = '.$db->quote($order->order_id);
    $db->setQuery($query);
    $db->query();
    add2log('Verificado e atualizado o banco para Pagamento efetuado! \o/');
}

include(JPATH_ADMINISTRATOR.'/components/com_virtuemart/classes/payment/pg_pagseguro.cfg.php');
define ('TOKEN', PGSE_TOKEN);
if ($_POST) {
    add2log(''); add2log('---- '.date('r').' ---');
    add2log('Oops! Recebi um POST, verificando jundo ao PagSeguro! Este eh o POST:');
    $r = '[[['; foreach ($_POST as $k=>$v) $r .= " $k => '$v';"; add2log($r.']]]');
    include ('pagseguro/retorno.php');
    die();
}
header('Location: '.PGSE_RETORNO);
