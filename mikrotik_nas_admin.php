<?php
/**
 * Mikrotik NAS Admin
 *
 * Este arquivo tem por objetivo Genrecias NAS na API.
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


/*******************************************************************************
 * Recebe o ID do servidor via metodo get 
 ******************************************************************************/
if (isset($_GET['server']))
	$id_servidor = $_GET['server'];
else
	$id_servidor = '';



/*******************************************************************************
 * Consulta os dados do servico do servidor
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
 ******************************************************************************/
$dss = $database->select( "servidor_services", "*", array( "server_id[=]" => $id_servidor ));
foreach($dss as $ds) {
	
	// recebe a porta da API
	if ($ds['service_name'] == "api" and $ds['service_status'] == 1)
		$APIPort  = $ds['service_port']; // porta usada para acessar a API Mikrotik

	if ($ds['service_name'] == "www-ssl" and $ds['service_status'] == 1)
		$GrapPort = $ds['service_port']; // porta usada para acessar o graficos

	if ($ds['service_name'] == "www" and $ds['service_status'] == 1)
		$GrapPort = $ds['service_port']; // porta usada para acessar o graficos
}
// condicao para mostrar a mensagem no template caso o servico nao esteja habilitado
if (empty($GrapPort) or $GrapPort == "")
	$GrapPortMsg = "Ative um dos servi&ccedil;os `www` ou `www-ssl` para que o gr&aacute;fico seja exibido.";
else
	$GrapPortMsg = "";


/*******************************************************************************
 * IMPORTANTE:
 * Chamada da conexao. Esses dados precisam vir do cadastro do servidor
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
 ******************************************************************************/
$ds       = $database->select( "servidor", "*", array( "id[=]" => $id_servidor ));
$ServerIP = $ds['0']['ip_servidor'];
$Username = $ds['0']['usuario'];
$pwdd     = $ds['0']['autenticacao'];
$Pass     = trim($pwdd);
$port     = $APIPort;                   // "8728"



/*******************************************************************************
 * Conecta na API do Mikrotik
 ******************************************************************************/
$API = new RouterosAPI();
$API->debug = false;
if ($API->connect($ServerIP , $Username , $Pass, $port)) {

	/*******************************************************************************
	 * BLOCO: INTERFACES -> BRIDGE PORTS
	 ******************************************************************************/
	$API->write("/interface/bridge/port/getall",true);   // true ou false
	$READ  = $API->read(false);
	$ARRAY = $API->ParseResponse($READ);

	// se houver mais que uma bridge ports
	if (count($ARRAY)>0) {
		
		for ($x=0; $x<count($ARRAY); $x++) {
			
			$interface = $ARRAY[$x]['interface'];
			//$default_name = $ARRAY[$x]['default-name'];
			//$speed = $ARRAY[$x]['speed'];
			$comment = $ARRAY[$x]['comment'];
			$running = $ARRAY[$x]['inactive'];
			$slave = $ARRAY[$x]['slave'];
			
			// flag - running 
			if ($running == "false") {
				$estado = "<pre>interface bridge port conectada</pre>";
				$flag_running = "R";
			} else {
				$estado = "<pre>interface bridge port desconectada</pre>";
				$flag_running = "I";
			}

			// wlan status - criar os links para bridge ports
			if ($ARRAY[$x]['disabled'] == "false") 
				$link_status  = "<font face='arial' style='font-size: 15pt'><a href='mikrotik_nas_admin_action.php?op=mk_bridge_ports_status&val=true&server=" . $_GET[server] . "&interface=" . $interface . "' style='color: #2fcc66; text-decoration: none' title='Ativo'>&#9679;</a></font>";
			else
				$link_status = "<font face='arial' style='font-size: 15pt'><a href='mikrotik_nas_admin_action.php?op=mk_bridge_ports_status&val=false&server=" . $_GET[server] . "&interface=" . $interface . "' style='color: #e74c3c; text-decoration: none' title='Inativo'>&#x25CF;</a></font>";

			// flag - slave
			if ($slave == true)
				$flag_slave = "S";
			else
				$flag_slave = "";

			// link para excluir a bridge
			$del_button_bridge_ports = '<a href="mikrotik_nas_admin_action.php?op=mk_del_bridge_ports&server=' . $_GET[server] . '&interface=' . $interface . '" style="text-decoration: none;">excluir</a>';

			// ether html tr usado no template
			$bridge_ports_tr .= '
				<tr>
					<td><font face="Arial" size="2" title="R: running -- I: inactive">' . $flag_running . '' . $flag_slave . '&nbsp;</font></td>
					<td><font face="Arial" size="2">' . $interface . '</font></td>
					<td align="left">
						' . $link_status . '
						<a href="mikrotik_bridge_ports_set.php?server=' . $_GET[server] . '&interface=' . $interface . '" class="fancybox fancybox.iframe">Editar Bridge Ports</a>
						' . $del_button_bridge_ports . '
						<a href="#" style="text-decoration: none;" title="Ler comentário -- ' . $comment . '">Ler comentário</a>
					</td>
				</tr>
			';
		}
		$bridge_ports_add_button = '<a href="mikrotik_bridge_ports_set.php?server=' . $_GET['server'] . '&action=add" class="fancybox fancybox.iframe" title="Adicionar Bridge Port">Adicionar Bridge Port</a>';

	} else
		echo "<pre>nenhuma interface bridge ports foi encontrada.</pre>";



	/*******************************************************************************
	 * BLOCO: INTERFACES -> BRIDGE
	 ******************************************************************************/
	$API->write("/interface/bridge/getall",true);   // true ou false
	$READ  = $API->read(false);
	$ARRAY = $API->ParseResponse($READ);

	// se houver mais que uma bridge
	if (count($ARRAY)>0) {
		
		for ($x=0; $x<count($ARRAY); $x++) {
			
			$name = $ARRAY[$x]['name'];
			//$default_name = $ARRAY[$x]['default-name'];
			//$speed = $ARRAY[$x]['speed'];
			$comment = $ARRAY[$x]['comment'];
			$running = $ARRAY[$x]['running'];
			$slave   = $ARRAY[$x]['slave'];
			
			// flag - running 
			if ($ARRAY[$x]['running'] == "true") {
				$estado = "<pre>interface conectada</pre>";
				$flag_running = "R";
			} else {
				$estado = "<pre>interface desconectada</pre>";
				$flag_running = "";
			}

			// wlan status - criar os links para bridge
			if ($ARRAY[$x]['disabled'] == "false") 
				$link_status  = "<font face='arial' style='font-size: 15pt'><a href='mikrotik_nas_admin_action.php?op=mk_bridge_status&val=true&server=" . $_GET[server] . "&name=" . $name . "' style='color: #2fcc66; text-decoration: none' title='Ativo'>&#9679;</a></font>";
			else
				$link_status = "<font face='arial' style='font-size: 15pt'><a href='mikrotik_nas_admin_action.php?op=mk_bridge_status&val=false&server=" . $_GET[server] . "&name=" . $name . "' style='color: #e74c3c; text-decoration: none' title='Inativo'>&#x25CF;</a></font>";

			// flag - slave
			if ($slave == true)
				$flag_slave = "S";
			else
				$flag_slave = "";

			// link para excluir a bridge
			$del_button_bridge = '<a href="mikrotik_nas_admin_action.php?op=mk_del_bridge&server=' . $_GET[server] . '&name=' . $name . '" style="text-decoration: none;" title="Excluir bridge">Excluir bridge</a>';

			// ether html tr usado no template
			$bridge_tr .= '
				<tr>
					<td><font face="Arial" size="2" title="R: running -- S: slave">' . $flag_running . '' . $flag_slave . '&nbsp;</font></td>
					<td><font face="Arial" size="2">' . $name . '</font></td>
					<td align="left">
						' . $link_status . '
						<a href="mikrotik_bridge_set.php?server=' . $_GET[server] . '&name=' . $name . '" class="fancybox fancybox.iframe" title="Editar Bridge">Editar Bridge</a>
						' . $del_button_bridge . '
						<a href="#" style="text-decoration: none;" title="Ler comentário -- ' . $comment . '">Ler comentário</a>
					</td>
				</tr>
			';
		}
		$bridge_add_button = '<a href="mikrotik_bridge_set.php?server=' . $_GET[server] . '&action=add" class="fancybox fancybox.iframe" title="Adicionar interface Bridge">Adicionar interface Bridge</a>';

	} else
		echo "<pre>nenhuma interface bridge foi encontrada.</pre>";



	/*******************************************************************************
	 * BLOCO: INTERFACES -> WLAN
	 ******************************************************************************/
	$API->write("/interface/wireless/getall",true);   // true ou false
	$READ  = $API->read(false);
	$ARRAY = $API->ParseResponse($READ);

	// se houver mais que uma wlan
	if (count($ARRAY)>0) {
		
		for ($x=0; $x<count($ARRAY); $x++) {
			
			$name          = $ARRAY[$x]['name'];
			$default_name  = $ARRAY[$x]['default-name'];
			//$speed         = $ARRAY[$x]['speed'];
			$comment       = $ARRAY[$x]['comment'];
			$running       = $ARRAY[$x]['running'];
			$slave         = $ARRAY[$x]['slave'];
			
			// flag - running 
			if ($ARRAY[$x]['running'] == "true") {
				$estado = "<pre>interface conectada</pre>";
				$flag_running = "R";
			} else {
				$estado = "<pre>interface desconectada</pre>";
				$flag_running = "";
			}

			// wlan status - criar os links para wlan
			if ($ARRAY[$x]['disabled'] == "false") 
				$link_status  = "<font face='arial' style='font-size: 15pt'><a href='mikrotik_nas_admin_action.php?op=mk_wlan_status&val=true&server=" . $_GET[server] . "&default_name=" . $default_name . "' style='color: #2fcc66; text-decoration: none' title='Ativo'>&#9679;</a></font>";
			else
				$link_status = "<font face='arial' style='font-size: 15pt'><a href='mikrotik_nas_admin_action.php?op=mk_wlan_status&val=false&server=" . $_GET[server] . "&default_name=" . $default_name . "' style='color: #e74c3c; text-decoration: none' title='Inativo'>&#x25CF;</a></font>";

			// flag - slave
			if ($slave == true)
				$flag_slave = "S";
			else
				$flag_slave = "";
			
			// ether html tr usado no template
			$wlan_tr .= '
				<tr>
					<td><font face="Arial" size="2" title="R: running -- S: slave">' . $flag_running . '' . $flag_slave . '&nbsp;</font></td>
					<td><font face="Arial" size="2">' . $name . '</font></td>
					<td align="left">
						' . $link_status . '
						<a href="mikrotik_wlan_edit.php?server=' . $_GET[server] . '&default_name=' . $default_name . '" class="fancybox fancybox.iframe" title="Editar Wlan">Editar Wlan</a>
						<a href="#" style="text-decoration: none;" title="Ler comentário -- ' . $comment . '">Ler comentário</a>
					</td>
				</tr>
			';
		}
		
	} else
		echo "<pre>nenhuma interface wlan foi encontrada.</pre>";


	
	/*******************************************************************************
	 * BLOCO: INTERFACES -> ETHER
	 ******************************************************************************/
	$API->write("/interface/ethernet/getall",true);   // true ou false
	$READ  = $API->read(false);
	$ARRAY = $API->ParseResponse($READ);
	
	// se houver mais que uma ether, exatamente o que precisamos para listar na interface
	if (count($ARRAY)>0) {
		
		for ($x=0; $x<count($ARRAY); $x++) {
			
			$name          = $ARRAY[$x]['name'];
			$default_name  = $ARRAY[$x]['default-name'];
			$speed         = $ARRAY[$x]['speed'];
			$comment       = $ARRAY[$x]['comment'];
			$running       = $ARRAY[$x]['running'];
			$slave         = $ARRAY[$x]['slave'];
			
			// criar os links para ether
			if ($ARRAY[$x]['running'] == "true") {
				$estado = "<pre>interface conectada</pre>";
				$flag_running = "R";
				$link_status  = "<font face='arial' style='font-size: 15pt'><a href='mikrotik_nas_admin_action.php?op=mk_ether_status&val=true&server=" . $_GET[server] . "&default_name=" . $default_name . "' style='color: #2fcc66; text-decoration: none' title='Ativo'>&#9679;</a></font>";
			} else {
				$estado = "<pre>interface desconectada</pre>";
				$flag_running = "";
				$link_status = "<font face='arial' style='font-size: 15pt'><a href='mikrotik_nas_admin_action.php?op=mk_ether_status&val=false&server=" . $_GET[server] . "&default_name=" . $default_name . "' style='color: #e74c3c; text-decoration: none' title='Inativo'>&#x25CF;</a></font>";
			}
			
			// flags - running and slave
			if ($slave == true)
				$flag_slave = "S";
			else
				$flag_slave = "";
			
			// ether html tr usado no template
			$ether_tr .= '
				<tr>
					<td><font face="Arial" size="2" title="R: running -- S: slave">' . $flag_running . '' . $flag_slave . '&nbsp;</font></td>
					<td><font face="Arial" size="2">' . $name . '</font></td>
					<td align="left">
						' . $link_status . '
						<a href="mikrotik_ether_edit.php?server=' . $_GET[server] . '&default_name=' . $default_name . '" class="fancybox fancybox.iframe" title="Editar Ether">Editar Ether</a>
						<a href="#" style="text-decoration: none;" title="Ler comentário -- ' . $comment . '">Ler comentário</a>
					</td>
				</tr>
			';
		}
		
	} else
		echo "<pre>nenhuma interface ether foi encontrada.</pre>";
	
	
	
	/*******************************************************************************
	 * BLOCO: INTERFACES -> security profiles
	 ******************************************************************************/
	$API->write("/interface/wireless/security-profiles/getall",true);   // true ou false
	$READ  = $API->read(false);
	$ARRAY = $API->ParseResponse($READ);

	// se houver um ou mais security profile, exatamente o que precisamos para listar em interface
	if (count($ARRAY)>0) {
		
		for ($x=0; $x<count($ARRAY); $x++) {
			
			$name = $ARRAY[$x]['name'];
			
			// default sec. profile isn't possible to exclude
			if ($name != "default")
				$del_button_sec_profile = '<a href="mikrotik_nas_admin_action.php?op=mk_del_secprofile&server=' . $_GET[server] . '&name=' . $name . '" style="text-decoration: none;" title="Excluir security profile">Excluir security profile</a>';

			// security profiles html tr usado no template
			$sec_profile_tr .= '
				<tr>
					<td><font face="Arial" size="2"></font></td>
					<td><font face="Arial" size="2">' . $name . '</font></td>
					<td align="left">
						<a href="mikrotik_secprofile_set.php?server=' . $_GET[server] . '&name=' . $name . '" class="fancybox fancybox.iframe" title="Editar Security Profile">Editar Security Profile</a>

						' . $del_button_sec_profile . '

					</td>
				</tr>
			';
		}
		$sec_profile_add_button = '<a href="mikrotik_secprofile_set.php?server=' . $_GET[server] . '&action=add" class="fancybox fancybox.iframe" title="Adicionar interface Security Profile">Adicionar interface Security Profile</a>';

	} else
		echo "<pre>nenhum `Security Profiles` foi encontrado.</pre>";

	
	// para gravar o servico use o comando `/ip/service/set`  
	// -- os campos são: name, address(available from), certificate, port
	$API->write("/system/ident/getall",true);
	$READ                        = $API->read(false);
	$ARRAY                       = $API->ParseResponse($READ);
	$name                        = $ARRAY[0]["name"];

	// conectado
	if (count($ARRAY)>0) {
		
		$API->write("/system/licen/getall",true);
		$READ                    = $API->read(false);
		$ARRAY                   = $API->ParseResponse($READ);
		$nlevel                  = $ARRAY[0]["nlevel"];
		$software_id             = $ARRAY[0]["software-id"];
		$features                = $ARRAY[0]["features"];
		
		$API->write("/system/resource/getall",true);
		$READ                    = $API->read(false);
		$ARRAY                   = $API->ParseResponse($READ);
		$cpu                     = $ARRAY[0]["cpu"];
		$cpu_frequency           = $ARRAY[0]["cpu-frequency"];
		$architecture            = $ARRAY[0]["board-name"];
		$uptime                  = $ARRAY[0]["uptime"];
		$build_time              = $ARRAY[0]["build-time"];
		$free_memory             = $ARRAY[0]["free-memory"];
		$total_memory            = $ARRAY[0]["total-memory"];
		$total_memory            = $ARRAY[0]["total-memory"];
		$cpu_count               = $ARRAY[0]["cpu-count"];
		$cpu_load                = $ARRAY[0]["cpu-load"];
		$free_hdd_space          = $ARRAY[0]["free-hdd-space"];
		$total_hdd_space         = $ARRAY[0]["total-hdd-space"];
		$write_sect_since_reboot = $ARRAY[0]["write-sect-since-reboot"];
		$write_sect_total        = $ARRAY[0]["write-sect-total"];
		$bad_blocks              = $ARRAY[0]["bad-blocks"];
		$architecture_name       = $ARRAY[0]["architecture-name"];
		$board_name              = $ARRAY[0]["board-name"];
		$platform                = $ARRAY[0]["platform"];
		
		$API->write("/system/pack/getall",true);
		$READ                    = $API->read(false);
		$ARRAY                   = $API->ParseResponse($READ);

		/* o resultado usado aqui foi da primeira posicao do array ou seja,
		 * usou a version do `routeros-mipsbe`
		 * Aqui nao vou coletar todas as infos da API nas vars, se precisar 
		 * porem o valor ja existe no array..
		 * exemplo do retorno 

			[0] => Array
	        (
	            [.id] => *1
	            [name] => routeros-mipsbe
	            [version] => 6.28
	            [build-time] => apr/15/2017 15:18:31
	            [scheduled] => 
	            [disabled] => false
        	)
        */
		$version = $ARRAY[0]["version"];
		
		$msg  = "<pre>";
		$msg .= "<a style='font-size: 25px; color:#2fcc66;' title='Ativo'>&#x25CF;</a>&nbsp;";
		$msg .= "<strong>" . $name . " (" . $architecture . ")</strong>&nbsp;&nbsp;\n";
		$msg .= "Versão MK: " . $version . "&nbsp;&nbsp;\n";
		$msg .= "Level: " . $nlevel . "&nbsp;&nbsp;\n";
		$msg .= "CPU: " . $cpu. " (" . $cpu_frequency . " Mhz)";
		$msg .= "</pre>";
		
	} else {
		
		/***************************************************************
		 * Comentario adicionado em: 2016-04-07
		 * E possivel cair nesse else em caso onde o retorno do 
		 * count($ARRAY) for igual ou menor que zero(0). Nesse caso foi
		 * por que nao houve resposta do comando `/system/ident/getall`
		 * na API MK ou a resposta foi zero(0).
		 **************************************************************/
		
		// Usuário Offline (não sei se isso é possível nas versões recentes do
		// MK, pois se houver falha no user/password automaticamente aplica-se
		// o segundo "else")
		echo "<p><a style='font-size: 25px; color:#e74c3c;' title='Retornou ZERO'>&#x25CF;</a></p>&nbsp;" . $ARRAY['!trap'][0]['message'];
	}

// Falha na conexão
} else {

	echo "<pre>";
	echo "<a style='font-size: 25px; color:#e74c3c;' title='Falha na conexão'>&#x25CF;</a>&nbsp;";
	echo "<font color='#ff0000'>Falha na conexão. Verifique as informações abaixo:</font>\n";
	echo "<font color='#ff0000'>Se estão corretos o usuário e senha para login via API;</font>\n";
	echo "<font color='#ff0000'>Se o IP $ServerIP realmente é do equipamento ao qual se deseja conectar;</font>\n";
	echo "<font color='#ff0000'>Se a API está ativa em $ServerIP;</font>\n";
	echo "<font color='#ff0000'>Se o IP do sistema está liberado para conectar-se ao equipamento $ServerIP.</font>\n";
	echo "</pre>";
}

// desconecta da api
$API->disconnect();



##################TEMPLATE##############
$msg = file('mikrotik_nas_admin.html'); //, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
foreach($msg as $m) { $mensagem = $mensagem.urldecode($m); }

#tags
$mensagem = str_replace("#SERVER_IP#", $ServerIP, $mensagem);
$mensagem = str_replace("#SERVER_PORT#", $GrapPort, $mensagem);
$mensagem = str_replace("#SERVER_PORT_ERROR#", $GrapPortMsg, $mensagem); // exibe uma mensagem de erro na apresentacao do grafico
$mensagem = str_replace("#NAME#", $name, $mensagem);
$mensagem = str_replace("#ARCHITECTURE#", $architecture, $mensagem);
$mensagem = str_replace("#OS#", "MikroTik", $mensagem);
$mensagem = str_replace("#PLATFORM#", $platform, $mensagem);
$mensagem = str_replace("#VERSION#", $version, $mensagem);
$mensagem = str_replace("#CPU#", $cpu, $mensagem);
$mensagem = str_replace("#CPU_FREQUENCY#", $cpu_frequency, $mensagem);
$mensagem = str_replace("#LEVEL#", $nlevel, $mensagem);
$mensagem = str_replace("#UPTIME#", $uptime, $mensagem);
$mensagem = str_replace("#BUILD_TIME#", $build_time, $mensagem);
$mensagem = str_replace("#FREE_MEMORY#", $free_memory, $mensagem);
$mensagem = str_replace("#TOTAL_MEMORY#", $total_memory, $mensagem);

#memory
$free_memory_perc = ($free_memory/$total_memory)*100;
$mensagem = str_replace("#FREE_MEMORY_PERC#", $free_memory_perc, $mensagem);
$mensagem = str_replace("#CPU_COUNT#", $cpu_count, $mensagem);
$mensagem = str_replace("#CPU_LOAD#", $cpu_load, $mensagem);

#hdd/sdd
$free_hdd_space_perc = ($free_hdd_space/$total_hdd_space)*100;
$mensagem = str_replace("#TOTAL_HDD_SPACE#", $total_hdd_space, $mensagem);
$mensagem = str_replace("#FREE_HDD_SPACE#", $free_hdd_space, $mensagem);
$mensagem = str_replace("#FREE_HDD_SPACE_PERC#", $free_hdd_space_perc, $mensagem);
$mensagem = str_replace("#WRITE_SECT_SINCE_REBOOT#", $write_sect_since_reboot, $mensagem);
$mensagem = str_replace("#WRITE_SECT_TOTAL#", $write_sect_total, $mensagem);
$mensagem = str_replace("#BAD_BLOCKS#", $bad_blocks, $mensagem);
$mensagem = str_replace("#ARCHITECTURE_NAME#", $architecture_name, $mensagem);
$mensagem = str_replace("#BOARD_NAME#", $board_name, $mensagem);
$mensagem = str_replace("#PLATFORM#", $platform, $mensagem);

#html tr - usar em INTERFACES -> BRIDGE
$mensagem = str_replace("#BRIDGE_HTML_TR#", $bridge_tr, $mensagem);
$mensagem = str_replace("#BRIDGE_ADD#", $bridge_add_button, $mensagem);
$mensagem = str_replace("#BRIDGE_PORTS_HTML_TR#", $bridge_ports_tr, $mensagem);
$mensagem = str_replace("#BRIDGE_PORTS_ADD#", $bridge_ports_add_button, $mensagem);

#html tr - usar em INTERFACES -> ETHERNET (ETHER)
$mensagem = str_replace("#ETHER_HTML_TR#", $ether_tr, $mensagem);

#html tr - usar em INTERFACES -> Security Profiles
$mensagem = str_replace("#SEC_PROFILE_HTML_TR#", $sec_profile_tr, $mensagem);
$mensagem = str_replace("#SEC_PROFILE_ADD#", $sec_profile_add_button, $mensagem);

#html tr - usar em INTERFACES -> WIRELESS (WLAN)
$mensagem = str_replace("#WLAN_HTML_TR#", $wlan_tr, $mensagem);

print $mensagem;
?>