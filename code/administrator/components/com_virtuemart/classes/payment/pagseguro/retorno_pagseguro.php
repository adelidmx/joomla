<?php
function pasta(&$pasta=null) {
  if (!$pasta) $pasta = __FILE__;
  $path = pathinfo($pasta);
  $pasta = $path['dirname'];
  return $pasta;
}

function retorno_automatico ($VendedorEmail, $TransacaoID, $Referencia, $TipoFrete, $ValorFrete, $Anotacao, $DataTransacao, $TipoPagamento, $StatusTransacao, $CliNome, $CliEmail, $CliEndereco, $CliNumero, $CliComplemento, $CliBairro, $CliCidade, $CliEstado, $CliCEP, $CliTelefone, $produtos, $NumItens) {
  $config = new JConfig;
  $con = new mysqli($config->host, $config->user, $config->password, $config->db);
  if (in_array($StatusTransacao, array('Completo', 'Aprovado'))) {
    $query = sprintf("UPDATE %svm_orders SET order_status = 'C' WHERE order_id = '%s'", $config->dbprefix, $Referencia);
    $con->query($query);
  } elseif ($StatusTransacao=="Cancelado") {
    $query = sprintf("UPDATE %svm_orders SET order_status = 'X' WHERE order_id = '%s'", $config->dbprefix, $Referencia);
    $con->query($query);
  }
}

define ( '_VALID_MOS' , true ); define ( '_JEXEC' , true );
include ('../ps_pagseguro.cfg.php');

$pasta = null; while (!file_exists("$pasta/configuration.php")) pasta($pasta);
include ("$pasta/configuration.php");
define ('TOKEN', PGS_TOKEN);

include ('biblioteca/retorno.php');

