<?php
#displays the resources to be edited
function editVcentResourcesFunction(){
global $user;
$iam = $user['id'];
#print "The user id is $iam" ;
print "<H2>Select Resource to be Edited</H2>";
$resourcequery = "SELECT res.id, location"
        . " FROM vcentresources res, vcentSmartRoom sroom  WHERE ownerID = $iam AND sroom.id = res.smartroomid order by res.id";
$resourceqh = doQuery($resourcequery);
print "<form action = \"". BASEURL . SCRIPT . "\" method = 'post'>";
print "<input type =submit value = edit />";
print "<table border = 1>";
print "<tr><th>  </th><th>Resource ID </th> <th> Location </th></tr>";

while($row = mysql_fetch_assoc($resourceqh)){
  $resid =  $row['id'];
  $loc = $row['location'];
	print "<tr><td><input type = radio name = rButton value = $resid></input></td><td>$resid</td>" ;
	print "<td>$loc </td></tr>";

}
$editcont = addContinuationsEntry("chooseResourceToEdit",$row);
print "</table>";
print "<input type=hidden name=continuation value=\"$editcont\>";
print "<br/> <br/>";


print "</form>";

}

function chooseResourceToEdit(){
# states array
$states = array(		2 => "available",
		                10 => "maintenance",
						13 => "new");

#get all smart rooms list - start
$smartquery = "SELECT id"
        . " FROM vcentSmartRoom";
$smartqh = doQuery($smartquery);
$ids = array();

while($rowSmart = mysql_fetch_assoc($smartqh)){
  $ids[] = $rowSmart['id'];
}
#get all smart rooms list - end


$selectedResId  = processInputVar("rButton", ARG_NUMERIC);
print "<H2>The resource id to be edited is $selectedResId</H2>";
$selectResQuery = " select smartroomid, des, state, cost from vcentresources where id =$selectedResId";
#print "$selectResQuery";
$selectResqh = doQuery($selectResQuery);
while($rowRes = mysql_fetch_assoc($selectResqh))
{
	$oldSmartRoomID = $rowRes['smartroomid'];
	$oldDesc = $rowRes['des'];
	$oldState = $rowRes['state'];
	$oldCost = $rowRes['cost'];
}



print "<form action = \"". BASEURL . SCRIPT . "\" method = 'post' >";
print "<table> ";
print " <tr><td>Smart Room Id </td><td>";

#print "<input type=text name =smartroomid value=$oldSmartRoomID></input>";
printSelectInput("smartroomindex",$ids,array_search($oldSmartRoomID,$ids));


print "<br/>". "</td></tr> <tr> <td>Description for Resource</td> ";
print "<td>" . "<textarea name=resourcedescription>$oldDesc</textarea> </td> </tr>";
print "<tr> "." <td> State </td> ";
#print "<td> <input type=text name=state value=$oldState></input></td></tr>";
print "<td> ";
printSelectInput("stateindex",$states,$oldState);
print "</td></tr>";

print "<tr> "." <td> Cost </td> ";
print "<td> <input type=text name=costField value=$oldCost></input></td></tr>";

print "<tr><td><input type=submit value=submit></input></td></tr>";
$sendarray = array('smartids'=>$ids,'selectedResId'=>$selectedResId);
$changecont = addContinuationsEntry("makeChangesToResources",$sendarray);
print "</table>";
print "<input type=hidden name=continuation value=\"$changecont\>";
print "</form>";
}

function makeChangesToResources(){
$receivearray = getContinuationVar();
#print " tha array value is $editdata['$selectedResId']";
print " update changes";
$newsmartRoomIndex = processInputVar("smartroomindex", ARG_NUMERIC);
$smatrooms = $receivearray['smartids'];

$newsmartRoomId = $smatrooms[$newsmartRoomIndex];
$selectedResId = $receivearray['selectedResId'];
print "<br/>";
print "The details of Resource# $selectedResId are successfully updated!";	
$newresourcedescription = processInputVar("resourcedescription", ARG_STRING);
$newstate = processInputVar("stateindex", ARG_NUMERIC);
$newcostField = processInputVar("costField", ARG_NUMERIC);
$editResourceQuery = " update vcentresources set smartroomid=$newsmartRoomId ,state=$newstate , cost=$newcostField where id=$selectedResId";
$editResqh=doQuery($editResourceQuery);
$subimagequery = "UPDATE subimages "
		."set imagemetaid = "
		."(select imagemetaid from image where id = "
		."(select imageid from vcentSmartRoom where id = "
		."$newsmartRoomId)) "
		."where imageid = $selectedResId";
doQuery($subimagequery);
}



?>