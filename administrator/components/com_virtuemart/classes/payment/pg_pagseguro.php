<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );
class Pg_Pagseguro
{
    public $classname    = 'pg_pagseguro';
    public $payment_code = 'PGSE';

    public function Pagseguro()
    {
        $this->classname = strtolower(get_class($this));
    }

    function show_configuration() 
    {
        global $row;
        $configs = $this->configs();
        $form    = array();
        $row     = 0;
        foreach($configs as $item) {
            $form[] .= $this->trataInput($item);
        }
        $form = implode("\n\n", $form);
        echo ' <div style="padding:0 10px;"> <table class="adminform"> '.$form.' </table> </div>';
    }

    function configs() 
    {
        return $configs = array(
            array('name' => 'token',   'label' => 'Token', 'help' => 'Numero gerado no painel de controle do PagSeguro'),
            array('name' => 'email',   'label' => 'O seu e-mail de login no PagSeguro'),
            array('name' => 'retorno', 'label' => 'Pagina de retorno do PagSeguro', 'help' => 'Página para onde o usuário será redirecionado quando voltar do PagSeguro'),
        );
    }

    function trataInput($input) 
    {
        global $row;
        $row++;
        require_once(CLASSPATH ."payment/".$this->classname.".cfg.php");
        if (!isset($input['type'])) $input['type'] = 'text';
        $input['id'] = "{$this->classname}_{$input['name']}";
        $code = $this->payment_code.'_'.strtoupper($input['name']);
        $code = defined($code) ? constant($code) : '';
        $help = isset($input['help']) ? mm_ToolTip($input['help']) : '';
        switch ($input['type']) {
        case 'select':
            $options = array();
            foreach($input['options'] as $k=>$v) {
                $options[] = sprintf('<option value="%s"%s>%s</option>', $k, ($k==$code ? ' selected="selected"': ''), $v);
            }
            return sprintf ('<tr class="row%s"><td class="labelcell">%s:</td><td><select name="%s">%s</select> %s</td></tr>',
                $row%2, $input['label'], strtoupper($this->payment_code.'_'.$input['name']), implode("\n", $options),
                $help
            );
            break;
        default:
            return sprintf ('<tr class="row%s"><td class="labelcell">%s:</td><td><input type="%s" name="%s" value="%s" /> %s</td></tr>',
                $row%2, $input['label'], $input['type'], strtoupper($this->payment_code.'_'.$input['name']),
                $code, $help
            );
        }
    }
    function has_configuration() 
    {
        return true; 
    }

    function configfile_writeable() 
    {
        return is_writeable( CLASSPATH."payment/".$this->classname.".cfg.php" );
    }

    function configfile_readable() 
    {
        return is_readable( CLASSPATH."payment/".$this->classname.".cfg.php" );
    }

    function write_configuration( &$d ) 
    {
        $configs = $this->configs();
        $config = "<?php if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); \n\n";
        foreach($configs as $item) {
            $name = strtoupper($this->payment_code.'_'.$item['name']);
            $value = $d[$name];
            $config .= "define ('{$name}', '{$value}');\n";
        }
        if ($fp = fopen(CLASSPATH ."payment/".$this->classname.".cfg.php", "w")) {
            fputs($fp, $config, strlen($config));
            fclose ($fp);
            return true;
        } else {
            return false;
        }
    }

    public function process_payment($order, $order_total, &$d)
    {
        global $cart;
        // Configurações
        require_once(CLASSPATH.'payment/pg_pagseguro.cfg.php');
        // Biblioteca de produtos
        require_once(CLASSPATH.'ps_product.php');
        $ps_product = new ps_product;
        // Biblioteca de usuario
        require_once(CLASSPATH.'ps_user.php');
        $ps_user = new ps_user;
        // Biblioteca PagSeguro
        require_once(CLASSPATH.'payment/pagseguro/pgs.php');
        require_once(CLASSPATH.'payment/pagseguro/tratadados.php');
        // Banco de dados
        $db =& JFactory::getDBO();
        // Pegando os produtos
        $products = array();
        foreach ($cart as $item) {
            if ( 'array' !== gettype($item) ) { continue; }
            $id = $item['product_id'];
            $price = $ps_product->get_price($id);
            $price = $ps_product->calcEndUserprice($id, null);
            $products[] = array(
                'id'         => $id,
                'descricao'  => $ps_product->get_field($id, 'product_name'),
                'quantidade' => $item['quantity'],
                'valor'      => number_format($price['product_price'], 2, '.', ''),
            );
        }
        // Informações do usuario
        $user = (object) $_SESSION['auth'];
        $user = $ps_user->getUserInfo($user->user_id);
        $user = $db->loadObject();
        // Montando o Objeto PagSeguro
        list($telefone_ddd, $telefone) = trataTelefone($user->phone_1);
        list($endereco, $endereco_num) = trataEndereco("{$user->address_1} {$user->address_2}");
        $pgs = new Pgs(array(
            'email_cobranca' => PGSE_EMAIL,
            'tipo'           => 'CP',
            'ref_transacao'  => $order,
            'item_frete_1'   => number_format($d['shipping_total'] * 100, 0, '', ''),
        ));
        $pgs->cliente(array(
            'nome'   => $user->first_name." ".$user->last_name,
            'cep'    => $user->zip,
            'end'    => $endereco,
            'num'    => $endereco_num,
            'compl'  => $user->address_2,
            'cidade' => $user->city,
            'uf'     => $user->state,
            'pais'   => $user->country,
            'ddd'    => $telefone_ddd,
            'tel'    => $telefone,
            'email'  => $user->user_email,
        ));
        $pgs->adicionar($products);
        print '<form target="pagseguro" action="https://pagseguro.uol.com.br/security/webpagamentos/webpagto.aspx" method="post" id="formulario_pagseguro">';
        $pgs->mostra(array('show_submit' => false));
        print '<script>document.getElementById("formulario_pagseguro").submit()</script>';
        return true;
    }
}
