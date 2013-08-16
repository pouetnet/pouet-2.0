<?

class PouetBoxSubmitGroup extends PouetBox
{
  function PouetBoxSubmitGroup()
  {
    parent::__construct();
    $this->uniqueID = "pouetbox_submitgroup";
    $this->title = "submit a group!";
    $this->formifier = new Formifier();
    $this->fields = array();
  }

  function ValidateInput( $data )
  {
    $errormessage = array();
    return $errormessage;
  }
  function Validate( $data )
  {
    global $groupID,$currentUser;

    if (!$currentUser)
      return array("you have to be logged in !");

    if (!$currentUser->CanSubmitItems())
      return array("not allowed lol !");

    if (!trim($data["name"]))
    {
      return array("Whitespace groupnames are sooo \t       \t        \t.");
    }
    if ($data["website"])
    {
      $url = parse_url($data["website"]);
      if (($url["scheme"]!="http" && $url["scheme"]!="https") || strstr($data["website"],"://")===false)
        return array("please only websites with http or https links, kthx");
    }
    return array();
  }
  function Commit($data)
  {
    global $groupID;

    $a = array();
    $a["name"] = trim($data["name"]);
    $a["acronym"] = $data["acronym"];
    $a["web"] = $data["website"];
    $a["added"] = get_login_id();
    $a["csdb"] = $data["csdbID"];
    $a["zxdemo"] = $data["zxdemoID"];
    $a["quand"] = date("Y-m-d H:i:s");
    $this->groupID = SQLLib::InsertRow("groups",$a);

    return array();
  }
  function GetInsertionID()
  {
    return (int)$this->groupID;
  }

  function LoadFromDB()
  {
    global $PLATFORMS;
    $plat = array();
	  foreach($PLATFORMS as $k=>$v) $plat[$k] = $v["name"];
	  uasort($plat,"strcasecmp");

    $this->fields = array(
      "name"=>array(
        "name"=>"group name",
        "required"=>true,
      ),
      "acronym"=>array(
        "name"=>"acronym",
      ),
      "website"=>array(
        "name"=>"website url",
        "type"=>"url",
      ),
      "csdbID"=>array(
        "name"=>"csdb ID",
      ),
      "zxdemoID"=>array(
        "name"=>"zxdemo ID",
      ),
    );
    foreach($_POST as $k=>$v)
      if ($this->fields[$k])
        $this->fields[$k]["value"] = $v;
  }

  function Render()
  {
    global $currentUser;
    if (!$currentUser)
      return;

    if (!$currentUser->CanSubmitItems())
      return;

    echo "\n\n";
    echo "<div class='pouettbl' id='".$this->uniqueID."'>\n";

    echo "  <h2>".$this->title."</h2>\n";
    echo "  <div class='content'>\n";
    $this->formifier->RenderForm( $this->fields );
    echo "  </div>\n";

    echo "  <div class='foot'><input type='submit' value='Submit' /></div>";
    echo "</div>\n";
  }
};

?>
