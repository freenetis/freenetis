<?php
/*
 * This file is part of open source system FreenetIS
 * and it is released under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * Enables self canceling messages. Written in pure PHP due to performance reasons.
 * @author Jiri Svitak
 *
 */

// loading to access database password
define('SYSPATH', str_replace('\\', '/', realpath('system')).'/');
require '../config.php';
// connect to database
$link = mysqli_connect($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']) or die(mysqli_error($link));
mysqli_query( $link ,"SET CHARACTER SET utf8") or die(mysqli_error($link));
mysqli_query($link, "SET NAMES utf8") or die(mysqli_error($link));
mysqli_select_db($link, $config['db_name']) or die(mysqli_error($link));
// obtain remote ip address
$ip_address = $_SERVER['REMOTE_ADDR'];

$redirect_to = "";
if (isset($_GET['redirect_to']))
{
    $redirect_to = $_GET['redirect_to'];
	
	// if empty then google
	if (trim($redirect_to) == '')
	{
		$redirect_to = 'http://www.google.com';
	}
	
	// split url to segments
	$url_segments = explode("://", $redirect_to);
	
	// test if first segment is protocol
	if ($url_segments[0] != 'http' &&
		$url_segments[0] != 'https' &&
		$url_segments[0] != 'ftp')
	{
		// add http to url
		$redirect_to = 'http://'.$redirect_to;
	}
}

// content of redirection message
$message_query = "
	SELECT message_id, m.text, m.self_cancel, m.ip_address, subnet_name,
		members.name AS member_name, members.id AS member_id,
		(
			SELECT GROUP_CONCAT(vs.variable_symbol) AS variable_symbol
			FROM variable_symbols vs
			LEFT JOIN accounts a ON a.id = vs.account_id
			WHERE a.member_id = members.id
		) AS variable_symbol,
		a.balance, m.comment, ip_address_id
	FROM
	(
		SELECT m.id,message_id,text,self_cancel,ip_address,
		subnet_name, m.comment, IFNULL(m.member_id,u.member_id) AS member_id,
		datetime, ip_address_id
		FROM
		(
			SELECT m.id, m.id AS message_id, m.text, m.self_cancel, ip.ip_address,
				s.name AS subnet_name, mip.comment, ip.member_id,
				ip.iface_id AS iface_id, mip.datetime, ip.id AS ip_address_id
			FROM messages m
			JOIN messages_ip_addresses mip ON m.id = mip.message_id
			JOIN ip_addresses ip ON ip.id = mip.ip_address_id
			JOIN subnets s ON s.id = ip.subnet_id
		) m
		LEFT JOIN ifaces i ON i.id = m.iface_id
		LEFT JOIN devices d ON d.id = i.device_id
		LEFT JOIN users u ON u.id = d.user_id
	) m
	JOIN members ON members.id = m.member_id
	LEFT JOIN accounts a ON a.member_id = m.id AND m.id <> 1
	WHERE m.ip_address = '$ip_address'
	ORDER BY m.self_cancel DESC, m.datetime ASC
	LIMIT 1";
$message_result = mysqli_query($link, $message_query) or die(mysqli_error($link));
$message = mysqli_fetch_array($message_result);


// no redirection found - perhaps visiting this page by mistake?
if ($message && count($message) > 0)
{
	// cannot be canceled
	if ($message['self_cancel'] == 0)
	{
		echo 'This redirection cannot be canceled by user himself.';
		die();
	}
	// canceling of redirection
	if ($message['self_cancel'] == 1)
	{
		// gets ip addresses and redirection of member
		$ip_query = "SELECT ip.id AS ip_address_id, ip.ip_address,
					m.id AS message_id, m.name AS message, m.type,
					".$message['member_id']." AS member_id
				FROM ip_addresses ip
				LEFT JOIN ifaces i ON ip.iface_id = i.id
				LEFT JOIN devices d ON i.device_id = d.id
				LEFT JOIN users u ON d.user_id = u.id
				LEFT JOIN messages_ip_addresses mip ON mip.ip_address_id = ip.id
				LEFT JOIN messages m ON m.id = mip.message_id
				WHERE u.member_id = ".$message['member_id']." OR ip.member_id = ".$message['member_id'];
		$ip_result = mysqli_query($link, $ip_query);
		$ip_id_array = array();
		while($item = mysqli_fetch_array($ip_result))
		{
			$ip_id_array[] = $item['ip_address_id'];
		}
		$d_query = "DELETE FROM messages_ip_addresses WHERE ip_address_id IN (".implode(",",$ip_id_array).")
			AND message_id = ".$message['message_id'];
		mysqli_query($link, $d_query);
	}
	else
	{
		$d_query = "DELETE FROM messages_ip_addresses WHERE ip_address_id = ".$message['ip_address_id'].
			" AND message_id = ".$message['message_id'];
		mysqli_query($link, $d_query);
	}
}
// message after redirection
$message_query = "SELECT * FROM messages WHERE type = 2";
$message_result = mysqli_query($link, $message_query) or die(mysqli_error($link));
$message = mysqli_fetch_array($message_result);
$content = $message['text'];

if ($redirect_to != '')
	$content = str_replace('{request_url}', $redirect_to, $content);

// redirection logo url
$suffix_query = "SELECT name, value FROM config WHERE name = 'suffix'";
$suffix_result = mysqli_query($link, $suffix_query) or die(mysqli_error($link));
$suffix_array = mysqli_fetch_array($suffix_result);
$logo = '';
if ($suffix_array &&
	isset($suffix_array['value']))
{
	$logo = $suffix_array['value'].'redirect/logo';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php // useful settings for expiration prevent caching of this website ?>
<meta http-equiv="Expires" content="0" />
<meta http-equiv="Cache-Control" content="No-Cache" />
<?php if (!empty($redirect_to)): ?>
<meta http-equiv="Refresh" content="5; url=<?php echo $redirect_to ?>" />
<?php endif; ?>
<title>FreenetIS</title>
<link href="../media/images/favicon.ico" rel="shorcut icon" type="image/x-icon" />
<link href="../media/css/style.css" rel="stylesheet" type="text/css" />
<style type="text/css">
#content-padd h2 {margin: 10px 0px;}
#content-padd h3 {margin: 10px 0px;}
#content-padd li {margin-left: 20px;}
#content-padd a {font-weight: bold;}
td {width: 100px;}
li {
	list-style-type: none;
}
</style>
</head>
<body>
<div style="position:relative;width:1000px;margin:auto;">
 	<div id="header">
		<h1 style="position:absolute;
			top:24px;
			left:18px;
			background:url(<?php echo $logo ?>);
			width:212px;
			height:49px;
			background-repeat:no-repeat;
		"></h1>
		<div class="status">

		</div>
		<div class="map"></div>
	</div>
	<div style="margin-top:10px;">
		<?php echo $content; ?>
	</div>
</div>
<span style="display:none;"><?php echo number_format(memory_get_usage() / 1024 / 1024, 2).' MB'; ?></span>

<?php
$gateway_result = mysqli_query($link, "SELECT name, value FROM config WHERE name = 'gateway'");
$gateway = mysqli_fetch_array($gateway_result);

$port_self_cancel_result = mysqli_query($link, "SELECT name, value FROM config WHERE name = 'redirection_port_self_cancel'");
$port_self_cancel = mysqli_fetch_array($port_self_cancel_result);

$port_self_cancel = ($port_self_cancel['value']!='') ? $port_self_cancel['value'] : 80;

?>
<img style="display: none" src="http://<?php echo $gateway['value'].":".$port_self_cancel."/".rand() ?>.jpg">
</body>
</html>