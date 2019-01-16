<?php include 'inc/ac_funcs.php'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
   <title>Ajax Convo</title>
   <link rel="stylesheet" type="text/css" href="ajax_convo.css" />
</head>

<body>
<div id="wrapper">
   <div id="header">
      <h1>Ajax Convo 2.0b</h1>
      <?php
// $test = new acMySqlDB("ajax_convo", "ac_user", "ajaxrox");
// echo $test->createMsgCollection();
      ?>
   </div>
   
   <div id="threadlist">
      <div id="threads">
         <?php $selected = ac_showThreads(); ?>
      </div>
      <a href="#" id="createThreadLink">Create a new thread</a>
   </div>
   
   <div id="threadview">
      <div id="messages">
         <?php ac_showMessages($selected); ?>
      </div>
      
      <form id="messageForm" method="post">
         <textarea id="message" name="message" class="message_prompt message_base"></textarea>
         <input id="share" class="submitButton" name="share" type="submit" value="Share" />
         <input id="cancel" class="submitButton" name="cancel" type="submit" value="Cancel" />
      </form>
   </div>
   
   <script src="jquery-1.3.2.min.js"></script>
   <script src="ajax_convo.js"></script>
</div>
</body>
</html>