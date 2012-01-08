<?php

require_once "PagSeguroLibrary/PagSeguroLibrary.php";

class NotificationListener  {

    public static function main() {
 
     	$code = self::verifyData($_POST['notificationCode']);
    	$type = self::verifyData($_POST['notificationType']);

    	if ( $code && $type ) {
			
    		$notificationType = new PagSeguroNotificationType($type);
    		$strType = $notificationType->getTypeFromValue();
			
			switch($strType) {
				
				case 'TRANSACTION':
					self::transactionNotification($code);
					break;
				
				default:
					LogPagSeguro::error("Unknown notification type [".$notificationType->getValue()."]");
					
			}

			self::saveLog($strType);
			
		} else {
			
			LogPagSeguro::error("Invalid notification parameters.");
			self::saveLog();
			
		}

    }
    
    private function transactionNotification($notificationCode) {
		
    	/*
    	* #### Crendenciais #####
    	* Se desejar, utilize as credenciais pré-definidas no arquivo de configurações
    	* $credentials = PagSeguroConfig::getAccountCredentials();
    	*/

    	$credentials = new PagSeguroAccountCredentials(PGS_EMAIL, PGS_TOKEN);
    	
    	try {
    		
        $transaction = PagSeguroNotificationService::checkTransaction($credentials, $notificationCode);

            self::validateTransaction($transaction);

    	} catch (PagSeguroServiceException $e) {

    		die($e->getMessage());

    	}
        
    }

    private static function validateTransaction(Transaction $transaction) 
    {
        $config = new JConfig;
        $con = new mysqli($config->host, $config->user, $config->password, $config->db);

        $Referencia = $transaction->getReference();
        
        $statusTransacao = $transaction->getStatus();
        

        if ($statusTransacao == '3') {
        
            $query = sprintf("UPDATE %svm_orders SET order_status = 'C' WHERE order_id = '%s'", $config->dbprefix, $Referencia);
            $con->query($query);
            
        } else ( strtolower($statusTransacao) == '7' ) {
        
            $query = sprintf("UPDATE %svm_orders SET order_status = 'X' WHERE order_id = '%s'", $config->dbprefix, $Referencia);
            $con->query($query);
        
        }
        
    }
        
    /**
     * verifyData - Corrige os dados enviados via post
     * @data string Dados enviados via post
     */
    private static function verifyData($data){

        return isset($data) && trim($data) !== "" ? trim($data) : null;

    }  

    private static function saveLog($strType = null) {
        #LogPagSeguro::getHtml();
    }

}

define ( '_VALID_MOS' , true ); define ( '_JEXEC' , true );
include ('../ps_pagseguro.cfg.php');

NotificationListener::main();

