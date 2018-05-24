<?php
/**
 * Mikrotik Ping
 *
 * PHP versions 5 and 7
 *
 * LICENSE: This source file is subject to version 3 of the GNU license
 * that is available through the world-wide-web at the following URI:
 * https://www.gnu.org/licenses/gpl.txt.  If you did not receive a copy of
 * the GNU License and are unable to obtain it through the web, please
 * send a note to gnu@gnu.org so we they can mail you a copy immediately.
 *
 * @category   Servers
 * @package    Mikrotik
 * @author     costa <deepcell@gmail.com>
 * @copyright  Free to use and change under the terms of the license.
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU License Version 3
 * @version    0.1
 * @link       https://github.com/deepcell/Mikrotik
 * @see        --
 * @since      File available since Release 0.1
 * @deprecated --
 */

/* controle aqui o acesso(sessao) ao script */

require 'config.php';

/**
*
* Importante
* para poder testar ativar "TEST" nas permições de acesso para o 
* usuário da API -->>  `/system/user`
*
* Exemplo de GET request via url: 
* `http://localhost/mikrotik_ping.php?oprt=ping&serverid=4&rec=true&idcontrato=xxxx&protocolo=xxxxxx&ip=192.168.100.25`
*
*/
$op       = $_GET["oprt"];
$serverid = $_GET["serverid"];    // ID do servidor ANTIGO que ja vem carregado no dropbox
$rec      = $_GET["rec"];         // rec - significa gravar ou nao no banco como hisorico essa consulta
$idc      = $_GET["idcontrato"];  // ID do contrato p/ gravar no historico do ping, assim sabemos que aquele ping ocorreu para o contrato especifico..
$proto    = $_GET["protocolo"];   // ID do protocolo -- isso precisa ser exibido em paralelo ao historico do protocolo do cliente.
$pingAddressDST = $_GET["ip"];    // O PING será executado à partir do MK acessado, que tem que ser o server do cliente.
$pingCount = 10;                  // Quantidade de PING a se executar



############################ SERVIDOR ATUAL ###################################################
/*******************************************************************************
 * Consulta os dados do servico do servidor - precisamos pegar o port do servico
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[serverid]
 ******************************************************************************/
$dss = $database->select( 'servidor_services', '*', array( 'server_id[=]' => $serverid )); //4
foreach($dss as $ds) {
	// recebe a porta da API
	if ($ds['service_name'] == "api" and $ds['service_status'] == 1)
		$APIPort = $ds['service_port']; // porta usada para acessar a API Mikrotik

	// recebe a porta https da API
	if ($ds['service_name'] == "www-ssl" and $ds['service_status'] == 1)
		$APIPortHttps = $ds['service_port']; // porta usada para acessar a API Mikrotik via ssl

	// recebe a porta http da API
	if ($ds['service_name'] == "www" and $ds['service_status'] == 1)
		$APIPortHttp = $ds['service_port']; // porta usada para acessar a API Mikrotik via ssl

}

/*******************************************************************************
 *consulta os dados do servidor para acesso
 ******************************************************************************/
$ds   = $database->select( 'servidor', '*', array( 'id[=]' => $serverid ));
$ipmk = $ds['0']['ip_servidor'];
$user = $ds['0']['usuario'];
$pwdd = $ds['0']['autenticacao'];
$Pass = trim($pwdd);
###############################################################################################



/*******************************************************************************
 * PING : faz o ping no ip do cliente apresentado
 * acesso a api mikrotik
 ******************************************************************************/
if ($op == "ping") {

	$API = new RouterosAPI();
	$API->debug = false;
	
	if ($API->connect($ipmk, $user, $Pass, $APIPort)) {

		$rows = array(); $rows2 = array();	
		$API->write("/ping",false);
		$API->write("=address=".$pingAddressDST,false);  
		$API->write("=count=".$pingCount,true);
		$READ = $API->read(false);
		$ARRAY = $API->ParseResponse($READ);

		if (count($ARRAY) > 0) {

			$arr_hist_ping = [];
			for ($n=0;$n<count($ARRAY);$n++) {

				if ($ARRAY[$n]["received"]!=$ARRAY[$n]["sent"] || $ARRAY[$n]["status"]=="timeout") {
					$error .= $ARRAY[$n]["status"]."<br/>";
					$arr_hist_ping = []; // empty array in case of no response
					$html_hist_ping .= "<div>N&atilde;o houve resposta do servidor.</div>";
				}
				else {
					$response .= "Resposta de ".$pingAddressDST.": bytes=".$ARRAY[$n]["size"]." tempo=".$ARRAY[$n]["time"]." TTL=".$ARRAY[$n]["ttl"].".<br/>";
					// dados no array - se desejar gravar no banco como historico
					$arr_hist_ping[] = array("ip_client" => $pingAddressDST, "size" => $ARRAY[$n]["size"], "time" => $ARRAY[$n]["time"], "ttl" => $ARRAY[$n]["ttl"]);
					// 	dados se desejar fazer um output na tela em html
					$html_hist_ping .= "<div> IP: ".$pingAddressDST.", SIZE: ".$ARRAY[$n]['size'].", TIME: ".$ARRAY[$n]['time'].", TTL: ".$ARRAY[$n]['ttl']." </div>";
				}
			}

		} else {

			$error_api = $ARRAY['!trap'][0]['message'];
			$error .= $ARRAY['!trap'][0]['message'];
		}

	} else
		$error .= "Falha na conex&atilde;o. Verifique se a API est&aacute; ativa em " . $ipmk;

	$API->disconnect();
}



// registra historico do ping na base de dados
if ($rec == "true") {
	
	$arr_hist_ping = array_filter($arr_hist_ping);

	if (!empty($arr_hist_ping)) {

		foreach($arr_hist_ping as $arrping) {

			// grava o historico no banco
			// importante: a tabela `mikrotik_ping` precisa ser criada
			$last_id = $database->insert("mikrotik_ping", array(
				"id" => NULL,             // ID PK
				"serverid" => $serverid,  // o ID do servidor que o cliente encontra-se
				"contratoid" => $idc,     // aqui precisa ser o ID do contrato do cliente para controle pois cliente pode ter 2 ou mais contratos
				"protocoloid" => $proto,  // se desejar incluir o protocolo que originou o pedido de ping
				// ping statistics
				"ip_client" => $pingAddressDST, 
				"size" => $arrping["size"], 
				"time" => $arrping["time"], 
				"ttl" => $arrping["ttl"], 
				"date_time" => date("Y-m-d H:i:s")
			));
		}
		echo "<pre>O resultado foi gravado no banco de dados.</pre>";
	}
	echo "<pre>N&atilde;o houve resposta do servidor, nada foi gravado no banco de dados.</pre>";
}



// mostrar resposta quando nao for vazio, senao mostra o erro
if (!empty($response))
	echo "<pre>" . $response . "</pre>";
else
	echo "<pre>" . $error . "</pre>";