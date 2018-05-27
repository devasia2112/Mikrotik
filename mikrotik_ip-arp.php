<?php
/**
 * Mikrotik IP ARP
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
* `http://localhost/mikrotik_ip-arp.php?oprt=set&serverid=4&serveridnew=5&mk_mac=00:0C:42:69:E7:69&mk_ip=192.168.100.201&mk_published=true&mk_interface=NAMEINTERFACE&disabled=false&comment=test`
*
*/

$op           = $_GET["oprt"];         // operacoes sao: delete, deletebyip, set e setmacip
$serverid     = $_GET["serverid"];     //ID do servidor extraido do ip do cliente
$serveridnew  = $_GET['serveridnew'];  //ID do novo servidor escolhido
$mk_mac       = $_GET['mk_mac'];
$mk_ip        = $_GET['mk_ip'];
$mk_published = $_GET['mk_published']; // true ou false
$mk_interface = $_GET['mk_interface']; // nome da interface
$disabled     = $_GET['disabled'];     // true ou false
$comment      = $_GET['comment'];



############################################## SERVIDOR ATUAL ##################
/*******************************************************************************
 * Consulta os dados do servico do servidor - precisamos pegar o port do servico
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[serverid]
 ******************************************************************************/
$dss = $database->select( 'servidor_services', '*', array( 'server_id[=]' => $serverid ));
foreach($dss as $ds) {
	// recebe a porta da API
	if ($ds['service_name'] == "api" and $ds['service_status'] == 1)
		$APIPort = $ds['service_port']; // porta usada para acessar a API Mikrotik
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
}

/*******************************************************************************
 *consulta os dados do servidor para acesso
 *
 ******************************************************************************/
$ds22   = $database->select( 'servidor', '*', array( 'id[=]' => $serveridnew ));
$ipmk2 = $ds22['0']['ip_servidor'];
$user2 = $ds22['0']['usuario'];
$pwdd2 = $ds22['0']['autenticacao'];
$Pass2 = trim($pwdd2);

// guardar na sessao dados da conexao
session_start();
$_SESSION['MK_IP2'] = $ipmk2;
$_SESSION['MK_USER2'] = $user2;
$_SESSION['MK_AUTH2'] = $Pass2;
$_SESSION['MK_PORT2'] = $APIPort2;
################################################################################



/**********************************************************************************
 * DELETE : faz a remocao dos ips do gateway apresentado - remocao feito via MAC
 **********************************************************************************/
if ($op == "delete") {

	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk, $user, $Pass, $APIPort)) {

		$API->write("/ip/arp/getall",false);
		$API->write('?mac-address='.$mk_mac,true);
		$READ = $API->read(false);
		$ARRAY = $API->ParseResponse($READ);

		if (count($ARRAY)>0) {
			
			$API->write("/ip/arp/remove",false);  	// Na linha xx listamos as ether
			$API->write("=.id=".$ARRAY[0]['.id'],true);	// ID sequencial do MK da interface
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			$msg .= "<pre> A associa&ccedil;&atilde;o MACxIP foi removida na API com sucesso. </pre>";

	  	} else {

			//$READ = $API->read(false);             nao usar esse metodo aqui novamente pois vai travar.
			//$ARRAY = $API->ParseResponse($READ);   nao usar esse metodo aqui novamente pois vai travar.
			$msg .= "<pre>O mac n&atilde;o foi encontrado na API. Nada foi removido. <a href='' onclick='location.reload()'>&#x27f3;</a></pre>";
	  }
	  $API->disconnect();

	} else
		$msg .= "<pre>Erro na conex&atilde;o com API Mikrotik.</pre>";
}



/**********************************************************************************
 * DELETE : faz a remocao dos ips do gateway apresentado - remocao feito via IP
 **********************************************************************************/
elseif ($op == "deletebyip") {

	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk, $user, $Pass, $APIPort)) {

		$API->write("/ip/arp/getall",false);
		$API->write('?address='.$mk_ip,true);
		$READ = $API->read(false);
		$ARRAY = $API->ParseResponse($READ);

		if (count($ARRAY)>0) {
			
			$API->write("/ip/arp/remove",false);  	// Na linha xx listamos as ether
			$API->write("=.id=".$ARRAY[0]['.id'],true);	// ID sequencial do MK da interface
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			$msg .= "<pre> A associa&ccedil;&atilde;o MACxIP foi removida na API com sucesso. </pre>";

	  	} else
			$msg .= "<pre>O IP n&atilde;o foi encontrado na API. Nada foi removido. <a href='' onclick='location.reload()'>&#x27f3;</a></pre>";

	  $API->disconnect();

	} else
		$msg .= "<pre>Erro na conex&atilde;o com API Mikrotik.</pre>";
}



/*******************************************************************************
 * SET : faz a insert/update dos ips do gateway apresentado
 * Obs.: Caso ocorra algum problema com SET entao alterar a consulta (getall)
 *       ao innvez de consultar pelo IP, entao consultar por MAC ADDRESS
 ******************************************************************************/
elseif ($op == "set") {

	/* nao validar aqui, pois o metodo esta sendo usado em mais de um local, e alguns locais nao podem ser validado!
	if ($mk_mac == "" && $mk_interface != "" && $mk_ip != "") {
		die("erro: os dados n&atilde; foram validados corretamente.");
	} */
	
	// checa se dados do novo servidor existe, caso positivo entao precisa gravar os dados nesse novo servidor.
	if (!empty($serveridnew)) {
		$ipmk = $ipmk2;
		$user = $user2;
		$Pass = $Pass2;
		$APIPort = $APIPort2;
	} else {
		$ipmk = $ipmk;
		$user = $user;
		$Pass = $Pass;
		$APIPort = $APIPort;
	}

	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk, $user, $Pass, $APIPort)) {
		
		$API->write("/ip/arp/getall",false);
		$API->write('?address='.$mk_ip,true);
		$READ = $API->read(false);
		$ARRAY = $API->ParseResponse($READ);
		
		if (count($ARRAY)>0) {
			
			// apenas alteramos
			$API->write("/ip/arp/set",false);
			$API->write("=.id=".$ARRAY[0]['.id'],false);
			$API->write('=mac-address='.$mk_mac,false);	    // Edita o campo MAC ($macAddress)
			$API->write('=interface='.$mk_interface,false);	// Nome da interface que esse IP atuará
			$API->write('=published='.$mk_published,false);	// Insere yes/no. Padrão="no". Não o utilizaremos agora.
			$API->write('=disabled='.$disabled,false);		// true/false
			$API->write('=comment='.$comment,true);			// Edita o campo Comentario
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			$msg .= "O MAC Address `".$mk_mac."` foi vinculado ao IP `".$mk_ip."` com sucesso na ARP List do servidor `".$ipmk."`.";
			
			// update na tabela mikroti_ip-arp para atualizar historico
			$database->update( "mikrotik_ip-arp", 
				array( 	
					"mac" => $mk_mac, 
		    		"interface" => $mk_interface, 
		    		"server_id" => $serverid, 
		    		"published" => $mk_published, 
		    		"disabled" => $disabled, 
		    		"comment" => $comment
				), 
				array( "ip[=]" => $mk_ip )
			);
			
			
		} else {
			

			$API->write("/ip/arp/add",false);
			$API->write('=address='.$mk_ip,false);
			$API->write('=mac-address='.$mk_mac,false);	
			$API->write('=interface='.$mk_interface,false);
			$API->write('=published='.$mk_published,false);
			$API->write('=disabled='.$disabled,false);
			$API->write('=comment='.$comment,true);
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			$msg .= "O IP ".$mk_ip." foi vinculado ao MAC ".$mk_mac." na ARP List do servidor `".$ipmk."`.";
			
			
			// insert na tabela mikrotik_ip-arp para registrar historico.
			$lastid = $database->insert("mikrotik_ip-arp", 
				array(
					"id" => NULL, 
					"ip" => $mk_ip, 
					"mac" => $mk_mac,
		    		"interface" => $mk_interface, 
		    		"server_id" => $serverid, 
		    		"published" => $mk_published, 
		    		"disabled" => $disabled, 
		    		"comment" => $comment
				)
			);
			
		}
		$API->disconnect();
		
	} else
		$msg .= "<pre>Erro na conex&atilde;o com API Mikrotik.</pre>";
}



/*******************************************************************************
 * SET NEW MAC ADDRESS : usado para atualizar ou cadastrar novo MACxIP
 * A consulta na api deve ser feita pelo mac aqui.
 ******************************************************************************/
elseif ($op == "setmacip") {

	if ($mk_mac == "" && $mk_interface != "" && $mk_ip != "")
		die("erro: os dados n&atilde; foram validados corretamente.");

	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk, $user, $Pass, $APIPort)) {
		
		$API->write("/ip/arp/getall",false);
		$API->write('?mac-address='.$mk_mac,true);
		$READ = $API->read(false);
		$ARRAY = $API->ParseResponse($READ);
		
		if (count($ARRAY)>0) {
			
			// desabilita o registro na api
			$dis = "true";
			$comm = "esse registro foi desabilitado, pois uma nova entrada com o mesmo IPxMAC foi registrada.";
			$API->write("/ip/arp/set",false);
			$API->write("=.id=".$ARRAY[0]['.id'],false);
			//$API->write('=mac-address='.$mk_mac,false);	    // Edita o campo MAC ($macAddress)
			//$API->write('=interface='.$mk_interface,false);	// Nome da interface que esse IP atuará
			//$API->write('=published='.$pub,false);	        // Insere yes/no. Padrão="no". Não o utilizaremos agora. 
			$API->write('=disabled='.$dis,false);		        // desabilitar esse registro na api
			$API->write('=comment='.$comm,true);			    // Comentario qualquer
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			$msg .= "O MAC Address `".$mk_mac."` foi vinculado ao IP `".$mk_ip."` com sucesso na ARP List do servidor `".$ipmk."`.";
			
			// cria um novo registro na api
			$API->write("/ip/arp/add",false);
			$API->write('=address='.$mk_ip,false);
			$API->write('=mac-address='.$mk_mac,false);	
			$API->write('=interface='.$ARRAY[0]['.interface'],false);   // aqui nao temos de onde receber esse dado
			$API->write('=published='.$mk_published,false);
			$API->write('=disabled='.$disabled,false);
			$API->write('=comment='.$comment,true);
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			$msg .= "O IP ".$mk_ip." foi vinculado ao MAC ".$mk_mac." na ARP List do servidor `".$ipmk."`.";			
			
			// update na tabela mikroti_ip-arp - atualizacao do historico
			$database->update("mikrotik_ip-arp",
				array(
					//"mac" => $mk_mac,
		    		//"interface" => $mk_interface,
		    		//"server_id" => $serverid,
		    		"published" => $mk_published,
		    		"disabled" => $disabled,
		    		"comment" => $comment
				),
				array("mac[=]" => $mk_mac)
			);
			
		} else {
			
			$API->write("/ip/arp/add",false);
			$API->write('=address='.$mk_ip,false);
			$API->write('=mac-address='.$mk_mac,false);	
			//$API->write('=interface='.$mk_interface,false);   // aqui nao temos de onde receber esse dado
			$API->write('=published='.$mk_published,false);
			$API->write('=disabled='.$disabled,false);
			$API->write('=comment='.$comment,true);
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			$msg .= "O IP ".$mk_ip." foi vinculado ao MAC ".$mk_mac." na ARP List do servidor `".$ipmk."`.";
			
			// insert na tabela mikrotik_ip-arp - registro de historico.
			$lastid = $database->insert("mikrotik_ip-arp", 
				array(
					"id" => NULL, 
					"ip" => $mk_ip, 
					"mac" => $mk_mac,
		    		//"interface" => $mk_interface, 
		    		"server_id" => $serverid, 
		    		"published" => $mk_published, 
		    		"disabled" => $disabled, 
		    		"comment" => $comment
				)
			);
			
		}
		$API->disconnect();
		
	} else 
		$msg .= "<pre>Erro na conex&atilde;o com API Mikrotik.</pre>";
}


/*******************************************************************************
 * DEFAULT : null
 ******************************************************************************/
else { echo "null"; }



/*******************************************************************************
 * display
 ******************************************************************************/
echo $msg;