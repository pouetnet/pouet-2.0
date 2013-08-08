<?
class PouetBox {
  var $title;
  var $uniqueID;
  var $logz;
  var $classes;
  function PouetBox() { // constructor
    $this->title = "";
    $this->uniqueID = "pouetbox";
    $this->classes = array();
  }

  function Validate( $data )
  {
    return array();
  }  

  function Commit( $data )
  {
    return array();
  }  

  function ParsePostMessage( $data )
  {
    $errors = $this->Validate( $data );
   
    if (count($errors))
      return $errors;
      
    return $this->Commit( $data );
  }
  
  function GetInsertionID()
  {
    return 0;
  }
  
  function GetData() // override
  {
    return NULL;
  }

  function RenderHeader() {
    echo "\n\n";
    echo "<div class='pouettbl".($this->classes?(" ".implode(" ",$this->classes)):"")."' id='".$this->uniqueID."'>\n";
    echo " <h2>".$this->title."</h2>\n";
    return $s;
  }
  
  function RenderBody() {
    echo " <div class='content'>\n";
    $this->RenderContent();
    echo " </div>\n";
    return $s;
  }

  function RenderContent() { // override
    return "content comes here";
  }
  
  function RenderFooter() {
    echo "</div>\n";
  }
  
  function Render() {
    global $timer;
    $timer[$this->uniqueID." render"]["start"] = microtime_float();
    $this->RenderHeader();
    $this->RenderBody();
    $this->RenderFooter();
    $timer[$this->uniqueID." render"]["end"] = microtime_float();
    return $s;
  }

  function RenderBuffered() {
    ob_start();
    $this->Render();
    return ob_get_clean();
  }

  function LoadFromDB() { // override
  }
  
  function Load()
  {
    global $timer;
    $timer[$this->uniqueID." load"]["start"] = microtime_float();
    $this->LoadFromDB();
    $timer[$this->uniqueID." load"]["end"] = microtime_float();
  } 
}

class PouetBoxCachable extends PouetBox {
  var $cacheTime;
  function PouetBoxCachable() {
    parent::__construct();
    $this->cacheTime = 60*60*24;
  }
  function GetCacheableData() { // override
    return "";
  }

  function LoadFromCachedData($data) { // override
    
  }

  function GetCacheFilename() {
    return POUET_ROOT_LOCAL . "/cache/".$this->uniqueID.".cache";
  }
  
  function SaveToCache() {
    $s = $this->GetCacheableData();
    file_put_contents($this->GetCacheFilename(),$s);
  }

  function GetCachedData() {
    return file_get_contents($this->GetCacheFilename(),$s);
  }
  
  function IsCacheValid() {
    $f = $this->GetCacheFilename();
    return (file_exists($f) && (  (time() - filemtime($f)) < $this->cacheTime));
  }
  
  function ForceCacheUpdate()
  {
    $this->LoadFromDB();
    $this->SaveToCache();
  }
  function SetParameters($data)
  {
  }
  function Load($cached=false) {
    global $timer;
    $timer[$this->uniqueID." load"]["start"] = microtime_float();
    if ($cached) {
      if ($this->IsCacheValid()) {
        $this->logz .= "<!-- loading ".$this->uniqueID." from cache... -->\n";
        $this->LoadFromCachedData($this->GetCachedData());
      } else {
        $this->LoadFromDB();
        $this->SaveToCache();
      }
    } else {
      $this->LoadFromDB();
    }
    $timer[$this->uniqueID." load"]["end"] = microtime_float();
  }
  
};
?>