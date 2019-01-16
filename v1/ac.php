<?php
   function ac_showMessages() {
      $file = fopen('ac_data.txt', 'r');
      while (!feof($file)) {
         $line = fgets($file);
         if(empty($line)) break;
         $data = explode("\t", $line);
         $id = $data[0];
         $msg = $data[1];
         include('ac_message_template.php');
      }
   }
?>