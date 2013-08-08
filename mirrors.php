<?
include_once("bootstrap.inc.php");
include_once("include_pouet/box-bbs-post.php");

class PouetBoxMirrors extends PouetBox {
  function PouetBoxMirrors() {
    parent::__construct();
    $this->uniqueID = "pouetbox_mirrors";
  }

  function LoadFromDB() {
    $this->prod = PouetProd::spawn($_GET["which"]);
    if (!$this->prod) return;
    
    $a = array(&$this->prod);
    PouetCollectPlatforms( $a );

    $this->title = "mirrors :: ".$this->prod->name;
  }

  function RenderContent() {
    echo "got a 404 or the server is still having its morning coffee? try one of these mirror lists:";
    
    
    $somepos = strrpos(basename($this->prod->download), ".");
  	if ($pos === false) { // not found means it is extensionless, cool for amiga stuff
  	  $extensionless = basename($this->prod->download);
  	} else { //lets strip the extension to help searches for prods of morons who insist in using .rar instead of .zip
  	  $extensionless = substr(basename($this->prod->download), 0, $somepos);
  	} 

    $extensionless = rawurlencode($extensionless);
    
    $links = array();
    
    $links["http://www.scene.org/search.php?search=".$extensionless.""] = $this->prod->name." on scene.org"; //(works now! [in theory])
    $links["http://www.google.com/search?q=".$extensionless.""] = $this->prod->name . " on google";
    $links["http://www.filesearching.com/cgi-bin/s?q=".$extensionless.""] = $this->prod->name . " on filesearching.com";
    $links["http://www.filemirrors.com/search.src?file=".$extensionless.""] = $this->prod->name . " on filemirrors";
    $links["http://hornet.scene.org/cgi-bin/scene-search.cgi?search=".$extensionless.""] = $this->prod->name . " on the hornet archive";

    global $PLATFORMS;
    $hasAmiga = false;
    foreach($this->prod->platforms as $v)
      if (stristr($PLATFORMS[$v]["name"],"amiga")!==false)
        $hasAmiga = true;
        
    if ($hasAmiga)
    {
      $links["http://aminet.net/search.php?query=".$extensionless.""] = $this->prod->name . " on aminet (new)";
      //$links["http://uk.aminet.net/aminetbin/find?".$extensionless.""] = $this->prod->name . " on aminet (uk)";
      //$links["http://de.aminet.net/aminetbin/find?".$extensionless.""] = $this->prod->name . " on aminet (de)";
      //$links["http://no.aminet.net/aminetbin/find?".$extensionless.""] = $this->prod->name . " on aminet (no)";
      $links["http://amigascne.org/cgi-bin/search.cgi?searchstr=".$extensionless.""] = $this->prod->name . " on amigascne.org";
    }
    if (array_search("cracktro",$this->prod->types)!==false)
    { 
      $links["http://www.defacto2.net/cracktros-detail.cfm?type=file&value=".$extensionless.""] = $this->prod->name . " on defacto2";
    }
    echo "<ul>\n";
    foreach($links as $url=>$desc)
      printf("<li><a href='%s'>%s</a></li>",_html($url),_html($desc));
    echo "</ul>\n";
	}
  function RenderFooter() {
    echo "  <div class='foot'><a href='prod.php?which=".$this->prod->id."'>back to "._html($this->prod->name)."</a></div>\n";
    echo "</div>\n";
  }
	
};

$p = new PouetBoxMirrors();
$p->Load();

$TITLE = $p->title;

include("include_pouet/header.php");
include("include_pouet/menu.inc.php");

echo "<div id='content'>\n";
if ($p->prod)
  echo $p->Render();
echo "</div>\n";

include("include_pouet/menu.inc.php");
include("include_pouet/footer.php");
?>
