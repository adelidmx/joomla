<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );

class pagseguro
{
    public $cep_origem  = null;
    public $cep_destino = null;
    public $valor       = null;
    public $preso       = null;
    public $classname   = __CLASS__;

	/**
	 * Lists all available shipping rates
	 *
	 * @param array $d
	 * @return boolean
	 */
    function list_rates( &$d ) 
    {
		global $total, $tax_total, $CURRENCY_DISPLAY;
        // Read current configuration
        require_once(CLASSPATH .'shipping/'.$this->classname.'.cfg.php');
        $cep           = defined('PS_SHIPPING_METHOD_CEP')           ? PS_SHIPPING_METHOD_CEP : '';
        $titulo        = defined('PS_SHIPPING_METHOD_TITLE')         ? PS_SHIPPING_METHOD_TITLE : 'PagSeguro';
        $sedex         = defined('PS_SHIPPING_METHOD_SEDEX')         ? PS_SHIPPING_METHOD_SEDEX : 1;
        $pac           = defined('PS_SHIPPING_METHOD_PAC')           ? PS_SHIPPING_METHOD_PAC   : 1;
        $default_value = defined('PS_SHIPPING_METHOD_DEFAULT_VALUE') ? PS_SHIPPING_METHOD_DEFAULT_VALUE   : '0';
        $default_value = (float) $default_value;
        // Time to start
        // Create DB User Object for Current User
		$dbu = new ps_DB;
		$q  = "SELECT zip FROM #__{vm}_user_info WHERE user_info_id = '{$d['ship_to_info_id']}'";
		$dbu->query($q);
		if (!$dbu->next_record()) {
			# $vmLogger->err( 'Dados do usuário não encontrados para fazer o fretamento.' );
			# return False;
		}
		$destino = preg_replace('@\D@', '', $dbu->f('zip'));
        if (strlen($destino)!=8) { $destino = '00000-000'; }
        else { $destino = substr($destino, 0, 5).'-'.substr($destino, -3); }
        $peso    = number_format($d['weight']/100, 3, ',', '');
        $valor   = number_format($total+$tax_total, 2, ',', '');
        // Calculating...
        require_once('pagseguro/frete.php');
        $frete = new PgsFrete();
        $valores = $frete->gerar($cep, $peso, $valor, $destino); # A magica acontece!
        // Definindo o valor default
        $total_itens = 0;
        foreach ($_SESSION['cart'] as $item) {
            if (isset($item['quantity'])) {
                $total_itens += $item['quantity'];
            }
        }
        $default_value = $total_itens*$default_value;
        $default_value = number_format($default_value, 2, ',', '');
        // Compondo o valor
        $shipping = array();
        $base    = '<input type="radio" name="shipping_rate_id" value="%s" /> %s - <strong>%s</strong>';
        $base_id = __CLASS__.'|correios|%s|%s|%s';
        if ($sedex) {
            if (!isset($valores['Sedex'])) {
                $valores['Sedex'] = $default_value;
            }
            $id            = urlencode(sprintf($base_id, 'SEDEX', str_replace(',', '.', $valores['Sedex']), 1));
            $_SESSION[$id] = true; # Setando a $_SESSION para usar no validate
            $shipping[]    = sprintf ($base, $id, 'Sedex', 'R$ '.$valores['Sedex']);
        }
        if ($pac) {
            if (!isset($valores['PAC'])) {
                $valores['PAC'] = $default_value;
            }
            $id            = urlencode(sprintf($base_id, 'PAC', str_replace(',', '.', $valores['PAC']), 1));
            $_SESSION[$id] = true; # Setando a $_SESSION para usar no validate
            $shipping[]    = sprintf ($base, $id, 'PAC (encomenda comum)', 'R$ '.$valores['PAC']);
        }
        echo "<strong>$titulo</strong><br />" . join('<br />', $shipping);
        return true;
    }

    /**
     * Returns the "is_writeable" status of the configuration file
     * @param void
     * @returns boolean True when the configuration file is writeable, false when not
     */
    function configfile_writeable() 
    {
        return is_writeable( CLASSPATH . 'shipping/' . $this->classname . '.cfg.php' ) ;
    }

    /**
     * Show all configuration parameters for this Shipping method
     * @returns boolean False when the Shipping method has no configration
     */
    function show_configuration() 
    {
        require_once(CLASSPATH ."shipping/".$this->classname.".cfg.php");
        $help_cep      = mm_ToolTip('Informe o CEP de origem dos produtos no formato <strong>XXXXX-XXX</strong>');
        $help_default  = mm_ToolTip( 'Se não for possivel encontrar o valor em PAC<br />
                                      ou Sedex o calculo usará este valor como padrão<br />
                                      multiplicado para cada item no carrinho' );
        $cep           = defined('PS_SHIPPING_METHOD_CEP')           ? PS_SHIPPING_METHOD_CEP             : '';
        $titulo        = defined('PS_SHIPPING_METHOD_TITLE')         ? PS_SHIPPING_METHOD_TITLE           : 'PagSeguro';
        $sedex         = defined('PS_SHIPPING_METHOD_SEDEX')         ? PS_SHIPPING_METHOD_SEDEX           : 1;
        $pac           = defined('PS_SHIPPING_METHOD_PAC')           ? PS_SHIPPING_METHOD_PAC             : 1;
        $default_value = defined('PS_SHIPPING_METHOD_DEFAULT_VALUE') ? PS_SHIPPING_METHOD_DEFAULT_VALUE   : '0';
        $sedex_checked = $sedex === 1 ? 'checked="checked"' : '';
        $pac_checked   = $pac   === 1 ? 'checked="checked"' : '';
        echo <<<EOF

<div style="width:80%;padding:0 10px;">
    <table class="adminform">
		<tr>
			<td class="labelcell">Título:</td>
			<td><input type="text" name="PS_SHIPPING_METHOD_TITLE" class="inputbox" value="$titulo" /></td>
		</tr>
		<tr>
			<td class="labelcell">Informe seu CEP:</td>
			<td><input type="text" name="PS_SHIPPING_METHOD_CEP" class="inputbox" value="$cep" /> $help_cep</td>
		</tr>
        <tr>
            <td class="labelcell">Formas de envio disponíveis:</td>
            <td>
                <input type="checkbox" name="PS_SHIPPING_METHOD_SEDEX" class="inputbox" value="1" $sedex_checked /> SEDEX<br />
                <input type="checkbox" name="PS_SHIPPING_METHOD_PAC"   class="inputbox" value="1" $pac_checked   /> PAC (Encomenda comum)<br />
                <em>Infome pelo menos um</em>
            </td>
        </tr>
		<tr>
			<td class="labelcell">Valor padrão:</td>
			<td><input type="text" name="PS_SHIPPING_METHOD_DEFAULT_VALUE" class="inputbox" value="$default_value" /> $help_default</td>
		</tr>
    </table>
</div>

EOF;
        return true;
    }

    /**
     * Writes the configuration file for this shipping method
     * @param array An array of objects
     * @returns boolean True when writing was successful
     */
    function write_configuration( &$d ) 
    {
        global $vmLogger;
        // Manipulando o CEP
        $cep = $d['PS_SHIPPING_METHOD_CEP'];
        $cep = preg_replace('@\D@', '', $cep);
        if (strlen($cep)!=8) {
			$vmLogger->err( 'O CEP que você infomou não é válido.' );
            return false;
        }
        $cep     = substr($cep, 0, 5).'-'.substr($cep, -3);
        // O resto dos dados...
        $titulo  = addslashes($d['PS_SHIPPING_METHOD_TITLE']);
        $sedex   = (int)   $d['PS_SHIPPING_METHOD_SEDEX'];
        $pac     = (int)   $d['PS_SHIPPING_METHOD_PAC'];
        $def_val = (float) str_replace(',', '.', $d['PS_SHIPPING_METHOD_DEFAULT_VALUE']);

        $config  = "<?php\n";
        $config .= "if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); \n\n";
        $config .= "define('PS_SHIPPING_METHOD_TITLE',        '$titulo');\n";
        $config .= "define('PS_SHIPPING_METHOD_CEP',          '$cep');\n";
        $config .= "define('PS_SHIPPING_METHOD_PAC',           $pac);\n";
        $config .= "define('PS_SHIPPING_METHOD_SEDEX',         $sedex);\n";
        $config .= "define('PS_SHIPPING_METHOD_DEFAULT_VALUE', $def_val);\n";

		if ($fp = fopen(CLASSPATH ."shipping/".$this->classname.".cfg.php", "w")) {
			fputs($fp, $config, strlen($config));
			fclose ($fp);
			return true;
		} else {
			$vmLogger->err( "Error writing to configuration file" );
			return false;
		}
    }

	/**
	 * Returns the rate for the selected shipping method
	 *
	 * @param array $d
	 * @return float
	 */
	function get_rate( &$d ) {
		$shipping_rate_id = $d["shipping_rate_id"];
		$is_arr = explode("|", urldecode( urldecode($shipping_rate_id)) );
		$order_shipping = (float) $is_arr[3];
		return $order_shipping;
	}

	/**
	 * Returns the tax rate for this shipping method
	 *
	 * @return float The tax rate (e.g. 0.16)
	 */
	function get_tax_rate() {
		return 0;
	}

	/**
	 *  Validate this Shipping method by checking if the SESSION contains the key
	 * @returns boolean False when the Shipping method is not in the SESSION
	 */
    function validate( $d ) 
    {
		$shipping_rate_id = $d["shipping_rate_id"];
        return array_key_exists( $shipping_rate_id, $_SESSION );
    }
}
