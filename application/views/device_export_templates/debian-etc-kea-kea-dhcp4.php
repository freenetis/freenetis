{
  "Dhcp4": {
    "valid-lifetime": 500,
    "renew-timer": 100,
    "rebind-timer": 250,
    "server-tag": "all",
    "interfaces-config": {
      "interfaces": ["*"]
    },
    "expired-leases-processing": {
      "reclaim-timer-wait-time": 3,
      "flush-reclaimed-timer-wait-time": 0,
      "hold-reclaimed-time": 3600,
      "max-reclaim-leases": 100,
      "max-reclaim-time": 50,
      "unwarned-reclaim-cycles": 10
    },
    "host-reservation-identifiers": [
      "hw-address",
      "duid"
    ],
    "control-socket": {
      "socket-type": "unix",
      "socket-name": "/tmp/kea4-ctrl-socket"
    },
    "subnet4": [
<?php foreach ($result->dhcp_servers as $server_id => $dhcp_server): ?>
        # <?php echo $dhcp_server->name ?> 
        {
            "subnet": "<?php echo $dhcp_server->cidr ?>",
            "pools": [
<?php foreach ($dhcp_server->ranges as $range_id => $range): ?>
                {
                    "pool": "<?php echo $range->start ?> - <?php echo $range->end ?>"
                }<?php echo $range_id < (count($dhcp_server->ranges) - 1) ? ',': '' ?> 
<?php endforeach ?>
            ],
            "option-data": [{
                "name": "routers",
                "data": "<?php echo $dhcp_server->gateway ?>"
            }, {
                "name": "domain-name-servers",
                "data": "<?php echo implode(",", $dhcp_server->dns_servers) ?>"
            }],
            "reservations": [
<?php foreach ($dhcp_server->hosts as $host_id => $host): ?>
                # <?php echo text::cs_utf2ascii(text::object_format($host, $host->comment)) ?> 
                {
                    "hw-address": "<?php echo $host->mac ?>",
                    "ip-address": "<?php echo $host->ip_address ?>"
                }<?php echo $host_id < (count($dhcp_server->hosts) - 1) ? ',': '' ?> 
<?php endforeach ?>
            ]
        }<?php echo $server_id < (count($result->dhcp_servers) - 1) ? ',': '' ?> 
<?php endforeach ?>
    ]
  }
}
