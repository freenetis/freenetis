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
$link = mysqli_connect($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']) or die(mysqli_error($link));
mysqli_query( $link ,"SET CHARACTER SET utf8") or die(mysqli_error($link));
mysqli_query($link, "SET NAMES utf8") or die(mysqli_error($link));
mysqli_select_db($link, $config['db_name']) or die(mysqli_error($link));

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

// check validity of ip address
if (!preg_match ("/^((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])$/", $ip_address))
{
	echo 'Invalid IP address.';
	die();
}

// content of redirection message
$info_query = "
	SELECT '$ip_address' AS ip_address,
		IFNULL(IFNULL(subnet_name, us.name),'???') AS subnet_name,
		IFNULL(member_name,'???') AS member_name,
		IFNULL(member_id,'???') AS member_id,
		IFNULL(ip.variable_symbol, '???') AS variable_symbol,
		IFNULL(login,'???') AS login,
		IFNULL(balance,'???') AS balance
	FROM members
	LEFT JOIN
	(
		SELECT ip.ip_address, subnet_name,
			mm.name AS member_name, mm.id AS member_id,
			(
				SELECT GROUP_CONCAT(vs.variable_symbol) AS variable_symbol
				FROM variable_symbols vs
				LEFT JOIN accounts a ON a.id = vs.account_id
				WHERE a.member_id = mm.id
			) AS variable_symbol,
			login, a.balance
		FROM
		(
			SELECT ip.ip_address, s.name AS subnet_name,
				IFNULL(ip.member_id,u.member_id) AS member_id, u.login
			FROM ip_addresses ip
			LEFT JOIN ifaces i ON ip.iface_id = i.id
			LEFT JOIN subnets s ON s.id = ip.subnet_id
			LEFT JOIN devices d ON d.id = i.device_id
			LEFT JOIN users u ON u.id = d.user_id
		) ip
		JOIN members mm ON mm.id = ip.member_id
		LEFT JOIN accounts a ON a.member_id = mm.id AND mm.id <> 1
	) ip ON ip.ip_address = '$ip_address'
	LEFT JOIN subnets us
	ON inet_aton(us.netmask) & inet_aton('$ip_address') = inet_aton(us.network_address)
	LIMIT 1
	";
$info_result = mysqli_query($link, $info_query) or die(mysqli_error($link));
$info = mysqli_fetch_array($info_result);

// text in left contact panel,
// it asssumed that after installation, there is always contact message with ID 1
$contact_query = "SELECT * FROM messages WHERE type = 1";
$contact_result = mysqli_query($link, $contact_query) or die(mysqli_error($link));
$contact_array = mysqli_fetch_array($contact_result) or die(mysqli_error($link));
$contact = $contact_array['text'];
// replace tags in curly brackets to contain particular values associated to visitor
foreach ($info as $key => $value)
{
	if ($key != 'text')
	{
		$contact = str_replace('{'.$key.'}', $value, $contact);
	}
}

// redirection logo url
$suffix_query = "SELECT name, value FROM config WHERE name = 'suffix'";
$suffix_result = mysqli_query($link, $suffix_query) or die(mysqli_error($link));
$suffix_array = mysqli_fetch_array($suffix_result);
$logo = '';

if ($suffix_array && isset($suffix_array['value']))
{
	$logo = $suffix_array['value'].'redirect/logo';
}

// close database connection
mysqli_close($link);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php // useful settings for expiration prevent caching of this website ?>
<meta http-equiv="Expires" content="0" />
<meta http-equiv="Cache-Control" content="No-Cache" />
<title>FreenetIS</title>
<link href="../media/images/favicon.ico" rel="shorcut icon" type="image/x-icon" />
<link href="../media/css/style.css" rel="stylesheet" type="text/css" />
<style type="text/css">
#content-padd h2 {margin: 10px 0px;}
#content-padd h3 {margin: 10px 0px;}
#content-padd li {margin-left: 20px;}
#content-padd a {font-weight: bold;}
td {width: 100px;}
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
				<?php echo $contact; ?>
			</div>
		</div>
		<div id="content">
			<div id="content-padd" style="margin:10px">

			</div>
		</div>
		<div class="clear"></div>
	</div>
</div>
</body>
</html>
