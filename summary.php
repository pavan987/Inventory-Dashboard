<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Summary</title>
<link rel="stylesheet" href="style.css" />
<style>

.boxed
{
color:#000;
border:2px solid #a1a1a1;
padding:10px 40px; 
background:#dddddd;
width:800px;
border-radius:25px;
-moz-border-radius:25px; /* Old Firefox */
box-shadow: 10px 10px 5px #888888;

}
</style>

</head>
<body>

<?php session_start();
require 'curl.php';
if(isset($_SESSION['Cookie']))
{
	//refreshCookie();
	$model= $_REQUEST['model'];
	$name= $_REQUEST['name'];
	#ini_set('display_errors', "1");
	$url= $_SESSION['url'];
	preg_match("/https?:\/\/([^\/]*)(.*)/", $url, $matches);
	$host=$matches[1];
	$request=$matches[2];

	//XML Input
	$mxml= '<configResolveClass cookie="'.$_SESSION['Cookie'].'" classId="computeBlade" inHierarchical="false">
	</configResolveClass>';
	$rxmlB=curlCall($mxml,$url,$host,$request);


	$computeBlades = simplexml_load_string($rxmlB);
	$computeBlade=$computeBlades->outConfigs->computeBlade;

	$mxml= '<configResolveClass cookie="'.$_SESSION['Cookie'].'" classId="computeRackUnit" inHierarchical="false">
	</configResolveClass>';

	$rxmlB=curlCall($mxml,$url,$host,$request);
	$computeRackUnits = simplexml_load_string($rxmlB);
	$computeRackUnit=$computeRackUnits->outConfigs->computeRackUnit;

$mxml= '<configResolveClass cookie="'.$_SESSION['Cookie'].'" classId="computeServerUnit" inHierarchical="false">
	</configResolveClass>';
	$rxmlB=curlCall($mxml,$url,$host,$request);


	$computeServers = simplexml_load_string($rxmlB);
	$computeServer=$computeServers->outConfigs->computeServerUnit;

	
	//file_put_contents("Process.xml",$rxmlP);
	$A[0]=$computeBlade;
	$A[1]=$computeRackUnit;
	$A[2]=$computeServer;
	


	$i=0;
	$up=0;
	$down=0;
	for($j=0; $j<3; $j++)
	{

	$c[$j]=0;
	foreach ($A[$j] as $bladeinfo)
		{
			$c[$j]=$c[$j]+1;
			$managingInst[$i]=(string)$bladeinfo['managingInst'];
			$connPath[$i]=(string)$bladeinfo['connPath'];
			$connStatus[$i]=(string)$bladeinfo['connStatus'];
			$dn[$i]=(string)$bladeinfo['dn'];
			$pieces=explode("/", $dn[$i], -1);
			$computedn=implode("/", $pieces);
			$mxml= '<configResolveDn cookie="'.$_SESSION['Cookie'].'" inHierarchical="true" dn="'.$dn[$i].'/board"/>';
			$rxmlP=curlCall($mxml,$url,$host,$request);
			$processorUnits= simplexml_load_string($rxmlP);
			$processorUnit[$i]=(string)$processorUnits->outConfig->computeBoard->processorUnit[0]['model'];

			if(strpos($computedn, "chassis")==4)
			{
				$motherBoardRTemp[$i]=(float)$processorUnits->outConfig->computeBoard->computeMbTempStats[0]['fmTempSenRear'];
				$motherBoardLTemp[$i]=(float)$processorUnits->outConfig->computeBoard->computeMbTempStats[0]['fmTempSenRearL'];
			
			}
			else
			{
				$motherBoardRTemp[$i]=(float)$processorUnits->outConfig->computeBoard->computeRackUnitMbTempStats[0]['rearTemp'];
				$motherBoardLTemp[$i]=(float)$processorUnits->outConfig->computeBoard->computeRackUnitMbTempStats[0]['frontTemp'];
			}

			$motherBoardTemp[$i]=round($motherBoardRTemp[$i], 2);
			if($motherBoardTemp[$i]==0)
				$motherBoardTemp[$i]=round($motherBoardLTemp[$i], 2);


			$mxml= '<configResolveClass cookie="'.$_SESSION['Cookie'].'" classId="faultInst" inHierarchical="false">
			</configResolveClass>';
			$rxmlF=curlCall($mxml,$url,$host,$request);
			$faultInsts= simplexml_load_string($rxmlF);
			$faultInst=$faultInsts->outConfigs->faultInst;
			$criticalFault[$i]=0;
			$majorFault[$i]=0;
			$minorFault[$i]=0;
			$warningFault[$i]=0;
			foreach($faultInst as $f)
			{				
				if(stripos($f['dn'],$dn[$i])!== false)
				{
					$severity=$f['severity'];
					if($severity=="critical")
						$criticalFault[$i]=$criticalFault[$i]+1;
					if($severity=="major")
						$majorFault[$i]=$majorFault[$i]+1;
					if($severity=="minor")
						$minorFault[$i]=$minorFault[$i]+1;
					if($severity=="warning")
						$warningFault[$i]=$warningFault[$i]+1;
				}
			}

			$cores[$i]=(string)$bladeinfo['numOfCores'];		
			$serverId[$i]=(string)$bladeinfo['serverId'];
			if($serverId[$i] == "")
			{
                        $chassisId[$i]=(int)$bladeinfo['chassisId'];
                        $slotId[$i]=(int)$bladeinfo['slotId'];
                        $instanceId[$i]=(int)$bladeinfo['instanceId'];          
                        $serverId[$i]=$chassisId[$i]."/".$slotId[$i]."/".$instanceId[$i];	
			}
			$association[$i]=(string)$bladeinfo['assignedToDn'];

			if($association[$i]!="")
			{
			$mxml= '<configResolveDn cookie="'.$_SESSION['Cookie'].'" inHierarchical="false" dn="'.$association[$i].'"/>';
			$rxmlBP=curlCall($mxml,$url,$host,$request);
			$lsServers= simplexml_load_string($rxmlBP);
			
			$lsServer[$i]=(string)$lsServers->outConfig->lsServer[0]['operBootPolicyName'];

			$mxml= '<configResolveChildren cookie="'.$_SESSION['Cookie'].'" inHierarchical="false" inDn="'.$lsServer[$i].'"/>';
			$rxmlBO=curlCall($mxml,$url,$host,$request);
			$outConfigs= simplexml_load_string($rxmlBO);
			
			$outConfig[$i]=$outConfigs->outConfigs;


			foreach($outConfig[$i]->children() as $border)
			{
				$abc=(string)$border['order'];
				if($abc=="1")
					$bootOrder[$i]=$border['type'];
			
			}
			}
			else
				$bootOrder[$i]="NA";

			$operPower[$i]=(string)$bladeinfo['operPower'];
			if($operPower[$i]=="on")
				$up=$up+1;
			else
				$down=$down+1;
			$availableMemory[$i]=(int)$bladeinfo['availableMemory'];
			#$totalMemory[$i]=(int)$bladeinfo['totalMemory'];
			$totalMemory[$i]=(int)$processorUnits->outConfig->computeBoard->memoryArray['maxCapacity'];
			$fsmProgr[$i]=(string)$bladeinfo['fsmProgr'];
			$pid[$i]=(string)$bladeinfo['model'];
			$mxml= '<configResolveClass cookie="'.$_SESSION['Cookie'].'" classId="equipmentManufacturingDef" inHierarchical="false">
			<inFilter><eq class="equipmentManufacturingDef" property="pid" value="'.$pid[$i].'" /></inFilter>
			</configResolveClass>';
			$rxmlB=curlCall($mxml,$url,$host,$request);
			$bNames = simplexml_load_string($rxmlB);
			$bName[$i]=$bNames->outConfigs->equipmentManufacturingDef[0]['name'];
			if($bName[$i]=="")
			{
			$bName[$i]="Cisco UCSME-142-M4";
			}
			$i=$i+1;
		}
	}
//echo "<script type='text/javascript'>alert('hello');</script>";
$mxml= '<aaaLogout inCookie="'.$_SESSION['Cookie'].'" />';
$rxml=curlCall($mxml,$url,$host,$request);	
?>


<center>

<div class="boxed">
<h2>Summary:  Total Servers (<?php echo $i; ?>)  B Series (<?php echo $c[0]; ?>)  C Series (<?php echo $c[1]; ?>) M Series (<?php echo $c[2]; ?>)  Up (<?php echo $up; ?>)  Down (<?php echo $down; ?>)</h2>
</div>
</center>
<br/>
	<table cellpadding="0" cellspacing="0" border="0" id="table" class="sortable">
		<thead>
			<tr>
				<th ><h3>ID</h3></th>
				<th><h3>Server ID</h3></th>
				<th><h3>Server model</h3></th>
				<th><h3>Service-Profile</h3></th>
				<th><h3>Primary Boot order</h3></th>
				<th><h3>Power</h3></th>
				<th><h3>CPU</h3></th>
				<th><h3>Memory</h3></th>
				<th><h3>FSM</h3></th>
				<th><h3>Temp ®F</h3></th>
				<th><h3>conn Path</h3></th>
				<th><h3>conn Status</h3></th>
				<th><h3>Mangng Inst</h3></th>
				<th><h3>Fault Summary</h3></th>
			</tr>
		</thead>
		<tbody>
	<?php $i=0;
	foreach($serverId as $serverinfo){ ?>
			<tr>
				<td><?php echo $i+1; ?></td>
				<td><?php echo $serverinfo; ?></td>
				<td><?php echo $bName[$i]; ?></td>
				<td><?php 
				if($association[$i]=="")
					echo "unassociated";
				else
					echo $association[$i]; ?></td>
				
				<td><?php echo $bootOrder[$i]; ?></td>
				<td><?php echo $operPower[$i]; ?></td>
				<td><?php echo $processorUnit[$i].";".$cores[$i]." Cores"; ?></td>
				<td><?php echo $totalMemory[$i]." MB"; ?></td>
				<td><?php echo $fsmProgr[$i]." %"; ?></td>
				<td><?php echo $motherBoardTemp[$i]; ?></td>
				<td><?php echo $connPath[$i]; ?></td>
				<td><?php echo $connStatus[$i]; ?></td>
				<td><?php echo $managingInst[$i]; ?></td>
				<td>
				 <table border="0">
				 <tr>
				 <td><img src="images/critical.png" /></td>
				 <td><img src="images/major.png" /></td>
				 <td><img src="images/minor.png" /></td>
				 <td><img src="images/warning.png" /></td>
				 </tr>
				 <tr>
				 <td align=center><?php echo $criticalFault[$i]; ?></td>
				 <td align=center><?php echo $majorFault[$i]; ?></td>
				 <td align=center><?php echo $minorFault[$i]; ?></td>
				 <td align=center><?php echo $warningFault[$i]; ?></td>
				 </tr>
				</table></td>
				
			</tr>
			<?php $i=$i+1;} ?>
		</tbody>
  </table>
	<div id="controls">
		<div id="perpage">
			<select onchange="sorter.size(this.value)">
			<option value="5">5</option>
				<option value="10" selected="selected">10</option>
				<option value="20">20</option>
				<option value="50">50</option>
				<option value="100">100</option>
			</select>
			<span>Entries Per Page</span>
		</div>
		<div id="navigation">
			<img src="images/first.gif" width="16" height="16" alt="First Page" onclick="sorter.move(-1,true)" />
			<img src="images/previous.gif" width="16" height="16" alt="First Page" onclick="sorter.move(-1)" />
			<img src="images/next.gif" width="16" height="16" alt="First Page" onclick="sorter.move(1)" />
			<img src="images/last.gif" width="16" height="16" alt="Last Page" onclick="sorter.move(1,true)" />
		</div>
		<div id="text">Displaying Page <span id="currentpage"></span> of <span id="pagelimit"></span></div>
	</div>
	<script type="text/javascript" src="script.js"></script>
	<script type="text/javascript">
  var sorter = new TINY.table.sorter("sorter");
	sorter.head = "head";
	sorter.asc = "asc";
	sorter.desc = "desc";
	sorter.even = "evenrow";
	sorter.odd = "oddrow";
	sorter.evensel = "evenselected";
	sorter.oddsel = "oddselected";
	sorter.paginate = true;
	sorter.currentid = "currentpage";
	sorter.limitid = "pagelimit";
	sorter.init("table",1);
  </script>
</body>
</html>
<?php } ?>
