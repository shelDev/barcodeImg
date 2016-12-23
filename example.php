<?php 

class BarcodeReader 
{ 
     private $_appID; 
     private $_pwdApp; 
     private $_fileName; 
     private $_localImageDir = "images/"; 
     private $_ResultXML; 

     public function setFileName($filename) 
     { 
          if (!$filename) { 
               die('ERROR: Please, set filename'); 
          } 

          if (!file_exists($this->_localImageDir.$filename)) { 
               die('ERROR: Can\'t found the file on server.('.$this->_localImageDir.$filename.')'); 
          } 
          $this->_fileName = $filename; 
     } 
     public function setPassword($password){ 
        $this->_pwdApp = $password; 
     } 
     public function setAppID($appID){ 
        $this->_appID = $appID; 
     } 
     public function getFileName() 
     { 
          return $this->_fileName; 
     } 

     public function Result(){ 
        
        return $this->_ResultXML; 
     } 
     public function Read() 
     { 
          // Application ID and password are created during registration.
          // Register at http://cloud.ocrsdk.com/Account/Register
          
          $applicationId = $this->_appID; 
          $password = $this->_pwdApp; 
          $fileName = $this->_fileName; 

          //////////////////////////////////////////////////////////////// 
          // Send image with barcodes to Cloud OCR server using processImage call 
          // with barcodeRecognition profile as a parameter, or 
          // Send an image of a barcode to Cloud OCR server using processBarcodeField call. 
          // Get response as XML. 
          // Read taskId from XML. 
          //////////////////////////////////////////////////////////////// 

          // Get path to the file that you are going to process. 
          $local_directory = dirname(__file__) . '/images/'; 

          // Using the processImage method. 
          // Use barcodeRecognition profile to extract barcode values. 
          // Save results in XML (you can use any other available output format). 
          // See details in API Reference. 
          $url = 'http://cloud.ocrsdk.com/processImage?profile=barcodeRecognition&exportFormat=xml'; 


          // Send HTTP POST request and get XML response. 
          $ch = curl_init(); 
          curl_setopt($ch, CURLOPT_URL, $url); 
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
          curl_setopt($ch, CURLOPT_USERPWD, "$applicationId:$password"); 
          curl_setopt($ch, CURLOPT_POST, 1); 
          $post_array = array("my_file" => "@" . $local_directory . '/' . $fileName, ); 
          curl_setopt($ch, CURLOPT_POSTFIELDS, $post_array); 
          $response = curl_exec($ch); 
          curl_close($ch); 

          // Parse XML response. 
          $xml = simplexml_load_string($response); 
          $arr = $xml->task[0]->attributes(); 

          // Task id. 
          $taskid = $arr["id"]; 

          ///////////////////////////////////////////////////////////////// 
          // 4. Get task information in a loop until task processing finishes. 
          // 5. If response contains "Completed" status, extract URL with result. 
          // 6. Download recognition result. 
          ///////////////////////////////////////////////////////////////// 

          $url = 'http://cloud.ocrsdk.com/getTaskStatus'; 
          $qry_str = "?taskid=$taskid"; 

          // Check task status in a loop until it is "Completed". 
          do { 
               sleep(5); 
               $ch = curl_init(); 
               curl_setopt($ch, CURLOPT_URL, $url . $qry_str); 
               curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
               curl_setopt($ch, CURLOPT_USERPWD, "$applicationId:$password"); 
               $response = curl_exec($ch); 
               curl_close($ch); 
               $xml = simplexml_load_string($response); 
               $arr = $xml->task[0]->attributes(); 
          } while ($arr["status"] != "Completed"); 

          // Result is ready. Download it. 

          $url = $arr["resultUrl"]; 
          $ch = curl_init(); 
          curl_setopt($ch, CURLOPT_URL, $url); 
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
          $response = curl_exec($ch); 
          $this->_ResultXML = $response; 
          curl_close($ch); 
     } 
} 

/* USE */ 

$Barcode = new BarcodeReader(); 
$Barcode->setAppID("Barcode Reader PHP"); //use your appID 
$Barcode->setPassword("xxxxxxxxxxxxxxxxxxxxxxxxx"); //use your password 
$Barcode->setFileName('barcode1.jpg'); 
$Barcode->Read(); 
$Result = $Barcode->Result(); 

echo "<img src='images/barcode1.jpg' /><br />"; 
echo "Result: ". 
var_dump($Result); 
