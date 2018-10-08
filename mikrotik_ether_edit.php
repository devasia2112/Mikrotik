<?php
/**
 * Mikrotik ETHER
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
* `http://localhost/mikrotik_ether_edit.php?server=4&default_name=NOMETHER`
*
*/



/*******************************************************************************
 * Chamada da conexao. Esses dados precisam vir do cadastro do servidor
 * Precisa fazer a consulta baseado no parametro passado aqui via $_GET[server]
 ******************************************************************************/
$defaultName = $_GET['default_name'];  //nome padrao da ether 
$serverId    = $_GET['server'];



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
        
        // filter POST data before input
        $defaultName         = filter_var($_POST['default_name'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $name                = filter_var($_POST['name'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $type                = filter_var($_POST['type'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $mtu                 = filter_var($_POST['mtu'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $l2mtu               = filter_var($_POST['l2_mtu'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $maxl2mtu            = filter_var($_POST['max_l2_mtu'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $mac_address         = filter_var($_POST['mac_address'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $arp                 = filter_var($_POST['arp'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $masterPort          = filter_var($_POST['master_port'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        
        // bandwidth
        $bandwidth_rx        = filter_var($_POST['bandwidth_rx'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $bandwidth_tx        = filter_var($_POST['bandwidth_tx'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $bandwidth           = $bandwidth_rx . "/" . $bandwidth_tx;
        
        $switch              = filter_var($_POST['switch'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $autoNegotiation     = filter_var($_POST['auto_negotiation'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        
        // advertise field
        $advertise_10_half   = filter_var($_POST['advertise_10_half'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $advertise_100_half  = filter_var($_POST['advertise_100_half'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $advertise_1000_half = filter_var($_POST['advertise_1000_half'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $advertise_10_full   = filter_var($_POST['advertise_10_full'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $advertise_100_full  = filter_var($_POST['advertise_100_full'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $advertise_1000_full = filter_var($_POST['advertise_1000_full'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $advertise           = $advertise_10_half . "," . $advertise_100_half . "," . $advertise_1000_half . "," . $advertise_10_full . "," . $advertise_100_full . "," . $advertise_1000_full;
        $advertise           = rtrim($advertise, ',');

        $speed               = filter_var($_POST['speeds'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $fullDuplex          = filter_var($_POST['full_duplex'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $comment             = filter_var($_POST['comment'] ,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        
        
        /*******************************************************************************
        * Update data 
        *******************************************************************************/
        $API->write("/interface/ethernet/getall",false);
        $API->write('?default-name='.$defaultName,true);
        $READ  = $API->read(false);
        $ARRAY = $API->ParseResponse($READ);
        
        if (count($ARRAY) > 0) {
            
            $API->write("/interface/ethernet/set",false);
            $API->write("=.id=".$ARRAY[0]['.id'],false);
            // dados vindos do form via POST
            $API->write('=name='.$name,false);
            $API->write('=mtu='.$mtu,false);
            $API->write('=l2mtu='.$l2mtu,false);
            $API->write('=mac-address='.$mac_address,false);
            $API->write('=arp='.$arp,false);
            $API->write('=auto-negotiation='.$autoNegotiation,false);
            $API->write('=advertise='.$advertise,false);
            $API->write('=full-duplex='.$fullDuplex,false);
            $API->write('=speed='.$speed,false);
            $API->write('=master-port='.$masterPort,false);
            $API->write('=bandwidth='.$bandwidth,false);
            //$API->write('=switch='.$switch,false);
            $API->write('=comment='.$comment,true);
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>A interface física `{$defaultName}` de nome `{$name}` foi editada no servidor `{$ip}` com sucesso.</pre>";

        } else {
            
            $READ = $API->read(false);
            $ARRAY = $API->ParseResponse($READ);
            print "<pre>Houve uma falha e a interface `{$name}` não foi editada em `{$ServerIP}`.</pre>";
            
        }
        // desconecta da api
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

    /*
    -= uncomment the block below to test the server's response =-

    $API->write("/interface/ethernet/getall",false);
    $API->write('?default-name='.$defaultName,true);
    $READ  = $API->read(false);
    $ARRAY = $API->ParseResponse($READ);
    print "<pre>"; print_r($ARRAY); print "</pre>";
    */
}
?>


<!-- esse form foi disponibilizado apenas como modelo -->
<form name="form1" method="post" action="">

    <input type="hidden" name="id_server" value="<?php echo $serverId; ?>">
    <input type="hidden" name="default_name" value="<?php echo $defaultName; ?>">

    <fieldset>
        <legend>General</legend>
        
        <div style="display:table; width:600px; margin:0 auto; margin-top:0px;">
            <div style="display:table-row">
                <div style="width:50%">Name</div>
                <div style="width:50%"><input type="text" name="name" value="<?php echo $ARRAY[0][name]; ?>"></div>
            </div>
            <div style="display:table-row">
                <div style="width:50%">Type</div>
                <div style="width:50%"><input type="text" name="type" value="Ethernet" readonly></div>
            </div>
            <div style="display:table-row">
                <div style="width:50%">MTU</div>
                <div style="width:50%"><input type="text" name="mtu" value="<?php echo $ARRAY[0][mtu]; ?>"></div>
            </div>
            <div style="display:table-row">
                <div style="width:50%">L2 MTU</div>
                <div style="width:50%"><input type="text" name="l2_mtu" value="<?php echo $ARRAY[0][l2mtu]; ?>" readonly></div>
            </div>
            <div style="display:table-row">
                <div style="width:50%">Max. L2 MTU</div>
                <div style="width:50%"><input type="text" name="max_l2_mtu" value="" readonly></div>
            </div>
            <div style="display:table-row">
                <div style="width:50%">MAC Address</div>
                <div style="width:50%"><input type="text" name="mac_address" value="<?php echo $ARRAY['0']['mac-address']; ?>" readonly></div>
            </div>
            <div style="display:table-row">
                <div style="width:50%">ARP</div>
                <div style="width:50%">
                    <select name="arp">
                        <option value="disabled" <?php if ($ARRAY['0']['arp'] == "disabled") echo "selected"; else echo ""; ?> >disabled</option>
                        <option value="enabled" <?php if ($ARRAY['0']['arp'] == "enabled") echo "selected"; else echo ""; ?> >enabled</option>
                        <option value="reply-only" <?php if ($ARRAY['0']['arp'] == "reply-only") echo "selected"; else echo ""; ?> >reply-only</option>
                        <option value="proxy-arp" <?php if ($ARRAY['0']['arp'] == "proxy-arp") echo "selected"; else echo ""; ?> >proxy-arp</option>
                    </select>
                </div>
            </div>
            <div style="display:table-row">
                <div style="width:50%">Master Port</div> <!-- via comando -> `/interface/getall` -->
                <div style="width:50%">
                    <select name="master_port">
                        <option value="none">none</option>
                    </select>
                </div>
            </div>
            <div style="display:table-row">
                <div style="width:50%">Bandwidth (Rx/Tx)</div>
                <div style="width:50%">
                    <?php $xp = explode("/", $ARRAY['0'][bandwidth]); ?>
                    <select name="bandwidth_rx">
                        <option value="64K" <?php if ($xp[0] == "64K") echo "selected"; else echo ""; ?> >64K</option>
                        <option value="128K" <?php if ($xp[0] == "128K") echo "selected"; else echo ""; ?> >128K</option>
                        <option value="256K" <?php if ($xp[0] == "256K") echo "selected"; else echo ""; ?> >256K</option>
                        <option value="512K" <?php if ($xp[0] == "512K") echo "selected"; else echo ""; ?> >512K</option>
                        <option value="1M" <?php if ($xp[0] == "1M") echo "selected"; else echo ""; ?> >1M</option>
                        <option value="2M" <?php if ($xp[0] == "2M") echo "selected"; else echo ""; ?> >2M</option>
                        <option value="4M" <?php if ($xp[0] == "4M") echo "selected"; else echo ""; ?> >4M</option>
                        <option value="5M" <?php if ($xp[0] == "5M") echo "selected"; else echo ""; ?> >5M</option>
                        <option value="8M" <?php if ($xp[0] == "8M") echo "selected"; else echo ""; ?> >8M</option>
                        <option value="10M" <?php if ($xp[0] == "10M") echo "selected"; else echo ""; ?> >10M</option>
                        <option value="unlimited" <?php if ($xp[0] == "unlimited") echo "selected"; else echo ""; ?> >unlimited</option>
                    </select>
                    <select name="bandwidth_tx">
                        <option value="64K" <?php if ($xp[1] == "64K") echo "selected"; else echo ""; ?> >64K</option>
                        <option value="128K" <?php if ($xp[1] == "128K") echo "selected"; else echo ""; ?> >128K</option>
                        <option value="256K" <?php if ($xp[1] == "256K") echo "selected"; else echo ""; ?> >256K</option>
                        <option value="512K" <?php if ($xp[1] == "512K") echo "selected"; else echo ""; ?> >512K</option>
                        <option value="1M" <?php if ($xp[1] == "1M") echo "selected"; else echo ""; ?> >1M</option>
                        <option value="2M" <?php if ($xp[1] == "2M") echo "selected"; else echo ""; ?> >2M</option>
                        <option value="4M" <?php if ($xp[1] == "4M") echo "selected"; else echo ""; ?> >4M</option>
                        <option value="5M" <?php if ($xp[1] == "5M") echo "selected"; else echo ""; ?> >5M</option>
                        <option value="8M" <?php if ($xp[1] == "8M") echo "selected"; else echo ""; ?> >8M</option>
                        <option value="10M" <?php if ($xp[1] == "10M") echo "selected"; else echo ""; ?> >10M</option>
                        <option value="unlimited" <?php if ($xp[1] == "unlimited") echo "selected"; else echo ""; ?> >unlimited</option>
                    </select>
                </div>
            </div>
            <div style="display:table-row">
                <div style="width:50%">Switch</div>
                <div style="width:50%"><input type="text" name="switch" value="<?php echo $ARRAY['0']['switch']; ?>" readonly></div>
            </div>
            <div style="display:table-row">
                <div style="width:50%">comment</div>
                <div style="width:50%"><input type="text" name="comment" value="<?php echo $ARRAY['0']['comment']; ?>" ></div>
            </div>
        </div>
        
    </fieldset>
    
    <fieldset>
        <legend>Ethernet</legend>
        
        <!-- advertise -->
        <?php
        if (strpos($ARRAY['0']['advertise'], "10M-half") !== FALSE) $advert_chk_10h = "checked"; else $advert_chk_10h = "";
        if (strpos($ARRAY['0']['advertise'], "100M-half") !== FALSE) $advert_chk_100h = "checked"; else $advert_chk_100h = "";
        if (strpos($ARRAY['0']['advertise'], "1000M-half") !== FALSE) $advert_chk_1000h = "checked"; else $advert_chk_1000h = "";
        if (strpos($ARRAY['0']['advertise'], "10M-full") !== FALSE) $advert_chk_10f = "checked"; else $advert_chk_10f = "";
        if (strpos($ARRAY['0']['advertise'], "100M-full") !== FALSE) $advert_chk_100f = "checked"; else $advert_chk_100f = "";
        if (strpos($ARRAY['0']['advertise'], "1000M-full") !== FALSE) $advert_chk_1000f = "checked"; else $advert_chk_1000f = "";
        ?>
        
        <?php if ($ARRAY['0']['auto-negotiation'] == TRUE) { $checked = "checked"; ?>
            
            <input type="checkbox" name="auto_negotiation" value="true" onclick="mostra()" <?php echo $checked; ?> > Auto Negotiation <br><br>
            <div id="show_auto_negotiation" style="display: block;">
                Advertise &nbsp;&nbsp;
                <input type="checkbox" value="10M-half" name="advertise_10_half" <?php echo $advert_chk_10h; ?> >10M half
                <input type="checkbox" value="100M-half" name="advertise_100_half" <?php echo $advert_chk_100h; ?> >100M half
                <input type="checkbox" value="1000M-half" name="advertise_1000_half" <?php echo $advert_chk_1000h; ?> >1000M half
                <input type="checkbox" value="10M-full" name="advertise_10_full" <?php echo $advert_chk_10f; ?> >10M full
                <input type="checkbox" value="100M-full" name="advertise_100_full" <?php echo $advert_chk_100f; ?> >100M full
                <input type="checkbox" value="1000M-full" name="advertise_1000_full" <?php echo $advert_chk_1000f; ?> >1000M full
            </div>
            
            
        <?php } ?>
        
        
        <!-- speeds radio -->
        <?php
        if ($ARRAY['0']['speed'] == "10Mbps")
            $speeds1 = "checked";
        elseif ($ARRAY['0']['speed'] == "100Mbps")
            $speeds2 = "checked";
        elseif ($ARRAY['0']['speed'] == "1Gbps")
            $speeds3 = "checked";
        elseif ($ARRAY['0']['speed'] == "10Gbps")
            $speeds4 = "checked";
        else
            $speeds = "";
        ?>
        
        
        <!-- full duplex checkbox -->
        <?php if ($ARRAY['0']['full-duplex'] == TRUE) { $full_duplex = "checked"; $full_duplex_val = "true"; } else { $full_duplex = ""; $full_duplex_val = "false"; } ?>
        
        
        <?php if ($ARRAY['0']['auto-negotiation'] == FALSE) { $checked = "checked"; ?>
            
            <input type="checkbox" name="auto_negotiation" value="false" onclick="mostra()" <?php echo $checked; ?> > Auto Negotiation <br><br>
            <div id="show_auto_negotiation2" style="display: block;">
                Speed &nbsp;&nbsp; 
                <input type="radio" name="speeds" value="10Mbps" <?php echo $speeds1; ?> >10Mbps
                <input type="radio" name="speeds" value="100Mbps" <?php echo $speeds2; ?> >100Mbps
                <input type="radio" name="speeds" value="1Gbps" <?php echo $speeds3; ?> >1Gbps
                <input type="radio" name="speeds" value="10Gbps" <?php echo $speeds4; ?> >10Gbps  &nbsp;&nbsp; - &nbsp;&nbsp;
                <input type="checkbox" name="full_duplex" value="<?php echo $full_duplex_val; ?>" <?php echo $full_duplex; ?> > Full Duplex
            </div>
            
        <?php } ?>
        
        
        <div id="show_auto_negotiation2" style="display: none;">
            Speed &nbsp;&nbsp; 
            <input type="radio" name="speeds" value="10Mbps" <?php echo $speeds1; ?> >10Mbps
            <input type="radio" name="speeds" value="100Mbps" <?php echo $speeds2; ?> >100Mbps
            <input type="radio" name="speeds" value="1Gbps" <?php echo $speeds3; ?> >1Gbps
            <input type="radio" name="speeds" value="10Gbps" <?php echo $speeds4; ?> >10Gbps  &nbsp;&nbsp; - &nbsp;&nbsp;
            <input type="checkbox" name="full_duplex" value="<?php echo $full_duplex_val; ?>" <?php echo $full_duplex; ?> > Full Duplex
        </div>
        
        
    </fieldset>
    
    <input type="submit" value="OK">
    
</form>

<script type="text/javascript">
function mostra()
{
	if (document.getElementById("show_auto_negotiation").style.display != "none"){
	    document.getElementById("show_auto_negotiation").style.display = "none";
	    document.getElementById("show_auto_negotiation2").style.display = "block";
	} else {
	    document.getElementById("show_auto_negotiation").style.display = "block";
	    document.getElementById("show_auto_negotiation2").style.display = "none";
	}
}
</script>
