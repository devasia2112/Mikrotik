<?php
/**
 * Mikrotik Bridge Ports Set (NAS Management)
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
* `http://localhost/mikrotik_ether_edit.php?server=4&interface=INTERFACE&action=ACTION`
*
*/



/*******************************************************************************
 * Chamada da conexao. Esses dados precisam vir do cadastro do servidor
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
 ******************************************************************************/
$serverId  = $_GET['server'];
$interface = $_GET['interface'];  // default name for bridge ports 
$action    = $_GET['action']; // edit(empty) or add



/*******************************************************************************
 * Consulta os dados do servico do servidor
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
 ******************************************************************************/
$dss = $database->select( "servidor_services", "*", array( "server_id[=]" => $serverId ));
foreach($dss as $ds) {
	// recebe a porta da API
	if ($ds['service_name'] == "api" and $ds['service_status'] == 1) {
		$APIPort = $ds['service_port'];
	}
}



/*******************************************************************************
 *consulta os dados do servidor para acesso
 ******************************************************************************/
$ds   = $database->select( "servidor", "*", array( "id[=]" => $serverId ));
$ip   = $ds['0']['ip_servidor'];
$user = $ds['0']['usuario'];
$pwdd = $ds['0']['autenticacao'];
$Pass = trim($pwdd);



/*******************************************************************************
 * acesso a API Mikrotik
 ******************************************************************************/
$API = new RouterosAPI();
$API->debug = true;
if ($API->connect($ip, $user, $Pass, $APIPort)) {

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        
        // validation
        $id_server    = filter_var($_POST['id_server'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);             // usado para gravar o historico no sistema
        $default      = filter_var($_POST['default'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $interface    = filter_var($_POST['interface'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $bridge       = filter_var($_POST['bridge'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $priority     = filter_var($_POST['priority'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $pathCost     = filter_var($_POST['path-cost'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $horizon      = filter_var($_POST['horizon'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $edge         = filter_var($_POST['edge'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		$pointToPoint = filter_var($_POST['point-to-point'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);                   // Padrão 1500
		$externalFDB  = filter_var($_POST['external-fdb'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);                   // Padrão: "enabled". {disabled, enabled, proxy-arp, reply-only}
		
        if (isset($_POST['auto-isolate'])) {
			$autoIsolate = filter_var($_POST['auto-isolate'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);                    // Pradrão: "true". {true/false}
        }
        else {
            $autoIsolate = "false";
        }
		
        if (isset($_POST['disabled'])) {
			$disabled = filter_var($_POST['disabled'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);                   // Padrão: "false". {true/false}
        }
        else {
            $disabled = "false";
        }
		
		$comment      = filter_var($_POST['comment'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);               // Padrão: vazio. Campo Texto (Ex.: "teste-on-line0")

        /*******************************************************************************
         * consulta as bridges pelo nome do mesmo
         ******************************************************************************/
        $API->write("/interface/bridge/port/getall",false);
        $API->write('?interface='.$interface,true);
        $READ = $API->read(false);
        $ARRAY = $API->ParseResponse($READ);

        // se houver resposta
        if (count($ARRAY) > 0) {

            // data update
            $API->write("/interface/bridge/port/set",false);
            $API->write("=.id=".$ARRAY[0]['.id'],false);
			$API->write('=interface='.$interface,false);
			$API->write('=bridge='.$bridge,false);
			$API->write('=priority='.$priority,false);
			$API->write('=path-cost='.$pathCost,false);
			$API->write('=horizon='.$horizon,false);
			$API->write('=edge='.$edge,false);
			$API->write('=point-to-point='.$pointToPoint,false);
			$API->write('=external-fdb='.$externalFDB,false);
			$API->write('=auto-isolate='.$autoIsolate,false);
			$API->write('=disabled='.$disabled,false);
			$API->write('=comment='.$comment,true);
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>A interface Bridge Ports `{$name}` foi editada no servidor `{$ip}` com sucesso.</pre>";

        } else {
            
            // data insert
			$API->write("/interface/bridge/port/add",false);
			$API->write('=interface='.$interface,false);
			$API->write('=bridge='.$bridge,false);
			$API->write('=priority='.$priority,false);
			$API->write('=path-cost='.$pathCost,false);
			$API->write('=horizon='.$horizon,false);
			$API->write('=edge='.$edge,false);
			$API->write('=point-to-point='.$pointToPoint,false);
			$API->write('=external-fdb='.$externalFDB,false);
			$API->write('=auto-isolate='.$autoIsolate,false);
			$API->write('=disabled='.$disabled,false);
			$API->write('=comment='.$comment,true);
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>A interface Bridge Ports `{$name}` foi criada no servidor `{$ip}` com sucesso.</pre>";
        }
        $API->disconnect();
        die("EOF"); 
    }

    if ($_SERVER['REQUEST_METHOD'] == "PUT" or
        $_SERVER['REQUEST_METHOD'] == "HEAD" or
        $_SERVER['REQUEST_METHOD'] == "DELETE" or
        $_SERVER['REQUEST_METHOD'] == "OPTIONS" or
        $_SERVER['REQUEST_METHOD'] == "TRACE" or
        $_SERVER['REQUEST_METHOD'] == "CONNECT") 
    {
        print "The method `" . $_SERVER['REQUEST_METHOD'] . "` is not allowed here.";
    }

    /*******************************************************************************
     * consulta todos as bridges pelo nome da mesma
     ******************************************************************************/
    $API->write("/interface/bridge/port/getall",false);
    $API->write('?interface='.$interface,true);
    $READ = $API->read(false);
    $ARRAY = $API->ParseResponse($READ);

	/*******************************************************************************
	 * get all interfaces and list in a dropdown
	 ******************************************************************************/
	$API->write("/interface/getall",true);
	$READ2  = $API->read(false);
	$ARRAY2 = $API->ParseResponse($READ2);
	if (count($ARRAY2) > 0) {
		for($x=0; $x<count($ARRAY2); $x++) {
			if ($ARRAY['0']['interface'] == $ARRAY2[$x]['name']) $sel = "selected"; else $sel = "";
			if ($ARRAY2[$x][type] != "bridge")
	        	$opt_interface .= '<option value="'.$ARRAY2[$x]['name'].'" '.$sel.'>'.$ARRAY2[$x]['name'].'</option>';
		}
	} else {
		echo "<pre>nenhuma `interface` foi encontrada.</pre>";
	}

	/*******************************************************************************
	 * get all bridges and list in a dropdown
	 ******************************************************************************/
	$API->write("/interface/bridge/getall",true);
	$READ3  = $API->read(false);
	$ARRAY3 = $API->ParseResponse($READ3);
	if (count($ARRAY3) > 0) {
		for($x=0; $x<count($ARRAY3); $x++) {
			if ($ARRAY['0']['bridge'] == $ARRAY3[$x]['name']) {
				$sel = "selected"; else $sel = "";
			}
	        $opt_bridge .= '<option value="'.$ARRAY3[$x]['name'].'" '.$sel.'>'.$ARRAY3[$x]['name'].'</option>';
		}
	} else {
		echo "<pre>nenhuma `bridge` foi encontrada.</pre>";
	}

	if (count($ARRAY) > 0) { /* se houver resposta update form */ ?>


	    <!-- este form server como modelo apenas -->
	    <form name="form1" method="post" action="">
	        
	        <input type="hidden" name="id_server" value="<?php echo $serverId; ?>">
	        <input type="hidden" name="default" value="<?php echo $ARRAY['0']['default']; ?>">

	        <fieldset>
	            <legend>GENERAL</legend>
	            interface
	            <select name="interface">
	            	<?php echo $opt_interface; ?>
	            </select><br>
	            bridge
	            <select name="bridge">
	            	<?php echo $opt_bridge; ?>
	            </select><br>
	            priority
	            <input type="text" name="priority" value="<?php echo $ARRAY[0]['priority']; ?>"><br>
	            path-cost
	            <input type="number" name="path-cost" value="<?php echo $ARRAY[0]['path-cost']; ?>"><br>
	            horizon
	            <input type="number" name="horizon" value="<?php echo $ARRAY[0]['horizon']; ?>"><br>
	            edge
	            <select name="edge">
	                <option value="auto" <?php if ($ARRAY['0']['edge'] == "auto") echo "selected"; else echo ""; ?> >auto</option>
	                <option value="no" <?php if ($ARRAY['0']['edge'] == "no") echo "selected"; else echo ""; ?> >no</option>
	                <option value="no-discover" <?php if ($ARRAY['0']['edge'] == "no-discover") echo "selected"; else echo ""; ?> >no-discover</option>
	                <option value="yes" <?php if ($ARRAY['0']['edge'] == "yes") echo "selected"; else echo ""; ?> >yes</option>
	                <option value="yes-discover" <?php if ($ARRAY['0']['edge'] == "yes-discover") echo "selected"; else echo ""; ?> >yes-discover</option>
	            </select><br>
	            point-to-point
	            <select name="point-to-point">
	                <option value="auto" <?php if ($ARRAY['0']['point-to-point'] == "auto") echo "selected"; else echo ""; ?> >auto</option>
	                <option value="no" <?php if ($ARRAY['0']['point-to-point'] == "no") echo "selected"; else echo ""; ?> >no</option>
	                <option value="yes" <?php if ($ARRAY['0']['point-to-point'] == "yes") echo "selected"; else echo ""; ?> >yes</option>
	            </select><br>
	            external-fdb
	            <select name="external-fdb">
	                <option value="auto" <?php if ($ARRAY['0']['external-fdb'] == "auto") echo "selected"; else echo ""; ?> >auto</option>
	                <option value="no" <?php if ($ARRAY['0']['external-fdb'] == "no") echo "selected"; else echo ""; ?> >no</option>
	                <option value="yes" <?php if ($ARRAY['0']['external-fdb'] == "yes") echo "selected"; else echo ""; ?> >yes</option>
	            </select><br>
	            auto-isolate
                <input type="checkbox" value="true" name="auto-isolate" <?php if ($ARRAY['0']['auto-isolate'] == "true") echo "checked"; else echo ""; ?> >
            </fieldset>
	        comment
            <input type="text" name="comment" value="<?php echo $ARRAY[0]['comment']; ?>"> &nbsp;&nbsp;&nbsp; 
            disabled
            <input type="checkbox" value="true" name="disabled" <?php if (strpos($ARRAY['0']['disabled'], "true") == "true") echo "checked"; else echo ""; ?> ><br><br>

            <input type="submit" value="OK">

	    </form>

	<?php } else { /* insert form */?>

	    <!-- este form serve como modelo apenas -->
	    <form name="form1" method="post" action="">
	        
	        <input type="hidden" name="id_server" value="<?php echo $serverId; ?>">
	        <input type="hidden" name="default" value="">

	        <fieldset>
	            <legend>GENERAL</legend>
	            interface
	            <select name="interface">
	            	<?php echo $opt_interface; ?>
	            </select><br>
	            bridge
	            <select name="bridge">
	            	<?php echo $opt_bridge; ?>
	            </select><br>
	            priority
	            <input type="text" name="priority" value="0x"><br>
	            path-cost
	            <input type="number" name="path-cost" value=""><br>
	            horizon
	            <input type="number" name="horizon" value=""><br>
	            edge
	            <select name="edge">
	                <option value="auto">auto</option>
	                <option value="no">no</option>
	                <option value="no-discover">no-discover</option>
	                <option value="yes">yes</option>
	                <option value="yes-discover">yes-discover</option>
	            </select><br>
	            point-to-point
	            <select name="point-to-point">
	                <option value="auto">auto</option>
	                <option value="no">no</option>
	                <option value="yes">yes</option>
	            </select><br>
	            external-fdb
	            <select name="external-fdb">
	                <option value="auto">auto</option>
	                <option value="no">no</option>
	                <option value="yes">yes</option>
	            </select><br>
	            auto-isolate
                <input type="checkbox" value="true" name="auto-isolate">
            </fieldset>
	        comment
            <input type="text" name="comment" value=""> &nbsp;&nbsp;&nbsp; 
            disabled
            <input type="checkbox" value="true" name="disabled"><br><br>

            <input type="submit" value="OK">

	    </form>

	<?php } ?>

<?php } ?>
