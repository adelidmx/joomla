<?php
include_once ('config-test.php');
include_once ('autorun.php');
include_once ('web_tester.php');

class AdminTest extends WebTestCase {
  function setUp() {
    global $con;
    $con->query('DELETE FROM jos_vm_payment_method WHERE payment_class = \'ps_pagseguro\'');
  }
  function testListInPaymentClassName() {
    $this->get(BASE_URL.'/administrator');
    $this->setField('username', JOOMLA_TEST_ADMIN_USER);
    $this->setField('passwd',   JOOMLA_TEST_ADMIN_PASS);
    $this->submitFormById('form-login');
    $this->click('VirtueMart');
    $this->click('List Payment Methods');
    $this->assertNoPattern('@PGS@', 'Não deve ter o pagseguro, não por enquanto.');
    $this->click('Add Payment Method');
    $this->assertPattern('@pagseguro@', 'Deve incluir a classe de pagamento do pagseguro.');
    $this->assertTrue($this->setField('payment_method_name', 'PagSeguro'));
    $this->assertTrue($this->setField('payment_method_code', 'PGS'));
    $this->assertTrue($this->setField('payment_class', 'ps_pagseguro'));
    $this->assertTrue($this->setField('enable_processor', 'P'));
    $this->submitFormByName('adminForm');
    $this->click('List Payment Methods');
    $this->assertPattern('@PGS@', 'Deve ter o pagseguro na lista de metodos de pagamento');
    $this->click('PagSeguro');
    $this->assertPattern('@Payment Method Form@');
    $codigo_token = rand(1000,9999);
    $this->assertTrue($this->setField('PGS_TOKEN', $codigo_token), "Tentando inserir o valor do Token %s");
    $this->assertTrue($this->setField('PGS_EMAIL', "teste_{$codigo_token}@teste.org"), "Tentando inserir o valor da Url de Retorno %s");
    $this->assertTrue($this->setField('PGS_TIPO_FRETE', "SD"), "Tentando inserir o valor da Url de Retorno %s");
    $this->submitFormByName('adminForm');
    $this->click('PagSeguro');
    $this->assertField('PGS_TOKEN', $codigo_token);
    $this->assertField('PGS_EMAIL', "teste_{$codigo_token}@teste.org");
    $this->assertField('PGS_TIPO_FRETE', "SD");
  }
}
