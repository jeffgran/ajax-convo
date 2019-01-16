<?php
function ac_showMessages($threadID) {
   
   //initialize objects
   $mngr = new acManager();
   $db = $mngr->getDB();
   
   $msgs = $mngr->getMsgCollection($threadID);
   
   // populate messages and write them
   $msgs->populateFromDB($db);
   $msgs->writeMsgs();
   
}

function ac_showThreads() {
   //initialize objects
   $mngr = new acManager();
   $db = $mngr->getDB();
   
   $threads = $db->getThreadList();
   
   // show thread titles w/ link
   // this is fugly.  need to fix.
   $selected;
   $class = " threadLink_current";
   foreach ($threads as $i=>$title) {
      if ($class != "") $selected = $i;
      echo '<a href="#" class="threadLink' . $class . '" id="thread_' . $i . '">Thread ' . $i . ": $title</a>";
      $class = "";
   }
   return $selected;
}


// this is the only thing that a user should
// have to touch to set up the configuration.
// everything else is handled behind the scenes.
class Config {
   static $dbStyle = "tabbedtext";                                 // [default]"tabbedtext", "mysql"
   // for text db:
   static $textDBFileNamePrefix = "ac_data_";
   static $textDBFilePath = "ac_tt_db/";                           // must end in trailing slash
   // for mysql db:
   static $mysqlDBname = "ajax_convo";
   static $mysqlUser = "ac_user";
   static $mysqlPass = "ajaxrox";
   
   // default message template
   static $msgTemplateFileName = "inc/ac_message_template.php";    // valid tags: {id}, {msg}
   
}

// this is the supreme ruler class.  The goal
// is that the client code should be able to
// access everything it needs through this one
// class, and everything else is abstracted through
// this.
class acManager {
   private $cfg;
   
   function __construct() {
      $this->cfg = ConfigManager::open();
   }
   
   function getDB() {
      return $this->cfg->getDB();
   }
   function getThreadList() {
   }
   function getMsgCollection($ID) {
      return new msgCollection($ID);
   }
   function getMsgTemplate() {
      return $this->cfg->getMsgTemplate();
   }
   function setMsgTemplate($fileStr) {
      $this->cfg->setMsgTemplate($fileStr);
   }
}


// this class is a Singleton that reads the
// configuration options from the Config class
// and stores instances of the objects that
// depend on those options.  All other classes
// should access these objects through here.
class ConfigManager {
   private static $instance;
   private $msgTemplate;
   private $db;
   
   private function __construct() {
      // populate the ConfigManager->msgTemplate
      // with an instance of the msgTemplate class
      // using the template file specified in the
      // Config options
      $this->msgTemplate = new msgTemplate(Config::$msgTemplateFileName);
      
      // populate the ConfigManager->db with an
      // instance of the db that was chosen in 
      // the Config options
      switch (Config::$dbStyle) {
         case ("mysql"):
            $this->db = new acMySqlDB(Config::$mysqlDBname, Config::$mysqlUser, Config::$mysqlPass);
            break;
         case ("tabbedtext"):
            $this->db = new acTabbedTextFileDB(Config::$textDBFilePath, Config::$textDBFileNamePrefix);
            break;
         default:
            $this->db = new acTabbedTextFileDB(Config::$textDBFilePath, Config::$textDBFileNamePrefix);
      }
   }
   
   // get the Singleton instance of this class
   function open() {
      if(empty(self::$instance)) {
         self::$instance = new ConfigManager();
      }
      return self::$instance;
   }
   
   // get the template instance
   function getMsgTemplate() {
      return $this->msgTemplate;
   }
   
   // set a new template on the fly
   function setTemplate($fileStr) {
      if (file_exists($fileStr)) {
         $this->msgTemplate = new msgTemplate($fileStr);
      } else throw new Exception("Template file $fileStr does not exist.");
   }
   
   // get the DB instance
   function getDB() {
      return $this->db;
   }
}

// a collection of msgData objects.
// this class uses the DB object to
// get a set of messages from the database
// and write them using the current template.
class msgCollection {
   private $ID;
   private $msgs = array();
   
   function __construct($ID) {
      $this->ID = $ID;
   }
   
   function getID() {
      return $this->ID;
   }
   
   function pushMsg(msgData $msg) {
      $this->msgs[] = $msg;
   }
   
   function populateFromDB($db) {
      $this->msgs = $db->getMsgs($this);
   }
   
   function writeMsgs() {
      $template = ConfigManager::open()->getMsgTemplate();
      foreach ($this->msgs as $msg) {
         echo $template->getOutput($msg);
      }
   }
   
   // only write the id/text for each message
   // to send to the browswer, which will
   // handle the display via template.
   function writeMsgsJSON() {
      $arr = array();
      foreach ($this->msgs as $msg) {
         $arr[] = array(
            "ID" => $msg->getID(),
            "msg" => $msg->getMsg()
         );
      }
      echo json_encode($arr);
   }
   
}


// we will use polymorphism to abstract
// away the DB functionality.  This is
// kind of silly but it's for practice -
// the two kinds of DBs are a flat text file
// or a mysql database.
abstract class acDBConnect {
   abstract function createMsgCollection();
   abstract function getThreadList();
   abstract function getMsgs(msgCollection $msgColl);
   abstract function appendMsg($msgText, msgCollection $msgColl);
   abstract function editMsgText($ID, $newMsg, msgCollection $msgColl);
   abstract function deleteMsgByID($ID, msgCollection $msgColl);
}

// tab-separated text file "DB"
class acTabbedTextFileDB extends acDBConnect {
   private $filePath;
   private $fileNamePrefix;
   private $filePrefix;
   
   function __construct($filePath, $fileNamePrefix) {
      $this->filePath = $filePath;
      $this->fileNamePrefix = $fileNamePrefix;
      $this->filePrefix = $filePath . $fileNamePrefix;
   }
   
   function createMsgCollection() {
      // get list of thread files
      $threads = $this->getThreadList();
      // find the next unique ID
      $keys = array_keys($threads);
      $newID = max($keys) + 1;
      
      // instantiate and return a new msgCollection w/ that id
      return new msgCollection($newID);
      
      
   }
   
   function getThreadList() {
      $threads = array();
      if ($dir = opendir($this->filePath)) {
         while (false !== ($file = readdir($dir))) {
            if ($file != "." && $file != "..") {
               $num = preg_replace("#" . $this->fileNamePrefix . "([0-9]*)\.txt#", "$1\n", $file);
               $threads[$num] = "";
            }
         }
         closedir($dir);
         return $threads;
      }
   }
   
   function getMsgs(msgCollection $msgColl) {
      $file = fopen($this->getFileName($msgColl), "r");
      while (!feof($file)) {
         $line = fgets($file);
         if(empty($line)) break;
         $data = explode("\t", $line);
         $id = $data[0];
         $msg = $data[1];
         $arr[] = new msgData($id, $msg);
      }
      fclose($file);
      return $arr;
   }
   
   function appendMsg($msgText, msgCollection $msgColl) {
      // create a unique id for the new message.
      $ID = round(microtime(true), 2) * 100;
      
      $file = fopen($this->getFileName($msgColl), 'a');
      fwrite($file, $ID . "\t" . $msgText . "\r\n");
      fclose($file);
      
      // return the id of the new message
      return $ID;
   }
   
   function editMsgText($ID, $newMsg, msgCollection $msgColl) {
      $lines = file($this->getFileName($msgColl));
      
      $i = $this->findLineByID($lines, $ID);
      $lines[$i] = "$ID\t$newMsg\r\n";
      $this->writeLinesToFile($lines, $msgColl);
      // should return true/false depending on if it worked.
   }
   
   function deleteMsgByID($ID, msgCollection $msgColl) {
      $lines = file($this->getFileName($msgColl));
      
      $i = $this->findLineByID($lines, $ID);
      unset($lines[$i]);
      if (count($lines) != 0) {
         $this->writeLinesToFile($lines, $msgColl);
      } else {
         if (is_file($this->getFileName($msgColl)))
            unlink($this->getFileName($msgColl));
      }
      // should return true/false depending on if it worked.
   }
   
   
   
   // private utility functions for working with text files:
   private function getFileName($msgColl) {
      return $this->filePrefix . trim($msgColl->getID()) . ".txt";
   }
   
   
   private function findLineByID($lines, $ID) {
      foreach ($lines as $i=>$line) {
         if(preg_match('/^' . $ID . '\t/', $line)) {
            return $i;
         }
      }
   }
   
   private function writeLinesToFile($lines, $msgColl) {
      $file = fopen($this->getFileName($msgColl), 'w+');
      foreach ($lines as $line) {
         fwrite($file, $line);
      }
      fclose($file);
   }
}


class acMySqlDB extends acDBConnect {
   private $conn;
   
   function __construct($dbname, $user, $pwd) {
      try {
         $this->conn = new PDO("mysql:host=localhost;dbname=$dbname", $user, $pwd);
      } catch (PDOException $e) {
         echo "Could not connect to database";
         exit;
      }
   }
   function createMsgCollection(){
      $this->conn->exec("INSERT INTO threads VALUES (NULL)");
      return new msgCollection($this->conn->lastInsertId());
   }
   function getThreadList(){
      $threads = array();
      $result = $this->conn->query("SELECT * FROM threads");
      foreach ($result as $row) {
         $threads[$row["threadID"]] = "";
      }
      return $threads;
   }
   function getMsgs(msgCollection $msgColl){
      $query = "SELECT * FROM msgs WHERE threadID = ?";
      $stmt = $this->conn->prepare($query);
      $stmt->execute(array($msgColl->getID()));
      
      $arr = array();
      
      while ($row = $stmt->fetch()) {
         $arr[] = new msgData($row['msgID'], $row['msgText']);
      }
      
      return $arr;
   }
   function appendMsg($msgText, msgCollection $msgColl){
      $query = "INSERT INTO msgs (msgText, threadID) VALUES (:msgText, :threadID)";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':msgText', $msgText, PDO::PARAM_STR);
      $stmt->bindParam(':threadID', $msgColl->getID(), PDO::PARAM_INT);
      $stmt->execute();
      return $this->conn->lastInsertId();
   }
   function editMsgText($ID, $newMsg, msgCollection $msgColl){
      $query = "UPDATE msgs SET msgText = ? WHERE msgID = ? AND threadID = ?";
      $stmt = $this->conn->prepare($query);
      return $stmt->execute(array($newMsg, $ID, $msgColl->getID()));
   }
   function deleteMsgByID($ID, msgCollection $msgColl){
      $query = "DELETE FROM msgs WHERE msgID = ? AND threadID = ?";
      $stmt = $this->conn->prepare($query);
      return $stmt->execute(array($ID, $msgColl->getID()));
      // should delete the thread if that was the last message
   }
}



// This class is very simple at the moment
// It will expand to contain more data, such
// as the time/date of the message, the user
// who posted it, etc.
class msgData {
   private $id;
   private $msg;
   
   function __construct($id, $msg) {
      $this->id = $id;
      $this->msg = $msg;
   }
   
   function getID() {
      return $this->id;
   }
   
   function getMsg() {
      return $this->msg;
   }
}

// this class is for working with the template
class msgTemplate {
   private $templateFile;
   
   function __construct($templateFile) {
      $this->templateFile = $templateFile;
   }
   
   function getTemplateFileName() {
      return $this->templateFile;
   }
   
   function getRawTemplate() {
      return file_get_contents($this->templateFile);
   }
   
   function getOutput(msgData $data) {
      $templateHTML = file_get_contents($this->templateFile);
      $val = str_replace("{id}", $data->getID(), $templateHTML);
      $val = str_replace("{msg}", $data->getMsg(), $val);
      return $val;
   }
}

?>