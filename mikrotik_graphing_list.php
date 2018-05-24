<?php
/**
 * Mikrotik Graphing List
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
* para poder testar ativar "TEST" nas permissões de acesso para o 
* usuário da API -->>  `/system/user`
*
* Exemplo de GET request via url: 
* `http://localhost/mikrotik_graphing_list.php?oprt=set&serverid=3&serveridnew=4&ip=192.168.100.25&storeondisk=true&allowtarget=false&disabled=false`
*
*/
$op              = $_GET["oprt"];         // tipo de operacao: {graph, set, remove}
$serverid        = $_GET["serverid"];     // ID do servidor ANTIGO que ja vem carregado no dropbox
$serveridnew     = $_GET["serveridnew"];  // ID do NOVO servidor escolhido no dropbox
$ip              = $_GET["ip"];           // novo ip em alguns casos
$simple_queue    = "";                    // inicia vazio entao recebe o ip com zero a esquerda. Ex.: routeros_api::zerofillIp($datas['0']['ip']);
$store_on_disk   = $_GET["storeondisk"];
$allow_target    = $_GET["allowtarget"];
$disabled        = $_GET['disabled'];
$arr_ip_usado    = array();               // recebe os ips de equip. usados no cliente
$arr_ip_naousado = array();               // recebe os ips de equip. nao usados no cliente



// aqui precisa ser uma consulta com todos os IPs da rede do IP apresentado
// no entanto vou deixar um array com alguns dados daquela rede para proposito de teste apenas.
$data1 = [
	"0" => "10.0.26.1", 
	"1" => "10.0.27.1", 
	"2" => "10.0.28.1",
]; // use seus ips aqui para testar
$i = 0;
foreach ($data1 as $value) {

	// aqui precisamos de 2 novos arrays com os IPs que estao sendo usado 
	// no cliente e IPs que nao estao sendo usados no cliente.
	$data2[] = [
		[
		"control_conn" => "queues", 
		"usado_cliente" => 1, // 1 sim 0 nao
		"ip" => $value, 
		],
	]; // use seus ips aqui para testar	

	// guardar no array apenas os ips que `estao em uso` no cliente e o controle da conexao como `queues`
	if ($data2[$i]['control_conn'] == 'queues' and $data2[$i]['usado_cliente'] == 1 and !empty($data2[$i]['ip'])) {
		$arr_ip_usado[] = $data2[$i]['ip'];
	}

	// guardar no array apenas os ips que `nao estao em uso` no cliente e o controle da conexao como `queues`
	elseif ($data2[$i]['control_conn'] == 'queues' and $data2[$i]['usado_cliente'] == 0 and !empty($data2[$i]['ip'])) {
		$arr_ip_naousado[] = $data2[$i]['ip'];
	}

	// null
	else 
		echo "";

	$i +=1;
}



/*******************************************************************************
 * Bloco Experimental com dados no array
 ******************************************************************************/
// checa se o array esta vazio, caso nao esteja executa o bloco
$arr_ip_usado_checked = array_filter($arr_ip_usado);
if (!empty($arr_ip_usado_checked)) {

	foreach ($arr_ip_usado as $value_usado)
		$ipzerofilled[] = routeros_api::zerofillIp($value_usado); // esse IP precisa ter control_conn = queues e IP nao nulo

	foreach ($ipzerofilled as $val3)
		if (count($val3)>0)
			$datasanit_usado[] = $val3;
}
// checa se o array esta vazio, caso nao esteja executa o bloco
$arr_ip_naousado_checked = array_filter($arr_ip_naousado);
if (!empty($arr_ip_naousado_checked)) {

	foreach ($arr_ip_naousado as $value_naousado) 
		$ipzerofilled[] = routeros_api::zerofillIp($value_usado); // esse IP precisa ter control_conn = queues e IP nao nulo

	foreach ($ipzerofilled as $val4) 
		if (count($val4)>0)
			$datasanit_naousado[] = $val4;
}



############# SERVIDOR ATUAL ###################################################
/*******************************************************************************
 * Consulta os dados do servico do servidor - precisamos pegar o port do servico
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[serverid]
 ******************************************************************************/
$dss = $database->select( 'servidor_services', '*', array( 'server_id[=]' => $serverid ));
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
################################################################################



########## SERVIDOR SELECIONADO ################################################
/*******************************************************************************
 * Consulta os dados do servico do servidor - precisamos pegar o port do servico
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[serverid]
 ******************************************************************************/
$dss2 = $database->select( 'servidor_services', '*', array( 'server_id[=]' => $serveridnew ));
foreach($dss2 as $ds2) {
	// recebe a porta da API
	if ($ds2['service_name'] == "api" and $ds2['service_status'] == 1)
		$APIPort2 = $ds2['service_port']; // porta usada para acessar a API Mikrotik

	// recebe a porta https da API
	if ($ds2['service_name'] == "www-ssl" and $ds2['service_status'] == 1)
		$APIPortHttps2 = $ds2['service_port']; // porta usada para acessar a API Mikrotik via ssl

	// recebe a porta http da API
	if ($ds2['service_name'] == "www" and $ds2['service_status'] == 1)
		$APIPortHttp2 = $ds2['service_port']; // porta usada para acessar a API Mikrotik via ssl

}

/*******************************************************************************
 *consulta os dados do servidor para acesso
 ******************************************************************************/
$ds22   = $database->select( 'servidor', '*', array( 'id[=]' => $serveridnew ));
$ipmk2 = $ds22['0']['ip_servidor'];
$user2 = $ds22['0']['usuario'];

// guardar na sessao dados da conexao
$pwdd2 = $ds22['0']['autenticacao'];
$Pass2 = trim($pwdd2);
$_SESSION['MK_IP2'] = $ipmk2;
$_SESSION['MK_USER2'] = $user2;
$_SESSION['MK_AUTH2'] = $Pass2;
$_SESSION['MK_PORT2'] = $APIPort2;
################################################################################



/*******************************************************************************
 * DELETE : faz a remocao dos ips do gateway/network apresentado
 * acesso a api mikrotik
 ******************************************************************************/
if ($op == "remove") {

	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk, $user, $Pass, $APIPort)) {

		// rodar o foreach para cada ip aqui dentro
		// remover ip `USADO` no equip. do cliente
		if (isset($datasanit_usado)) {

			foreach ($datasanit_usado as $valueusado) {		

				$API->write("/tool/graphing/queue/getall",false);
				$API->write('?simple-queue='.$valueusado['0']['ipzerofilled'],true);
				$READ = $API->read(false);
				$ARRAY = $API->ParseResponse($READ);

				if (count($ARRAY)>0) {
					
					$API->write("/tool/graphing/queue/remove",false);  	// Na linha xx listamos as ether
					$API->write("=.id=".$ARRAY[0]['.id'],true);	// ID sequencial do MK da interface
					$READ = $API->read(false);
					$ARRAY = $API->ParseResponse($READ);
					$msg .= "<pre>O IP ". $valueusado['0']['ip'] ." foi removido da graphing list no servidor ".$ipmk.".</pre>";

			  	} else
					$msg .= "<pre>Houve uma falha e o ip ". $valueusado['0']['ip'] ." não foi removido da graphing list no servidor ".$ipmk.". <a href='' onclick='location.reload()'>&#x27f3;</a></pre>";
			}
		}
		// caso nao exista dados da consulta 
		else
			echo "erro: N&atilde;o existem IPs dessa rede sendo usado por clientes. Nada foi removido.";

		// disconecta da api
	  	$API->disconnect();

	} else
		$msg .= "<pre>Erro na conex&atilde;o com API Mikrotik.</pre>";
}

/*******************************************************************************
 * SET : faz a insert/update dos ips do gateway apresentado - funciona p/ range
 * acesso a api mikrotik
 ******************************************************************************/
elseif ($op == "set") {

	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk2, $user2, $Pass2, $APIPort2)) {

		// rodar o foreach para cada ip aqui dentro
		// ip `USADO` no equip. do cliente
		if (isset($datasanit_usado)) {

			foreach ($datasanit_usado as $valueusado) {

				$API->write("/tool/graphing/queue/getall",false);
				$API->write('?simple-queue='.$valueusado['0']['ipzerofilled'],true);
				$READ = $API->read(false);
				$ARRAY = $API->ParseResponse($READ);

				if (count($ARRAY)>0) {

					$API->write("/tool/graphing/queue/set",false);
					$API->write("=.id=".$ARRAY[0]['.id'],false);
					$API->write('=simple-queue='.$valueusado['0']['ipzerofilled'],false);
					$API->write('=store-on-disk='.$store_on_disk,false);
					$API->write('=allow-target='.$allow_target,false);
					$API->write('=disabled='.$disabled,true);		//true/false
					$READ = $API->read(false);
					$ARRAY = $API->ParseResponse($READ);
					$msg .= "Os dados foram atualizados para Graphing List do servidor ".$ipmk2.".";

				} else {

					$API->write("/tool/graphing/queue/add",false);
					$API->write('=simple-queue='.$valueusado['0']['ipzerofilled'],false);
					$API->write('=store-on-disk='.$store_on_disk,false);
					$API->write('=allow-target='.$allow_target,false);
					$API->write('=disabled='.$disabled,true);		//true/false
					$READ = $API->read(false);
					$ARRAY = $API->ParseResponse($READ);
					$msg .= "Os dados foram inseridos na Graphing List do servidor ".$ipmk2.".";
				}
			}
		} 
		// caso nao exista dados da consulta 
		else
			echo "erro: N&atilde;o existem IPs dessa rede sendo usado por clientes. Nada foi alterado.";

		// obs.: ip `NAO USADO` no equip. do cliente -- nao existe a necessidade, pois se nao existe o equip. nao e possivel gerar grafico.

		// disconecta
		$API->disconnect();

	} else
		$msg .= "<pre>Erro op=set: Erro na conex&atilde;o com API Mikrotik.</pre>";
}

/*******************************************************************************
 * GRAPH : faz a exibicao dos graficos
 * acesso a api mikrotik
 ******************************************************************************/
elseif ($op == "graph") {

	// protocolo e porta para acesso ao grafico
	if (!empty($APIPortHttps) and !empty($APIPortHttp)) {
		$protocol = "https";
		$port = $APIPortHttps;
	} elseif (!empty($APIPortHttps) and empty($APIPortHttp)) {
		$protocol = "https";
		$port = $APIPortHttps;
	} elseif (empty($APIPortHttps) and !empty($APIPortHttp)) {
		$protocol = "http";
		$port = $APIPortHttp;
	} elseif (empty($APIPortHttps) and empty($APIPortHttp)) {
		echo "<pre>nenhum servico habilitado para esse servidor. Nao foi possivel gerar o grafico.</pre>";
		$port = ""; // vai causar erro no acesso da api
		exit;
	} else {
		echo "default";
		$port = ""; // vai causar erro no acesso da api
	}

	// ip 
	$ipzerofilled = routeros_api::zerofillIp($ip);

	// periodo 
	$graph_freq_1 = "daily.gif";
	$graph_freq_2 = "weekly.gif";
	$graph_freq_3 = "monthly.gif";
	$graph_freq_4 = "yearly.gif";

	for ($x=1; $x<5; $x++) {
		
		// precisa rever isso aqui em breve
		//${'url' . $x} = $x;
		${'url' . $x} = $protocol . "://" . $ipmk . ":" . $port . "/graphs/queue/" . $ipzerofilled . "/" . ${'graph_freq_' . $x};
		echo "<div><img height='50%' width='100%' src='" . ${'url' . $x} . "' /></div>";
	}
}

/*******************************************************************************
 * DEFAULT : null
 ******************************************************************************/
else 
	echo "null";



/*******************************************************************************
 * display
 ******************************************************************************/
echo $msg;