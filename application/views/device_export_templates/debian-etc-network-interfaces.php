# This file describes the network interfaces available on your system
# and how to activate them. For more information, see interfaces(5).

# The loopback network interface
auto lo
iface lo inet loopback

<?php
foreach ($result->interfaces as $interfaces)
{
	foreach ($interfaces as $interface)
	{
		foreach ($interface->ip_addresses as $i => $ip_address)
		{
			$name = ($i > 0) ? $interface->name.':'.$i : $interface->name;
?>
auto <?php echo $name ?> 
iface <?php echo $name ?> inet static
   address <?php echo $ip_address->address ?> 
   netmask <?php echo $ip_address->netmask ?> 
<?php if (isset($ip_address->gateway)): ?>
   gateway <?php echo $ip_address->gateway ?> 
<?php endif ?>

<?php
		}
	}
}
?>
