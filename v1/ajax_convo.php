<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Title</title>

<link rel="stylesheet" type="text/css" href="ajax_convo.css" />

<script type="text/javascript" src="jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="ajax_convo.js"></script>
</head>

<body>

<div id="messages">
   <?php
      include("ac.php");
      ac_showMessages();
   ?>
</div>

<form id="messageForm" method="post">
   <textarea id="message" name="message"></textarea>
   <input id="share" class="submitButton" name="share" type="submit" value="Share" />
   <input id="cancel" class="submitButton" type="submit" name="cancel" value="Cancel" />
</form>


</body>
</html>