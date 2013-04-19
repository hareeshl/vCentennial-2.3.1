<?php
function addVcentResourcesFunction(){

#echo "Hello World";

$smartquery = "SELECT id"
        . " FROM vcentSmartRoom";
$smartqh = doQuery($smartquery);
#$result = mysql_fetch_array($smartqh);

$result = array();
$ids = array();

while($row = mysql_fetch_assoc($smartqh)){
  $ids[] = $row['id'];
}
print " Adding a resource:";
print "<form action = \"". BASEURL . SCRIPT . "\" method = 'post'>";
print "<table> ". "<tr> " ."<td>";
print " Select a resource type" . "</td>";
$resourceType = array("camera" =>"Camera","microscope" =>"Microscope");
print "<td>";
printSelectInput("selectedResType1", $resourceType);
$pass = array("resourceType" => $resourceType, "allSmartRooms" => $ids);
$cont = addContinuationsEntry("submitForm",$pass);

print "</td></tr><tr><td> <input type = hidden name=continuation value=\"$cont\">";
print "<br/></td></tr>" . " <tr><td>Smart Room Id </td><td>";

printSelectInput("selectedSmartRoom",$ids);


print "<br/>". "</td></tr> <tr> <td>Description for Resource</td> ";
print "<td>" . "<textarea name=resourcedescription></textarea> </td> </tr>";
print "<tr> "." <td> Cost </td> ";
print "<td> <input type=text name=costField ></input></td></tr>";
print "</table>";
print "<p> <input type = submit value = submit></p>";

print "</form>";

} 

function submitForm(){
$input_data = getContinuationVar();
$cont = processInputVar("continuation", ARG_STRING);
$data = getContinuationsData($cont);
$userID = $data['userid'];
#print "userid is $userID";
#print $data 
#$user['id'];	
$selectedResType = processInputVar("selectedResType1", ARG_STRING);
$finalresdes  = processInputVar("resourcedescription", ARG_STRING);
$selectedSmartRoom = processInputVar("selectedSmartRoom",ARG_NUMERIC);
$cost1 = processInputVar("costField",ARG_STRING);
print "cost1 value is " . "$cost1";
$ids = $input_data["allSmartRooms"];
$resourceType = $input_data["resourceType"];

if(! array_key_exists($selectedResType, $resourceType)) {
      print "invalid option submitted\n";
      return;
   }
   print "The option you selected was: ";
   print "$resourceType[$selectedResType]<br>\n";
$selectquery = "SELECT MAX(id)"
        . " FROM vcentresources";
	$queryImp = doQuery($selectquery);
	$oldIDRow = mysql_fetch_row($queryImp);
	$oldID = $oldIDRow[0];
	$newID = $oldID + 1;
	print " The generated id is $newID ";
$query = "INSERT INTO vcentresources "
		. "(id, type, smartroomid, ownerID, des, cost) " 
		. "VALUES " 
	    . "($newID,'$resourceType[$selectedResType]', "
	       .       "$ids[$selectedSmartRoom],"
		   .	   "$userID,"
	       .       "'$finalresdes',"
		   .	   "$cost1);";
		   
  doQuery($query);
 
    
}




?>