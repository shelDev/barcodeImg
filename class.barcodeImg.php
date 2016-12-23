/*------------------------------------------------------------------------------ 
** class.barcodeImg.php
**------------------------------------------------------------------------------ 
** 
*/ 

class BarcodeImg 
{ 
     private $_appID; 
     private $_pwdApp; 
     private $_fileName; 
     private $_localImageDir = "images/"; 
     private $_ResultXML; 
     private $debug = false; 

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
     public function setDebugTrue(){ 
        $this->debug = true; 
     } 
     public function setDebugFalse(){ 
        $this->debug = false; 
     } 
 
// Application ID and password need to be passed to Cloud OCR server with each request.
// These are be created during registration. Registration at http://cloud.ocrsdk.com/Account/Register

     public function Read(){
          $applicationId = $this->_appID; 
          $password = $this->_pwdApp; 
          $fileName = $this->_fileName; 

          //////////////////////////////////////////////////////////////// 
          // Send an image with barcodes to Cloud OCR server using processImage call
          // with barcodeRecognition profile as a parameter, or
          // send an image of a barcode to Cloud OCR server using processBarcodeField call.
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
          if($this->debug){ 
                echo "<pre>"; 
                print_r($xml); 
                echo "</pre>"; 
          } 
          
          if($xml->task[0]['status']=='NotEnoughCredits'){ 
            die('SORRY: No credits in http://cloud.ocrsdk.com/'); 
          } 
          
          $arr = $xml->task[0]->attributes(); 

          // Task id. 
          $taskid = $arr["id"]; 

          ///////////////////////////////////////////////////////////////// 
          // Get task information in a loop until task processing finishes. 
          // If response contains "Completed" status, extract URL with result. 
          // Download recognition result. 
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

               // Parse XML. 
               $xml = simplexml_load_string($response); 
               if($this->debug){ 
                    echo "<pre>"; 
                    var_dump($xml); 
                    echo "</pre>"; 
              } 
               $arr = $xml->task[0]->attributes(); 
               
          } while ($arr["status"] != "Completed"); 

          // Result is completed, download 

          $url = $arr["resultUrl"]; 
          $ch = curl_init(); 
          curl_setopt($ch, CURLOPT_URL, $url); 
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
          $response = curl_exec($ch); 
          $this->_ResultXML = $response; 
          curl_close($ch); 

          // Parse output XML to extract barcode values. 
          // Note that output XML files have different structure 
          // depending on the method you used for processing. 
     } 
} 
