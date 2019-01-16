<?php
if (empty($_POST['act'])) die();
include 'inc/ac_funcs.php';
//parse the POST vars
foreach ($_POST as $key => $val) {
   ${$key} = $val;
}

//initialize objects
$mngr = new acManager();
$db = $mngr->getDB();
   
switch($act) {    
   case ("createMsg"):
      $msgColl = $mngr->getMsgCollection($threadID);
      // returns the new ID, echo to send it to browser
      echo $db->appendMsg($msg, $msgColl);
      break;
      
   case ("removeMsg"):
      $msgColl = $mngr->getMsgCollection($threadID);
      $db->deleteMsgByID($ID, $msgColl);
      break;
   
   case ("editMsg"):
      $msgColl = $mngr->getMsgCollection($threadID);
      $db->editMsgText($ID, $msg, $msgColl);
      break;
   
   case ("getMsgTemplate"):
      switch ($template) {
         default:
            echo $mngr->getMsgTemplate()->getRawTemplate();
         }
      break;
   
   case ("showMessages"):
      $msgColl = $mngr->getMsgCollection($threadID);
      $msgColl->populateFromDB($db);
      // send only ids and msg texts to the browser
      $msgColl->writeMsgsJSON();
      break;
      
   case ("createThread"):
      $msgColl = $db->createMsgCollection();
      echo $msgColl->getID();
      break;
}

?>