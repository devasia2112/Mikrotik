<?php
/**
 * Mikrotik Bridge Set (NAS Management)
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
* `http://localhost/mikrotik_ether_edit.php?server=4&name=BRIDGE&action=ACTION`
*
*/



/*******************************************************************************
 * Chamada da conexao. Esses dados precisam vir do cadastro do servidor
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
 ******************************************************************************/
$serverId = $_GET['server'];
$name     = $_GET['name'];  // bridge default name
$action   = $_GET['action']; // edit(empty) or add



/*******************************************************************************
 * Consulta os dados do servico do servidor
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
 ******************************************************************************/
$dss = $database->select( "servidor_services", "*", array( "server_id[=]" => $serverId ));
foreach($dss as $ds) {
	// recebe a porta da API
	if ($ds['service_name'] == "api" and $ds['service_status'] == 1) {
		$APIPort = $ds['service_port']; // porta usada para acessar a API Mikrotik
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
        
        // validacao dos dados
        $id_server         = filter_var($_POST['id_server'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);             // usado para gravar o historico no sistema
        $default           = filter_var($_POST['default'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $name              = filter_var($_POST['name'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		$mtu               = filter_var($_POST['mtu'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);                   // Padrão 1500
		$arp               = filter_var($_POST['arp'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);                   // Padrão: "enabled". {disabled, enabled, proxy-arp, reply-only}
		
        if (isset($_POST['auto-mac'])) {
			$autoMAC = filter_var($_POST['auto-mac'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);                    // Pradrão: "true". {true/false}
        }
        else {
            $autoMAC = "false";
        }

		$adminMAC          = filter_var($_POST['admin-mac'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);             // Hex. Padrão: 00:00:00:00:00:00
	    //$macAddress        = filter_var($_POST[''], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);                      // Somente Leitura. Editável somente através de $adminMAC
		$protocolMode      = filter_var($_POST['protocol-mode'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);         // {none, stp, rstp. Padrão: none}
		$priority          = filter_var($_POST['priority'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);              // Padrão: 8000
		$maxMessageAge     = filter_var($_POST['max-message-age'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);       // Padrão: 00:00:20. Aceita: formato 20s
		$forwardDelay      = filter_var($_POST['forward-delay'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);         // Padrão: 00:00:15. Aceita: formato 15s
		$transmitHoldCount = filter_var($_POST['transmit-hold-count'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);   // Padrão: 6
		$ageingTime        = filter_var($_POST['ageing-time'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);           // Padrão: 00:05:00. Aceita: formato 5m
		
        if (isset($_POST['disabled'])) {
			$disabled = filter_var($_POST['disabled'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);                   // Padrão: "false". {true/false}
        }
        else {
            $disabled = "false";
        }
		
		$comment           = filter_var($_POST['comment'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);               // Padrão: vazio. Campo Texto (Ex.: "teste-on-line")


        /*******************************************************************************
         * consulta as bridges pelo nome do mesmo
         ******************************************************************************/
        $API->write("/interface/bridge/getall",false);
        $API->write('?name='.$name,true);
        $READ = $API->read(false);
        $ARRAY = $API->ParseResponse($READ);

        // se houver resposta
        if (count($ARRAY) > 0) {

            // data update
            $API->write("/interface/bridge/set",false);
            $API->write("=.id=".$ARRAY[0]['.id'],false);
            $API->write('=name='.$name,false);
			$API->write('=mtu='.$mtu,false);
			$API->write('=arp='.$arp,false);
			$API->write('=auto-mac='.$autoMAC,false);
			$API->write('=admin-mac='.$adminMAC,false);
			$API->write('=protocol-mode='.$protocolMode,false);
			$API->write('=priority='.$priority,false);
			$API->write('=max-message-age='.$maxMessageAge,false);
			$API->write('=forward-delay='.$forwardDelay,false);
			$API->write('=transmit-hold-count='.$transmitHoldCount,false);
			$API->write('=ageing-time='.$ageingTime,false);
			$API->write('=disabled='.$disabled,false);
			$API->write('=comment='.$comment,true);
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>A interface Bridge `{$name}` foi editada no servidor `{$ip}` com sucesso.</pre>";

        } else {
            
            // data insert
            $API->write("/interface/bridge/add",false);
			$API->write('=name='.$name,false);
			$API->write('=mtu='.$mtu,false);
			$API->write('=arp='.$arp,false);
			$API->write('=auto-mac='.$autoMAC,false);
			$API->write('=admin-mac='.$adminMAC,false);
			$API->write('=protocol-mode='.$protocolMode,false);
			$API->write('=priority='.$priority,false);
			$API->write('=max-message-age='.$maxMessageAge,false);
			$API->write('=forward-delay='.$forwardDelay,false);
			$API->write('=transmit-hold-count='.$transmitHoldCount,false);
			$API->write('=ageing-time='.$ageingTime,false);
			$API->write('=disabled='.$disabled,false);
			$API->write('=comment='.$comment,true);
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>A interface Bridge `{$name}` foi criada no servidor `{$ip}` com sucesso.</pre>";
        }
        $API->disconnect();
        die("EOF");
    }

    if ($_SERVER['REQUEST_METHOD'] == "PUT" or
        $_SERVER['REQUEST_METHOD'] == "HEAD" or
        $_SERVER['REQUEST_METHOD'] == "DELETE" or
        $_SERVER['REQUEST_METHOD'] == "OPTIONS" or
        $_SERVER['REQUEST_METHOD'] == "TRACE" or
        $_SERVER['REQUEST_METHOD'] == "CONNECT" ) 
    {
        print "<img src='../../images/001_19.png'> &nbsp; The method `" . $_SERVER['REQUEST_METHOD'] . "` is not allowed here.";
    }

    /*******************************************************************************
     * consulta todos as bridges pelo nome da mesma
     ******************************************************************************/
    $API->write("/interface/bridge/getall",false);
    $API->write('?name='.$name,true);
    $READ = $API->read(false);
    $ARRAY = $API->ParseResponse($READ);

	if (count($ARRAY) > 0) { /* update form caso houver resposta */ ?>

	    <!-- este form serve apenas como modelo -->
	    <form name="form1" method="post" action="">
	        
	        <input type="hidden" name="id_server" value="<?php echo $serverId; ?>">
	        <input type="hidden" name="default" value="<?php echo $ARRAY['0']['default']; ?>">

	        <fieldset>
	            <legend>GENERAL</legend>
	            name
	            <input type="text" name="name" value="<?php echo $ARRAY[0]['name']; ?>"><br>
	            MTU
	            <input type="text" name="mtu" value="<?php echo $ARRAY[0]['mtu']; ?>"><br>
	            ARP
	            <select name="arp">
	                <option value="disabled" <?php if ($ARRAY['0']['arp'] == "disabled") echo "selected"; else echo ""; ?> >disabled</option>
	                <option value="enabled" <?php if ($ARRAY['0']['arp'] == "enabled") echo "selected"; else echo ""; ?> >enabled</option>
	                <option value="proxy-arp" <?php if ($ARRAY['0']['arp'] == "proxy-arp") echo "selected"; else echo ""; ?> >proxy-arp</option>
	                <option value="reply-only" <?php if ($ARRAY['0']['arp'] == "reply-only") echo "selected"; else echo ""; ?> >reply-only</option>
	            </select><br>

	            auto-mac
                <input type="checkbox" value="true" name="auto-mac" class="one" <?php if (strpos($ARRAY['0']['auto-mac'], "true") !== FALSE) echo "checked"; else echo ""; ?> >
	            <div id="block-admin-mac">
		            admin-mac
		            <input type="text" name="admin-mac" value="<?php echo $ARRAY[0]['admin-mac']; ?>" id="show-admin-mac"><br>
	            </div>

	            <br>mac-address
	            <input type="text" name="mac-address" value="<?php echo $ARRAY[0]['mac-address']; ?>"><br>
            </fieldset>

	        <fieldset>
	            <legend>STP</legend>
	            protocol-mode
	            <select name="protocol-mode">
	                <option value="none" <?php if ($ARRAY['0']['protocol-mode'] == "none") echo "selected"; else echo ""; ?> >none</option>
	                <option value="stp" <?php if ($ARRAY['0']['protocol-mode'] == "stp") echo "selected"; else echo ""; ?> >stp</option>
	                <option value="rstp" <?php if ($ARRAY['0']['protocol-mode'] == "rstp") echo "selected"; else echo ""; ?> >rstp</option>
	            </select><br>
	            priority
	            <input type="text" name="priority" value="<?php echo $ARRAY[0]['priority']; ?>"><br>
	            max-message-age
	            <input type="text" name="max-message-age" value="<?php echo $ARRAY[0]['max-message-age']; ?>"><br>
	            forward-delay
	            <input type="text" name="forward-delay" value="<?php echo $ARRAY[0]['forward-delay']; ?>"><br>
	            transmit-hold-count
	            <input type="text" name="transmit-hold-count" value="<?php echo $ARRAY[0]['transmit-hold-count']; ?>"><br>
	            ageing-time
	            <input type="text" name="ageing-time" value="<?php echo $ARRAY[0]['ageing-time']; ?>"><br>
	        </fieldset>

	        comment
            <input type="text" name="comment" value="<?php echo $ARRAY[0]['comment']; ?>"> &nbsp;&nbsp;&nbsp; 
            disabled
            <input type="checkbox" value="true" name="disabled" <?php if (strpos($ARRAY['0']['disabled'], "true") == "true") echo "checked"; else echo ""; ?> ><br><br>

            <input type="submit" value="OK">

	    </form>


	<?php } else { /* else insert */ ?>


	    <!-- este form serve apenas como modelo -->
	    <form name="form1" method="post" action="">
	        
	        <input type="hidden" name="id_server" value="<?php echo $serverId; ?>">
	        <input type="hidden" name="default" value="<?php echo $ARRAY['0']['default']; ?>">
	        
	        <fieldset>
	            <legend>GENERAL</legend>
	            name
	            <input type="text" name="name" value=""><br>
	            MTU
	            <input type="text" name="mtu" value=""><br>
	            ARP
	            <select name="arp">
	                <option value="disabled">disabled</option>
	                <option value="enabled">enabled</option>
	                <option value="proxy-arp">proxy-arp</option>
	                <option value="reply-only">reply-only</option>
	            </select><br>

	            auto-mac
                <input type="checkbox" value="true" name="auto-mac" class="one">
	            <!-- o campo admin-mac o padrao precisa ser  para validar na api -->
	            <div id="block-admin-mac">
		            admin-mac
		            <input type="text" name="admin-mac" value="00:00:00:00:00:00" id="show-admin-mac"><br>
	            </div>

	            <br>mac-address
	            <input type="text" name="mac-address" value=""><br>
            </fieldset>

	        <fieldset>
	            <legend>STP</legend>
	            protocol-mode
	            <select name="protocol-mode">
	                <option value="none">none</option>
	                <option value="stp">stp</option>
	                <option value="rstp">rstp</option>
	            </select><br>
	            priority
	            <input type="text" name="priority" value=""><br>
	            max-message-age
	            <input type="text" name="max-message-age" value=""><br>
	            forward-delay
	            <input type="text" name="forward-delay" value=""><br>
	            transmit-hold-count
	            <input type="text" name="transmit-hold-count" value=""><br>
	            ageing-time
	            <input type="text" name="ageing-time" value=""><br>
	        </fieldset>

	        comment
            <input type="text" name="comment" value=""> &nbsp;&nbsp;&nbsp; 
            disabled
            <input type="checkbox" value="true" name="disabled"><br><br>

            <input type="submit" value="OK">

	    </form>

	<?php } ?>

<?php } ?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    $('input[type=checkbox].one').change(function(){
        if($('input[type=checkbox].one:checked').size() == 1){
			$("#block-admin-mac").hide("slow");
            $("#show-admin-mac").hide("slow");
            // admin-mac precisa ser passado para API mesmo quando auto-mac esta checado, caso contrario a API retorna erro 
            //$("#show-admin-mac").attr("disabled", "disabled").css("background-color", "#ccc");
        } else {
			$("#block-admin-mac").show("slow");
            $("#show-admin-mac").show("slow");
            //$("#show-admin-mac").removeAttr("disabled").css("background-color", "#fff");
        }
    });
    // onload
    if($('input[type=checkbox].one:checked').size() == 1){
		$("#block-admin-mac").hide("slow");
        $("#show-admin-mac").hide("slow");
        //$("#show-admin-mac").attr("disabled", "disabled").css("background-color", "#ccc");
    }

});
</script>
