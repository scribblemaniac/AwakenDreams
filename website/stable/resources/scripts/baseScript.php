<?php

header('MIME-Version: 1.0');
header('Content-Type: text/javascript');

?>

function logMessage(message, level) {
	thresholdLevel = thresholdLevel || 1;
	levelMap = ["DEBUG", "INFO", "WARNING", "ERROR"];
	// Map level string to a ordianal value
	level = level.toUpperCase();
	if(Math.min(0, levelMap.indexOf(level)) < thresholdLevel || !window.console) {
		return;
	}
	var hasLogged = false;
	switch(level) {
	case "debug":
		if(window.console.debug) {
			console.debug("[" + level + "]" + message);
			hasLogged = true;
		}
		break;
	case "info":
		if(window.console.info) {
			console.info("[" + level + "]" + message);
			hasLogged = true;
		}
		break;
	case "warning":
		if(window.console.warn) {
			console.warn("[" + level + "]" + message);
			hasLogged = true;
		}
		break;
	case "error":
		if(window.console.error) {
			console.error("[" + level + "]" + message);
			hasLogged = true;
		}
		break;
	}
	if(!hasLogged) {
		if(window.console.log) {
			console.log("[" + level + "]" + message);
		}
	}
}

var LoginSystem = { realm: "TVP", _loginRound: 0 };

LoginSystem._getStoredItem = function(item) {
	function isSessionStorageSupported() {
		type = "sessionStorage";
		try {
			// TODO refactor this for sessionStorage detection only
			var storage = window[type], x = '__storage_test__';
			storage.setItem(x, x);
			storage.removeItem(x);
			return true;
		}
		catch(e) {
			return false;
		}
	};
	
	if(isSessionStorageSupported()) {
		return sessionStorage.getItem(item);
	}
	else {
		// Output warning about insecurity
		
		// TODO Fallback to cookies
	}
};

LoginSystem._setStoredItem = function(item, value) {
	function isSessionStorageSupported() {
		type = "sessionStorage";
		try {
			// TODO refactor this for sessionStorage detection only
			var storage = window[type], x = '__storage_test__';
			storage.setItem(x, x);
			storage.removeItem(x);
			return true;
		}
		catch(e) {
			return false;
		}
	};
	
	if(isSessionStorageSupported()) {
		return sessionStorage.setItem(item, value);
	}
	else {
		// Output warning about insecurity
		
		// TODO Fallback to cookies
	}
};

LoginSystem.getUsername = function() { return this._getStoredItem("username"); }; // TODO this must be cookie

LoginSystem.setUsername = function(value) { this._setStoredItem("username", value); }; // TODO this must be cookie

LoginSystem.getOpaque = function() { return this._getStoredItem("opaque"); }; // TODO this must be cookie

LoginSystem.setOpaque = function(value) { this._setStoredItem("opaque", value); }; // TODO this must be cookie

LoginSystem.getNonce = function() { return this._getStoredItem("nonce"); };

LoginSystem.setNonce = function(value) { this._setStoredItem("nonce", value); };

LoginSystem.getCNonce = function() { return this._getStoredItem("cnonce"); };

LoginSystem.setCNonce = function(value) { this._setStoredItem("cnonce"); };

LoginSystem.getSubHash = function() { return this._getStoredItem("subhash"); };

LoginSystem.setSubHash = function(value) { this._setStoredItem("subhash"); };

LoginSystem.getHash = function() { return this._getStoredItem("hash"); }; // TODO this must be cookie

LoginSystem.setHash = function(value) { this._setStoredItem("hash"); }; // TODO this must be cookie

LoginSystem._loginRound = function() {
	// Do 32 hash iterations (or 30 on the last round)
	for(var i = 0; i < (this._loginRound == 4064 ? 30 : 32); i++) {
		var shaObj = new jsSHA("SHA-512", "BYTES");
		shaObj.setHMACKey(this.message, "TEXT");
		shaObj.update(this.hash);
		this.hash = shaObj.getHMAC("BYTES");
	}
	this._loginRound += 32;
	
	this.loginProgressBar.val(this.loginProgressBar.val() + 1);
	
	setTimeout(this._loginRound, 0);
};

LoginSystem._loginFinish = function() {
	// The final sub hash iteration outputs as hex
	var shaObj = new jsSHA("SHA-512", "BYTES");
	shaObj.setHMACKey(this.message, "TEXT");
	shaObj.update(this.hash);
	this.hash = shaObj.getHMAC("HEX");
	
	// Generate the hmac for the nonce
	shaObj = new jsSHA("SHA-512", "BYTES");
	shaObj.setHMACKey(this.hash, "TEXT");
	shaObj.update(this.getNonce() + 0);
	var finalHash = shaObj.getHMAC("HEX");
	
	// TODO post request
	$.post("TODO all this stuff", function() {
		// TODO Callback stuff
	});
	
	// Mark login process as finished
	this._loginRound = 0;
}

LoginSystem.login = function(username, password, progressBarElement) {
	// Make sure a login isn't already in progress
	if(this._loginRound != 0) return;
	
	// Begin generating subHash
	this.message = username + this.realm + password;
	var shaObj = new jsSHA("SHA-512", "TEXT");
	shaObj.update(this.message);
	this.hash = shaObj.getHash("BYTES");
	this.loginProgressBar = progressBarElement;
	setTimeout(this._loginRound, 0);
};

LoginSystem.updateLoginCookie = function() {
	if(!this.message) return;
	
	// Generate the hmac
	var shaObj = new jsSHA("SHA-512", "BYTES");
	shaObj.setHMACKey(this.getSubHash(), "TEXT");
	var cnonce = this.getCNonce();
	shaObj.update(this.getNonce() + (++cnonce));
	this.setCNonce(cnonce);
	this.setHash(shaObj.getHMAC("HEX"));
};

LoginSystem.logout = function() {
	// Make sure a login isn't already in progress
	if(this._loginRound != 0) return;
	
	this.updateLoginCookie();
	// TODO post request
	
	this.message = null;
};



$(document).ready(function() {
	$("button").each(function() {
		if(!$(this).parent().hasClass("button-wrapper")) {
			$(this).wrap("<div class=\"button-wrapper\"></div>");
		}
	});
	$("input[type=text],input[type=password]").each(function() {
		if(!$(this).parent().hasClass("text-wrapper")) {
			$(this).wrap("<div class=\"text-wrapper\"></div>");
		}
	});
	//$("#pageContents").css("height", ($("#footerDivider").position().top - $("#pageContents").position().top - 7) + "px");
	/*$("#mainNavBorderBottom").css("width", $("#mainNavigation").outerWidth() + "px");
	submenu = $(".submenu");
	for(i=0;i<submenu.length;i++) {
		$(submenu[i]).children(".submenuBorderBottom").css({ "width": $(submenu[i]).outerWidth() + "px", "top": ($(submenu[i]).children(".submenuBorderBottom").position().top + 7) + "px" });
		$(submenu[i]).children(".submenuBorderLeft").css("height", $(submenu[i]).outerHeight() + "px");
		$(submenu[i]).children(".submenuBorderRight").css({ "height": $(submenu[i]).outerHeight() + "px", "margin-left": ($(submenu[i]).width() + 7) + "px" });
		$(submenu[i]).children(".submenuBorderBottomRight").css({ "margin-left": ($(submenu[i]).width() + 7) + "px", "top": ($(submenu[i]).children(".submenuBorderBottomRight").position().top + 7) + "px" });
		$(submenu[i]).children(".submenuBorderBottomLeft").css("top", ($(submenu[i]).children(".submenuBorderBottomLeft").position().top + 7) + "px");
	}
	$("#mainNavBorderBottomRight").css("margin-left", $("#mainNavigation").width() + "px");
	$("#pageContents p").show();
	$(window).resize(function() {
		$("#pageContents").css("height", ($("#footerDivider").position().top - $("#pageContents").position().top - 7) + "px");
	});
	$("#mainNavigation span").mouseover(function() {
		if(activeMenu != null && activeMenu != $(this).attr("name"))
			$("#mainNavigation span[name=" + activeMenu + "] .submenu").hide();
		activeMenu = $(this).attr("name");
		$(this).children(".submenu").show();
	});
	$("#navWrapper").mouseleave(function() {
		$("#mainNavigation span[name=" + activeMenu + "] .submenu").hide();
		activeMenu = null;
	});
	submenus = $(".submenu");
	for(i=0;i<submenus.length;i++) {
		submenu = $(submenus[i]);
		submenu.css({ "top": ($("#mainNavigation").position().top + $("#mainNavigation").outerHeight()) + "px", "left": (submenu.parent().attr("value") == 0 ? submenu.parent().position().left - 8 : $("#mainNavigation [alignIndex=" + submenu.parent().attr("value") + "]").position().left) + "px" });
		submenu.hide();
	}
	$(".link").click(function() {
		window.location.href = $(this).attr("href");
	});
	$("#primaryContainer").css("visibility", "visible");*/
});