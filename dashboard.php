<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >

	
	<meta name="language" content="en">

	<title>UCS Dashboard</title>

	<link rel="stylesheet" type="text/css" href="css/style.css">
	<link rel="stylesheet" type="text/css" href="css/health-style.css">
	<link rel="stylesheet" type="text/css" href="css/accordion.css">
	<link rel="stylesheet" type="text/css" href="css/layout-default-latest.css">
	<!-- CUSTOMIZE/OVERRIDE THE DEFAULT CSS -->
	<style type="text/css">

#dvLoading
{
   background:#fff url(images/loader.gif) no-repeat center center;
   height: 100px;
   width: 100px;
   position: fixed;
   z-index: 1000;
   left: 50%;
   top: 50%;
   margin: -25px 0 0 -25px;
}

	.logout,
	.logout:visited,
	.logout:hover,
	.logout:active 
	{
	color:#fff;
	text-decoration: none;
	}

	/* remove padding and scrolling from elements that contain an Accordion OR a content-div */
        .ui-layout-north ,
	.ui-layout-center ,	/* has content-div */
	.ui-layout-west ,	/* has Accordion */
	.ui-layout-east ,	/* has content-div ... */
	.ui-layout-east .ui-layout-content { /* content-div has Accordion */
		padding: 0;
		overflow: hidden;
	}
	.ui-layout-center P.ui-layout-content {
		line-height:	1.4em;
		margin:			0; /* remove top/bottom margins from <P> used as content-div */
	}
	h3, h4 { /* Headers & Footer in Center & East panes */
		font-size:		1.1em;
		background:		#EEF;
		border:			1px solid #BBB;
		border-width:	0 0 1px;
		padding:		7px 10px;
		margin:			0;
	}
	.ui-layout-east h4 { /* Footer in East-pane */
		font-size:		0.9em;
		font-weight:	normal;
		border-width:	1px 0 0;
	}

	
	</style>

	<!-- REQUIRED scripts for layout widget -->
	<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
		<script type="text/javascript" src="js/jquery-ui-1.8.2.custom.min.js"></script>
	<script type="text/javascript" src="js/jquery.layout-latest.js"></script>
	<script type="text/javascript" src="js/debug.js"></script>

	<script type="text/javascript">

	var myLayout; // init global vars

	$(document).ready( function() {

		myLayout = $('body').layout({
			west__size:			260
		,	west__maxSize:		260
		,	north__size:		42
		,	north__maxSize:		42
			// RESIZE Accordion widget when panes resize
		,	west__onresize:		function () { $("#accordion1").accordion("resize"); }
		,	autoBindCustomButtons: true
		});

		// ACCORDION - in the West pane
		$("#accordion1").accordion({
			fillSpace:	true
		});		
	});

	</script>
</head>


<?php 
include 'configure/session.php';
require 'curl.php';
if($res=$mysqli->query("select IP,Username, Password from hosts where Email = '$email' "))
{
for($row_no=$res->num_rows-1; $row_no>=0; $row_no--)
{
$res->data_seek($row_no);
$row=$res->fetch_assoc();
$ip=$row['IP'];
$user=$row['Username'];
$password=$row['Password'];
//XML Inputs
$url= 'https://'.$ip.'/nuova';
$_SESSION['url']=$url;
preg_match("/https?:\/\/([^\/]*)(.*)/", $url, $matches);
$host=$matches[1];
$request=$matches[2];
$mxml= '<aaaLogin inName="'.$user.'" inPassword="'.$password.'"/>';
$rxml=curlCall($mxml,$url,$host,$request);
//get Cookie from Response XML
$login = simplexml_load_string($rxml);
if($login) {
$errorDescr = (string) $login[0]['errorDescr'];
$result=strcmp($errorDescr,"Authentication failed");
if($result==0)
{
echo "Authentication Failed. Please Check the testbed configuration once";
exit();
}
$cookie = (string) $login[0]['outCookie'];
$_SESSION['Cookie']=$cookie;

	//XML Input for Blade
	$mxml= '<configResolveClass cookie="'.$cookie.'" classId="computeBlade" inHierarchical="false">
	</configResolveClass>';
	$rxmlB=curlCall($mxml,$url,$host,$request);

	//XML Input for Rack
	$mxml= '<configResolveClass cookie="'.$cookie.'" classId="computeRackUnit" inHierarchical="false">
	</configResolveClass>';
	$rxmlC=curlCall($mxml,$url,$host,$request);

	//XML Input for NJ
	$mxml= '<configResolveClass cookie="'.$cookie.'" classId="computeServerUnit" inHierarchical="false">
	</configResolveClass>';
	$rxmlM=curlCall($mxml,$url,$host,$request);

	//XML Input for Adapter
	$mxml= '<configResolveClass cookie="'.$cookie.'" classId="adaptorUnit" inHierarchical="false">
	</configResolveClass>';
	$rxmlA=curlCall($mxml,$url,$host,$request);
	echo "hello";

	//file_put_contents('computeBlade.xml', $rxml);
	$computeBlades = simplexml_load_string($rxmlB);
	$computeBlade=$computeBlades->outConfigs->computeBlade;
	
	$computeRackUnits = simplexml_load_string($rxmlC);
	$computeRackUnit=$computeRackUnits->outConfigs->computeRackUnit;
	//file_put_contents('computeBlade.xml', $rxmlC);

	$computeServers = simplexml_load_string($rxmlM);
	$computeServer=$computeServers->outConfigs->computeServerUnit;
	//file_put_contents('computeServer.xml', $rxmlM);

	$computeadaptorUnits = simplexml_load_string($rxmlA);
	$computeadaptorUnit=$computeadaptorUnits->outConfigs->adaptorUnit;
	//file_put_contents('computeBlade.xml', $rxmlA);

	$i=0;
	foreach($computeBlade as $b)
	{
		$present=0;
		if($i==0)
			$bArray[$i]=(string)$b['model'];
		else
			foreach($bArray as $z)
				if($z == $b['model'])
					$present=1;
		if($present==0)
			$bArray[$i]=(string)$b['model'];
		$i=$i+1;
	}

	//echo "<script type='text/javascript'>alert('".$bArray."');</script>";

	// Find count of B Models and store it in Array
	$i=0;
	$bTotalCount=0;
	foreach($bArray as $pid)
	{
		$count=0;
		$mxml= '<configResolveClass cookie="'.$cookie.'" classId="equipmentManufacturingDef" inHierarchical="false">
		<inFilter><eq class="equipmentManufacturingDef" property="pid" value="'.$pid.'" /></inFilter>
		</configResolveClass>';
		$rxmlB=curlCall($mxml,$url,$host,$request);
		$bNames = simplexml_load_string($rxmlB);
		$bName=$bNames->outConfigs->equipmentManufacturingDef[0]['name'];
	
		foreach ($computeBlade as $bladeinfo)
		{
			$pid1=(string)$bladeinfo['model'];
			if($pid==$pid1)
				$count=$count+1;
		}
		if($count>0){
			$bArrayName[$i]=$bName;
			$bArrayCount[$i]=$count;
			$bTotalCount=$bTotalCount+$count;
			$bArrayPid[$i]=$pid;
			$i=$i+1;}
	}
		

	$i=0;
	foreach($computeRackUnit as $c)
	{
		$present=0;
		if($i==0)
			$cArray[$i]=(string)$c['model'];
		else
			foreach($cArray as $z)
				if($z == $c['model'])
					$present=1;
		if($present==0)
			$cArray[$i]=(string)$c['model'];
		$i=$i+1;
	}


	// Find count of C Models and store it in Array
	$i=0;
	$cTotalCount=0;
	foreach($cArray as $pid)
	{
		$count=0;
		$mxml= '<configResolveClass cookie="'.$cookie.'" classId="equipmentManufacturingDef" inHierarchical="false">
		<inFilter><eq class="equipmentManufacturingDef" property="pid" value="'.$pid.'" /></inFilter>
		</configResolveClass>';
		$rxmlB=curlCall($mxml,$url,$host,$request);
		$cNames = simplexml_load_string($rxmlB);
		$cName=$cNames->outConfigs->equipmentManufacturingDef[0]['name'];
	
		foreach ($computeRackUnit as $bladeinfo)
		{
			$pid1=(string)$bladeinfo['model'];
			if($pid==$pid1)
				$count=$count+1;
		}
		if($count>0){
			$cArrayName[$i]=$cName;
			$cArrayCount[$i]=$count;
			$cTotalCount=$cTotalCount+$count;
			$cArrayPid[$i]=$pid;
			$i=$i+1;}
	}

	$i=0;
	foreach($computeServer as $m)
	{
		$present=0;
		if($i==0)
			$mArray[$i]=(string)$m['model'];
		else
			foreach($mArray as $z)
				if($z == $m['model'])
					$present=1;
		if($present==0)
			$mArray[$i]=(string)$m['model'];
		$i=$i+1;
	}

	// Find count of M Models and store it in Array
	$i=0;
	$mTotalCount=0;
	foreach($mArray as $pid)
	{
		$count=0;
		$mxml= '<configResolveClass cookie="'.$cookie.'" classId="equipmentManufacturingDef" inHierarchical="false">
		<inFilter><eq class="equipmentManufacturingDef" property="pid" value="'.$pid.'" /></inFilter>
		</configResolveClass>';
		$rxmlM=curlCall($mxml,$url,$host,$request);
		$mNames = simplexml_load_string($rxmlM);
		$mName=$mNames->outConfigs->equipmentManufacturingDef[0]['name'];
		#$mName="Cisco UCSME-142-M4";
	
		foreach ($computeServer as $bladeinfo)
		{
			$pid1=(string)$bladeinfo['model'];
			if($pid==$pid1)
				$count=$count+1;
		}
		if($count>0){
			$mArrayName[$i]=$mName;
			$mArrayCount[$i]=$count;
			$mTotalCount=$mTotalCount+$count;
			$mArrayPid[$i]=$pid;
			$i=$i+1;}
	}
		

	$i=0;
	foreach($computeadaptorUnit as $a)
	{
		$present=0;
		if($i==0)
			$aArray[$i]=(string)$a['model'];
		else
			foreach($aArray as $z)
				if($z == $a['model'])
					$present=1;
		if($present==0)
			$aArray[$i]=(string)$a['model'];
		$i=$i+1;
	}


	// Find count of A Models and store it in Array
	$i=0;
	$aTotalCount=0;
	foreach($aArray as $pid)
	{
		$count=0;
		$mxml= '<configResolveClass cookie="'.$cookie.'" classId="equipmentManufacturingDef" inHierarchical="false">
		<inFilter><eq class="equipmentManufacturingDef" property="pid" value="'.$pid.'" /></inFilter>
		</configResolveClass>';
		$rxmlB=curlCall($mxml,$url,$host,$request);
 		$aNames = simplexml_load_string($rxmlB);
		$aName=$aNames->outConfigs->equipmentManufacturingDef[0]['name'];
		foreach ($computeadaptorUnit as $bladeinfo)
		{
			$pid1=(string)$bladeinfo['model'];
			if($pid==$pid1)
				$count=$count+1;
		}
		if($count>0){
			$aArrayName[$i]=$aName;
			$aArrayCount[$i]=$count;
			$aTotalCount=$aTotalCount+$count;
			$aArrayPid[$i]=$pid;
			$i=$i+1;}
	}
}}}		
?>

<body style="position: relative; height: 100%; overflow: hidden; margin: 0px; padding: 0px; border: none;" unselectable="off">
<script>
$(window).load(function(){
  $('#dvLoading').fadeOut(10);
});
</script>

<div id="dvLoading"></div>
<div class="ui-layout-north ui-layout-pane ui-layout-pane-north" style="display: block; position: absolute; margin: 0px; top: 0px; bottom: auto; left: 0px; right: 0px; width: auto; z-index: 1; visibility: visible; height: 40px;">
<div class="header">
<div class="heading">Welcome to Cisco UCS Health Monitor, <?php echo $_SESSION['name']; ?></div>
<ul class="menu">
<li><a href='http://wikicentral.cisco.com/display/UCSMQABLR/UCS+Health+Monitor' target="_blank">Help</a></li>
<li><a href='home.php'>Configure</a></li>
<li><a href='monitor.php'>Monitor-1</a></li>
<li><a href='fsm.php'>Monitor-2</a></li>
<li><a href='upgrade.php'>Upgrade</a></li>
<li><a href='dashboard.php'>Dashboard</a></li>
<li><a href='logs.php'>Logs</a></li>
<li><a href='configure/logout.php'>Logout</a></li>
</ul>
</div>
</div>
<div class="ui-layout-center ui-layout-pane ui-layout-pane-center ui-layout-pane-hover ui-layout-pane-center-hover ui-layout-pane-open-hover ui-layout-pane-center-open-hover" style="position: absolute; margin: 0px; left: 266px; right: 0px; top: 48px; bottom: 0px; height: 862px; width: 1652px; z-index: 1; visibility: visible; display: block;"> 
	<iframe id="mainFrame" name="mainFrame" width="100%" height="100%" frameborder="0" scrolling="auto" src="summary.php"></iframe>
</div>
<div class="ui-layout-west ui-layout-pane ui-layout-pane-west" style="display: block; position: absolute; margin: 0px; left: 0px; right: auto; top: 48px; bottom: 0px; height: 862px; z-index: 1; visibility: visible; width: 258px;">
	<div id="accordion1" class="basic ui-accordion ui-widget ui-helper-reset ui-accordion-icons" role="tablist">

			<a href="bMain.php" target="mainFrame" class="ui-accordion-header ui-helper-reset ui-state-active ui-corner-top"  role="tab" aria-expanded="true" tabindex="0"><span class="ui-icon ui-icon-triangle-1-s"></span>B Series (<?php echo $bTotalCount; ?>)</a>
			<div class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active" style="height: 738px; overflow: auto;" role="tabpanel">
			<?php 
			$j=0;
			if($bTotalCount>0)
			foreach($bArrayName as $bName){ ?>
			<p><a class="ape-menu-link" target="mainFrame" title="<?php echo $bName; ?>" href="Bmain.php?model=<?php echo $bArrayPid[$j]; ?>&name=<?php echo $bName; ?>"> <?php echo $bName." (".$bArrayCount[$j].")";
			$j=$j+1;?></a></p>
			<?php } ?>
			</div>
			<a href="Cmain.php" class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all" role="tab" aria-expanded="false" tabindex="-1"><span class="ui-icon ui-icon-triangle-1-e"></span>C Series (<?php echo $cTotalCount; ?>)</a>
			<div class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom" style="height: 738px; overflow: auto; display: none;" role="tabpanel">
			<?php 
			$j=0;
			if($cTotalCount>0)
			foreach($cArrayName as $cName){ ?>
			<p><a class="ape-menu-link" target="mainFrame" title="<?php echo $cName; ?>" href="Cmain.php?model=<?php echo $cArrayPid[$j]; ?>&name=<?php echo $cName; ?>"> <?php echo $cName." (".$cArrayCount[$j].")";
			$j=$j+1;?></a></p>
			<?php } ?>
			</div>

<a href="modular.php" class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all" role="tab" aria-expanded="false" tabindex="-1"><span class="ui-icon ui-icon-triangle-1-e"></span>M Series (<?php echo $mTotalCount; ?>)</a>
			<div class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom" style="height: 738px; overflow: auto; display: none;" role="tabpanel">
			<?php 
			$j=0;
			if($mTotalCount>0)
			foreach($mArrayName as $mName){ ?>
			<p><a class="ape-menu-link" target="mainFrame" title="<?php echo $mName; ?>" href="modular.php?model=<?php echo $mArrayPid[$j]; ?>&name=<?php echo $mName; ?>"> <?php echo $mName." (".$mArrayCount[$j].")";
			$j=$j+1;?></a></p>
			<?php } ?>
			</div>

			<a href="Amain.php" class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all" role="tab" aria-expanded="false" tabindex="-1"><span class="ui-icon ui-icon-triangle-1-e"></span>Adapters (<?php echo $aTotalCount; ?>)</a>
			<div class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom" style="height: 738px; overflow: auto; display: none;" role="tabpanel">
			<?php 
			$j=0;
			if($aTotalCount>0)
			foreach($aArrayName as $aName){
			$aName1 = (strlen($aName) > 20) ? substr($aName,0,18).'..' : $aName;
				?>
			<p><a class="ape-menu-link" title="<?php echo $aName; ?>" target="mainFrame" href="Amain.php?model=<?php echo $aArrayPid[$j]; ?>&name=<?php echo $aName; ?>"> <?php echo $aName1."(".$aArrayCount[$j].")";
			$j=$j+1;?></a></p>
			<?php } ?>
			</div>

</div>
</div>
 
<div id="" style="position: absolute; padding: 0px; margin: 0px; font-size: 1px; text-align: left; overflow: hidden; z-index: 2; top: 42px; cursor: n-resize; width: 1920px; height: 6px; left: 0px;" class="ui-layout-resizer ui-layout-resizer-north ui-layout-resizer-open ui-layout-resizer-north-open" title="Resize"><div id="" style="position: absolute; padding: 0px; margin: 0px; overflow: hidden; text-align: center; font-size: 1px; cursor: pointer; z-index: 1; width: 48px; height: 6px; left: 935px; top: 0px;" class="ui-layout-toggler ui-layout-toggler-north ui-layout-toggler-open ui-layout-toggler-north-open" title="Close"></div></div><div id="" style="position: absolute; padding: 0px; margin: 0px; font-size: 1px; text-align: left; overflow: hidden; z-index: 2; left: 260px; cursor: w-resize; height: 864px; width: 6px; top: 48px;" class="ui-layout-resizer ui-layout-resizer-west ui-layout-resizer-open ui-layout-resizer-west-open" title="Resize"><div id="" style="position: absolute; padding: 0px; margin: 0px; overflow: hidden; text-align: center; font-size: 1px; cursor: pointer; z-index: 1; height: 48px; width: 6px; top: 407px; left: 0px;" class="ui-layout-toggler ui-layout-toggler-west ui-layout-toggler-open ui-layout-toggler-west-open" title="Close"></div></div></body></html>
