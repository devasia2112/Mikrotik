<?php
/**
 * Mikrotik NAS Admin Actions
 *
 * Controla operacoes no servidor.
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



// api mikrotik - ethernet status
if ( $_REQUEST["op"] == "mk_ether_status" )
{
	// ether default name
	$defaultName = $_REQUEST['default_name'];
	$id_servidor = $_REQUEST['server'];
	$value_stat  = $_REQUEST['val'];

	/*******************************************************************************
	 * Consulta os dados do servico do servidor
	 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
	 ******************************************************************************/
	$dss = $database->select( "servidor_services", "*", array( "server_id[=]" => $id_servidor ));
	foreach($dss as $ds) {
		
		// recebe a porta da API
		if ($ds['service_name'] == "api" and $ds['service_status'] == 1)
			$APIPort = $ds['service_port']; // porta usada para acessar a API Mikrotik
	}
	
	//consulta os dados do servidor para acesso
	$ds   = $database->select("servidor", "*", array( "id[=]" => $id_servidor));
	$ip   = $ds['0']['ip_servidor'];
	$user = $ds['0']['usuario'];
	$pwdd = $ds['0']['autenticacao'];
	$Pass = trim($pwdd);
	$port = $APIPort;
	
	// acesso a API Mikrotik
	$API = new RouterosAPI();
	$API->debug = false;
	if ($API->connect($ip , $user , $Pass, $port)) {
		
        /*******************************************************************************
        * Update data 
        *******************************************************************************/
        $API->write("/interface/ethernet/getall",false);    // getall lista as informações
        $API->write('?default-name='.$defaultName,true);    // a idéia aqui é separar somente os valores da interface $defaultName
        $READ  = $API->read(false);
        $ARRAY = $API->ParseResponse($READ);
        
        if(count($ARRAY)>0) {
            
            $API->write("/interface/ethernet/set",false);
            $API->write("=.id=".$ARRAY[0]['.id'],false);    // o update (set) ocorre via ID e nao defaultName - Isso seria um equivalente a uma Where clausule no SQL by ID
            // dados vindos do POST (form)
            $API->write('=disabled='.$value_stat,true);
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;A interface física `{$defaultName}` de nome `{$ARRAY[0]['name']}` foi editada no servidor `{$ip}` com sucesso.</pre>";
			
        } else {
            
            // caso nao exista um interface ether para editar com o defaultName passado acima, entao retorna reposta abaixo
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;Houve uma falha e a interface $name não foi editada em $ip.</pre>";
        
        }
        // desconecta da api
        $API->disconnect();
	}
    
	// se nao redirecionar use JavaScript ou HTML
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit;
}

// api mikrotik - wireless (wlan) status
elseif( $_REQUEST["op"] == "mk_wlan_status" )
{
	// ether default name
	$defaultName = $_REQUEST['default_name'];
	$id_servidor = $_REQUEST['server'];
	$value_stat  = $_REQUEST['val'];

	/*******************************************************************************
	 * Consulta os dados do servico do servidor
	 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
	 ******************************************************************************/
	$dss = $database->select( "servidor_services", "*", array( "server_id[=]" => $id_servidor ));
	foreach($dss as $ds) {
		
		// recebe a porta da API
		if ($ds['service_name'] == "api" and $ds['service_status'] == 1)
			$APIPort = $ds['service_port']; // porta usada para acessar a API Mikrotik
	}
	
	//consulta os dados do servidor para acesso
	$ds   = $database->select("servidor", "*", array( "id[=]" => $id_servidor));
	$ip   = $ds['0']['ip_servidor'];
	$user = $ds['0']['usuario'];
	$pwdd = $ds['0']['autenticacao'];
	$Pass = trim($pwdd);
	$port = $APIPort;
	
	// acesso a API Mikrotik
	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ip , $user , $Pass, $port)) {
		
        /*******************************************************************************
        * Update data 
        *******************************************************************************/
        $API->write("/interface/getall",false);    // getall lista as informações
        $API->write('?default-name='.$defaultName,true);    // a idéia aqui é separar somente os valores da interface $defaultName
        $READ  = $API->read(false);
        $ARRAY = $API->ParseResponse($READ);

        if(count($ARRAY)>0) {
            echo $ARRAY[0]['.id'];
            $API->write("/interface/wireless/set",false);
            $API->write("=.id=".$ARRAY[0]['.id'],false);    // o update (set) ocorre via ID e nao defaultName - Isso seria um equivalente a uma Where clausule no SQL by ID
            // dados vindos do POST (form)
            //$API->write('=running='.$value_stat,false);
            $API->write('=disabled='.$value_stat,true);
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;A interface f&iacute;sica wlan `{$defaultName}` de nome `" . $ARRAY[0]['name'] . "` foi editada no servidor `{$ip}` com sucesso.</pre>";
			
        } else {
            
            // caso nao exista um interface ether para editar com o defaultName passado acima, entao retorna reposta abaixo
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;Houve uma falha e a interface wlan `{$defaultName}` não foi editada em `{$ip}`.</pre>";
            
        }
        // desconecta da api
        $API->disconnect();
	}
    
	// se nao redirecionar use JavaScript ou HTML
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit;
}

// api mikrotik - bridge ports status
elseif ($_REQUEST["op"] == "mk_bridge_ports_status") {

	// ether default name
	$name = $_REQUEST['interface'];
	$id_servidor = $_REQUEST['server'];
	$value_stat  = $_REQUEST['val'];

	/*******************************************************************************
	 * Consulta os dados do servico do servidor
	 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
	 ******************************************************************************/
	$dss = $database->select( "servidor_services", "*", array( "server_id[=]" => $id_servidor ));
	foreach($dss as $ds) {
		
		// recebe a porta da API
		if ($ds['service_name'] == "api" and $ds['service_status'] == 1)
			$APIPort = $ds['service_port']; // porta usada para acessar a API Mikrotik
	}

	//consulta os dados do servidor para acesso
	$ds   = $database->select("servidor", "*", array( "id[=]" => $id_servidor));
	$ip   = $ds['0']['ip_servidor'];
	$user = $ds['0']['usuario'];
	$pwdd = $ds['0']['autenticacao'];
	$Pass = trim($pwdd);
	$port = $APIPort;
	
	// acesso a API Mikrotik
	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ip , $user , $Pass, $port)) {
		
        /*******************************************************************************
        * Update data 
        *******************************************************************************/
        $API->write("/interface/bridge/port/getall",false);
        $API->write('?interface='.$name,true);
        $READ  = $API->read(false);
        $ARRAY = $API->ParseResponse($READ);

        if(count($ARRAY)>0) {

            $API->write("/interface/bridge/port/set",false);
            $API->write("=.id=".$ARRAY[0]['.id'],false);
            $API->write('=disabled='.$value_stat,true);
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;A interface `{$name}` foi editada no servidor `{$ip}` com sucesso.</pre>";
			
        } else {
            
            // caso nao exista um interface ether para editar com o `name` passado acima, entao retorna reposta abaixo
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;Houve uma falha e a interface `{$name}` não foi editada no servidor `{$ip}`.</pre>";
            
        }
        // desconecta da api
        $API->disconnect();
	}

	// se nao redirecionar use JavaScript ou HTML
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit;
}

// api mikrotik - bridge status
elseif ($_REQUEST["op"] == "mk_bridge_status") {

	// ether default name
	$name = $_REQUEST['name'];
	$id_servidor = $_REQUEST['server'];
	$value_stat  = $_REQUEST['val'];

	/*******************************************************************************
	 * Consulta os dados do servico do servidor
	 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
	 ******************************************************************************/
	$dss = $database->select( "servidor_services", "*", array( "server_id[=]" => $id_servidor ));
	foreach($dss as $ds) {
		
		// recebe a porta da API
		if ($ds['service_name'] == "api" and $ds['service_status'] == 1)
			$APIPort = $ds['service_port']; // porta usada para acessar a API Mikrotik
	}

	//consulta os dados do servidor para acesso
	$ds   = $database->select("servidor", "*", array( "id[=]" => $id_servidor));
	$ip   = $ds['0']['ip_servidor'];
	$user = $ds['0']['usuario'];
	$pwdd = $ds['0']['autenticacao'];
	$Pass = trim($pwdd);
	$port = $APIPort;
	
	// acesso a API Mikrotik
	$API = new RouterosAPI();
	$API->debug = true;
	if ($API->connect($ip , $user , $Pass, $port)) {
		
        /*******************************************************************************
        * Update data 
        *******************************************************************************/
        $API->write("/interface/bridge/getall",false);
        $API->write('?name='.$name,true);
        $READ  = $API->read(false);
        $ARRAY = $API->ParseResponse($READ);
        
        if(count($ARRAY)>0) {

            $API->write("/interface/bridge/set",false);
            $API->write("=.id=".$ARRAY[0]['.id'],false);
            $API->write('=disabled='.$value_stat,true);
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;A interface física `{$name}` de nome `{$ARRAY[0]['name']}` foi editada no servidor `{$ip}` com sucesso.</pre>";
			
        } else {
            
            // caso nao exista um interface ether para editar com o `name` passado acima, entao retorna reposta abaixo
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;Houve uma falha e a interface `{$name}` não foi editada em `{$ip}`.</pre>";
            
        }
        // desconecta da api
        $API->disconnect();
	}

	// se nao redirecionar use JavaScript ou HTML
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit;
}

// api mikrotik - bridge ports delete op
elseif ($_REQUEST["op"] == "mk_del_bridge_ports") {

	$name = $_REQUEST['interface'];
	$id_servidor = $_REQUEST['server'];
	
	/*******************************************************************************
	 * Consulta os dados do servico do servidor
	 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
	 ******************************************************************************/
	$dss = $database->select( "servidor_services", "*", array( "server_id[=]" => $id_servidor ));
	foreach($dss as $ds) {
		
		// recebe a porta da API
		if ($ds['service_name'] == "api" and $ds['service_status'] == 1)
			$APIPort = $ds['service_port']; // porta usada para acessar a API Mikrotik
	}
	
	//consulta os dados do servidor para acesso
	$ds   = $database->select("servidor", "*", array( "id[=]" => $id_servidor));
	$ip   = $ds['0']['ip_servidor'];
	$user = $ds['0']['usuario'];
	$pwdd = $ds['0']['autenticacao'];
	$Pass = trim($pwdd);
	$port = $APIPort;     // "8728";
	
	// acesso a API Mikrotik
	$API = new RouterosAPI();
	$API->debug = false;
	if ($API->connect($ip , $user , $Pass, $port)) {
		
        /*******************************************************************************
        * delete data 
        *******************************************************************************/
        $API->write("/interface/bridge/port/getall",false);
        $API->write('?interface='.$name,true);
        $READ  = $API->read(false);
        $ARRAY = $API->ParseResponse($READ);
        
        if(count($ARRAY)>0) {

			//print "<pre>"; print_r($ARRAY); print "</pre>";die;
            $API->write("/interface/bridge/port/remove",false);
            $API->write("=.id=".$ARRAY[0]['.id'],true);    // o update (set) ocorre via ID e nao defaultName - Isso seria um equivalente a uma Where clausule no SQL by ID
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;A interface `{$name}` foi excluida do servidor `{$ip}` com sucesso.</pre>";
			
        } else {
            
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;Houve uma falha e a interface $name não pode ser exclu&iacute;da no servidor $ip.</pre>";
            
        }
        // desconecta da api
        $API->disconnect();
	}

	// se nao redirecionar use JavaScript ou HTML
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit;
}

// api mikrotik - bridge delete op
elseif ($_REQUEST["op"] == "mk_del_bridge") {

	$name = $_REQUEST['name'];
	$id_servidor = $_REQUEST['server'];
	
	/*******************************************************************************
	 * Consulta os dados do servico do servidor
	 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
	 ******************************************************************************/
	$dss = $database->select( "servidor_services", "*", array( "server_id[=]" => $id_servidor ));
	foreach($dss as $ds) {
		
		// recebe a porta da API
		if ($ds['service_name'] == "api" and $ds['service_status'] == 1)
			$APIPort = $ds['service_port']; // porta usada para acessar a API Mikrotik
	}
	
	//consulta os dados do servidor para acesso
	$ds   = $database->select("servidor", "*", array( "id[=]" => $id_servidor));
	$ip   = $ds['0']['ip_servidor'];
	$user = $ds['0']['usuario'];
	$pwdd = $ds['0']['autenticacao'];
	$Pass = trim($pwdd);
	$port = $APIPort;     // "8728";
	
	// acesso a API Mikrotik
	$API = new RouterosAPI();
	$API->debug = false;
	if ($API->connect($ip , $user , $Pass, $port)) {
		
        /*******************************************************************************
        * delete data 
        *******************************************************************************/
        $API->write("/interface/bridge/getall",false);
        $API->write('?name='.$name,true);
        $READ  = $API->read(false);
        $ARRAY = $API->ParseResponse($READ);
        
        if(count($ARRAY)>0) {

            $API->write("/interface/bridge/remove",false);
            $API->write("=.id=".$ARRAY[0]['.id'],true);    // o update (set) ocorre via ID e nao defaultName - Isso seria um equivalente a uma Where clausule no SQL by ID
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;A interface física `{$name}` foi excluida do servidor `{$ip}` com sucesso.</pre>";
			
        } else {
            
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;Houve uma falha e a interface $name não pode ser exclu&iacute;da no servidor $ip.</pre>";

        }
        // desconecta da api
        $API->disconnect();
	}

	// se nao redirecionar use JavaScript ou HTML
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit;
}

// api mikrotik - security profiles
elseif ($_REQUEST["op"] == "mk_del_secprofile") {
	
	$name = $_REQUEST['name'];
	$id_servidor = $_REQUEST['server'];
	
	/*******************************************************************************
	 * Consulta os dados do servico do servidor
	 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
	 ******************************************************************************/
	$dss = $database->select( "servidor_services", "*", array( "server_id[=]" => $id_servidor ));
	foreach($dss as $ds) {
		
		// recebe a porta da API
		if ($ds['service_name'] == "api" and $ds['service_status'] == 1)
			$APIPort = $ds['service_port']; // porta usada para acessar a API Mikrotik
	}
	
	//consulta os dados do servidor para acesso
	$ds   = $database->select("servidor", "*", array( "id[=]" => $id_servidor));
	$ip   = $ds['0']['ip_servidor'];
	$user = $ds['0']['usuario'];
	$pwdd = $ds['0']['autenticacao'];
	$Pass = trim($pwdd);
	$port = $APIPort;     // "8728";
	
	// acesso a API Mikrotik
	$API = new RouterosAPI();
	$API->debug = false;
	if ($API->connect($ip , $user , $Pass, $port)) {
		
        /*******************************************************************************
        * delete data 
        *******************************************************************************/
        $API->write("/interface/wireless/security-profiles/getall",false);    // getall lista as informações
        $API->write('?name='.$name,true);    // a idéia aqui é separar somente os valores da interface $defaultName
        $READ  = $API->read(false);
        $ARRAY = $API->ParseResponse($READ);
        
        if(count($ARRAY)>0) {
            
            $API->write("/interface/wireless/security-profiles/remove",false);
            $API->write("=.id=".$ARRAY[0]['.id'],true);    // o update (set) ocorre via ID e nao defaultName - Isso seria um equivalente a uma Where clausule no SQL by ID
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;A interface física `{$name}` de nome `{$ARRAY[0]['name']}` foi excluida do servidor `{$ip}` com sucesso.</pre>";
			
        } else {
            
            // caso nao exista um interface ether para editar com o defaultName passado acima, entao retorna reposta abaixo
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;Houve uma falha e a interface $name não pode ser exclu&iacute;da no servidor $ip.</pre>";
            
        }
        // desconecta da api
        $API->disconnect();
	}

	// se nao redirecionar use JavaScript ou HTML
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit;
}

// defualt
else
{
	echo "Escolha uma a&ccedil;&atilde;o.";
}
?>