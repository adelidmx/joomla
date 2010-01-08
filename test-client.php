<?php
include_once ('config-test.php');
include_once ('autorun.php');
include_once ('web_tester.php');

class ClientTest extends WebTestCase {
  var $id;
  var $const;
  function setUp() {
    $file = file (VM_PAYMENT_MODULE_PATH.'/ps_pagseguro.cfg.php');
    foreach($file as $item)
      if (preg_match("@define \('(PGS_\w+)', '([^']+)'@", $item, $matches))
        $this->const[$matches[1]] = $matches[2];
    $code = file_get_contents('payment_method_code.php');
    global $con;
    $con->query('DELETE FROM jos_vm_payment_method WHERE payment_class = \'ps_pagseguro\'');
    $data = array(
      'payment_method_id' => '',
      'vendor_id' => '1',
      'payment_method_name' => 'PagSeguro',
      'payment_class' => 'ps_pagseguro',
      'shopper_group_id' => 5,
      'payment_method_discount' => 0,
      'payment_method_discount_is_percent' => 0,
      'payment_method_discount_max_amount' => 0,
      'payment_method_discount_min_amount' => 0,
      'list_order' => 0,
      'payment_method_code' => 'PGS',
      'enable_processor' => 'P',
      'is_creditcard' => 0,
      'payment_enabled' => 'Y',
      'accepted_creditcards' => '',
      'payment_extrainfo' => addslashes($code),
      'payment_passkey' => '',
    );
    array_walk($data, create_function('&$v', '$v = "\'$v\'";'));
    $q=sprintf ('INSERT INTO `jos_vm_payment_method` (%s) VALUES (%s);', implode(', ', array_keys($data)), implode(', ', $data));
    $con->query($q);
    $r = $con->query('SELECT * FROM jos_vm_payment_method WHERE payment_method_name = \'PagSeguro\'');
    $this->id = $r->fetch_object()->payment_method_id;

    /* Gerando um novo usuário */
    $data = array(
      'id'  => 65, 'name' => 'Michael', 'username' => 'dgmike', 'email' => 'joao@nasa.gov',
      'password' => '85f216897b53539f8f5ed2dd82cf3fc8:6Qc9ER5XPzbUSGyPv2sKaQNrYn7InHG1',
      'usertype' => 'Registered', 'block' => 0, 'sendEmail' => 0, 'gid' => 18,
    );
    array_walk($data, create_function('&$v', '$v = "\'$v\'";'));
    $data = $data + array( 'registerDate' => 'NOW()', 'lastvisitDate' => 'NOW()');
    $con->query("DELETE FROM jos_users WHERE username = 'dgmike'");
    $q=sprintf ('INSERT INTO `jos_users` (%s) VALUES (%s);', implode(', ', array_keys($data)), implode(', ', $data));
    $con->query($q);

    $data = array (
       'user_info_id' => 'f7d95cc8d4c75b8caeda36d3bfced56c', 'user_id' => 65, 'address_type' => 'BT',
       'address_type_name' => '-default-', 'company' => 'Visie', 'title' =>  'Mr.', 'last_name' => 'Granados',
       'first_name' => 'Michael', 'middle_name' => 'Castillo', 'phone_1' => '(11) 1111-0098', 'phone_2' => '', 'fax' => '',
       'address_1' => 'Pq Eldorado, 512', 'address_2' => 'apto 550', 'city' => 'Sao Paulo', 'state' => 'SP',
       'country' => 'Brazil', 'zip' => '02022100', 'user_email' => 'joao@nasa.gov', 'cdate' => '1226327187',
       'mdate' => '1226327187', 'perms' => 'shopper', 'bank_account_type' => 'Checking',
    );
    array_walk($data, create_function('&$v', '$v = "\'$v\'";'));
    $con->query("DELETE FROM `jos_vm_user_info` WHERE user_info_id = 'f7d95cc8d4c75b8caeda36d3bfced56c' OR user_id = 65");
    $q=sprintf ('INSERT INTO `jos_vm_user_info` (%s) VALUES (%s);', implode(', ', array_keys($data)), implode(', ', $data));
    $con->query($q);

    $con->query("DELETE FROM `jos_core_acl_aro` WHERE id = '21'");
    $con->query("INSERT INTO jos_core_acl_aro (id,section_value,value,order_value,name,hidden) VALUES(21,'users','65',0,'Michael Granados',0)");

    $con->query("DELETE FROM `jos_vm_auth_user_vendor` WHERE user_id = '67'");
    $con->query("INSERT INTO jos_vm_auth_user_vendor (user_id,vendor_id) VALUES ('67','1');");

    $con->query("DELETE FROM `jos_vm_shopper_vendor_xref` WHERE user_id = '67'");
    $con->query("INSERT INTO jos_vm_shopper_vendor_xref (user_id,vendor_id,shopper_group_id,customer_number) VALUES ('67', '1','5', '1778372287493457d82bbe9')");

    $con->query("DELETE FROM `jos_core_acl_groups_aro_map` WHERE aro_id = '21'");
    $con->query("INSERT INTO jos_core_acl_groups_aro_map (group_id,aro_id) VALUES (18,21)");
  }
  function testCompra($doisItens=false){
    $qty1 = rand(1, 40); $qty2 = rand(1, 40); $i=0;
    foreach(array('Hammer', 'Nice Saw') as $item) {
      $this->get(BASE_URL, array(
        'option' => 'com_virtuemart',
        'page' => 'shop.browse',
        'category_id' => 1,
        'item_id' => 2,
      ));
      $this->assertPattern("@{$item}@", 'Deve ter o Martelo');
      $this->click($item);
      $this->setField('quantity[]', ${'qty'.++$i});
      $this->click('Add to Cart');
      if(!$doisItens) break;
    }
    $this->get(BASE_URL, array(
      'option' => 'com_virtuemart',
      'page' => 'shop.cart',
    ));
    $this->assertPattern('@Cart@', 'Você chegou à página do carrinho');
    $this->assertPattern('@Hammer@', 'Existe o martelo no carrinho');
    $this->click('Checkout');
    $this->assertPattern('@Please Log In@', 'Área de login para o usuário');
    $this->setField('username', 'dgmike');
    $this->setField('passwd', '123123');
    $this->click('Login');
    $this->assertPattern('@Shipping Address@', 'Chegamos ao checkout do sistema.');
    $this->click('Next >>');
    $this->assertPattern('@Please select a Shipping Method@', 'Tela de envio de mercadoria.');
    $this->setField('shipping_rate_id', 'flex%7CSTD%7CStandard+Shipping+under+25.00%7C12.99');
    $this->click('Next >>');
    $this->assertPattern('@Other Payment Methods@', 'Tela de métodos de pagamento.');
    $this->setField('payment_method_id', $this->id);
    $this->click('Next >>');
    $this->click('Confirm Order');
    /* Dados do comprador  */
    $this->assertField('email_cobranca', $this->const['PGS_EMAIL']);
    $this->assertField('tipo', 'CP');
    $this->assertField('tipo_frete', $this->const['PGS_TIPO_FRETE']);
    $this->assertField('moeda', 'BRL');
    /* Dados do cliente */
    $this->assertField('cliente_nome', 'Michael Granados');
    $this->assertField('cliente_cep', '02022100');
    $this->assertField('cliente_end', 'Pq Eldorado');
    $this->assertField('cliente_num', '512');
    $this->assertField('cliente_compl', 'apto 550');
    $this->assertField('cliente_cidade', 'Sao Paulo');
    $this->assertField('cliente_uf', 'SP');
    $this->assertField('cliente_pais', 'Brazil');
    $this->assertField('cliente_ddd', '11');
    $this->assertField('cliente_tel', '11110098');
    $this->assertField('cliente_email', 'joao@nasa.gov');
    /* Carrinho */
    $this->assertField('item_id_1', 'H02');
    $this->assertField('item_descr_1', 'Hammer<br />Size: big<br/> Material: wood and metal');
    $this->assertField('item_quant_1', $qty1);
    if ($doisItens) {
      $this->assertField('item_id_2', 'H01');
      $this->assertField('item_descr_2', 'Nice Saw<br />Size: big<br/> Power: 100W');
      $this->assertField('item_quant_2', $qty2);
    }
  }
  function testDoisProdutos() {
    $this->testCompra(true);
  }
  function testRetorno($data=array('Completo', 'C')) { # Ative o sistema de emular o PagSeguro
    global $con;
    $this->testCompra();
    $this->click('Pague com o PagSeguro');
    $this->assertTrue($this->setField('TipoPagamento', 'Pagamento Online'));
    $this->assertTrue($this->setField('StatusTransacao', $data[0]));
    $this->click('Testar Retorno Automático');
    $res=$con->query('SELECT * FROM jos_vm_orders ORDER BY order_id DESC LIMIT 1');
    $this->assertEqual($res->fetch_object()->order_status, $data[1], "Conseguiu terminar a compra. %s");
  }
  function testRetornoAprovado() {
    $this->testRetorno(array('Aprovado', 'C'));
  }
  function testRetornoCancelado() {
    $this->testRetorno(array('Cancelado', 'X'));
  }
}
