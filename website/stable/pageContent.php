<?php

/* Notice
 * 
 * If you are modfiying this file, you must also update pageTemplate.html in the related resources section accordingly.
 */

// Import files
try {
	require_once(realpath($_SERVER['DOCUMENT_ROOT']).'/resources/serverside_scripts/global.php');
	require($GLOBALS['root'].'/resources/serverside_scripts/database_manager.php');
}
catch(Exception $e) {
	http_response_code(500);
	exit('Internal error occurred');
}

$dbReader = new DBManager('ohtar', 'logindb');

$loggedIn = isset($_COOKIE['username']);

function head() {
	echo '<link rel="icon" type="image/png" href="/resources/images/favicon.png" /><link rel="stylesheet" type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css" /><link rel="stylesheet" type="text/css" href="/resources/styling/baseStyling.css" /><script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script><script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script><script src="/resources/scripts/baseScript.php"></script>';
}

function bodyStart() {
	global $loggedIn;
	echo '<div id="primaryContainer"><div id="hoveringContent">';
	if($loggedIn) {
		echo '<span id="userInfo"><span>';
		echo $_COOKIE['username'];
		echo '</span></span>';
	}
	else {
		echo '<span id="loginButton"><a href="/login.php">Sign Up / Log In</a></span>';
	}
	echo '<div id="rightSidebar1Header" class="sidebarHeader">&nbsp;</div><div id="rightSidebar1" class="rightSidebar"><div><div class="sidebarTitle">Latest News</div><div class="pageDivider">&nbsp;</div><div class="sidebarContent">Website Online</div><div class="submenuDivider">&nbsp;</div><div class="sidebarContent">Scribble takes on Helm\'s Deep</div></div></div><div id="rightSidebar2Header" class="sidebarHeader">&nbsp;</div><div id="rightSidebar2" class="rightSidebar"><div><div class="sidebarTitle">Status</div><div class="pageDivider">&nbsp;</div><div class="sidebarContent">Server is online<br />All services are operational</div><div class="submenuDivider">&nbsp;</div><div class="sidebarContent">Awaken Dreams Mod is at version 0.4</div></div></div><div id="rightSidebar3Header" class="sidebarHeader">&nbsp;</div><div id="rightSidebar3" class="rightSidebar"><div>Lower Sidebar</div></div><img src="/resources/images/vignette.png" id="vignette" alt="vignette" /><a id="masthead" class="hiddenLink" href="/index.php">&nbsp;</a><div class="pageDivider">&nbsp;</div><div id="navWrapper"><nav id="mainNavigation"><div id="serverWrapper" class="navMenuWrapper"><div class="submenuTransparency">&nbsp;</div><a href="/server.php" class="hiddenLink">Server</a><div class="navSubmenuWrapper"><ul><li><a href="/places.php" class="hiddenLink">Places of Interest</a><div class="submenuDivider">&nbsp;</div></li><li><a href="/ranks.php" class="hiddenLink">Ranks</a><div class="submenuDivider">&nbsp;</div></li><li><a href="/map.php" class="hiddenLink">Map</a><div class="submenuDivider">&nbsp;</div></li><li><a href="/serverPlans.php" class="hiddenLink">Plans</a></li></ul></div></div><div class="nav-divider">&nbsp;</div><div id="modWrapper" class="navMenuWrapper"><div class="submenuTransparency">&nbsp;</div><a href="/mod.php" class="hiddenLink">Mod</a><div class="navSubmenuWrapper"><ul><li><a href="/features.php" class="hiddenLink">Current Features</a><div class="submenuDivider">&nbsp;</div></li><li><a href="/dataValues.php" class="hiddenLink">Data Values</a><div class="submenuDivider">&nbsp;</div></li><li><a href="/modPlans.php" class="hiddenLink">Plans</a></li></ul></div></div><div class="nav-divider">&nbsp;</div><a href="/forums.php" class="hiddenLink">Forums</a><div class="nav-divider">&nbsp;</div><a href="/help.php" class="hiddenLink">How to Help</a><div class="nav-divider">&nbsp;</div><a href="/contact.php" class="hiddenLink">Contact Us</a></nav></div><div id="pageContents"><noscript>Javascript must be enabled to properly view this website.</noscript><div style="padding: 7px;">';
}

function bodyEnd() {
	echo '</div></div></div></div>';
}
?>