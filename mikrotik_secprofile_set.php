<?php
/**
 * Mikrotik Security Profiles
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
* `http://localhost/mikrotik_ether_edit.php?server=4&name=NOMETHER&action=ACTION`
*
*/



/*******************************************************************************
 * Chamada da conexao. Esses dados precisam vir do cadastro do servidor
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
 ******************************************************************************/
$id_servidor = $_GET['server'];
$name = $_GET['name'];  //nome padrao da ether 
$action = $_GET['action']; // edit(empty) or add



/*******************************************************************************
 * consulta os dados do servidor para acesso
 ******************************************************************************/
$ip      = "192.168.23.23";
$user    = "admin";
$Pass    = "passwd";
$APIPort = 9090;



/*******************************************************************************
 * acesso a API Mikrotik
 ******************************************************************************/
$API = new RouterosAPI();
$API->debug = true;
if ($API->connect($ip , $user , $Pass, $APIPort)) {

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        
        // validation and sanitization 
        $id_server = filter_var($_POST['id_server'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);  // usado para gravar o historico no sistema
        $default = filter_var($_POST['default'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $name = filter_var($_POST['name'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $mode = filter_var($_POST['mode'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        
        // authentication types
        $wpa_psk = filter_var($_POST['wpa_psk'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $wpa2_psk = filter_var($_POST['wpa2_psk'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $wpa_eap = filter_var($_POST['wpa_eap'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $wpa2_eap = filter_var($_POST['wpa2_eap'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $authenticationTypes = $wpa_psk . "," . $wpa2_psk . "," . $wpa_eap . "," . $wpa2_eap;
        $authenticationTypes = rtrim($authenticationTypes, ',');
        
        // unicast ciphers
        $unicast_aes_ccm = filter_var($_POST['unicast_aes_ccm'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $unicast_tkip = filter_var($_POST['unicast_tkip'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $unicastCiphers = $unicast_aes_ccm . "," . $unicast_tkip;
        $unicastCiphers = rtrim($unicastCiphers, ',');

        // group ciphers
        $group_aes_ccm = filter_var($_POST['group_aes_ccm'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $group_tkip = filter_var($_POST['group_tkip'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $groupCiphers = $group_aes_ccm . "," . $group_tkip;
        $groupCiphers = rtrim($groupCiphers, ',');

        $wpaPreSharedKey = filter_var($_POST['wpa_pre_shared_key'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $wpa2PreSharedKey = filter_var($_POST['wpa2_pre_shared_key'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $supplicantIdentity = filter_var($_POST['supplicant_identity'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $groupKeyUpdate = filter_var($_POST['group_key_update'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $managementProtection = filter_var($_POST['management_protection'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $managementProtectionKey = filter_var($_POST['management_protection_key'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        

        if (isset($_POST['radius_mac_authentication'])) {
            $radiusMacAuthentication = filter_var($_POST['radius_mac_authentication'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        }
        else {
            $radiusMacAuthentication = "false";
        }

        if (isset($_POST['radius_mac_accounting'])) {
            $radiusMacAccounting = filter_var($_POST['radius_mac_accounting'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        }
        else {
            $radiusMacAccounting = "false";
        }

        if (isset($_POST['radius_eap_accounting'])) {
            $radiusEapAccounting = filter_var($_POST['radius_eap_accounting'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        }
        else {
            $radiusEapAccounting = "false";
        }


        $interimUpdate = filter_var($_POST['interim_update'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $radiusMacFormat = filter_var($_POST['radius_mac_format'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $radiusMacMode = filter_var($_POST['radius_mac_mode'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $radiusMacCaching = filter_var($_POST['radius_mac_caching'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $eapMethods = filter_var($_POST['eap_methods'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $tlsMode = filter_var($_POST['tls_mode'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $tlsCertificate = filter_var($_POST['tls_certificate'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $staticAlgo0 = filter_var($_POST['static_algo_0'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $staticKey0 = filter_var($_POST['static_key_0'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $staticAlgo1 = filter_var($_POST['static_algo_1'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $staticKey1 = filter_var($_POST['static_key_1'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $staticAlgo2 = filter_var($_POST['static_algo_2'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $staticKey2 = filter_var($_POST['static_key_2'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $staticAlgo3 = filter_var($_POST['static_algo_3'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $staticKey3 = filter_var($_POST['static_key_3'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $staticTransmitKey = filter_var($_POST['static_transmit_key'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $staticStaPrivateAlgo = filter_var($_POST['static_sta_private_algo'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $staticStaPrivateKey = filter_var($_POST['static_sta_private_key'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $mschapv2Username = filter_var($_POST['mschapv2_username'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $mschapv2Password = filter_var($_POST['mschapv2_password'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

        /*******************************************************************************
         * consulta todos os security profiles pelo nome do mesmo
         ******************************************************************************/
        $API->write("/interface/wireless/security-profiles/getall",false); // getall lista todos os Sec. Prof.
        $API->write('?name='.$name,true);			// selecione o Security Profile
        $READ = $API->read(false);
        $ARRAY = $API->ParseResponse($READ);

        // quando houver rsposta
        if (count($ARRAY)>0) {

            // data update
            $API->write("/interface/wireless/security-profiles/set",false);
            $API->write("=.id=".$ARRAY[0]['.id'],false);
            $API->write('=name='.$name,false);
            $API->write('=mode='.$mode,false);
            $API->write('=authentication-types='.$authenticationTypes,false);
            $API->write('=unicast-ciphers='.$unicastCiphers,false);
            $API->write('=group-ciphers='.$groupCiphers,false);
            $API->write('=wpa-pre-shared-key='.$wpaPreSharedKey,false);
            $API->write('=wpa2-pre-shared-key='.$wpa2PreSharedKey,false);
            $API->write('=supplicant-identity='.$supplicantIdentity,false);
            $API->write('=group-key-update='.$groupKeyUpdate,false);
            $API->write('=management-protection='.$managementProtection,false);
            $API->write('=management-protection-key='.$managementProtectionKey,false);
            $API->write('=radius-mac-authentication='.$radiusMacAuthentication,false);
            $API->write('=radius-mac-accounting='.$radiusMacAccounting,false);
            $API->write('=radius-eap-accounting='.$radiusEapAccounting,false);
            $API->write('=interim-update='.$interimUpdate,false);
            $API->write('=radius-mac-format='.$radiusMacFormat,false);
            $API->write('=radius-mac-mode='.$radiusMacMode,false);
            $API->write('=radius-mac-caching='.$radiusMacCaching,false);
            $API->write('=eap-methods='.$eapMethods,false);
            $API->write('=tls-mode='.$tlsMode,false);
            $API->write('=tls-certificate='.$tlsCertificate,false);
            $API->write('=static-algo-0='.$staticAlgo0,false);
            $API->write('=static-key-0='.$staticKey0,false);
            $API->write('=static-algo-1='.$staticAlgo1,false);
            $API->write('=static-key-1='.$staticKey1,false);
            $API->write('=static-algo-2='.$staticAlgo2,false);
            $API->write('=static-key-2='.$staticKey2,false);
            $API->write('=static-algo-3='.$staticAlgo3,false);
            $API->write('=static-key-3='.$staticKey3,false);
            $API->write('=static-transmit-key='.$staticTransmitKey,false);	
            $API->write('=static-sta-private-algo='.$staticStaPrivateAlgo,false);
            $API->write('=static-sta-private-key='.$staticStaPrivateKey,false);
            $API->write('=mschapv2-username='.$mschapv2Username,false);
            $API->write('=mschapv2-password='.$mschapv2Password,true);
            //$API->write('=default='.$default,true);
            $READ = $API->read(false);

            echo "output: " . $READ[67][8];

            $ARRAY = $API->ParseResponse($READ);

            print "<pre>A interface Security Profile `{$name}` foi editada no servidor de IP: `{$ip}` com sucesso.</pre>";
            
        } else {
            
            // data insert
            $API->write("/interface/wireless/security-profiles/add",false);
            $API->write('=name='.$name,false);
            $API->write('=mode='.$mode,false);
            $API->write('=authentication-types='.$authenticationTypes,false);
            $API->write('=unicast-ciphers='.$unicastCiphers,false);
            $API->write('=group-ciphers='.$groupCiphers,false);
            $API->write('=wpa-pre-shared-key='.$wpaPreSharedKey,false);
            $API->write('=wpa2-pre-shared-key='.$wpa2PreSharedKey,false);
            $API->write('=supplicant-identity='.$supplicantIdentity,false);
            $API->write('=group-key-update='.$groupKeyUpdate,false);
            $API->write('=management-protection='.$managementProtection,false);
            $API->write('=management-protection-key='.$managementProtectionKey,false);
            $API->write('=radius-mac-authentication='.$radiusMacAuthentication,false);
            $API->write('=radius-mac-accounting='.$radiusMacAccounting,false);
            $API->write('=radius-eap-accounting='.$radiusEapAccounting,false);
            $API->write('=interim-update='.$interimUpdate,false);
            $API->write('=radius-mac-format='.$radiusMacFormat,false);
            $API->write('=radius-mac-mode='.$radiusMacMode,false);
            $API->write('=radius-mac-caching='.$radiusMacCaching,false);
            $API->write('=eap-methods='.$eapMethods,false);
            $API->write('=tls-mode='.$tlsMode,false);
            $API->write('=tls-certificate='.$tlsCertificate,false);
            $API->write('=static-algo-0='.$staticAlgo0,false);
            $API->write('=static-key-0='.$staticKey0,false);
            $API->write('=static-algo-1='.$staticAlgo1,false);
            $API->write('=static-key-1='.$staticKey1,false);
            $API->write('=static-algo-2='.$staticAlgo2,false);
            $API->write('=static-key-2='.$staticKey2,false);
            $API->write('=static-algo-3='.$staticAlgo3,false);
            $API->write('=static-key-3='.$staticKey3,false);
            $API->write('=static-transmit-key='.$staticTransmitKey,false);
            $API->write('=static-sta-private-algo='.$staticStaPrivateAlgo,false);
            $API->write('=static-sta-private-key='.$staticStaPrivateKey,false);
            $API->write('=mschapv2-username='.$mschapv2Username,false);
            $API->write('=mschapv2-password='.$mschapv2Password,true);
            //$API->write('=default='.$default,true);
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>A interface Security Profile `{$name}` foi inserida no servidor de IP: `{$ip}` com sucesso.</pre>";
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
        print "The method `{$_SERVER['REQUEST_METHOD']}` is not allowed here.";
    }
    

    
    /*******************************************************************************
     * consulta todos os security profiles pelo nome do mesmo.
     * use esse bloco para visualizar os dados.
     ******************************************************************************/
    $API->write("/interface/wireless/security-profiles/getall",false);
    $API->write('?name='.$name,true);
    $READ = $API->read(false);
    $ARRAY = $API->ParseResponse($READ);
    print "<pre>"; print_r($ARRAY); print "</pre>";
?>



<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    $('input[type=checkbox].one').change(function(){
        if($('input[type=checkbox].one:checked').size() == 2){
            $("#show-supplicant-identity").show("slow");
            $("#show-eap-block").show("slow");
        } else if($('input[type=checkbox].one:checked').size() == 1) {
            $("#show-supplicant-identity").show("slow");
            $("#show-eap-block").show("slow");
        } else if($('input[type=checkbox].one:checked').size() == 0) {
            $("#show-supplicant-identity").fadeToggle("slow");
            $("#show-eap-block").fadeToggle("slow");
        } else {}
    });
    // onload
    if($('input[type=checkbox].one:checked').size() == 0){
        $("#show-supplicant-identity").hide("slow");
    }
    $('input[type=checkbox].two').change(function(){
        if($('input[type=checkbox].two:checked').size() == 1){
            $("#show-wpa-pre-shared-key").show("slow");
        } else {
            $("#show-wpa-pre-shared-key").fadeToggle("slow");
        }
    });
    // onload
    if($('input[type=checkbox].two:checked').size() == 0){
        $("#show-wpa-pre-shared-key").hide("slow");
    }
    $('input[type=checkbox].three').change(function(){
        if($('input[type=checkbox].three:checked').size() == 1){
            $("#show-wpa2-pre-shared-key").show("slow");
        } else {
            $("#show-wpa2-pre-shared-key").hide("slow");
        }
    });
    // onload
    if($('input[type=checkbox].three:checked').size() == 0){
        $("#show-wpa2-pre-shared-key").hide("slow");
    }
    $(".four").change(function() {
        if ($(this).val() == "disabled") {
            $("#show-management-protection-key").fadeToggle("slow");
        } else {
            $("#show-management-protection-key").show("slow");
        }
    });
    // define initial state in the form - `mode` input 
    var element = document.getElementById("show-mode");
    var op = element.options[element.selectedIndex].value;
    if (op == "none") {
        $("#show-static-key").fadeToggle("slow");
        $("#hide-block-mode").fadeToggle("slow");
    } else if (op == "static-keys-optional" || op == "static-keys-required") {
        $("#hide-block-mode").fadeToggle("slow");
        $("#show-static-key").show("slow");
    } else if (op == "dynamic-keys") { 
        $("#show-static-key").fadeToggle("slow");
        $("#hide-block-mode").show("slow");
    } else {}
    // on change event via mode option  --- OBS fadeToggle as vezes NAO funciona, nesse caso usar hide
    $(".five").change(function() {
        if ($(this).val() == "dynamic-keys") {
            $("#hide-block-mode").show("slow");
            $("#show-static-key").hide("slow");
        } else if ($(this).val() == "none") {
            $("#hide-block-mode").fadeToggle("slow");
            $("#show-static-key").hide("slow");
        } else if ($(this).val() == "static-keys-optional" || $(this).val() == "static-keys-required") {
            $("#hide-block-mode").hide("slow");
            $("#show-static-key").show("slow");
        } else {
            //$("#show-static-key").show("slow");
        }
    });
});
</script>



<?php 
/*******************************************************************************
 * pegar todos os certificados dispoiveis para usar em EAP -> tls-certifcates
 ******************************************************************************/
$ARRAY_CERT = $API->comm('/certificate/getall');
foreach ($ARRAY_CERT as $key => $value) {
    if ($ARRAY['0']['tls-certificate'] == $value['name']) $selec = "selected"; else $selec = "";
    $opt_eap_tls_cert = '<option value="'.$value['name'].'" '.$selec.'>'.$value['name'].'</option>';
}



if (count($ARRAY) > 0) { /* se houver resposta update form */ ?>

    <!-- esse form serve como exemplo apenas -->
    <form name="form1" method="post" action="">
        
        <input type="hidden" name="id_server" value="<?php echo $id_servidor; ?>">
        <input type="hidden" name="default" value="<?php echo $ARRAY['0']['default']; ?>">
        <input type="hidden" name="mschapv2_username" value="<?php echo $ARRAY['0']['mschapv2-username']; ?>"> <!-- campo esta relacionado com a opcao `eap-methods` eap-ttls-mschapv2 -->
        <input type="hidden" name="mschapv2_password" value="<?php echo $ARRAY['0']['mschapv2-password']; ?>"> <!-- campo esta relacionado com a opcao `eap-methods` eap-ttls-mschapv2 -->

        <fieldset>
            <legend>GENERAL</legend>
            name
            <input type="text" name="name" value="<?php echo $ARRAY[0]['name']; ?>"><br>
            mode
            <select name="mode" class="five" id="show-mode">
                <option value="dynamic-keys" <?php if ($ARRAY['0']['mode'] == "dynamic-keys") echo "selected"; else echo ""; ?> >dynamic-keys</option>
                <option value="static-keys-optional" <?php if ($ARRAY['0']['mode'] == "static-keys-optional") echo "selected"; else echo ""; ?> >static-keys-optional</option>
                <option value="static-keys-required" <?php if ($ARRAY['0']['mode'] == "static-keys-required") echo "selected"; else echo ""; ?> >static-keys-required</option>
                <option value="none" <?php if ($ARRAY['0']['mode'] == "none") echo "selected"; else echo ""; ?> >none</option>
            </select><br>

            <div id="hide-block-mode" >
                authentication-types
                <input type="checkbox" value="wpa-psk" name="wpa_psk" id="show_wpa_psk" class="two" <?php if (strpos($ARRAY['0']['authentication-types'], "wpa-psk") !== FALSE) echo "checked"; else echo ""; ?> >wpa-psk
                <input type="checkbox" value="wpa2-psk" name="wpa2_psk" id="show_wpa2_psk" class="three" <?php if (strpos($ARRAY['0']['authentication-types'], "wpa2-psk") !== FALSE) echo "checked"; else echo ""; ?> >wpa2-psk
                <input type="checkbox" value="wpa-eap" name="wpa_eap" id="show_wpa_eap" class="one" <?php if (strpos($ARRAY['0']['authentication-types'], "wpa-eap") !== FALSE) echo "checked"; else echo ""; ?> >wpa-eap
                <input type="checkbox" value="wpa2-eap" name="wpa2_eap" id="show_wpa2_eap" class="one" <?php if (strpos($ARRAY['0']['authentication-types'], "wpa2-eap") !== FALSE) echo "checked"; else echo ""; ?> >wpa2-eap <br>
                unicast-ciphers
                <input type="checkbox" value="aes-ccm" name="unicast_aes_ccm" <?php if (strpos($ARRAY['0']['unicast-ciphers'], "aes-ccm") !== FALSE) echo "checked"; else echo ""; ?> >aes-ccm
                <input type="checkbox" value="tkip" name="unicast_tkip" <?php if (strpos($ARRAY['0']['unicast-ciphers'], "tkip") !== FALSE) echo "checked"; else echo ""; ?> >tkip  <br>
                group-ciphers
                <input type="checkbox" value="aes-ccm" name="group_aes_ccm" <?php if (strpos($ARRAY['0']['group-ciphers'], "aes-ccm") !== FALSE) echo "checked"; else echo ""; ?> >aes-ccm
                <input type="checkbox" value="tkip" name="group_tkip" <?php if (strpos($ARRAY['0']['group-ciphers'], "tkip") !== FALSE) echo "checked"; else echo ""; ?> >tkip <br>
                
                <div id="show-wpa-pre-shared-key">
                    wpa-pre-shared-key
                    <input type="text" name="wpa_pre_shared_key" value="<?php echo $ARRAY[0]['wpa-pre-shared-key']; ?>"><br>
                </div>

                <div id="show-wpa2-pre-shared-key" >
                    wpa2-pre-shared-key
                    <input type="text" name="wpa2_pre_shared_key" value="<?php echo $ARRAY[0]['wpa2-pre-shared-key']; ?>"><br>
                </div>

                <div id="show-supplicant-identity" >
                    supplicant-identity
                    <input type="text" name="supplicant_identity" value="<?php echo $ARRAY[0]['supplicant-identity']; ?>">
                </div>

                group-key-update ("hh:mm:ss", "5m", "60s", "1h")
                <input type="text" name="group_key_update" value="<?php if ($ARRAY[0]['group-key-update'] == "") echo "5m"; else echo $ARRAY[0]['group-key-update']; ?>"> <br>
            </div> <!-- here end the condition of `mode=none` -->

            management-protection
            <select name="management_protection" id="#mgmt-protection" class="four">
                <option value="disabled" <?php if ($ARRAY['0']['management-protection'] == "disabled") echo "selected"; else echo ""; ?> >disabled</option>
                <option value="required" <?php if ($ARRAY['0']['management-protection'] == "required") echo "selected"; else echo ""; ?> >required</option>
                <option value="allowed" <?php if ($ARRAY['0']['management-protection'] == "allowed") echo "selected"; else echo ""; ?> >allowed</option>
            </select> <br>
            <?php if ($ARRAY['0']['management-protection'] == "disabled") { ?>
                <div id="show-management-protection-key" style="display: none;">
                    management-protection-key
                    <input type="text" name="management_protection_key" value="<?php echo $ARRAY[0]['management-protection-key']; ?>"> <br>
                </div>
            <?php } else { ?>
                <div id="show-management-protection-key" style="display: block;">
                    management-protection-key
                    <input type="text" name="management_protection_key" value="<?php echo $ARRAY[0]['management-protection-key']; ?>"> <br>
                </div>
            <?php } ?>
        </fieldset>
        

        <fieldset>
            <legend>RADIUS</legend>
            <input type="checkbox" name="radius_mac_authentication" <?php if ($ARRAY['0']['radius-mac-authentication'] == "true") { echo "checked"; } else { echo ""; } ?> value="true">radius-mac-authentication
            <input type="checkbox" name="radius_mac_accounting" <?php if ($ARRAY['0']['radius-mac-accounting'] == "true") { echo "checked"; } else { echo ""; } ?> value="true">radius-mac-accounting
            <input type="checkbox" name="radius_eap_accounting" <?php if ($ARRAY['0']['radius-eap-accounting'] == "true") { echo "checked"; } else { echo ""; } ?> value="true">radius-eap-accounting
            interim-update ("hh:mm:ss", "5m", "60s", "1h")
            <input type="text" name="interim_update" value="<?php if ($ARRAY[0]['interim-update'] == "") echo "0s"; else echo $ARRAY[0]['interim-update']; ?>"> <br>
            radius-mac-format
            <select name="radius_mac_format">
                <option value="XX:XX:XX:XX:XX:XX" <?php if ($ARRAY['0']['radius-mac-format'] == "XX:XX:XX:XX:XX:XX") echo "selected"; else echo ""; ?> >XX:XX:XX:XX:XX:XX</option>
                <option value="XX XX XX XX XX XX" <?php if ($ARRAY['0']['radius-mac-format'] == "XX XX XX XX XX XX") echo "selected"; else echo ""; ?> >XX XX XX XX XX XX</option>
                <option value="XX-XX-XX-XX-XX-XX" <?php if ($ARRAY['0']['radius-mac-format'] == "XX-XX-XX-XX-XX-XX") echo "selected"; else echo ""; ?> >XX-XX-XX-XX-XX-XX</option>
                <option value="XXXX:XXXX:XXXX" <?php if ($ARRAY['0']['radius-mac-format'] == "XXXX:XXXX:XXXX") echo "selected"; else echo ""; ?> >XXXX:XXXX:XXXX</option>
                <option value="XXXXXX-XXXXXX" <?php if ($ARRAY['0']['radius-mac-format'] == "XXXXXX-XXXXXX") echo "selected"; else echo ""; ?> >XXXXXX-XXXXXX</option>
                <option value="XXXXXX:XXXXXX" <?php if ($ARRAY['0']['radius-mac-format'] == "XXXXXX:XXXXXX") echo "selected"; else echo ""; ?> >XXXXXX:XXXXXX</option>
                <option value="XXXXXXXXXXXX" <?php if ($ARRAY['0']['radius-mac-format'] == "XXXXXXXXXXXX") echo "selected"; else echo ""; ?> >XXXXXXXXXXXX</option>
            </select>
            radius-mac-mode
            <select name="radius_mac_mode">
                <option value="as-username" <?php if ($ARRAY['0']['radius-mac-mode'] == "as-username") echo "selected"; else echo ""; ?> >as-username</option>
                <option value="as-username-and-password" <?php if ($ARRAY['0']['radius-mac-mode'] == "as-username-and-password") echo "selected"; else echo ""; ?> >as-username-and-password</option>
            </select>
            radius-mac-caching
            <select name="radius_mac_caching">
                <option value="disabled" <?php if ($ARRAY['0']['radius-mac-caching'] == "disabled") echo "selected"; else echo ""; ?> >disabled</option>
                <option value="enabled" <?php if ($ARRAY['0']['radius-mac-caching'] == "enabled") echo "selected"; else echo ""; ?> >enabled</option>
            </select>
        </fieldset>
        
        
        <fieldset>
            <legend>EAP</legend>

            <?php if (strpos($ARRAY['0']['authentication-types'], "wpa-eap") !== FALSE or strpos($ARRAY['0']['authentication-types'], "wpa2-eap") !== FALSE) { ?>
                <div id="show-eap-block">
                    eap-methods
                    <select name="eap_methods">
                        <option value="" <?php if ($ARRAY['0']['eap-methods'] == "") echo "selected"; else echo ""; ?> >vazio</option>
                        <option value="passthrough" <?php if ($ARRAY['0']['eap-methods'] == "passthrough") echo "selected"; else echo ""; ?> >passthrough</option>
                        <option value="eap-tls" <?php if ($ARRAY['0']['eap-methods'] == "eap-tls") echo "selected"; else echo ""; ?> >eap-tls</option>
                        <option value="eap-ttls-mschapv2" <?php if ($ARRAY['0']['eap-methods'] == "eap-ttls-mschapv2") echo "selected"; else echo ""; ?> disabled>eap-ttls-mschapv2</option>
                    </select><br>
                    tls-mode
                    <select name="tls_mode">
                        <option value="no-certificates" <?php if ($ARRAY['0']['tls-mode'] == "no-certificates") echo "selected"; else echo ""; ?> >no-certificates</option>
                        <option value="verify-certificate" <?php if ($ARRAY['0']['tls-mode'] == "verify-certificate") echo "selected"; else echo ""; ?> >verify-certificate</option>
                        <option value="dont-verify-certificate" <?php if ($ARRAY['0']['tls-mode'] == "dont-verify-certificate") echo "selected"; else echo ""; ?> >dont-verify-certificate</option>
                    </select><br>
                    tls-certificate
                    <select name="tls_certificate">
                        <option value="none" <?php if ($ARRAY['0']['tls-certificate'] == "none") echo "selected"; else echo ""; ?> >none</option>
                        <?php echo $opt_eap_tls_cert; ?>
                    </select><br>
                </div>

            <?php } else { ?>

                <div id="show-eap-block" style="display: none;">
                    eap-methods
                    <select name="eap_methods">
                        <option value="" <?php if ($ARRAY['0']['eap-methods'] == "") echo "selected"; else echo ""; ?> >vazio</option>
                        <option value="passthrough" <?php if ($ARRAY['0']['eap-methods'] == "enabled") echo "selected"; else echo ""; ?> >passthrough</option>
                        <option value="eap-tls" <?php if ($ARRAY['0']['eap-methods'] == "disabled") echo "selected"; else echo ""; ?> >EAP-TLS</option>
                        <option value="eap-ttls-mschapv2" <?php if ($ARRAY['0']['eap-methods'] == "eap-ttls-mschapv2") echo "selected"; else echo ""; ?> disabled>eap-ttls-mschapv2</option>
                    </select><br>
                    tls-mode
                    <select name="tls_mode">
                        <option value="verify-certificate" <?php if ($ARRAY['0']['tls-mode'] == "verify-certificates") echo "selected"; else echo ""; ?> >verify-certificates</option>
                        <option value="no-certificates" <?php if ($ARRAY['0']['tls-mode'] == "no-certificates") echo "selected"; else echo ""; ?> >no-certificates</option>
                        <option value="dont-verify-certificate" <?php if ($ARRAY['0']['tls-mode'] == "do-not-verify-certificate") echo "selected"; else echo ""; ?> >do-not-verify-certificate</option>
                    </select><br>
                    tls-certificate
                    <select name="tls_certificate">
                        <option value="none" <?php if ($ARRAY['0']['tls-certificate'] == "none") echo "selected"; else echo ""; ?> >none</option>
                        <?php echo $opt_eap_tls_cert; ?>
                    </select>
                </div>

            <?php } ?>

        </fieldset>
        
        
        <fieldset>
            <legend>STATIC KEYS</legend>

                <div id="show-static-key" style="display: block;">

                    <?php for($i=0; $i<4; $i++) { ?>
                        key-<?php echo $i; ?>
                        <select name="static_algo_<?php echo $i; ?>">
                            <option value="40bit key" <?php if ($ARRAY['0']['static-algo-' . $i] == "40bit key") echo "selected"; else echo ""; ?> >40bit key</option>
                            <option value="104bit key" <?php if ($ARRAY['0']['static-algo-' . $i] == "104bit key") echo "selected"; else echo ""; ?> >104bit key</option>
                            <option value="aes-ccn" <?php if ($ARRAY['0']['static-algo-' . $i] == "aes-ccn") echo "selected"; else echo ""; ?> >aes-ccn</option>
                            <option value="none" <?php if ($ARRAY['0']['static-algo-' . $i] == "none") echo "selected"; else echo ""; ?> >none</option>
                            <option value="tkip" <?php if ($ARRAY['0']['static-algo-' . $i] == "tkip") echo "selected"; else echo ""; ?> >tkip</option>
                        </select>
                        0x
                        <!-- validacao do campo abaixo precisa ser: min 6 chars e max 256 chars [0-9] [A-F] [a-f] -->
                        <input type="text" pattern=".{0}|.{5,256}" required title="Entre 0 OU (Min. de 6 e Max. de 256 chars)" name="static_key_<?php echo $i; ?>" value="<?php echo $ARRAY['0']['static-key-' . $i]; ?>">
                        <br>
                    <?php } ?>
                    static-transmit-key
                    <select name="static_transmit_key">
                        <option value="key-1" <?php if ($ARRAY['0']['static-transmit-key'] == "key-1") echo "selected"; else echo ""; ?> >key-1</option>
                        <option value="key-2" <?php if ($ARRAY['0']['static-transmit-key'] == "key-2") echo "selected"; else echo ""; ?> >key-2</option>
                        <option value="key-3" <?php if ($ARRAY['0']['static-transmit-key'] == "key-3") echo "selected"; else echo ""; ?> >key-3</option>
                        <option value="key-0" <?php if ($ARRAY['0']['static-transmit-key'] == "key-0") echo "selected"; else echo ""; ?> >key-0</option>
                    </select>
                    static-sta-private-algo
                    <select name="static_sta_private_algo">
                        <option value="40bit key" <?php if ($ARRAY['0']['static-sta-private-algo'] == "40bit key") echo "selected"; else echo ""; ?> >40bit key</option>
                        <option value="104bit key" <?php if ($ARRAY['0']['static-sta-private-algo'] == "104bit key") echo "selected"; else echo ""; ?> >104bit key</option>
                        <option value="aes-ccn" <?php if ($ARRAY['0']['static-sta-private-algo'] == "aes-ccn") echo "selected"; else echo ""; ?> >aes-ccn</option>
                        <option value="none" <?php if ($ARRAY['0']['static-sta-private-algo'] == "none") echo "selected"; else echo ""; ?> >none</option>
                        <option value="tkip" <?php if ($ARRAY['0']['static-sta-private-algo'] == "tkip") echo "selected"; else echo ""; ?> >tkip</option>
                    </select>
                    0x
                    <!-- validacao do campo abaixo precisa ser: min 6 chars e max 256 chars [0-9] [A-F] [a-f] -->
                    <input type="text" name="static_sta_private_key" pattern=".{0}|.{5,256}" required title="Entre 0 OU (Min. de 6 e Max. de 256 chars)" value="<?php echo $ARRAY['0']['static-sta-private-key']; ?>">
                </div>
        </fieldset>
        <input type="submit" value="OK">

    </form>


<?php } else { ?>



    <!-- esse form serve como exemplo apenas -->
    <form name="form1" method="post" action="">
        
        <input type="hidden" name="id_server" value="<?php echo $id_servidor; ?>">
        <input type="hidden" name="default" value="">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="mschapv2_username" value=""> <!-- campo esta relacionado com a opcao `eap-methods` eap-ttls-mschapv2 -->
        <input type="hidden" name="mschapv2_password" value=""> <!-- campo esta relacionado com a opcao `eap-methods` eap-ttls-mschapv2 -->

        <fieldset>
            <legend>GENERAL</legend>
            name
            <input type="text" name="name" value=""><br>
            mode
            <select name="mode" class="five" id="show-mode">
                <option value="none">none</option>
                <option value="dynamic-keys">dynamic-keys</option>
                <option value="static-keys-optional">static-keys-optional</option>
                <option value="static-keys-required">static-keys-required</option>
            </select><br>

            <div id="hide-block-mode" >
                authentication-types
                <input type="checkbox" value="wpa-psk" name="wpa_psk" id="show_wpa_psk" class="two" >wpa-psk
                <input type="checkbox" value="wpa2-psk" name="wpa2_psk" id="show_wpa2_psk" class="three" >wpa2-psk
                <input type="checkbox" value="wpa-eap" name="wpa_eap" id="show_wpa_eap" class="one" >wpa-eap
                <input type="checkbox" value="wpa2-eap" name="wpa2_eap" id="show_wpa2_eap" class="one" >wpa2-eap <br>
                unicast-ciphers
                <input type="checkbox" value="aes-ccm" name="unicast_aes_ccm">aes-ccm
                <input type="checkbox" value="tkip" name="unicast_tkip" >tkip  <br>
                group-ciphers
                <input type="checkbox" value="aes-ccm" name="group_aes_ccm" >aes-ccm
                <input type="checkbox" value="tkip" name="group_tkip" >tkip <br>
                
                <div id="show-wpa-pre-shared-key">
                    wpa-pre-shared-key
                    <input type="text" name="wpa_pre_shared_key" value=""><br>
                </div>

                <div id="show-wpa2-pre-shared-key" >
                    wpa2-pre-shared-key
                    <input type="text" name="wpa2_pre_shared_key" value=""><br>
                </div>

                <div id="show-supplicant-identity" >
                    supplicant-identity
                    <input type="text" name="supplicant_identity" value="">
                </div>

                group-key-update ("hh:mm:ss", "5m", "60s", "1h")
                <input type="text" name="group_key_update" value="5m"> <br>
            </div> <!-- here end the condition of `mode=none` -->

            management-protection
            <select name="management_protection" id="#mgmt-protection" class="four">
                <option value="disabled"  >disabled</option>
                <option value="required"  >required</option>
                <option value="allowed"  >allowed</option>
            </select> <br>
            <div id="show-management-protection-key">
                management-protection-key
                <input type="text" name="management_protection_key" value=""> <br>
            </div>
        </fieldset>
        

        <fieldset>
            <legend>RADIUS</legend>
            <input type="checkbox" name="radius_mac_authentication"  value="true">radius-mac-authentication
            <input type="checkbox" name="radius_mac_accounting"  value="true">radius-mac-accounting
            <input type="checkbox" name="radius_eap_accounting"  value="true">radius-eap-accounting
            interim-update ("hh:mm:ss", "5m", "60s", "1h")
            <input type="text" name="interim_update" value="0s"> <br>
            radius-mac-format
            <select name="radius_mac_format">
                <option value="XX:XX:XX:XX:XX:XX" >XX:XX:XX:XX:XX:XX</option>
                <option value="XX XX XX XX XX XX" >XX XX XX XX XX XX</option>
                <option value="XX-XX-XX-XX-XX-XX" >XX-XX-XX-XX-XX-XX</option>
                <option value="XXXX:XXXX:XXXX" >XXXX:XXXX:XXXX</option>
                <option value="XXXXXX-XXXXXX" >XXXXXX-XXXXXX</option>
                <option value="XXXXXX:XXXXXX" >XXXXXX:XXXXXX</option>
                <option value="XXXXXXXXXXXX" >XXXXXXXXXXXX</option>
            </select>
            radius-mac-mode
            <select name="radius_mac_mode">
                <option value="as-username" >as-username</option>
                <option value="as-username-and-password" >as-username-and-password</option>
            </select>
            radius-mac-caching
            <select name="radius_mac_caching">
                <option value="disabled" >disabled</option>
                <option value="enabled" >enabled</option>
            </select>
        </fieldset>
        
        
        <fieldset>
            <legend>EAP</legend>
                <div id="show-eap-block" style="display: none;">
                    eap-methods
                    <select name="eap_methods">
                        <option value="" >Vazio</option>
                        <option value="passthrough" >passthrough</option>
                        <option value="eap-tls" >eap-tls</option>
                        <option value="eap-ttls-mschapv2" disabled>eap-ttls-mschapv2</option>
                    </select><br>
                    tls-mode
                    <select name="tls_mode">
                        <option value="no-certificates"  >no-certificates</option>
                        <option value="verify-certificate"  >verify-certificate</option>
                        <option value="dont-verify-certificate" >dont-verify-certificate</option>
                    </select><br>
                    tls-certificate
                    <select name="tls_certificate">
                        <option value="none">none</option>
                        <?php echo $opt_eap_tls_cert; ?>
                    </select>
                </div>
        </fieldset>
        
        
        <fieldset>
            <legend>STATIC KEYS</legend>
            <div id="show-static-key" style="display: block;">
                <?php for($i=0; $i<4; $i++) { ?>
                    key-<?php echo $i; ?>
                    <select name="static_algo_<?php echo $i; ?>">
                        <option value="none">none</option>
                        <option value="40bit key" >40bit key</option>
                        <option value="104bit key"  >104bit key</option>
                        <option value="aes-ccn"  >aes-ccn</option>
                        <option value="tkip"  >tkip</option>
                    </select>
                    0x
                    <!-- validacao do campo abaixo precisa ser: min 6 chars e max 256 chars [0-9] [A-F] [a-f] -->
                    <input type="text" name="static_key_<?php echo $i; ?>" value="">
                    <br>
                <?php } ?>
                static-transmit-key
                <select name="static_transmit_key">
                    <option value="key-1"  >key-1</option>
                    <option value="key-2"  >key-2</option>
                    <option value="key-3"  >key-3</option>
                    <option value="key-0"  >key-0</option>
                </select>
                static-sta-private-algo
                <select name="static_sta_private_algo">
                    <option value="none">none</option>
                    <option value="40bit key" >40bit key</option>
                    <option value="104bit key"  >104bit key</option>
                    <option value="aes-ccn"  >aes-ccn</option>
                    <option value="tkip" >tkip</option>
                </select>
                0x
                <!-- validacao do campo abaixo precisa ser: min 6 chars e max 256 chars [0-9] [A-F] [a-f] -->
                <input type="text" name="static_sta_private_key" value="">
            </div>
        </fieldset>
        <input type="submit" value="OK">

    </form>

<?php } ?>

<?php } // [endif] ?>
