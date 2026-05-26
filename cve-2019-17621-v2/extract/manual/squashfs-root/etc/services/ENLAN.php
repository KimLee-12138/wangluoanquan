<?
fwrite("w",$START, "#!/bin/sh\n");
// Power on Lan port here
fwrite("a",$START, "ethlink -i 1 -a UP\n");
fwrite("a",$START, "ethlink -i 2 -a UP\n");
fwrite("a",$START, "ethlink -i 3 -a UP\n");
fwrite("a",$START, "ethlink -i 4 -a UP\n");

fwrite("a",$START, "sleep 5;event IPV6ENABLE\n");

fwrite("a",$START, "echo 1 > /proc/sys/net/ipv6/conf/all/forwarding\n");
fwrite("a",$START, "exit 0\n");
fwrite("w",$STOP,  "#!/bin/sh\nexit 0\n");
?>
