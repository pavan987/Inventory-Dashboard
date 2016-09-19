<?php
function curlCall($mxml,$url,$host,$request)
{
	$yt =curl_init($url);
	if (false === $yt)
    {
       return false;
    }
	// Headers to send xml
	$header =   "POST $request  HTTP/1.0\r\n";
	$header .=  "Host: $host\r\n";
	$header .=  "Content-Type: text/xml\r\n";
	$header .=  "Content-Length: ".strlen($mxml)."\r\n";
	$header .=  "Content-Transfer-Encoding: text\r\n";
	$header .=  "Connection-Close: close\r\n\r\n";
	$header .=  $mxml;

	//Configure CURL
	curl_setopt($yt, CURLOPT_SSL_VERIFYPEER,0);
	curl_setopt($yt, CURLOPT_URL, $url);
	curl_setopt($yt, CURLOPT_CUSTOMREQUEST, $header);
	curl_setopt($yt, CURLOPT_RETURNTRANSFER, true);

	//Recieve XML
	//set_time_limit(120);
	$rxml=curl_exec($yt);
	return $rxml;
}
?>