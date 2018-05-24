<?php
/**
 * Mikrotik Address List
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
* Exemplo de GET request via url: 
* `http://localhost/mikrotik_address_list.php?oprt=set&serveridnew=5&serverid=4&exitnode=&exitnodelog=true&gateway=&ip=&subnet=30&network=&interface=INTERFACETEST01&broadcast=&disabled=false&comment=test`
*/
$op          = $_GET["oprt"];           // tipo operacao: {delete, set, nat_set_rule, nat_delete_rule}
$serveridnew = $_GET["serveridnew"];    // ID do NOVO servidor escolhido no dropbox
$serverid    = $_GET["serverid"];       // ID do servidor ANTIGO que ja vem carregado no dropbox
$exitnode    = $_GET["exitnode"];       // IP do exit node
$exitnodelog = $_GET["exitnodelog"];    // true ou false
$gateway     = $_GET["gateway"];        // IP do gateway
$ip          = $_GET["ip"];
$subnet      = $_GET["subnet"];         // subnet ex.: 30 
// used with set/add
$network     = $_GET['network'];        // IP da rede
$interface   = $_GET['interface'];      // Nome da interface que esse IP recebe
$broadcast   = $_GET['broadcast'];      // IP broadcast
$disabled    = $_GET['disabled'];       // true ou false
$comment     = $_GET['comment'];        // qualquer comentario relevante



############################################## SERVIDOR ATUAL ###########################
/*******************************************************************************
 * Consulta os dados do servico do servidor - precisamos pegar o port do servico
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[serverid]
 ******************************************************************************/
$dss = $database->select( 'servidor_services', '*', array( 'server_id[=]' => $serverid ));
foreach($dss as $ds) {
	if ($ds['service_name'] == "api" and $ds['service_status'] == 1)
		$APIPort = $ds['service_port']; // porta usada para acessar a API Mikrotik
}

/*******************************************************************************
 *consulta os dados do servidor para acesso
 *
 ******************************************************************************/
$ds   = $database->select( 'servidor', '*', array( 'id[=]' => $serverid ));
$ipmk = $ds['0']['ip_servidor'];
$user = $ds['0']['usuario'];
$pwdd = $ds['0']['autenticacao'];
$Pass = trim($pwdd);  // remover espacos
#########################################################################################



############################################## SERVIDOR SELECIONADO #####################
/*******************************************************************************
 * Consulta os dados do servico do servidor - precisamos pegar o port do servico
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[serverid]
 ******************************************************************************/
$dss2 = $database->select( 'servidor_services', '*', array( 'server_id[=]' => $serveridnew ));
foreach($dss2 as $ds2) {
	if ($ds2['service_name'] == "api" and $ds2['service_status'] == 1)
		$APIPort2 = $ds2['service_port']; // porta usada para acessar a API Mikrotik
}

/*******************************************************************************
 *consulta os dados do servidor para acesso
 *
 ******************************************************************************/
$ds22  = $database->select( 'servidor', '*', array( 'id[=]' => $serveridnew ));
$ipmk2 = $ds22['0']['ip_servidor'];
$user2 = $ds22['0']['usuario'];
$pwdd2 = $ds22['0']['autenticacao'];
$Pass2 = trim($pwdd2);  // remover espacos

// guardar na sessao dados da conexao
session_start();
$_SESSION['MK_IP2']   = $ipmk2;
$_SESSION['MK_USER2'] = $user2;
$_SESSION['MK_AUTH2'] = $Pass2;
$_SESSION['MK_PORT2'] = $APIPort2;
#########################################################################################


// address precisa conter a subnet para passar como parametro para api
$address = $gateway . "/" . $subnet;

// IP da REDE, acrescido de /netmask (/30, por exemplo)
$srcaddress = $network . "/" . $subnet;



/*******************************************************************************
 * DELETE : faz a remocao dos ips do gateway apresentado
 * acesso a api mikrotik
 ******************************************************************************/
if ($op == "delete") {

	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk, $user, $Pass, $APIPort)) {

		$API->write("/ip/address/getall",false);
		$API->write('?address='.$address,true);
		$READ = $API->read(false);
		$ARRAY = $API->ParseResponse($READ);

		if (count($ARRAY)>0) {
			
			$API->write("/ip/address/remove",false);  	// Na linha xx listamos as ether
			$API->write("=.id=".$ARRAY[0]['.id'],true);	// ID sequencial do MK da interface
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			$msg .= "<pre>Os ips do gateway ".$address." foram removidos do servidor ".$ipmk.". <a href='' onclick='location.reload()'>&#x27f3;</a></pre>";

	  	} else
			$msg .= "<pre>Houve uma falha e os ips do gateway ".$address." não foram removidos do servidor ".$ipmk.". <a href='' onclick='location.reload()'>&#x27f3;</a></pre>";

	  	$API->disconnect();

	} else
		$msg .= "<pre>Erro na conex&atilde;o com API Mikrotik.</pre>";
}

/*******************************************************************************
 * SET : faz a insert/update dos ips do gateway apresentado
 * acesso a api mikrotik
 ******************************************************************************/
elseif ($op == "set") {

	if (($address == "" or $address == "/") && $network == "" && $interface != "" && $broadcast != ""  )
		die("erro: os dados n&atilde; foram validados corretamente.");

	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk, $user, $Pass, $APIPort)) {

		$API->write("/ip/address/getall",false);
		$API->write('?address='.$address,true);
		$READ = $API->read(false);
		$ARRAY = $API->ParseResponse($READ);

		if (count($ARRAY)>0) {

			$API->write("/ip/address/set",false);
			$API->write("=.id=".$ARRAY[0]['.id'],false);
			$API->write('=interface='.$interface,false);	//Nome da interface que esse IP recebe
			$API->write('=disabled='.$disabled,false);		//true/false
			$API->write('=comment='.$comment,true);			//Edita o campo Comentario
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			$msg .= "A interface `".$interface."` com o gateway o `".$gateway."` foi editada com sucesso no Address List `".$ipmk."`.";

		} else {

			$API->write("/ip/address/add",false);
			$API->write('=address='.$address,false);
			$API->write('=network='.$network,false);
			$API->write('=interface='.$interface,false);
			$API->write('=broadcast='.$broadcast,false);
			$API->write('=disabled='.$disabled,false);
			$API->write('=comment='.$comment,true);
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			$msg .= "Foi atribuído na Address List de ".$ipmk.", os seguintes dados: GW=".$address.", Rede=".$network.", BCast=".$broadcast.",  Interface=".$interface." e com o Comentário=".$comment.". O status está inativo? ".$disabled.".";
		}
		$API->disconnect();

	} else
		$msg .= "<pre>Erro na conex&atilde;o com API Mikrotik.</pre>";
}

/*******************************************************************************
 * SET : faz o insert (add) ou update (set) da regra do firewall NAT 
 ******************************************************************************/
elseif ($op == "nat_set_rule") {

	$chain     = "srcnat";      // Tipo da regra: "srcnat" ou "dstnat"
	$action    = "masquerade";  // Se tratando de NAT, "mascarar".
	$log       = $exitnodelog;  // "true" MK faz log desse NAT? true/false. Padrão: "true"
	$logprefix = $network;      // $ip  -  Prefixo do nome para o LOG. Padrão: Campo IP do sistema ou algo da preferencia.

	// encerra script em caso de nada retornado
	if ($chain == "" && $srcaddress == "" && $interface == "" && $action == "")
		die("erro: os dados n&atilde; foram validados corretamente.");


	/* onChange() para servidor escohido no select da interface 
	-- NAO deve usar o servidor que vem carregado no select da interface */
	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk2, $user2, $Pass2, $APIPort2)) {

		$API->write("/ip/firewall/nat/getall",false);
		$API->write('?src-address='.$srcaddress,true);
		$READ = $API->read(false);
		$ARRAY = $API->ParseResponse($READ);

		if (count($ARRAY)>0) {

			$API->write("/ip/firewall/nat/set",false);
			$API->write("=.id=".$ARRAY[0]['.id'],false);       // ID sequencial do MK. Não é editável.
			$API->write('=out-interface='.$exitnode,false);
			$API->write('=action='.$action,false);
			$API->write('=log='.$log,false);
			$API->write('=log-prefix='.$logprefix,false);
			$API->write('=comment='.$comment,false);
			$API->write('=disabled='.$disabled,true);
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			$msg .= "Já existe uma entrada `".$chain."` para o IP `".$srcaddress."`, no entanto o seu Comentário foi registrado.";

		} else {

			$API->write("/ip/firewall/nat/add",false);
			$API->write('=chain='.$chain,false);
			$API->write('=src-address='.$srcaddress,false);
			$API->write('=out-interface='.$exitnode,false);
			$API->write('=action='.$action,false);
			$API->write('=log='.$log,false);
			$API->write('=log-prefix='.$logprefix,false);
			$API->write('=comment='.$comment,false);
			$API->write('=disabled='.$disabled,true);
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			echo "Foi adicionada a regra de NAT para a rede " . $srcaddress . " para o novo servidor " . $ipmk2;
		}
		$API->disconnect();

	} else
		$msg .= "<pre>Erro na conex&atilde;o com API Mikrotik.</pre>";
}

/*******************************************************************************
 * DELETE : faz o delete da regra do firewall NAT 
 ******************************************************************************/
elseif ($op == "nat_delete_rule") {

	$chain     = "srcnat";      // Tipo da regra: "srcnat" ou "dstnat"
	$action    = "masquerade";  // Se tratando de NAT, "mascarar".
	$log       = $exitnodelog;  // "true"  MK faz log desse NAT? true/false. Padrão: "true"
	$logprefix = $network;      // $ip  -  Prefixo do nome para o LOG. Padrão: Campo IP do sistema.


	// em caso de nada retornado, encerra execucao.
	if ($srcaddress == "" or empty($srcaddress))
		die("erro: os dados n&atilde; foram validados corretamente.");


	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ipmk, $user, $Pass, $APIPort)) {

		$API->write("/ip/firewall/nat/getall",false);
		$API->write('?src-address='.$srcaddress,true);
		$READ = $API->read(false);
		$ARRAY = $API->ParseResponse($READ);

		if (count($ARRAY)>0) {

			$API->write("/ip/firewall/nat/remove",false);
			$API->write("=.id=".$ARRAY[0]['.id'],true);
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
			echo "A regra de NAT para a rede ".$srcaddress." foi removido no servidor " . $ipmk;

        } else
	        $disconn = 1; // do nothing here

        $API->disconnect();
    }
}

/*******************************************************************************
 * DEFAULT : null
 ******************************************************************************/
else { echo "null"; }



/*******************************************************************************
 * display
 ******************************************************************************/
echo $msg;
