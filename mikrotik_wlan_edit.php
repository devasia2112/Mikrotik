<?php
/**
 * Mikrotik WLAN
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
* `http://localhost/mikrotik_nas_admin.php?server=4&default_name=NOMETHER`
*
*/

$defaultName = $_GET['default_name'];  //nome padrao da ether 
$id_servidor = $_GET['server'];



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
$ds   = $database->select( "servidor", "*", array( "id[=]" => $id_servidor ));
$ip   = $ds['0']['ip_servidor'];
$user = $ds['0']['usuario'];
$pwdd = $ds['0']['autenticacao'];
$Pass = trim($pwdd);

// acesso a API Mikrotik
$API = new RouterosAPI();
$API->debug = true;
if ($API->connect($ip , $user , $Pass, $APIPort)) {

    if ($_SERVER['REQUEST_METHOD'] == "POST") {

        $defaultName     = filter_var($_POST['default_name'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $name            = filter_var($_POST['name'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		$mtu             = filter_var($_POST['mtu'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); //"1500";
		$macAddress      = filter_var($_POST['mac-address'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); //"00:0C:42:69:E7:69";
		$arp             = filter_var($_POST['arp'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); //"enabled";
		$mode            = filter_var($_POST['mode'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); //"ap-bridge";
		$ssid            = filter_var($_POST['ssid'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		$radioName       = filter_var($_POST['radio-name'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		$securityProfile = filter_var($_POST['security-profile'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); //"default";
		$antennaGain     = filter_var($_POST['antenna-gain'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); //"17";
		$wmmSupport      = filter_var($_POST['wmm-support'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); //"enabled";
		$comment         = filter_var($_POST['comment'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); //"Uma wlan existente.";

		// checkbox
        if (isset($_POST['default-authentication']))
			$defaultAuthentication = filter_var($_POST['default-authentication'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); //"true";
        else
            $defaultAuthentication = "false";

        if (isset($_POST['default-forwarding']))
			$defaultForwarding = filter_var($_POST['default-forwarding'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); //"false";
        else
            $defaultForwarding = "false";

        if (isset($_POST['hide-ssid']))
			$hideSsid = filter_var($_POST['hide-ssid'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); //"false";
        else
            $hideSsid = "false";

        if (isset($_POST['disabled']))
			$disabled = filter_var($_POST['disabled'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH); //"true";
        else
            $disabled = "false";


		$API->write("/interface/getall",false);
		$API->write('?default-name='.$defaultName,true);
		$READ = $API->read(false);
		$ARRAY = $API->ParseResponse($READ);
		
		if(count($ARRAY)>0) {

			$API->write("/interface/wireless/set",false);
			$API->write("=.id=".$ARRAY[0]['.id'],false);     // ID sequencial do MK da interface
			// dados vindos do POST (form)
			$API->write('=name='.$name,false);
			$API->write('=mtu='.$mtu,false);
			$API->write('=mac-address='.$macAddress,false);
			$API->write('=arp='.$arp,false);
			$API->write('=mode='.$mode,false);
			$API->write('=ssid='.$ssid,false);
			$API->write('=radio-name='.$radioName,false);
			$API->write('=security-profile='.$securityProfile,false);
			$API->write('=antenna-gain='.$antennaGain,false);
			$API->write('=wmm-support='.$wmmSupport,false);
			$API->write('=default-authentication='.$defaultAuthentication,false);
			$API->write('=default-forwarding='.$defaultForwarding,false);
			$API->write('=hide-ssid='.$hideSsid,false);
			$API->write('=disabled='.$disabled,false);
			$API->write('=comment='.$comment,true);
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;A interface física `{$defaultName}` de nome `{$name}` foi editada no servidor `{$ip}` com sucesso.</pre>";

        } else {

			// caso nao exista um interface ether para editar com o `.id` passado acima, entao retorna reposta abaixo
			$READ = $API->read(false);
			$ARRAY = $API->ParseResponse($READ);
            print "<pre>&nbsp;Houve uma falha e a interface `{$name}` não foi editada em `{$ip}`.</pre>";

        }        
        $API->disconnect();
        die(); // fim do post
    }
    if ($_SERVER['REQUEST_METHOD'] == "PUT" or
        $_SERVER['REQUEST_METHOD'] == "HEAD" or
        $_SERVER['REQUEST_METHOD'] == "DELETE" or
        $_SERVER['REQUEST_METHOD'] == "OPTIONS" or
        $_SERVER['REQUEST_METHOD'] == "TRACE" or
        $_SERVER['REQUEST_METHOD'] == "CONNECT" ) {
            print "&nbsp; This method `" . $_SERVER['REQUEST_METHOD'] . "` is not allowed here.";
    }

    // get  wlan by default-name
	$API->write("/interface/wireless/getall",false);   // aqui troquei para TRUE
	$API->write('?default-name='.$defaultName,true);
	$READ  = $API->read(false); // manteve a linha
	$ARRAY = $API->ParseResponse($READ); // manteve a linha
}



/*******************************************************************************
 * get all security profiles to list in a combo - OK
 ******************************************************************************/
$API->write("/interface/wireless/security-profiles/getall",true);   // aqui troquei para TRUE 
$READ2  = $API->read(false); // manteve a linha
$ARRAY2 = $API->ParseResponse($READ2); // manteve a linha
// se houver um ou mais security profile, exatamente o que precisamos para listar em interface
if(count($ARRAY2)>0) {
	
	for($x=0; $x<count($ARRAY2); $x++) {
		if ($ARRAY['0']['security-profile'] == $ARRAY2[$x]['name']) $sel = "selected"; else $sel = "";
        $opt_sec_profile .= '<option value="'.$ARRAY2[$x]['name'].'" '.$sel.'>'.$ARRAY2[$x]['name'].'</option>';
	}
} else {
	echo "<pre>nenhum `Security Profiles` foi encontrado.</pre>";
}



/*******************************************************************************
 * precisa consultar torre + transceptor e gravar num select/combo.
 * Essa tabela nao esta disponibilizada nesse pacote, para fins de testes, 
 * vamos usar os valores `hardcoded`.
 * Obs.: No mikrotik foi criado a convencao de usar:
 * `nome_torre` + `nome_transmissor`, no entanto pode-se usar a convenca que 
 * desejar aqui.
 ******************************************************************************/
$arr_torre_trans = [
    ["nome_torre" => "TORRE1", "nome_transmissor" => "TRANS1"],
    ["nome_torre" => "TORRE2", "nome_transmissor" => "TRANS2"],
    ["nome_torre" => "TORRE3", "nome_transmissor" => "TRANS3"],
];
foreach ($arr_torre_trans as $key => $value) {
    $ssid = $value["nome_torre"] . $value["nome_transmissor"];
    
    if ($ARRAY[0]['ssid'] == $ssid)
        $sel_ssid = "selected"; 
    else 
        $sel_ssid = "";

    $opt_ssid .= '<option value="'.$ssid.'" '.$sel_ssid.'>'.$ssid.'</option>';
}
?>



<!-- form HTML - esse form pode ser passado para um template no futuro -->
<form name="form-wlan-edit" method="post" action="">

    <input type="hidden" name="id_server" value="<?php echo $id_servidor; ?>">
    <input type="hidden" name="default_name" value="<?php echo $defaultName; ?>">

    <fieldset>
        <legend>General</legend>
        name
		<input type="text" name="name" value="<?php echo $ARRAY[0]['name']; ?>"> <br>
		mtu
		<input type="text" name="mtu" value="<?php echo $ARRAY[0]['mtu']; ?>"><br>
		mac-address
		<input type="text" name="mac-address" value="<?php echo $ARRAY[0]['mac-address']; ?>"><br>
        arp
        <select name="arp">
            <option value="disabled" <?php if ($ARRAY['0']['arp'] == "disabled") echo "selected"; else echo ""; ?> >disabled</option>
            <option value="enabled" <?php if ($ARRAY['0']['arp'] == "enabled") echo "selected"; else echo ""; ?> >enabled</option>
            <option value="reply-only" <?php if ($ARRAY['0']['arp'] == "reply-only") echo "selected"; else echo ""; ?> >reply-only</option>
            <option value="proxy-arp" <?php if ($ARRAY['0']['arp'] == "proxy-arp") echo "selected"; else echo ""; ?> >proxy-arp</option>
        </select><br>
    </fieldset>

    <fieldset>
        <legend>Wireless(WLAN)</legend>
        mode
        <select name="mode">
            <option value="alignment-only" <?php if ($ARRAY['0']['mode'] == "alignment-only") echo "selected"; else echo ""; ?> >alignment-only</option>
            <option value="ap-bridge" <?php if ($ARRAY['0']['mode'] == "ap-bridge") echo "selected"; else echo ""; ?> >ap-bridge</option>
            <option value="bridge" <?php if ($ARRAY['0']['mode'] == "bridge") echo "selected"; else echo ""; ?> >bridge</option>
            <option value="station" <?php if ($ARRAY['0']['mode'] == "station") echo "selected"; else echo ""; ?> >station</option>
            <option value="station-bridge" <?php if ($ARRAY['0']['mode'] == "station-bridge") echo "selected"; else echo ""; ?> >station-bridge</option>
            <option value="station-wds" <?php if ($ARRAY['0']['mode'] == "station-wds") echo "selected"; else echo ""; ?> >station-wds</option>
            <option value="wds-slave" <?php if ($ARRAY['0']['mode'] == "wds-slave") echo "selected"; else echo ""; ?> >wds-slave</option>
        </select><br>
        ssid
		<!-- <input type="text" name="ssid" value="<?php echo $ARRAY[0]['ssid']; ?>"><br> -->
		<select name="ssid">
			<option value="">Vazio</option>
			<?php echo $opt_ssid; ?>
		</select><br>
		radio-name
		<input type="text" name="radio-name" value="<?php echo $ARRAY[0]['radio-name']; ?>"><br>
        security-profile
        <select name="security-profile">
            <?php echo $opt_sec_profile; ?>
        </select><br>
        antenna-gain
		<input type="text" name="antenna-gain" value="<?php echo $ARRAY[0]['antenna-gain']; ?>"><br>
        wmm-support
        <select name="wmm-support">
            <option value="disabled" <?php if ($ARRAY['0']['arp'] == "disabled") echo "selected"; else echo ""; ?> >disabled</option>
            <option value="enabled" <?php if ($ARRAY['0']['arp'] == "enabled") echo "selected"; else echo ""; ?> >enabled</option>
            <option value="required" <?php if ($ARRAY['0']['arp'] == "required") echo "selected"; else echo ""; ?> >required</option>
        </select><br>

        default-authentication 
        <input type="checkbox" value="true" name="default-authentication" <?php if ($ARRAY['0']['default-authentication'] == "true") echo "checked"; else echo ""; ?> > &nbsp;&nbsp;
        default-forwarding
        <input type="checkbox" value="true" name="default-forwarding" <?php if ($ARRAY['0']['default-forwarding'] == "true") echo "checked"; else echo ""; ?> > &nbsp;&nbsp;
        hide-ssid
        <input type="checkbox" value="true" name="hide-ssid" <?php if ($ARRAY['0']['hide-ssid'] == "true") echo "checked"; else echo ""; ?> > 

    </fieldset>

    comment
	<input type="text" name="comment" value="<?php echo $ARRAY[0]['comment']; ?>">
    disabled 
    <input type="checkbox" value="true" name="disabled" <?php if ($ARRAY['0']['disabled'] == "true") echo "checked"; else echo ""; ?> >

	<input type="submit" value="OK">

</form>