<?php
if($_POST['act'] == "create") {
   $msg = $_POST['msg'];
   $id = $_POST['ID'];
   
   $file = fopen('ac_data.txt', 'a');
   fwrite($file, "$id\t$msg\r\n");
   fclose($file);
   
} elseif ($_POST['act'] == "remove") {
   $id = $_POST['ID'];
   
   $lines = file('ac_data.txt');
   foreach ($lines as $i=>$line) {
      if(preg_match('/^' . $id . '\t/', $line)) {
         unset($lines[$i]);
         break;
      }
   }
   
   $file = fopen('ac_data.txt', 'w+');
   foreach ($lines as $line) {
      fwrite($file, $line);
   }
   fclose($file);

} elseif ($_POST['act'] == "display") {
   $id = $_POST['ID'];
   $msg = $_POST['msg'];
   include('ac_message_template.php');
   
} elseif ($_POST['act'] == "edit") {
   $id = $_POST['ID'];
   $msg = $_POST['msg'];
   include('ac_message_template.php');
   
   $lines = file('ac_data.txt');
   foreach ($lines as $i=>$line) {
      if(preg_match('/^' . $id . '\t/', $line)) {
         $lines[$i] = $id . "\t" . $msg . "\r\n";
         break;
      }
   }
   
   $file = fopen('ac_data.txt', 'w+');
   foreach ($lines as $line) {
      fwrite($file, $line);
   }
   fclose($file);
}

?>