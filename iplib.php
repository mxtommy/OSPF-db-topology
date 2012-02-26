<?php

// Some of these functions are based on or taken from http://iptrack.sourceforge.net/
// I'm a noob at licensing, if that's a problem someone let me know and I'll re-write them.


function TestBaseAddr($baseaddr, $subnetsize) {

    $newsize = $subnetsize-1;
    return !($baseaddr & $newsize);

}


// turn /24 into 256
// get how many hosts by CIDR notation
function cidr2size($n)
{
	$n = 32 - $n;
	if (!$n) return 1; # /32 = 1 host
	return pow(2,$n);
	
}

function size2cidr($n)
{
	if ($n == 1) { return 32; }
	$cnt = 0;
	while (pow(2,$cnt) < $n)
	{ $cnt++; 
		}

	if (cidr2size(32 - $cnt) > $n) { $cnt--; }
	return 32 - $cnt;

}

// php function ip2long is broken!!! (mod_php4.0.4p1)
function inet_aton($a) {
    $inet = 0.0;
    if (count($t = explode(".", $a)) != 4) return 0;
    //$t = explode(".", $a);
    for ($i = 0; $i < 4; $i++) {
        $inet *= 256.0;
        $inet += $t[$i];
    };
    return $inet;
}

// php function long2ip is broken!!! (mod_php4.0.4p1)
function inet_ntoa($n) {
    $t=array(0,0,0,0);
    $msk = 16777216.0;
    $n += 0.0;
    if ($n < 1)
        return('0.0.0.0');
    for ($i = 0; $i < 4; $i++) {
        $k = (int) ($n / $msk);
        $n -= $msk * $k;
        $t[$i]= $k;
        $msk /=256.0;
    };
    $a=join('.', $t);
    return($a);

}

function convertNetmask($cidr_mask)
{
        if ($cidr_mask <= 0) return '0.0.0.0';

        $bit_mask = 0x80000000;
        for ($i = $cidr_mask - 1; $i > 0; --$i)
                $bit_mask >>=1;

        return (long2ip($bit_mask));


}


// returns the number of bits in the mask cisco style
function inet_bits($n) {

    if ($n == 1)
       return 32;
    else
       return 32-strlen(decbin($n-1));
}

#ip = always in INT, netmask = CIDR style
function find_network($ip, $netmask)
{

	$size = cidr2size($netmask);

	#find network address for this netmask
	$mod = sprintf("%d", $ip / $size);
	$network = ($mod * $size);
	return $network;
}

?>
