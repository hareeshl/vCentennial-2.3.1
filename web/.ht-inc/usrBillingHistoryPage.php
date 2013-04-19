<?php
function forBillingHistory(){
	print " Welcome user";	
	print "<form id = \"validateForm\" action = \"". BASEURL . SCRIPT . "\" method = 'post'>";
	print "<p>From Date: <input type=\"text\" name=validBeforeDatepicker  id=\"validBeforeDatepicker\" /></p>";
	print "<p>End Date: <input type =\"text\"  name=validAfterDatepicker id =\"validAfterDatepicker\" /></p>";
	$cont = addContinuationsEntry("getBillingHistory");
	print "<input type = hidden name=continuation value=\"$cont\">";
	print "<script type=\"text/javascript\">$(function(){";
    print "\$(\"#validAfterDatepicker\").datepicker();";
	print "\$(\"#validBeforeDatepicker\").datepicker().bind(\"change\",function(){";
    print "var minValue = \$(this).val();";
    print "minValue = \$.datepicker.parseDate(\"mm/dd/yy\", minValue);";
    print "minValue.setDate(minValue.getDate()+1);";
    print "\$(\"#validAfterDatepicker\").datepicker( \"option\", \"minDate\", minValue );";
    print "})});";
	print "</script>";
	print "<input type=\"submit\" value=\"Get Transaction Details\"></input>";
	print "</form>";
}

function getTransaction()
{
global $user;
$iam = $user['id'];
print "<H2>Transaction Details</H2>";
$fromdate = processInputVar("validBeforeDatepicker",ARG_STRING);
$fromDateElts = explode("/",$fromdate);
$frommonth = $fromDateElts[0];
$fromday = $fromDateElts[1];
$fromyear = $fromDateElts[2];
$enddate = processInputVar("validAfterDatepicker",ARG_STRING);
$endDateElts = explode("/",$enddate);
$endmonth = $endDateElts[0];
$endday = $endDateElts[1];
$endyear = $endDateElts[2];

$transQuery = "select txn.id as 'txnid',txn.userid as 'userid', req.id as 'reqid', req.daterequested as 'reqdate', 
				req.start as 'start', req.end as 'end',
				res.imageid as 'imageid', txn.reqcost as 'reqcost'
				from vcenttrxn txn, request req, reservation res
				where txn.requestid = req.id
				and res.requestid = req.id
				and txn.userid = req.userid
				and txn.userid = $iam and date(req.daterequested) between '$fromyear-$frommonth-$fromday' and '$endyear-$endmonth-$endday'";
$transqh = doQuery($transQuery);
$numrows= mysql_num_rows($transqh);
$start =0;
 $end=0; 
 $end=0;
	print "<form action = \"". BASEURL . SCRIPT . "\" method = 'post'>";
	print "<table border=1>"; 
	print "<tr><th>TransactionID</th><th>UserID</th><th>RequestID</th><th>Cost</th><th>Transaction Date</th><th>Duration</th></tr>";
if($numrows > 0)
{
	while($rowTrans = mysql_fetch_assoc($transqh)){
  $transid = $rowTrans['txnid'];
  $userid = $rowTrans['userid'];
  $reqid = $rowTrans['reqid'];
  $reqcost = $rowTrans['reqcost'];
  $reqdate = $rowTrans['reqdate'];
  #$imageid = $rowTrans['imageid'];
  $start = $rowTrans['start'];
  $end = $rowTrans['end'];
  

	
	if($start > 0 && $end >0){
	$diff = abs(strtotime($start) - strtotime($end)); 
	$duration = $diff / ( 60 * 60 );
	}
	print "<tr><td>$transid</td><td>$userid</td><td>$reqid</td><td>$reqcost</td><td>$reqdate</td>";
	print "<td>$duration</td></tr>";
   }
}
else
{
	print "<script type=\"text/javascript\">";
	print "alert('No Transactions in the selected period')";
	print "</script>";
}	
print "</table>";

print "</form>";

}
?>