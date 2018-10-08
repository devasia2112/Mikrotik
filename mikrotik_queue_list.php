<?php
/**
 * Mikrotik Queue List
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
* para poder testar, ativar "TEST" nas permissões de acesso para o 
* usuário da API -->>  `/system/user`.
*
* Exemplo de GET request via url: 
* `http://localhost/mikrotik_queue_list.php?oprt=OP&serveridnew=NEWID&serverid=OLDID&name=NAME&ip=NEWIP&ipold=OLDIP&disabled=VALUE`
*
*/



/*******************************************************************************
 * Chamada da conexao. Esses dados precisam vir do cadastro do servidor
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
 ******************************************************************************/
$op              = $_GET["oprt"];
$serveridnew     = $_GET["serveridnew"]; // ID do NOVO servidor escolhido no dropbox
$serverid        = $_GET["serverid"];    // ID do servidor ANTIGO que ja vem carregado no dropbox
$name            = $_GET['name'];
$ip              = $_GET["ip"];          // novo ip em alguns casos
$ipold           = $_GET["ipold"];       // ip antigo - usado com a troca de ip de um kit(equip.)
$disabled        = $_GET['disabled'];
$ipforsetip      = $ip;   // a var $ip esta sendo substituido com o ip encontrado na rede.. logo abaixo, funciona nos outros casos, mas nao para o metodo `setip`
$arr_ip_usado    = []; // recebe os ips de equip. usados no cliente
$arr_ip_naousado = []; // recebe os ips de equip. nao usados no cliente



/*******************************************************************************
 * todos os ips daquela rede
 ******************************************************************************/
$data1 = ["192.168.23.1","192.168.23.2","192.168.23.3",]; // query("your query")->fetchAll(); funciona no foreach abaixo OU passar um array com os ips.
foreach ($data1 as $value) {

	// separe os IPs que estao sendo usados no cliente e os que nao estao, 
	// controle da conexao {queues, etc..], usado com cliente [yes or no], IP.
	// Esse informacao precisa ser consultada em algum local, esse script original 
	// a informacao vinha de uma tabela no banco.
	// Cada IP no array em $data1 precisa ser consultado para checar pelos da condicao abaixo.
	$valz = [
		"control_conn"=>"queues",
		"usado_cliente"=>0,
		"ip"=>"193.177.56.3", // exemplo
	];

	// guardar no array apenas os ips que `estao em uso` no cliente e o controle da conexao como `queues`
	if ($valz['control_conn'] == 'queues' and $valz['usado_cliente'] == 1 and !empty($valz['ip'])) {
		$arr_ip_usado[] = $valz['ip'];
	}

	// guardar no array apenas os ips que `nao estao em uso` no cliente e o controle da conexao como `queues`
	elseif ($valz['control_conn'] == 'queues' and $valz['usado_cliente'] == 0 and !empty($valz['ip'])) {
		$arr_ip_naousado[] = $valz['ip'];
	}

	// null
	else { echo ""; }
}



/*******************************************************************************
 * checar se o array esta vazio, caso nao esteja executa o bloco
 ******************************************************************************/
$arr_ip_usado_checked = array_filter($arr_ip_usado);
if (!empty($arr_ip_usado_checked)) {

	$n = 0;
	foreach ($arr_ip_usado as $value_usado) {
		$ipzerofilled = routeros_api::zerofillIp($value_usado);
		$data3[$n]['0']['ipzerofilled'] = $ipzerofilled;
		$n +=1;
	}
	//print "usado - query equip.contratos.planos<br>"; print_r($data3);
	foreach ($data3 as $val3) {
		if (!empty($val3['0']['ipzerofilled'])) {
			$datasanit_usado[] = $val3;
		}
	}
}



/*******************************************************************************
 * checa se o array esta vazio, caso nao esteja executa o bloco
 ******************************************************************************/
$arr_ip_naousado_checked = array_filter($arr_ip_naousado);
if (!empty($arr_ip_naousado_checked)) {

	$m = 0;
	foreach ($arr_ip_naousado as $value_naousado) {
		$ipzerofilled = routeros_api::zerofillIp($value_naousado);
		$data4[$m]['0']['ipzerofilled'] = $ipzerofilled;
		$m +=1;	
	}
	foreach ($data4 as $val4) {
		if (!empty($val4['0']['ipzerofilled'])) {
			$datasanit_naousado[] = $val4;
		}
	}
}



/*******************************************************************************
 * usado para troca de ip (individual).
 ******************************************************************************/
if ($op == "setip") {

	// aqui precisamos do IP do equipamento do cliente, 
	// na minha estrutura de dados eu consulta esse valor 
	// via IP e IP do servidor (precisa ser o IP do servidor correto, pois esses IPs repetem).
	$ipzerofilled  = routeros_api::zerofillIp($ipforsetip);
	$usado_cliente = 1; // pode ser 1 ou 0 ou yes ou no
}



/*******************************************************************************
 * consulta os dados do servidor atual para acesso.
 ******************************************************************************/
$ipmk = "192.168.23.24";
$user = "user";
$Pass = "passwd";
$APIPort = 9090; // porta usada para acessar a API Mikrotik



/*******************************************************************************
 * consulta os dados do servidor selecionado para acesso.
 ******************************************************************************/
$ipmk2 = "192.168.23.24";
$user2 = "user";
$Pass2 = "passwd";
$APIPort2 = 9090; // porta usada para acessar a API Mikrotik
/* guardar na sessao dados da conexao */
session_start();
$_SESSION['MK_IP2']   = $ipmk2;
$_SESSION['MK_USER2'] = $user2;
$_SESSION['MK_AUTH2'] = $Pass2;
$_SESSION['MK_PORT2'] = $APIPort2;



/*******************************************************************************
 * DELETE : faz a remocao dos ips do gateway apresentado
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

				$API->write("/queue/simple/getall",false);
				$API->write('?name='.$valueusado['0']['ipzerofilled'],true);
				$READ = $API->read(false);
				$ARRAY = $API->ParseResponse($READ);

				print "<pre> REMOVE USADO: "; print_r($ARRAY); print "</pre>";

				if (count($ARRAY)>0) {
					
					$API->write("/queue/simple/remove",false); // Na linha xx listaremos as ether
					$API->write("=.id=".$ARRAY[0]['.id'],true);	// ID sequencial do MK da interface
					$READ = $API->read(false);
					$ARRAY = $API->ParseResponse($READ);
					$msg .= "<pre>O ip ". $valueusado['0']['ip'] ." foi removido da queue list do servidor ".$ipmk.".</pre>";

			  	} else {

					$msg .= "<pre>Houve uma falha e o ip ". $valueusado['0']['ip'] ." não foi removido da queue list do servidor ".$ipmk.". <a href='' onclick='location.reload()'>&#x27f3;</a></pre>";
			  	}
			}
		}

		// remover ip `NAO USADO` no equip. do cliente
		if (isset($datasanit_naousado)) {

			foreach ($datasanit_naousado as $valuenaousado) {

				$API->write("/queue/simple/getall",false);
				$API->write('?name='.$valuenaousado['0']['ipzerofilled'],true);
				$READ = $API->read(false);
				$ARRAY = $API->ParseResponse($READ);

				print "<pre> REMOVE NAO USADO: "; print_r($ARRAY); print "</pre>";

				if (count($ARRAY)>0) {
					
					$API->write("/queue/simple/remove",false);
					$API->write("=.id=".$ARRAY[0]['.id'],true);
					$READ = $API->read(false);
					$ARRAY = $API->ParseResponse($READ);
					$msg .= "<pre>O ip ". $valuenaousado['0']['ip'] ." foi removido da queue list do servidor ".$ipmk.".</pre>";

			  	} else {

					$msg .= "<pre>Houve uma falha e o ip ". $valuenaousado['0']['ip'] ." não foi removido da queue list do servidor ".$ipmk.". <a href='' onclick='location.reload()'>&#x27f3;</a></pre>";
			  	}
			}
		}

		// disconecta da api
	  	$API->disconnect();

	} else {

		$msg .= "<pre>Erro na conex&atilde;o com API Mikrotik.</pre>";
	}
}



/*******************************************************************************
 * DELETE : faz a remocao apenas do ip apresentado.
 ******************************************************************************/
elseif ($op == "removeip") {

	$ipzerofilled = routeros_api::zerofillIp($ipold);

	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk, $user, $Pass, $APIPort)) {

		// remover ip do equip. do cliente na queue list
		$API->write("/queue/simple/getall",false);
		$API->write('?name='.$ipzerofilled,true);
		$READ = $API->read(false);
		$ARRAY = $API->ParseResponse($READ);

		if (count($ARRAY)>0) {
			
			$API->write("/queue/simple/remove",false);
			$API->write("=.id=".$ARRAY[0]['.id'],true);
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			$msg .= "<pre>O ip ". $ip ." foi removido da queue list do servidor ".$ipmk.".</pre>";

	  	} else {

			$msg .= "<pre>Houve uma falha e o ip ". $ip ." não foi removido da queue list do servidor ".$ipmk.". <a href='' onclick='location.reload()'>&#x27f3;</a></pre>";
	  	}

		// disconecta da api
	  	$API->disconnect();

	} else {

		$msg .= "<pre>Erro na conex&atilde;o com API Mikrotik.</pre>";
	}
}



/*******************************************************************************
 * DELETE : faz a remocao do ip apresentado pelo target ou seja pelo proprio ip
 * apresentado via parametro.
 ******************************************************************************/
elseif ($op == "removeipbytarget") {

	$ipzerofilled = routeros_api::zerofillIp($ipforsetip);

	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk, $user, $Pass, $APIPort)) {

		// remover ip do equip. do cliente na queue list
		$API->write("/queue/simple/getall",false);
		$API->write('?name='.$ipzerofilled,true);
		$READ = $API->read(false);
		$ARRAY = $API->ParseResponse($READ);

		if (count($ARRAY)>0) {
			
			$API->write("/queue/simple/remove",false);
			$API->write("=.id=".$ARRAY[0]['.id'],true);
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			echo $msg .= "<pre>O ip ". $ip ." foi removido da queue list do servidor ".$ipmk.".</pre>";

	  	} else {

			echo $msg .= "<pre>Houve uma falha e o ip ". $ip ." não foi removido da queue list do servidor ".$ipmk.". <a href='' onclick='location.reload()'>&#x27f3;</a></pre>";
	  	}

		// disconecta da api
	  	$API->disconnect();

	} else {

		$msg .= "<pre>Erro na conex&atilde;o com API Mikrotik.</pre>";
	}
}



/*******************************************************************************
 * SET : faz a insert/update dos ips do gateway apresentado
 * acesso a api mikrotik
 ******************************************************************************/
elseif ($op == "set") {

	/* -= use for debug only =-
	if (isset($datasanit_usado)) { foreach ($datasanit_usado as $valueusado) { print "<pre>1"; print_r($valueusado); print "</pre>"; } }
	if (isset($datasanit_naousado)) { foreach ($datasanit_naousado as $valuenaousado) { print "<pre>2"; print_r($valuenaousado); print "</pre>"; } }
	*/

	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk2, $user2, $Pass2, $APIPort2)) {

		// rodar o foreach para cada ip aqui dentro
		// ip `USADO` no equip. do cliente
		if (isset($datasanit_usado)) {

			foreach ($datasanit_usado as $valueusado) {

				$API->write("/queue/simple/getall",false);
				$API->write('?name='.$valueusado['0']['ipzerofilled'],true);
				$READ = $API->read(false);
				$ARRAY = $API->ParseResponse($READ);

				if (count($ARRAY)>0) {

					$API->write("/queue/simple/set",false);
					$API->write("=.id=".$ARRAY[0]['.id'],false);
					$API->write('=name='.$valueusado['0']['ipzerofilled'],false);
					$API->write('=target='.$valueusado['0']['ip'],false);
					$API->write('=max-limit='.$valueusado['0']['max_limit'],false);
					$API->write('=burst-threshold='.$valueusado['0']['burst_threshold'],false);
					$API->write('=burst-limit='.$valueusado['0']['burst_limit'],false);
					$API->write('=burst-time='.$valueusado['0']['burst_time'],false);
					$API->write('=priority='.$valueusado['0']['priority'],false);
					$API->write('=disabled='.$disabled,false);		//true/false
					$API->write('=comment='.$valueusado['0']['idcontrato'],true);			//Edita o campo Comentario
					$READ = $API->read(false);
					$ARRAY = $API->ParseResponse($READ);
					$msg .= "Os dados foram atualizados na Queue List de ".$ipmk2.".";

				} else {

					$API->write("/queue/simple/add",false);
					$API->write('=name='.$valueusado['0']['ipzerofilled'],false);
					$API->write('=target='.$valueusado['0']['ip'],false);
					$API->write('=max-limit='.$valueusado['0']['max_limit'],false);
					$API->write('=burst-threshold='.$valueusado['0']['burst_threshold'],false);
					$API->write('=burst-limit='.$valueusado['0']['burst_limit'],false);
					$API->write('=burst-time='.$valueusado['0']['burst_time'],false);
					$API->write('=priority='.$valueusado['0']['priority'],false);
					$API->write('=disabled='.$disabled,false);
					$API->write('=comment='.$valueusado['0']['idcontrato'],true);
					$READ = $API->read(false);
					$ARRAY = $API->ParseResponse($READ);
					$msg .= "Os dados foram inseridos na Queue List de ".$ipmk2.".";
				}
			}
		}

		// ip `NAO USADO` no equip. do cliente
		if (isset($datasanit_naousado)) {

			foreach ($datasanit_naousado as $valuenaousado) {

				$API->write("/queue/simple/getall",false);
				$API->write('?name='.$valuenaousado['0']['ipzerofilled'],true);
				$READ = $API->read(false);
				$ARRAY = $API->ParseResponse($READ);

				if (count($ARRAY)>0) {

					$API->write("/queue/simple/set",false);
					$API->write("=.id=".$ARRAY[0]['.id'],false);
					$API->write('=name='.$valuenaousado['0']['ipzerofilled'],false);
					$API->write('=target='.$valuenaousado['0']['ip'],false);
					$API->write('=max-limit='.'70K/70K',false);
					$API->write('=burst-threshold='.'0K/0K',false);
					$API->write('=burst-limit='.'0K/0K',false);
					$API->write('=burst-time='.'0s/0s',false);
					$API->write('=priority='.'8/8',false);
					$API->write('=disabled='.$disabled,false);
					$API->write('=comment='.'LIVRE',true);
					$READ = $API->read(false);
					$ARRAY = $API->ParseResponse($READ);
					$msg .= "Os dados foram atualizados na Queue List de ".$ipmk2.".";

				} else {

					$API->write("/queue/simple/add",false);
					$API->write('=name='.$valuenaousado['0']['ipzerofilled'],false);
					$API->write('=target='.$valuenaousado['0']['ip'],false);
					$API->write('=max-limit='.'70K/70K',false);
					$API->write('=burst-threshold='.'0K/0K',false);
					$API->write('=burst-limit='.'0K/0K',false);
					$API->write('=burst-time='.'0s/0s',false);
					$API->write('=priority='.'8/8',false);
					$API->write('=disabled='.$disabled,false);
					$API->write('=comment='.'LIVRE',true);
					$READ = $API->read(false);
					$ARRAY = $API->ParseResponse($READ);
					$msg .= "Os dados foram inseridos na Queue List de ".$ipmk2.".";
				}
			}
		}

		// disconecta
		$API->disconnect();

	} else {

		$msg .= "<pre>Erro na conex&atilde;o com API Mikrotik.</pre>";
	}
}



/*******************************************************************************
 * SET : faz a insert/update dos ips apresentado
 ******************************************************************************/
elseif ($op == "setip") {

	$ipzerofilled = routeros_api::zerofillIp($ipforsetip);
	echo "setip usado cliente: " . $usado_cliente . " ip zero-filled: " . $ipzerofilled . "<br>";

	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk2, $user2, $Pass2, $APIPort2)) {

		// ip `USADO` no equip. do cliente
		if ($usado_cliente == "1") { 

			$API->write("/queue/simple/getall",false);
			$API->write('?name='.$ipzerofilled,true);
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);

			if (count($ARRAY) > 0) {

				$API->write("/queue/simple/set",false);
				$API->write("=.id=".$ARRAY[0]['.id'],false);
				$API->write('=name='.$ipzerofilled,false);
				$API->write('=target='.$data_setip['0']['ip'],false);
				$API->write('=max-limit='.$data_setip['0']['max_limit'],false);
				$API->write('=burst-threshold='.$data_setip['0']['burst_threshold'],false);
				$API->write('=burst-limit='.$data_setip['0']['burst_limit'],false);
				$API->write('=burst-time='.$data_setip['0']['burst_time'],false);
				$API->write('=priority='.$data_setip['0']['priority'],false);
				$API->write('=disabled='.$disabled,false);		//true/false
				$API->write('=comment='.$data_setip['0']['cod_equip'],true);
				$READ = $API->read(false);
				$ARRAY = $API->ParseResponse($READ);
				$msg .= "Os dados foram atualizados na Queue List de ".$ipmk.".";

			} else {

				$API->write("/queue/simple/add",false);
				$API->write('=name='.$ipzerofilled,false);
				$API->write('=target='.$data_setip['0']['ip'],false);
				$API->write('=max-limit='.$data_setip['0']['max_limit'],false);
				$API->write('=burst-threshold='.$data_setip['0']['burst_threshold'],false);
				$API->write('=burst-limit='.$data_setip['0']['burst_limit'],false);
				$API->write('=burst-time='.$data_setip['0']['burst_time'],false);
				$API->write('=priority='.$data_setip['0']['priority'],false);
				$API->write('=disabled='.$disabled,false);
				$API->write('=comment='.$data_setip['0']['cod_equip'],true);
				$READ = $API->read(false);
				$ARRAY = $API->ParseResponse($READ);
				$msg .= "Os dados foram inseridos na Queue List de ".$ipmk.".";
			}
		}

		// ip `NAO USADO` no equip. do cliente
		if ($usado_cliente == "0") {

			$API->write("/queue/simple/getall",false);
			$API->write('?name='.$ipzerofilled,true);
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);

			if (count($ARRAY) > 0) {

				$API->write("/queue/simple/set",false);
				$API->write("=.id=".$ARRAY[0]['.id'],false);
				$API->write('=name='.$ipzerofilled,false);
				$API->write('=target='.$data_setip['0']['ip'],false);
				$API->write('=max-limit='.'70K/70K',false);
				$API->write('=burst-threshold='.'0K/0K',false);
				$API->write('=burst-limit='.'0K/0K',false);
				$API->write('=burst-time='.'0s/0s',false);
				$API->write('=priority='.'8/8',false);
				$API->write('=disabled='.$disabled,false);
				$API->write('=comment='.'LIVRE',true);
				$READ = $API->read(false);
				$ARRAY = $API->ParseResponse($READ);
				$msg .= "Os dados foram atualizados na Queue List de ".$ipmk.".";

			} else {

				$API->write("/queue/simple/add",false);
				$API->write('=name='.$ipzerofilled,false);
				$API->write('=target='.$data_setip['0']['ip'],false);
				$API->write('=max-limit='.'70K/70K',false);
				$API->write('=burst-threshold='.'0K/0K',false);
				$API->write('=burst-limit='.'0K/0K',false);
				$API->write('=burst-time='.'0s/0s',false);
				$API->write('=priority='.'8/8',false);
				$API->write('=disabled='.$disabled,false);
				$API->write('=comment='.'LIVRE',true);
				$READ = $API->read(false);
				$ARRAY = $API->ParseResponse($READ);
				$msg .= "Os dados foram inseridos na Queue List de ".$ipmk.".";
			}
		}

		// disconecta
		$API->disconnect();

	} else {

		$msg .= "<pre>Erro na conex&atilde;o com API Mikrotik.</pre>";
	}
}



/*******************************************************************************
 * DEFAULT : null
 ******************************************************************************/
else { echo "default null"; }



/*******************************************************************************
 * display
 ******************************************************************************/
echo $msg;
