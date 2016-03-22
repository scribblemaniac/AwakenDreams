<?php
include('pageContent.php');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
  <title>Home - The Valar Project</title>
  <?php head(); ?>
  <style type="text/css">
#carousel {
 width: 90%;
 height: 114px;
 padding: 7px;
 margin: 0 auto;
 text-align: center;
 display: none;
}

#carousel img {
 cursor: pointer;
 width: 150px;
 height: 113px;
}

#modalPopupBackground {
 background-color: rgba(0,0,0,0.5);
 width: 100%;
 height: 100%;
 position: absolute;
 top: 0;
 left: 0;
 z-index: 10;
 display: none;
}

#modalPopupWrapper {
 position: absolute;
 left: 10%;
 right: 10%;
 top: 10%;
 bottom: 10%;
 z-index: 11;
 display: none;
}

#modalPopup {
 width: 96%;
 height: 100%;
 margin: 0 auto;
 border-width: 4px;
 border-style: none solid solid;
 border-color: #e8ac3e #39290a #39290a #e8ac3e;
 padding: 0 8px 4px 0;
 position: relative;
 background-image: url("resources/images/containerBackground.png");
 text-align: center;
}

#modalPopupInner {
 border-width: 4px;
 border-style: none solid solid;
 border-color: #39290a #e8ac3e #e8ac3e #39290a;
 z-index: 13;
 position: absolute;
 top: 0;
 right: 0;
 bottom: 0;
 left: 0;
 padding: 0 58px;
}

#modalImageWrapper > img {
 max-height: 95%;
 max-width: 95%;
 position: absolute;
 top: 0;
 right: 0;
 bottom: 0;
 left: 0;
 margin: auto;
}

#modalPopupWrapper > .pageDivider {
 width: 100%;
 margin-top: -8px;
}

#modalPopupVignette {
 position: absolute;
 top: 0;
 right: 4px;
 bottom: 4px;
 left: 4px;
}

#modalPopupVignette > img {
 position: absolute;
 height: 100%;
 width: 100%;
 z-index: 12;
 top: 0;
 left: 0;
}

#modalImageWrapper {
 width: 100%;
 height: 100%;
 position: relative;
}

#modalPopupClose {
 position: absolute;
 top: 0;
 right: 4px;
 width: 50px;
 height: 50px;
 z-index: 14;
 border-bottom: 4px solid #39290a;
 border-left: 4px solid #e8ac3e;
 cursor: pointer;
}

#modalPopupClose > div {
 border-bottom: 4px solid #e8ac3e;
 border-left: 4px solid #39290a;
 background-image: url("resources/images/containerBackground.png");
 position: absolute;
 top: 0;
 right: 0;
 bottom: 0;
 left: 0;
 text-align: center;
 font-size: 22px;
}

#modalPopupLeft {
 position: absolute;
 top: 0;
 left: 4px;
 bottom: 0;
 margin: auto;
 width: 50px;
 height: 100px;
 z-index: 14;
 border-width: 4px;
 border-style: solid solid solid none;
 border-color: #e8ac3e #39290a #39290a #39290a;
 cursor: pointer;
 background-image: url("resources/images/containerBackground.png");
}

#modalPopupLeft > div {
 position: absolute;
 top: 0;
 right: 0;
 bottom: 0;
 left: 0;
 text-align: center;
 font-size: 22px;
 padding-top: 18px;
 border-width: 4px;
 border-style: solid solid solid none;
 border-color: #39290a #e8ac3e #e8ac3e #39290a;
}

#modalPopupLeft.disabled > div {
 background-color: rgba(0, 0, 0, 0.5);
 color: #39290a;
}

#modalPopupRight {
 position: absolute;
 top: 0;
 right: 4px;
 bottom: 0;
 margin: auto;
 width: 50px;
 height: 100px;
 z-index: 14;
 border-width: 4px;
 border-style: solid none solid solid;
 border-color: #e8ac3e #39290a #39290a #e8ac3e;
 cursor: pointer;
 background-image: url("resources/images/containerBackground.png");
}

#modalPopupRight > div {
 position: absolute;
 top: 0;
 right: 0;
 bottom: 0;
 left: 0;
 text-align: center;
 font-size: 22px;
 padding-top: 18px;
 border-width: 4px;
 border-style: solid none solid solid;
 border-color: #39290a #39290a #e8ac3e #39290a;
}

#modalPopupRight.disabled > div {
 background-color: rgba(0, 0, 0, 0.5);
 color: #39290a;
}
  </style>
  <script type="text/javascript">
var currentModalIndex = 0;
var imagesToPreload = ["resources/images/screenshots/fullsize/rivendell.png", "resources/images/screenshots/fullsize/bree.png", "resources/images/screenshots/fullsize/blocks0.4.png", "resources/images/screenshots/fullsize/fornost_night.png"];

$(document).ready(function() {
 $("#carousel > img").click(function() {
  $("#modalImageWrapper > img").attr("src", $(this).attr("src").replace(/thumbnail/, "fullsize"));
  currentModalIndex = Number($(this).attr("index"));
  if(currentModalIndex <= 0) {
   $("#modalPopupLeft").addClass("disabled");
  }
  else {
   $("#modalPopupLeft").removeClass("disabled");
  }
  if(currentModalIndex >= $("#carousel > img").size() - 1) {
   $("#modalPopupRight").addClass("disabled");
  }
  else {
   $("#modalPopupRight").removeClass("disabled");
  }
  $("#modalPopupWrapper,#modalPopupBackground").show();
 });
 $("#modalPopupLeft:not(.disabled)").click(function() {
  $("#carousel > img[index=" + (currentModalIndex - 1) + "]").click();
 });
 $("#modalPopupRight:not(.disabled)").click(function() {
  console.log((currentModalIndex + 1));
  $("#carousel > img[index=" + (currentModalIndex + 1) + "]").click();
 });
 $("#modalPopupClose").click(function() {
  $("#modalPopupWrapper,#modalPopupBackground").hide();
 });
 $("#carousel").show();
});
  </script>
 </head>
 <?php flush(); ?>
 <body>
  <div id="modalPopupBackground">&nbsp;</div>
  <div id="modalPopupWrapper">
   <div class="pageDivider">&nbsp;</div>
   <div id="modalPopup">
    <div id="modalPopupVignette"><img src="resources/images/vignette.png">
    </div>
    <div id="modalPopupInner">
     <div id="modalImageWrapper">
      <img src="resources/images/screenshots/fullsize/rivendell.png" />
     </div>
    </div>
    <div id="modalPopupClose"><div>X</div></div>
    <div id="modalPopupLeft"><div>&lt;&lt;</div></div>
    <div id="modalPopupRight"><div>&gt;&gt;</div></div>
   </div>
  </div>
  <?php bodyStart(); ?>
  <div id="carousel">
   <img src="resources/images/screenshots/thumbnail/rivendell.png" index="0" />
   <img src="resources/images/screenshots/thumbnail/bree.png" index="1" />
   <img src="resources/images/screenshots/thumbnail/blocks0.4.png" index="2" />
   <img src="resources/images/screenshots/thumbnail/fornost_night.png" index="3" />
  </div>
  <div style="text-align: center;">
   <h3>Server IP: tvp.squarechair.net</h3>
  </div>
  <h1>Welcome!</h1>
  <p>Welcome to the Valar Project. The Valar project is a project that will replicate the world of Middle Earth (The Lord of the Rings world) into Minecraft.</p>
  <p>TVP (The Valar Project) is a project that encompasses both a Minecraft server and a Minecraft mod. Our server has one of the largest scales of all LotR servers and also benefits from closely working with the Awaken Dreams mod to help build a world that is truly reflective of Tolkien's great works. The Valar Project server will allow you to join and participate in many different events such as parties, large scale battles and large scale builds. The Awaken Dreams mod is part the of The Valar Project, and it aims to enhance people's experiences on our server and to bring those same experiences back to their single-player world.</p>
  <p>One of TVP's primary goals is to allow you to play roleplay on our server and on single player. With the help of the mod we are able to include famous LotR NPCs and a new quest system. With RP you will be able to choose a race and a faction based on your rank. This will open up different opportunities such as which job you will do.</p>
  <p>The Valar Project will not only be the biggest scale Lord of the Rings map but will also be the most detailed. As well as including the Main Building and areas that are included in the war of the ring, we will be recreating middle earth so that means: Villages, Hideouts, Tombs etc. will all be built. All the locations will be based on the real works of J.R.R. Tolkien. Some of the villages and areas were left unnamed by J.R.R Tolkien so we will be naming these places based on their culture, inhabitants and style.</p>
  <h1>Why should you Join The Valar Project?</h1>
  <p>The Valar Project has one of the largest Lord of the Rings based servers and one of the most ambitious mods, which may seem like reason enough to join for some. However the best reason to join us is because it gives you the opportunity to see Middle-earth in a whole new way, living your own life in the world, starting your own adventures, and meeting plenty of great people along the way.</p>
  <p>When you join our server you will be able to:</p>
  <ul>
   <li>Explore Middle Earth</li>
   <li>Help create Middle Earth</li>
   <li>Take part of an event! (like the siege of Helm's Deep or the Battle of Pelennor Fields)</li>
   <li>Get promoted through hard work and receive new privileges</li>
   <li>Play Some Lord of the rings in-game</li>
   <li>Help naming some of the unnamed villages, locations etc.</li>
   <li>Find our Easter eggs all around in Middle Earth (like the ruins of Nogrod etc.)</li>
   <li>Get to Know the other members of The Valar Project</li>
   <li>Let your imagination run wild with LotR based builds in our expansive free build area</li>
  </ul>
  <h1>Getting Started</h1>
  <p>Hopefully by now you've decided that you want be a part of this great experience. To get started, just install the Awaken Dreams mod and come on our server. Our server's address is:</p>
  <h3>tvp.squarechair.net</h3>
  <p>Just come online and introduce yourself. Any of our staff would be glad to give you a tour of our progress so far.</p>
  <?php bodyEnd(); ?>
 </body>
</html>