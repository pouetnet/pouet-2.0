<?
require_once("bootstrap.inc.php");
require_once("include_pouet/box-modalmessage.php");
require_once("include_pouet/box-party-submit.php");

if ($currentUser && !$currentUser->CanEditItems())
{
  redirect("index.php");
  exit();
}

class PouetBoxAdminModificationRequests extends PouetBox
{
  function PouetBoxAdminModificationRequests( )
  {
    parent::__construct();
    $this->title = "process the following requests";
  }
  function Commit($data)
  {
    global $currentUser;
    if ($data["requestDeny"])
    {
      $a = array();
      $a["gloperatorID"] = $currentUser->id;
      $a["approved"] = 0;
      $a["approveDate"] = date("Y-m-d H:i:s");
      SQLLib::UpdateRow("modification_requests",$a,"id=".(int)$data["requestID"]);
      return array();
    }
    
    $req = SQLLib::SelectRow(sprintf_esc("select itemID,requestType,requestBlob from modification_requests where id = %d",$data["requestID"]));
    $reqData = unserialize($req->requestBlob);
    switch ($req->requestType)
    {
      case "prod_add_link":
        $a = array();
        $a["prod"] = $req->itemID;
        $a["type"] = $reqData["newLinkKey"];
        $a["link"] = $reqData["newLink"];
        SQLLib::InsertRow("downloadlinks",$a);
        break;
    }
    $a = array();
    $a["gloperatorID"] = $currentUser->id;
    $a["approved"] = 1;
    $a["approveDate"] = date("Y-m-d H:i:s");
    SQLLib::UpdateRow("modification_requests",$a,"id=".(int)$data["requestID"]);
    return array();
  }
  function LoadFromDB()
  {
    $s = new BM_Query("modification_requests");
    $s->AddField("modification_requests.id");
    $s->AddField("modification_requests.requestType");
    $s->AddField("modification_requests.itemID");
    $s->AddField("modification_requests.itemType");
    $s->AddField("modification_requests.requestBlob");
    $s->AddField("modification_requests.requestDate");
    $s->Attach(array("modification_requests"=>"userID"),array("users as user"=>"id"));
    $s->Attach(array("modification_requests"=>"itemID"),array("prods as prod"=>"id"));
    $s->AddWhere("approved is null");
    $s->AddOrder("requestDate desc");
    $this->requests = $s->perform();
  }
  function Render()
  {
    global $REQUESTTYPES;
    echo "<table id='".$this->uniqueID."' class='boxtable'>\n";
    echo "  <tr>\n";
    echo "    <th colspan='6'>".$this->title."</th>\n";
    echo "  </tr>\n";
    echo "  <tr>\n";
    echo "    <th>date</th>\n";
    echo "    <th>user</th>\n";
    echo "    <th>item</th>\n";
    echo "    <th>request</th>\n";
    echo "    <th>details</th>\n";
    echo "    <th>&nbsp;</th>\n";
    echo "  </tr>\n";
    foreach($this->requests as $r)
    {
      echo "  <tr>\n";
      echo "    <td>".$r->requestDate."</td>\n";
      echo "    <td>".$r->user->PrintLinkedAvatar()." ".$r->user->PrintLinkedName()."</td>\n";
      echo "    <td>".$r->itemType.": ";
      switch ($r->itemType)
      {
        case "prod": if ($r->prod) echo $r->prod->RenderSingleRowShort();
      }
      echo "</td>\n";
      echo "    <td>".$REQUESTTYPES[$r->requestType]."</td>\n";
      echo "    <td>";
      $data = unserialize($r->requestBlob);
      switch ($r->requestType)
      {
        case "prod_add_link":
          {
            echo _html($data["newLinkKey"])." - ";
            echo "<a href='"._html($data["newLink"])."'>"._html($data["newLink"])."</a>";
          } break;
        case "prod_change_link":
          {
            $row = SQLLib::selectRow(sprintf_esc("select * from downloadlinks where id = %d",$data["linkID"]));
            echo "<b>old</b>: ";
            echo _html($row->type)." - ";
            echo "<a href='"._html($row->link)."'>"._html($row->link)."</a>";
            echo "<br/><b>new</b>: ";
            echo _html($data["newLinkKey"])." - ";
            echo "<a href='"._html($data["newLink"])."'>"._html($data["newLink"])."</a>";
          } break;
        case "prod_remove_link":
          {
            $row = SQLLib::selectRow(sprintf_esc("select * from downloadlinks where id = %d",$data["linkID"]));
            echo _html($row->type)." - ";
            echo "<a href='"._html($row->link)."'>"._html($row->link)."</a>";
            echo "<br/><b>reason</b>: ";
            echo _html($data["reason"]);
          } break;
      }
      echo "</td>\n";
      echo "<td>";
      printf("  <input type='hidden' name='requestID' value='%d'/>",$r->id);
      printf("  <input type='submit' name='requestAccept' value='accept !'/>");
      printf("  <input type='submit' name='requestDeny' value='deny !'/>");
      echo "</td>\n";
      echo "  </tr>\n";
    }
    echo "</table>\n";
  }
}


$form = new PouetFormProcessor();

$form->SetSuccessURL( "party.php?which=".(int)$_GET["which"], true );

$box = new PouetBoxAdminModificationRequests( );
$form->Add( "party", $box );

$form->SetSuccessURL( "admin_modification_requests.php", true );

if ($currentUser && $currentUser->CanEditItems())
  $form->Process();

$TITLE = "process modification requests";

require_once("include_pouet/header.php");
require("include_pouet/menu.inc.php");

echo "<div id='content'>\n";

if (get_login_id())
{
  $form->Display();
}
else
{
  require_once("include_pouet/box-login.php");
  $box = new PouetBoxLogin();
  $box->Render();
}

echo "</div>\n";

require("include_pouet/menu.inc.php");
require_once("include_pouet/footer.php");

?>
