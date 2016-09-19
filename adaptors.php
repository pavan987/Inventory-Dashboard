I<?php 

include 'configure/session.php';
require 'curl.php';
if($res=$mysqli->query("select IP, Password from hosts where Email = '$email' "))
{
for($row_no=$res->num_rows-1; $row_no>=0; $row_no--)
{
$res->data_seek($row_no);
$row=$res->fetch_assoc();
$ip=$row['IP'];
$user="admin";
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
$errorDescr = (string) $login[0]['errorDescr’];:q!


I
$result=strcmp($errorDescr,"Authentication failed");
if($result==0)
{
echo "Authentication Failed. Please Check the testbed configuration once";
exit();
}
$cookie = (string) $login[0]['outCookie'];
$_SESSION['Cookie']=$cookie;

//refreshCookie();
$model= $_REQUEST['model'];
$name= $_REQUEST['name'];




	//XML Input

		$mxml= '<configResolveClass cookie="'.$_SESSION['Cookie'].'" classId="adaptorUnit" inHierarchical="false">
	<inFilter><eq class="adaptorUnit" property="model" value="'.$model.'" /></inFilter>
	</configResolveClass>';


	$rxmlA=curlCall($mxml,$url,$host,$request);


	$adaptorUnits = simplexml_load_string($rxmlA);
	$adaptorUnit=$adaptorUnits->outConfigs->adaptorUnit;

	
	//file_put_contents("Process.xml",$rxmlP);

	$i=0;
	foreach ($adaptorUnit as $Ainfo)
		{
			$dn[$i]=(string)$Ainfo['dn'];
			$pieces=explode("/", $dn[$i], -1);
			$computedn=implode("/", $pieces);
			$mxml= '<configResolveDn cookie="'.$_SESSION['Cookie'].'" inHierarchical="false" dn="'.$computedn.'"/>';
			$rxmlB=curlCall($mxml,$url,$host,$request);
			$computeBlades = simplexml_load_string($rxmlB);
			if(strpos($computedn, "chassis")==4)
				$computeBlade=$computeBlades->outConfig->computeBlade[0];
			else
				$computeBlade=$computeBlades->outConfig->computeRackUnit[0];
			$mxml= '<configResolveDn cookie="'.$_SESSION['Cookie'].'" inHierarchical="true" dn="'.$computedn.'/board"/>';
			$rxmlP=curlCall($mxml,$url,$host,$request);
			$processorUnits= simplexml_load_string($rxmlP);
			$processorUnit[$i]=$processorUnits->outConfig->computeBoard->processorUnit[0]['model'];
			
			
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
			$managingInst[$i]=(string)$computeBlade['managingInst'];
			$connPath[$i]=(string)$computeBlade['connPath'];
			$connStatus[$i]=(string)$computeBlade['connStatus'];
			$cores[$i]=(string)$computeBlade['numOfCores'];		
			$serverId[$i]=(string)$computeBlade['serverId'];
			$association[$i]=(string)$computeBlade['assignedToDn'];

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

			$operPower[$i]=(string)$computeBlade['operPower'];
			$totalMemory[$i]=(int)$computeBlade['totalMemory'];
			$pid[$i]=(string)$computeBlade['model'];
			$mxml= '<configResolveClass cookie="'.$_SESSION['Cookie'].'" classId="equipmentManufacturingDef" inHierarchical="false">
			<inFilter><eq class="equipmentManufacturingDef" property="pid" value="'.$pid[$i].'" /></inFilter>
			</configResolveClass>';
			$rxmlB=curlCall($mxml,$url,$host,$request);
			$bNames = simplexml_load_string($rxmlB);
			$bName[$i]=$bNames->outConfigs->equipmentManufacturingDef[0]['name'];
			$fsmProgr[$i]=(string)$computeBlade['fsmProgr'];
			$i=$i+1;

		}

$mxml= '<aaaLogout inCookie="'.$_SESSION['Cookie'].'" />';
$rxml=curlCall($mxml,$url,$host,$request);
}
}
}	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Summary</title>
<link rel="stylesheet" href="css/style.css" />
</head>
<body>

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
				<th><h3>Temp ÆF</h3></th>
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
	<script type="text/javascript" src="js/script.js"></script>
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
