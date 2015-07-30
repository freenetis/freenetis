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
 * Shows redirection page, written in pure PHP due to performance reasons,
 * it is not necessary to load whole Kohana framework.
 * @author Jiri Svitak
 */

// loading to access database password
define('SYSPATH', str_replace('\\', '/', realpath('system')).'/');
require '../config.php';
// connect to database
$link = mysql_connect($config['db_host'], $config['db_user'], $config['db_password']) or die(mysql_error());
mysql_query("SET CHARACTER SET utf8", $link) or die(mysql_error());
mysql_query("SET NAMES utf8", $link) or die(mysql_error());
mysql_select_db($config['db_name']) or die(mysql_error());

$redirect_to = "";
if (isset($_GET['redirect_to']))
    $redirect_to = $_GET['redirect_to'];

// obtain ip address
// preview of redirection can be viewed by passing GET argument
if (isset($_GET['ip_address']))
{
	$ip_address = $_GET['ip_address'];
	$get = true;
}
// otherwise it is real redirection, we have to use ip address of remote visitor
else
{
	$ip_address = $_SERVER['REMOTE_ADDR'];
}

// preview of type
if (isset($_GET['id']))
{
	$id = intval($_GET['id']);
	$get = true;
}
else
	$get = false;

// check validity of ip address
if (!preg_match ("/^((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])$/", $ip_address))
{
	echo 'Invalid IP address.';
	die();
}
// content of redirection message
if (!isset($id))
{
	$message_query = "
	SELECT ms.id, ms.text, ms.self_cancel, ip.ip_address, ip.whitelisted,
		subnet_name, mm.name AS member_name, mm.id AS member_id,
		(
			SELECT GROUP_CONCAT(vs.variable_symbol) AS variable_symbol
			FROM variable_symbols vs
			LEFT JOIN accounts a ON a.id = vs.account_id
			WHERE a.member_id = mm.id
		) AS variable_symbol,
		IFNULL(a.balance,'???') AS balance, mip.comment,
		IFNULL(ip.login, u.login) AS login
	FROM
	(
		SELECT ip.id, ip.ip_address, ip.whitelisted, s.name AS subnet_name,
			IFNULL(ip.member_id,u.member_id) AS member_id, u.login
		FROM ip_addresses ip
		LEFT JOIN ifaces i ON ip.iface_id = i.id
		LEFT JOIN subnets s ON s.id = ip.subnet_id
		LEFT JOIN devices d ON d.id = i.device_id
		LEFT JOIN users u ON u.id = d.user_id
	) ip
	JOIN members mm ON mm.id = ip.member_id
	LEFT JOIN users u ON u.member_id = mm.id AND u.type = 1
	LEFT JOIN accounts a ON a.member_id = mm.id AND mm.id <> 1
	LEFT JOIN messages_ip_addresses mip ON ip.id = mip.ip_address_id
	LEFT JOIN messages ms ON ms.id = mip.message_id
	WHERE ip.ip_address = '$ip_address'
	ORDER BY ms.self_cancel DESC, mip.datetime ASC
	LIMIT 1";
}
// prints preview for given message id
else
{
	$message_query = "
	SELECT ms.id, ms.text, ms.self_cancel, '$ip_address' AS ip_address,
		ip.whitelisted,
		IFNULL(IFNULL(subnet_name, us.name),'???') AS subnet_name,
		IFNULL(mm.name,'???') AS member_name, IFNULL(mm.id,'???') AS member_id,
		(
			SELECT IFNULL(GROUP_CONCAT(vs.variable_symbol),'???') AS variable_symbol
			FROM variable_symbols vs
			LEFT JOIN accounts a ON a.id = vs.account_id
			WHERE a.member_id = mm.id
		) AS variable_symbol,
		IFNULL(a.balance,'???') AS balance,
		IFNULL(mip.comment,'???') AS comment,
		IFNULL(IFNULL(ip.login, u.login),'???') AS login
	FROM messages ms
	LEFT JOIN
	(
		SELECT ip.id, ip.ip_address, ip.whitelisted, s.name AS subnet_name,
			IFNULL(ip.member_id,u.member_id) AS member_id, u.login
		FROM ip_addresses ip
		LEFT JOIN ifaces i ON ip.iface_id = i.id
		LEFT JOIN subnets s ON s.id = ip.subnet_id
		LEFT JOIN devices d ON d.id = i.device_id
		LEFT JOIN users u ON u.id = d.user_id
	) ip ON ip_address = '$ip_address'
	LEFT JOIN members mm ON ip.member_id = mm.id
	LEFT JOIN users u ON u.member_id = mm.id AND u.type = 1
	LEFT JOIN accounts a ON a.member_id = mm.id AND mm.id <> 1
	LEFT JOIN messages_ip_addresses mip ON ip.id = mip.ip_address_id
	LEFT JOIN subnets us
	ON inet_aton(us.netmask) & inet_aton('$ip_address') = inet_aton(us.network_address)
	WHERE ms.id = $id
	LIMIT 1";
}
$message_result = mysql_query($message_query, $link) or die(mysql_error());
$message = mysql_fetch_array($message_result);
// ip adress is found in the database
if ($message && count($message) > 0)
{
	// two options are possible - no redirection has been set, so user just sees preview
	// or it is our actual redirection which should be shown to user
	$content = $message['text'];
	// user has not send ip address through get parameter to see preview
	if (!$get && empty($content))
	{
		// typical situation: user sees message that redirection has been canceled
		// and he should wait, but he is anxious and clicks on some given link
		// but no redirection is available to him, so we redirect him again
		// on canceling page
        $request_uri = str_replace("?".$_SERVER['QUERY_STRING'],"",$_SERVER['REQUEST_URI']);
		$header = 'Location: http://'.$_SERVER['SERVER_NAME'].$request_uri.'cancel.php?redirect_to='.$redirect_to;
		//echo $header; die();
		header($header, TRUE, 302);
		die();
	}

}
// ip address has not been found in database
else
{
	// unknown device message, we assume that this message is always installed with id 3
	$message_query = "
		SELECT ms.text,
		(
			SELECT name
			FROM subnets s
			WHERE inet_aton(netmask) & inet_aton('$ip_address') = inet_aton(network_address)
		) AS subnet_name,
		'$ip_address' AS ip_address,
		'???' AS member_name,
		'???' AS member_id,
		'???' AS balance,
		'???' AS variable_symbol,
		'???' AS comment,
		'???' AS login
		FROM messages ms
		WHERE ms.type = 3";
	$message_result = mysql_query($message_query, $link) or die(mysql_error());
	$message = mysql_fetch_array($message_result);
	$content = $message['text'];
}
// text in left contact panel,
// it asssumed that after installation, there is always contact message with ID 1
$contact_query = "SELECT * FROM messages WHERE type = 1";
$contact_result = mysql_query($contact_query, $link) or die(mysql_error());
$contact_array = mysql_fetch_array($contact_result) or die(mysql_error());
$contact = $contact_array['text'];
// replace tags in curly brackets to contain particular values associated to visitor
foreach ($message as $key => $value)
{	
	if ($key != 'text')
	{
		$content = str_replace('{'.$key.'}', $value, $content);
		$contact = str_replace('{'.$key.'}', $value, $contact);
	}
}

$content = str_replace('{request_url}', $redirect_to, $content);

// redirection logo url
$suffix_query = "SELECT name, value FROM config WHERE name = 'suffix'";
$suffix_result = mysql_query($suffix_query, $link) or die(mysql_error());
$suffix_array = mysql_fetch_array($suffix_result);
$logo = '';
if ($suffix_array &&
	isset($suffix_array['value']))
{
	$logo = $suffix_array['value'].'redirect/logo';
}

// self cancelable messages have additional anchor for self canceling placed in footer
$sct_query = "SELECT name, value FROM config WHERE name = 'self_cancel_text'";
$sct_result = mysql_query($sct_query, $link) or die(mysql_error());
$sct_array = mysql_fetch_array($sct_result);
if (!$sct_array)
{
	echo 'self_cancel_text has not been set, configure your redirection settings.';
	die();
}
$sct = $sct_array['value'];
if (isset($message['self_cancel']) && $message['self_cancel'] > 0)
	$footer = '<a class="cancel_link" href="cancel.php?redirect_to='.$redirect_to.'">'.$sct.'</a>';
else
	$footer = '';
// close database connection
mysql_close($link);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php // useful settings for expiration prevent caching of this website ?>
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="pragma" content="no-cache" />
<meta http-equiv="expires" content="-1" />
<title>Freenetis</title>
<?php // echo str_replace('https', 'http', html::stylesheet('media/css/style.css', 'screen')) ?>
<link href="../media/css/style.css" rel="stylesheet" type="text/css" />
<style type="text/css">
#content-padd h2 {margin: 10px 0px;}
#content-padd h3 {margin: 10px 0px;}
#content-padd li {margin-left: 20px;}
#content-padd a {font-weight: bold;}
td {width: 100px;}
a.cancel_link	{
	color: red;
	font-size: 14px;
}
</style>
</head>
<body>
<div id="main">
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
	<div id="middle">
		<div id="menu">
			<div id="menu-padd">
				<?php echo $contact ?>
			</div>
		</div>
		<div id="content">
			<div id="content-padd" style="margin:10px">
				<?php echo $content; ?>
			</div>
		</div>
		<div class="clear"></div>
	</div>
	<div id="footer">
		<div id="footer-padd" style="text-align:center;">
			<?php echo $footer ?>
		</div>
	</div>
</div>
<span style="display:none;"><?php echo number_format(memory_get_usage() / 1024 / 1024, 2).' MB'; ?></span>
</body>
</html>