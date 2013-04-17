<?php
/*
  Licensed to the Apache Software Foundation (ASF) under one or more
  contributor license agreements.  See the NOTICE file distributed with
  this work for additional information regarding copyright ownership.
  The ASF licenses this file to You under the Apache License, Version 2.0
  (the "License"); you may not use this file except in compliance with
  the License.  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
*/

/**
 * \file
 */

////////////////////////////////////////////////////////////////////////////////
///
/// \fn dashboard()
///
/// \brief prints a page with various information about running system
///
////////////////////////////////////////////////////////////////////////////////
function dashboard() {
	print "<h2>VCL Dashboard</h2>\n";
	if(checkUserHasPerm('View Dashboard (global)')) {
		print "View data for:";
		$affils = getAffiliations();
		$affils = array_reverse($affils, TRUE);
		$affils[0] = "All Affiliations";
		$affils = array_reverse($affils, TRUE);
		printSelectInput('affilid', $affils, -1, 0, 0, 'affilid', 'onChange="updateDashboard();"');
	}
	print "<table summary=\"\" id=dashboard>\n";
	print "<tr>\n";

	# -------- left column ---------
	print "<td valign=\"top\">\n";
	print addWidget('status', 'Current Status');
	print addWidget('topimages', 'Top 5 Images in Use', '(Reservations &lt; 24 hours long)');
	print addWidget('toplongimages', 'Top 5 Long Term Images in Use', '(Reservations &gt; 24 hours long)');
	print addWidget('toppastimages', 'Top 5 Images From Past Day', '(Reservations with a start<br>time within past 24 hours)');
	print addWidget('topfailedcomputers', 'Top Recent Computer Failures', '(Failed in the last 5 days)');
	print "</td>\n";
	# -------- end left column ---------

	# ---------- right column ---------
	print "<td valign=\"top\">\n";
	print addWidget('topfailed', 'Top Recent Image Failures', '(Failed in the last 5 days)');
	print addWidget('blockallocation', 'Block Allocation Status');
	print addLineChart('reschart', 'Past 12 Hours of Active Reservations');
	print "</td>\n";
	# -------- end right column --------

	print "</tr>\n";
	print "</table>\n";
	print addWidget('newreservations', 'Loading Reservations', '');
	$cont = addContinuationsEntry('AJupdateDashboard', array('val' => 0), 90, 1, 0);
	print "<input type=\"hidden\" id=\"updatecont\" value=\"$cont\">\n";
}

////////////////////////////////////////////////////////////////////////////////
///
/// \fn AJupdateDashboard
///
/// \brief gets updated data and returns it in json format
///
////////////////////////////////////////////////////////////////////////////////
function AJupdateDashboard() {
	$data = array();
	$data['cont'] = addContinuationsEntry('AJupdateDashboard', array(), 90, 1, 0);
	$data['status'] = getStatusData();
	$data['topimages'] = getTopImageData();
	$data['toplongimages'] = getTopLongImageData();
	$data['toppastimages'] = getTopPastImageData();
	$data['topfailed'] = getTopFailedData();
	$data['topfailedcomputers'] = getTopFailedComputersData();
	$data['reschart'] = getActiveResChartData();
	$data['blockallocation'] = getBlockAllocationData();
	$data['newreservations'] = getNewReservationData();
	sendJSON($data);
}

////////////////////////////////////////////////////////////////////////////////
///
/// \fn addWidget($id, $title, extra)
///
/// \param $id - dom id for div
/// \param $title - title to print at top of box
/// \param $extra (optional, default='') - extra text to be placed below title,
///        but above data
///
/// \return div element for a section of data
///
/// \brief creates HTML for a box of data to be displayed
///
////////////////////////////////////////////////////////////////////////////////
function addWidget($id, $title, $extra='') {
	$txt  = "<div class=\"dashwidget\">\n";
	$txt .= "<h3>$title</h3>\n";
	if($extra != '')
		$txt .= "<div class=\"extra\">$extra</div>\n";
	$txt .= "<div id=\"$id\"></div>\n";
	$txt .= "</div>\n";
	return $txt;
}

////////////////////////////////////////////////////////////////////////////////
///
/// \fn addLineChart($id, $title)
///
/// \param $id - dom id for chart
/// \param $title - title to print at top of box
///
/// \return div element for a section of data
///
/// \brief creates HTML for a box of data to be displayed containing a dojox
///        chart
///
////////////////////////////////////////////////////////////////////////////////
function addLineChart($id, $title) {
	$txt  = "<div class=\"dashwidget\">\n";
	$txt .= "<h3>$title</h3>\n";
	$txt .= "<div dojoType=\"dojox.charting.widget.Chart2D\" id=\"$id\"\n";
	$txt .= "     theme=\"dojox.charting.themes.ThreeD\"\n";
	$txt .= "     style=\"width: 300px; height: 300px; text-align: left;\">\n";
	$txt .= "<div class=\"axis\"\n";
	$txt .= "     name=\"x\"\n";
	$txt .= "     labelFunc=\"timestampToTime\"\n";
	$txt .= "     maxLabelSize=\"35\"\n";
	$txt .= "     rotation=\"-90\"\n";
	$txt .= "     majorTickStep=\"4\"\n";
	$txt .= "     minorTickStep=\"1\">\n";
	$txt .= "     </div>\n";
	$txt .= "<div class=\"axis\" name=\"y\" vertical=\"true\" includeZero=\"true\"></div>\n";
	$txt .= "<div class=\"plot\" name=\"default\" type=\"Lines\" markers=\"true\"></div>\n";
	$txt .= "<div class=\"plot\" name=\"grid\" type=\"Grid\" vMajorLines=\"false\"></div>\n";
	$txt .= "<div class=\"series\" name=\"Main\" data=\"0\"></div>\n";
	$txt .= "<div class=\"action\" type=\"Tooltip\"></div>\n";
	$txt .= "<div class=\"action\" type=\"Magnify\"></div>\n";
	$txt .= "</div>\n";
	$txt .= "</div>\n";
	return $txt;
}

////////////////////////////////////////////////////////////////////////////////
///
/// \fn getStatusData()
///
/// \return array of data where element is an array with these keys:\n
/// \b key - name of data to be displayed\n
/// \b val - value of data\n
/// \b tooltip (optional) - text to be displayed when hovering over the key
///
/// \brief gets general status data about VCL system
///
////////////////////////////////////////////////////////////////////////////////
function getStatusData() {
	$affilid = getDashboardAffilID();
	$data = array();
	$data[] = array('key' => 'Active Reservations', 'val' => 0);
	$data[] = array('key' => 'Online Computers', 'val' => 0, 'tooltip' => 'Computers in states available, reserved,<br>reloading, inuse, or timeout');
	$data[] = array('key' => 'Failed Computers', 'val' => 0);
	$reloadid = getUserlistID('vclreload@Local');
	if($affilid == 0) {
		$query = "SELECT COUNT(id) "
		       . "FROM request "
		       . "WHERE userid != $reloadid AND "
		       .       "stateid NOT IN (1, 5, 12) AND "
		       .       "start < NOW() AND "
				 .       "end > NOW()";
	}
	else {
		$query = "SELECT COUNT(rq.id) "
		       . "FROM request rq, "
		       .      "user u "
		       . "WHERE rq.userid != $reloadid AND "
		       .       "rq.userid = u.id AND "
		       .       "u.affiliationid = $affilid AND "
		       .       "rq.stateid NOT IN (1, 5, 12) AND "
		       .       "rq.start < NOW() AND "
				 .       "rq.end > NOW()";
	}
	$qh = doQuery($query, 101);
	if($row = mysql_fetch_row($qh))
		$data[0]['val'] = $row[0];

	$query = "SELECT COUNT(id) FROM computer WHERE stateid IN (2, 3, 6, 8, 11)";
	$qh = doQuery($query, 101);
	if($row = mysql_fetch_row($qh))
		$data[1]['val'] = $row[0];

	$query = "SELECT COUNT(id) FROM computer WHERE stateid = 5";
	$qh = doQuery($query, 101);
	if($row = mysql_fetch_row($qh))
		$data[2]['val'] = $row[0];
	return $data;
}

////////////////////////////////////////////////////////////////////////////////
///
/// \fn getTopImageData()
///
/// \return array of data with these keys:\n
/// \b prettyname - name of image\n
/// \b count - number of reservations for this image
///
/// \brief gets data about top used images with reservation duration <= 24 hours
///
////////////////////////////////////////////////////////////////////////////////
function getTopImageData() {
	$affilid = getDashboardAffilID();
	if($affilid == 0) {
		$query = "SELECT COUNT(rs.imageid) AS count, "
		       .        "i.prettyname "
		       . "FROM reservation rs, "
		       .      "request rq, "
		       .      "image i "
		       . "WHERE rs.imageid = i.id AND "
		       .       "rq.stateid = 8 AND "
		       .       "rs.requestid = rq.id AND "
		       .       "TIMESTAMPDIFF(HOUR, rq.start, rq.end) <= 24 "
		       . "GROUP BY rs.imageid "
		       . "ORDER BY count DESC "
		       . "LIMIT 5";
	}
	else {
		$query = "SELECT COUNT(rs.imageid) AS count, "
		       .        "i.prettyname "
		       . "FROM reservation rs, "
		       .      "request rq, "
		       .      "image i, "
		       .      "user u "
		       . "WHERE rq.userid = u.id AND "
		       .       "u.affiliationid = $affilid AND "
		       .       "rs.imageid = i.id AND "
				 .       "rq.stateid = 8 AND "
				 .       "rs.requestid = rq.id AND "
				 .       "TIMESTAMPDIFF(HOUR, rq.start, rq.end) <= 24 "
		       . "GROUP BY rs.imageid "
		       . "ORDER BY count DESC "
		       . "LIMIT 5";
	}
	$data = array();
	$qh = doQuery($query, 101);
	while($row = mysql_fetch_assoc($qh))
		$data[] = $row;
	return $data;
}

////////////////////////////////////////////////////////////////////////////////
///
/// \fn getTopLongImageData()
///
/// \return array of data with these keys:\n
/// \b prettyname - name of image\n
/// \b count - number of reservations for this image
///
/// \brief gets data about top used images with reservation duration > 24 hours
///
////////////////////////////////////////////////////////////////////////////////
function getTopLongImageData() {
	$affilid = getDashboardAffilID();
	if($affilid == 0) {
		$query = "SELECT COUNT(rs.imageid) AS count, "
		       .        "i.prettyname "
		       . "FROM reservation rs, "
		       .      "request rq, "
		       .      "image i "
		       . "WHERE rs.imageid = i.id AND "
		       .       "rq.stateid = 8 AND "
		       .       "rs.requestid = rq.id AND "
		       .       "TIMESTAMPDIFF(HOUR, rq.start, rq.end) > 24 "
		       . "GROUP BY rs.imageid "
		       . "ORDER BY count DESC "
		       . "LIMIT 5";
	}
	else {
		$query = "SELECT COUNT(rs.imageid) AS count, "
		       .        "i.prettyname "
		       . "FROM reservation rs, "
		       .      "request rq, "
		       .      "image i, "
		       .      "user u "
		       . "WHERE rq.userid = u.id AND "
		       .       "u.affiliationid = $affilid AND "
		       .       "rs.imageid = i.id AND "
		       .       "rq.stateid = 8 AND "
		       .       "rs.requestid = rq.id AND "
		       .       "TIMESTAMPDIFF(HOUR, rq.start, rq.end) > 24 "
		       . "GROUP BY rs.imageid "
		       . "ORDER BY count DESC "
		       . "LIMIT 5";
	}
	$data = array();
	$qh = doQuery($query, 101);
	while($row = mysql_fetch_assoc($qh))
		$data[] = $row;
	return $data;
}

////////////////////////////////////////////////////////////////////////////////
///
/// \fn getTopPastImageData()
///
/// \return array of data with these keys:\n
/// \b prettyname - name of image\n
/// \b count - number of reservations for this image
///
/// \brief gets data about top reserved images over past day
///
////////////////////////////////////////////////////////////////////////////////
function getTopPastImageData() {
	$affilid = getDashboardAffilID();
	$reloadid = getUserlistID('vclreload@Local');
	if($affilid == 0) {
		$query = "SELECT COUNT(l.imageid) AS count, "
		       .        "i.prettyname "
		       . "FROM log l, "
		       .      "image i "
		       . "WHERE l.imageid = i.id AND "
		       .       "l.wasavailable = 1 AND "
		       .       "l.userid != $reloadid AND "
		       .       "l.start > DATE_SUB(NOW(), INTERVAL 1 DAY) "
		       . "GROUP BY l.imageid "
		       . "ORDER BY count DESC "
		       . "LIMIT 5";
	}
	else {
		$query = "SELECT COUNT(l.imageid) AS count, "
		       .        "i.prettyname "
		       . "FROM log l, "
		       .      "image i, "
		       .      "user u "
		       . "WHERE l.userid = u.id AND "
		       .       "u.affiliationid = $affilid AND "
		       .       "l.imageid = i.id AND "
		       .       "l.wasavailable = 1 AND "
		       .       "l.userid != $reloadid AND "
		       .       "l.start > DATE_SUB(NOW(), INTERVAL 1 DAY) "
		       . "GROUP BY l.imageid "
		       . "ORDER BY count DESC "
		       . "LIMIT 5";
	}
	$data = array();
	$qh = doQuery($query, 101);
	while($row = mysql_fetch_assoc($qh))
		$data[] = $row;
	return $data;
}

////////////////////////////////////////////////////////////////////////////////
///
/// \fn getTopFailedData()
///
/// \return array of data with these keys:\n
/// \b prettyname - name of image\n
/// \b count - number of failed reservations for this image
///
/// \brief gets data about images that have failed within the last 5 days
///
////////////////////////////////////////////////////////////////////////////////
function getTopFailedData() {
	$affilid = getDashboardAffilID();
	if($affilid == 0) {
		$query = "SELECT COUNT(l.imageid) AS count, "
		       .        "i.prettyname "
		       . "FROM log l, "
		       .      "image i "
		       . "WHERE l.imageid = i.id AND "
		       .       "l.ending = 'failed' AND "
		       .       "l.start > DATE_SUB(NOW(), INTERVAL 5 DAY) "
		       . "GROUP BY l.imageid "
		       . "ORDER BY count DESC "
		       . "LIMIT 5";
	}
	else {
		$query = "SELECT COUNT(l.imageid) AS count, "
		       .        "i.prettyname "
		       . "FROM log l, "
		       .      "image i, "
		       .      "user u "
		       . "WHERE l.userid = u.id AND "
		       .       "u.affiliationid = $affilid AND "
		       .       "l.imageid = i.id AND "
		       .       "l.ending = 'failed' AND "
		       .       "l.start > DATE_SUB(NOW(), INTERVAL 5 DAY) "
		       . "GROUP BY l.imageid "
		       . "ORDER BY count DESC "
		       . "LIMIT 5";
	}
	$data = array();
	$qh = doQuery($query, 101);
	while($row = mysql_fetch_assoc($qh))
		$data[] = $row;
	return $data;
}

////////////////////////////////////////////////////////////////////////////////
///
/// \fn getTopFailedComputersData()
///
/// \return array of data with these keys:\n
/// \b hostname - name of image\n
/// \b count - number of failures computer has had
///
/// \brief gets data about computers that have failed within the last 5 days
///
////////////////////////////////////////////////////////////////////////////////
function getTopFailedComputersData() {
	$affilid = getDashboardAffilID();
	if($affilid == 0) {
		$query = "SELECT COUNT(s.computerid) AS count, "
		       .        "c.hostname "
		       . "FROM log l, "
		       .      "sublog s, "
		       .      "computer c "
		       . "WHERE s.logid = l.id AND "
		       .       "s.computerid = c.id AND "
		       .       "l.ending = 'failed' AND "
		       .       "l.start > DATE_SUB(NOW(), INTERVAL 5 DAY) "
		       . "GROUP BY s.computerid "
		       . "ORDER BY count DESC "
		       . "LIMIT 5";
	}
	else {
		$query = "SELECT COUNT(s.computerid) AS count, "
		       .        "c.hostname "
		       . "FROM log l, "
		       .      "sublog s, "
		       .      "computer c, "
		       .      "user u "
		       . "WHERE l.userid = u.id AND "
		       .       "u.affiliationid = $affilid AND "
		       .       "s.logid = l.id AND "
		       .       "s.computerid = c.id AND "
		       .       "l.ending = 'failed' AND "
		       .       "l.start > DATE_SUB(NOW(), INTERVAL 5 DAY) "
		       . "GROUP BY s.computerid "
		       . "ORDER BY count DESC "
		       . "LIMIT 5";
	}
	$data = array();
	$qh = doQuery($query, 101);
	while($row = mysql_fetch_assoc($qh))
		$data[] = $row;
	return $data;
}

////////////////////////////////////////////////////////////////////////////////
///
/// \fn getActiveResChartData()
///
/// \return array with these keys:\n
/// \b points - array with these keys\n
/// \t\b x - x value for point (1-n)\n
/// \t\b y - number of reservations in this bin\n
/// \t\b value - same as x\n
/// \t\b text - value to be displayed as x label
/// \b maxy - max y value of returned data
///
/// \brief gets data about reservations during last 12 hours divided into bins
///        of 15 minutes
///
////////////////////////////////////////////////////////////////////////////////
function getActiveResChartData() {
	$data = array();
	$chartstart = unixFloor15(time() - (12 * 3600));
	$chartend = $chartstart + (12 * 3600) + 900;
	for($time = $chartstart, $i = 0; $time < $chartend; $time += 900, $i++) {
		$fmttime = date('g:i a', $time);
		$data["points"][$i] = array('x' => $i, 'y' => 0, 'value' => $i, 'text' => $fmttime);
	}
	$data['maxy'] = 0;
	$reloadid = getUserlistID('vclreload@Local');
	$affilid = getDashboardAffilID();
	if($affilid == 0) {
		$query = "SELECT id, "
		       .        "UNIX_TIMESTAMP(start) AS start, "
		       .        "UNIX_TIMESTAMP(finalend) AS end "
		       . "FROM log "
		       . "WHERE start < NOW() AND "
		       .       "finalend > DATE_SUB(NOW(), INTERVAL 12 HOUR) AND "
		       .       "ending NOT IN ('failed', 'failedtest') AND "
		       .       "wasavailable = 1 AND "
		       .       "userid != $reloadid";
	}
	else {
		$query = "SELECT l.id, "
		       .        "UNIX_TIMESTAMP(l.start) AS start, "
		       .        "UNIX_TIMESTAMP(l.finalend) AS end "
		       . "FROM log l, "
		       .      "user u "
		       . "WHERE l.userid = u.id AND "
		       .       "u.affiliationid = $affilid AND "
		       .       "l.start < NOW() AND "
		       .       "l.finalend > DATE_SUB(NOW(), INTERVAL 12 HOUR) AND "
		       .       "l.ending NOT IN ('failed', 'failedtest') AND "
		       .       "l.wasavailable = 1 AND "
		       .       "l.userid != $reloadid";
	}
	$qh = doQuery($query, 101);
	while($row = mysql_fetch_assoc($qh)) {
		for($binstart = $chartstart, $binend = $chartstart + 900, $binindex = 0; 
		   $binend <= $chartend;
		   $binstart += 900, $binend += 900, $binindex++) {
			if($binend <= $row['start'])
				continue;
			elseif($row['start'] < $binend &&
				$row['end'] > $binstart) {
				$data["points"][$binindex]['y']++;
			}
			elseif($binstart >= $row['end'])
				break;
		}
	}
	for($time = $chartstart, $i = 0; $time < $chartend; $time += 900, $i++) {
		if($data["points"][$i]['y'] > $data['maxy'])
			$data['maxy'] = $data['points'][$i]['y'];
		$data["points"][$i]['tooltip'] = "{$data['points'][$i]['text']}: {$data['points'][$i]['y']}";
	}
	return $data;
}

////////////////////////////////////////////////////////////////////////////////
///
/// \fn getBlockAllocationData()
///
/// \return array of data where element is an array with these keys:\n
/// \b title - name of data to be displayed\n
/// \b val - value of data\n
///
/// \brief gets information about active block allocations
///
////////////////////////////////////////////////////////////////////////////////
function getBlockAllocationData() {
	$affilid = getDashboardAffilID();
	# active block allocation
	if($affilid == 0) {
		$query = "SELECT COUNT(id) "
		       . "FROM blockTimes "
		       . "WHERE skip = 0 AND "
		       .       "start < NOW() AND "
				 .       "end > NOW()";
	}
	else {
		$query = "SELECT COUNT(bt.id) "
		       . "FROM blockTimes bt, "
		       .      "blockRequest br, "
		       .      "user u "
		       . "WHERE bt.blockRequestid = br.id AND "
		       .       "br.ownerid = u.id AND "
		       .       "u.affiliationid = $affilid AND "
		       .       "bt.skip = 0 AND "
		       .       "bt.start < NOW() AND "
				 .       "bt.end > NOW()";
	}
	$qh = doQuery($query, 101);
	$row = mysql_fetch_row($qh);
	$blockcount = $row[0];
	# computers in blockComputers for active allocations
	if($affilid == 0) {
		$query = "SELECT bc.computerid, "
		       .        "c.stateid "
		       . "FROM blockComputers bc "
		       . "LEFT JOIN computer c ON (c.id = bc.computerid) "
		       . "LEFT JOIN blockTimes bt ON (bt.id = bc.blockTimeid) "
		       . "WHERE c.stateid IN (2, 3, 6, 8, 19) AND "
		       .       "bt.start < NOW() AND "
		       .       "bt.end > NOW()";
	}
	else {
		$query = "SELECT bc.computerid, "
		       .        "c.stateid "
		       . "FROM blockComputers bc "
		       . "LEFT JOIN computer c ON (c.id = bc.computerid) "
		       . "LEFT JOIN blockTimes bt ON (bt.id = bc.blockTimeid) "
		       . "LEFT JOIN blockRequest br ON (bt.blockRequestid = br.id) "
		       . "LEFT JOIN user u ON (br.ownerid = u.id) "
		       . "WHERE u.affiliationid = $affilid AND "
		       .       "c.stateid IN (2, 3, 6, 8, 19) AND "
		       .       "bt.start < NOW() AND "
		       .       "bt.end > NOW()";
	}
	$qh = doQuery($query, 101);
	$total = 0;
	$used = 0;
	while($row = mysql_fetch_assoc($qh)) {
		$total++;
		if($row['stateid'] == 3 || $row['stateid'] == 8)
			$used++;
	}
	if($total)
		$compused = sprintf('%d / %d (%0.2f %%)', $used, $total, ($used / $total * 100));
	else
		$compused = 0;
	# number of computers that should be allocated
	if($affilid == 0) {
		$query = "SELECT br.numMachines "
		       . "FROM blockRequest br "
		       . "LEFT JOIN blockTimes bt ON (bt.blockRequestid = br.id) "
		       . "WHERE bt.start < NOW() AND "
		       .       "bt.end > NOW() AND "
		       .       "bt.skip = 0";
	}
	else {
		$query = "SELECT br.numMachines "
		       . "FROM blockRequest br "
		       . "LEFT JOIN blockTimes bt ON (bt.blockRequestid = br.id) "
		       . "LEFT JOIN user u ON (br.ownerid = u.id) "
		       . "WHERE u.affiliationid = $affilid AND "
		       .       "bt.start < NOW() AND "
		       .       "bt.end > NOW() AND "
		       .       "bt.skip = 0";
	}
	$alloc = 0;
	$qh = doQuery($query, 101);
	while($row = mysql_fetch_assoc($qh))
		$alloc += $row['numMachines'];
	if($alloc)
		$failed = sprintf('%d / %d (%0.2f %%)', ($alloc - $total), $alloc, (($alloc - $total) / $alloc * 100));
	else
		$failed = 0;
	$data = array();
	$data[] = array('title' => 'Active Block Allocations', 'val' => $blockcount);
	$data[] = array('title' => 'Block Computer Usage', 'val' => $compused);
	$data[] = array('title' => 'Failed Block Computers', 'val' => $failed);
	return $data;
}

////////////////////////////////////////////////////////////////////////////////
///
/// \fn getNewReservationData()
///
/// \return array of data with these keys:\n
/// \b computer - hostname of computer (without domain)\n
/// \b image - image being loaded\n
/// \b id - id of request\n
/// \b start - start date\n
/// \b state - current and last state\n
/// \b installtype - install for reservation\n
/// \b managementnode - hostname of mangementnode
///
/// \brief gets information about loading reservations
///
////////////////////////////////////////////////////////////////////////////////
function getNewReservationData() {
	$query = "SELECT c.hostname AS computer, "
	       .        "i.prettyname AS image, "
	       .        "rq.id, "
	       .        "rq.start, "
	       .        "CONCAT(s1.name, '|', s2.name) AS state, "
	       .        "o.installtype, "
	       .        "m.hostname AS managementnode "
	       . "FROM request rq "
	       . "LEFT JOIN reservation rs ON (rs.requestid = rq.id) "
	       . "LEFT JOIN computer c ON (c.id = rs.computerid) "
	       . "LEFT JOIN image i ON (i.id = rs.imageid) "
	       . "LEFT JOIN OS o ON (o.id = i.OSid) "
	       . "LEFT JOIN state s1 ON (s1.id = rq.stateid) "
	       . "LEFT JOIN state s2 ON (s2.id = rq.laststateid) "
	       . "LEFT JOIN managementnode m ON (m.id = rs.managementnodeid) "
	       . "WHERE (rq.stateid IN (13, 19, 6) OR "
	       .       "(rq.stateid = 14 AND rq.laststateid IN (6, 13, 19))) AND "
	       .       "rq.start < NOW() "
	       . "ORDER BY rq.start";
	$qh = doQuery($query, 101);
	$data = array();
	while($row = mysql_fetch_assoc($qh)) {
		$tmp = explode('.', $row['computer']);
		$row['computer'] = $tmp[0];
		$tmp = explode(' ', $row['start']);
		$row['start'] = $tmp[1];
		$tmp = explode('.', $row['managementnode']);
		$row['managementnode'] = $tmp[0];
		$data[] = $row;
	}
	return $data;
}

////////////////////////////////////////////////////////////////////////////////
///
/// \fn getDashboardAffilID()
///
/// \return an affiliation id
///
/// \brief if user has access to view the dashboard for any affiliation, returns
/// selected affiliationid; otherwise, returns user's affiliationid
///
////////////////////////////////////////////////////////////////////////////////
function getDashboardAffilID() {
	global $user;
	if(! checkUserHasPerm('View Dashboard (global)'))
		return $user['affiliationid'];
	$affilid = processInputVar('affilid', ARG_NUMERIC);
	$affils = getAffiliations();
	if($affilid != 0 && ! array_key_exists($affilid, $affils))
		return 0;
	return $affilid;
}
?>