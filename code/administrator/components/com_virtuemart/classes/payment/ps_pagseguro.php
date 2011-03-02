<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );

class ps_pagseguro {
  var $classname = 'ps_pagseguro';
  var $payment_code = 'PGS';

  function show_configuration() {
    $configs = $this->configs();
    foreach($configs as $item) 
      $this->trataInput($item);
  }

  function configs() {
    return $configs = array(
      array('name' => 'token',      'label' => 'Informação sobre seu TOKEN, que você consegue no site do pagseguro.'),
      array('name' => 'email',      'label' => 'O seu e-mail de login no PagSeguro'),
      array('name' => 'tipo_frete', 'label' => 'Tipo de frete', 'type' => 'select' , 'options' => array(
        'EN' => 'PAC - Encomenda',
        'SD' => 'Sedex',
      ))
    );
  }
  function trataInput($input) {
    require_once(CLASSPATH ."payment/".$this->classname.".cfg.php");
    if (!isset($input['type'])) $input['type'] = 'text';
    $input['id'] = "{$this->classname}_{$input['name']}";
    $code = $this->payment_code.'_'.strtoupper($input['name']);
    $code = defined($code) ? constant($code) : '';
    switch ($input['type']) {
      case 'select':
        $options = array();
        foreach($input['options'] as $k=>$v) {
          $options[] = sprintf('<option value="%s"%s>%s</option>', $k, ($k==$code ? ' selected="selected"': ''), $v);
        }
        printf ('<p><label for="%s">%s <select name="%s" id="%s">%s</select></label>',
          $input['id'],
          $input['label'],
          strtoupper($this->payment_code.'_'.$input['name']),
          $input['id'],
          implode("\n", $options)
        );
        break;
      default:
        printf ('<p><label for="%s">%s <input type="%s" name="%s" id="%s" value="%s" /></label></p>', 
          $input['id'],
          $input['label'],
          $input['type'],
          strtoupper($this->payment_code.'_'.$input['name']),
          $input['id'],
          $code
        );
    }
  }
  function has_configuration() {
    return true; 
  }
  function configfile_writeable() {
    return is_writeable( CLASSPATH."payment/".$this->classname.".cfg.php" );
  }
  function configfile_readable() {
    return is_readable( CLASSPATH."payment/".$this->classname.".cfg.php" );
  }

	function write_configuration( &$d ) {
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
   
  function process_payment($order_number, $order_total, &$d) {
    return true;
  }
}
