<?
include_once("bootstrap.inc.php");

class PouetBoxPartyHeader extends PouetBox 
{
  function PouetBoxPartyHeader( $partyView ) {
    parent::__construct();
    $this->uniqueID = "pouetbox_partyheader";
    
    $this->party = $partyView->party;
    $this->year = $partyView->year;

    $this->title = _html($this->party->name." ".$this->year);
  }
  
  function LoadFromDB()
  {
    $this->partylinks = SQLLib::selectRow(sprintf("SELECT * FROM `partylinks` WHERE party = %d and year = %d",$this->party->id,$this->year));

    $this->years = array();

    $rows = SQLLib::selectRows(sprintf("SELECT party_year FROM prods WHERE party = %d GROUP BY party_year",$this->party->id));
    foreach($rows as $v)
      $this->years[$v->party_year] = true;
    $rows = SQLLib::selectRows(sprintf("SELECT invitationyear FROM prods WHERE invitation = %d GROUP BY invitationyear",$this->party->id));
    foreach($rows as $v)
      $this->years[$v->invitationyear] = true;
  }
  
  function RenderContent() 
  {
    global $currentUser;
    
    if ($this->party->web)
      printf("[<a href='%s'>web</a>]\n",_html($this->party->web));
    if(file_exists($this->party->GetResultsLocalFileName($this->year)))
      echo $this->party->RenderResultsLink( $this->year );
    else if ($currentUser && $currentUser->CanSubmitItems())
      printf(" [<a class='submitadditional' href='submit_party_edition_info.php?which=%d&amp;when=%d'>+results</a>]\n",$this->party->id,$this->year);

    if($this->partylinks->download)
      echo "[<a href='".$this->partylinks->download."'>download</a>]\n";
    else if ($currentUser && $currentUser->CanSubmitItems())
      printf(" [<a class='submitadditional' href='submit_party_edition_info.php?which=%d&amp;when=%d'>+download</a>]\n",$this->party->id,$this->year);

    if($this->partylinks->slengpung)
      echo " [<a href='http://www.slengpung.com/?eventid=".(int)$this->partylinks->slengpung."'>slengpung</a>]";
    else if ($currentUser && $currentUser->CanSubmitItems())
      printf(" [<a class='submitadditional' href='submit_party_edition_info.php?which=%d&amp;when=%d'>+slengpung</a>]\n",$this->party->id,$this->year);
      
    if($this->partylinks->csdb)
      echo " [<a href='http://csdb.dk/event/?id=".(int)$this->partylinks->csdb."'>csdb</a>]";
    else if ($currentUser && $currentUser->CanSubmitItems())
      printf(" [<a class='submitadditional' href='submit_party_edition_info.php?which=%d&amp;when=%d'>+csdb</a>]\n",$this->party->id,$this->year);
      
    if($this->partylinks->zxdemo)
      echo " [<a href='http://zxdemo.org/party.php?id=".(int)$this->partylinks->zxdemo."'>zxdemo</a>]";
    else if ($currentUser && $currentUser->CanSubmitItems())
      printf(" [<a class='submitadditional' href='submit_party_edition_info.php?which=%d&amp;when=%d'>+zxdemo</a>]\n",$this->party->id,$this->year);
      
    if($this->partylinks->artcity)
      echo " [<a href='http://artcity.bitfellas.org/index.php?a=search&type=tag&text=".rawurlencode($this->partylinks->artcity)."'>artcity</a>]";
    else if ($currentUser && $currentUser->CanSubmitItems())
      printf(" [<a class='submitadditional' href='submit_party_edition_info.php?which=%d&amp;when=%d'>+artcity</a>]\n",$this->party->id,$this->year);
      
    if ($currentUser && $currentUser->CanEditItems())
    {
      printf(" [<a href='admin_party_edit.php?which=%d' class='adminlink'>edit</a>]\n",$this->party->id);
      printf(" [<a href='admin_party_edition_edit.php?which=%d&amp;when=%d' class='adminlink'>edit year</a>]\n",$this->party->id,$this->year);
    }
    printf(" [<a href='gloperator_log.php?which=%d&amp;what=party'>glöplog</a>]\n",$this->party->id);
      
  }

  function RenderFooter() 
  {
    $y = array();
    foreach($this->years as $v=>$dummy)
      $y[] = "<a href='party.php?which=".rawurlencode($this->party->id)."&amp;when=".$v."'>".$v."</a>";
    echo "  <div class='yearselect'>".implode(" |\n",$y)."</div>\n";
    echo "  <div class='foot'>added on the ".$this->party->quand." by ".$this->party->addeduser->PrintLinkedName()." ".$this->party->addeduser->PrintLinkedAvatar()."</div>\n";
    echo "</div>\n";
  }
};

class PouetBoxPartyView extends PouetBox 
{
  function PouetBoxPartyView() {
    parent::__construct();
    $this->uniqueID = "pouetbox_partyview";
  }
  
  function LoadFromDB() {
    $this->party = PouetParty::spawn($_GET["which"]);
    if (!$this->party) return;

    if (isset($_GET["when"]))
    {
      $this->year = $_GET["when"];
    }
    else
    {
      $r = SQLLib::selectRow(sprintf_esc("select party_year from prods where party = %d order by rand() limit 1",$_GET["which"]));
      $this->year = $r->party_year;
    }
    
    if ($this->year < 100)
    {
      $this->year += ($this->year < 50 ? 2000 : 1900);
    }
    
    $this->prods = array();
    $s = new BM_Query("prods");
    $s->AddWhere( sprintf_esc("(prods.party = %d AND prods.party_year = %d) or (prodotherparty.party = %d AND prodotherparty.party_year = %d)",$this->party->id,$this->year,$this->party->id,$this->year) );

    // this is where it gets nasty; luckily we can fake it relatively elegantly: ORM won't notice if we override some of the field selections
    $s->AddJoin("left","prodotherparty",sprintf_esc("prodotherparty.prod = prods.id and (prodotherparty.party = %d AND prodotherparty.party_year = %d)",$this->party->id,$this->year));
    foreach($s->fields as &$v)
    {
      if ($v == "prods.partycompo as prods_partycompo")
      {
        $v = "COALESCE(prodotherparty.partycompo,prods.partycompo) as prods_partycompo";
      }
      if ($v == "prods.party_place as prods_party_place")
      {
        $v = "COALESCE(prodotherparty.party_place,prods.party_place) as prods_party_place";
      }
    }

    $dir = "DESC";
    if ($_GET["reverse"])
      $dir = "ASC";
    $this->sortByCompo = false;
    switch($_GET["order"])
    {
      case "type": $s->AddOrder("prods.type ".$dir); break;
      case "name": $s->AddOrder("prods.name ".$dir); break;
      case "group": $s->AddOrder("prods.group1 ".$dir); $s->AddOrder("prods.group2 ".$dir); $s->AddOrder("prods.group3 ".$dir); break;
      case "party": $s->AddOrder("prods_party.name ".$dir); $s->AddOrder("prods.party_year ".$dir); $s->AddOrder("prods.party_place ".$dir); break;
      case "thumbup": $s->AddOrder("prods.voteup ".$dir); break;
      case "thumbpig": $s->AddOrder("prods.votepig ".$dir); break;
      case "thumbdown": $s->AddOrder("prods.votedown ".$dir); break;
      case "avg": $s->AddOrder("prods.voteavg ".$dir); break;
      case "views": $s->AddOrder("prods.views ".$dir); break;
      default: 
      {
        $s->AddOrder( "COALESCE(prodotherparty.partycompo ,prods.partycompo)" );
        $s->AddOrder( "COALESCE(prodotherparty.party_place,prods.party_place)" );
        $this->sortByCompo = true;
        
        // include invitations on top
        $inv = new BM_Query("prods");
        $inv->AddWhere( sprintf_esc("(prods.invitation = %d AND prods.invitationyear = %d)",$this->party->id,$this->year,$this->party->id,$this->year) );
        $inv->AddOrder( "prods.quand" );
        $prods = $inv->perform();
        foreach($prods as &$v)
        {
          $v->partycompo = "invit";
          unset($v->placings);
        }
        
        $this->prods = array_merge( $this->prods, $prods );
      } break;
    }
    $prods = $s->perform();
    $this->prods = array_merge( $this->prods, $prods );
    PouetCollectPlatforms($this->prods);
    PouetCollectAwards($this->prods);

    $this->maxviews = SQLLib::SelectRow("SELECT MAX(views) as m FROM prods")->m;
  }

  function BuildURL( $param ) {
    $query = array_merge($_GET,$param);
    unset( $query["reverse"] );
    if($param["order"] && $_GET["order"] == $param["order"] && !$_GET["reverse"])
      $query["reverse"] = 1;
    return _html("party.php?" . http_build_query($query));
  }
  
  function Render() 
  {
    echo "<table id='".$this->uniqueID."' class='boxtable'>\n";

    $headers = array(
      "compo"=>"compo",
      "type"=>"type",
      "name"=>"prodname",
/*
      "platform"=>"platform",
      "group"=>"group",
      "party"=>"release party",
      "release"=>"release",
      "added"=>"added",
*/
      "thumbup"=>"<img src='http://www.pouet.net/gfx/rulez.gif' alt='rulez' />",
      "thumbpig"=>"<img src='http://www.pouet.net/gfx/isok.gif' alt='piggie' />",
      "thumbdown"=>"<img src='http://www.pouet.net/gfx/sucks.gif' alt='sucks' />",
      "avg"=>"avg",
      "views"=>"popularity",
    );
    
    $lastCompo = "*";
    $headerDone = false;
    foreach($this->prods as $p)
    {
      if ($p->partycompo != $lastCompo && !$headerDone)
      {
        echo "<tr class='sortable'>\n";
        foreach($headers as $key=>$text)
        {
          $out = sprintf("<th><a href='%s' class='%s%s' id='%s'>%s</a></th>\n",
            $this->BuildURL(array("order"=>$key)),$_GET["order"]==$key?"selected":"",($_GET["order"]==$key && $_GET["reverse"])?" reverse":"","sort_".$key,$text); 
          if ($key == "type" || $key == "name") $out = str_replace("</th>","",$out);
          if ($key == "platform" || $key == "name") $out = str_replace("<th>"," ",$out);
          if ($key == "compo" && $this->sortByCompo) $out = "<th>".$p->partycompo."</th>";
          echo $out;
        }
        echo "</tr>\n";
        if (!$this->sortByCompo)
          $headerDone = true;
        $lastCompo = $p->partycompo;
      }
      echo "<tr>\n";
      echo "<td>\n";
      if ($p->placings[0])
        echo $p->placings[0]->PrintRanking();
      echo "</td>\n";
      echo "<td class='prod'>\n";
      echo $p->RenderTypeIcons();
      echo $p->RenderPlatformIcons();
      echo "".$p->RenderLink()." ";
      if ($p->groups)
        echo "by ".$p->RenderGroupsLong()."\n";
      echo $p->RenderAwards();
      echo "</td>\n";
      
      echo "<td class='votes'>".$p->voteup."</td>\n";
      echo "<td class='votes'>".$p->votepig."</td>\n";
      echo "<td class='votes'>".$p->votedown."</td>\n";

      $i = "isok";
      if ($p->voteavg < 0) $i = "sucks";
      if ($p->voteavg > 0) $i = "rulez";
      echo "<td class='votes'>".sprintf("%.2f",$p->voteavg)."&nbsp;<img src='http://www.pouet.net/gfx/".$i.".gif' alt='".$i."' /></td>\n";

      $pop = (int)($p->views * 100 / $this->maxviews);
      echo "<td><div class='innerbar_solo' style='width: ".$pop."px'>&nbsp;<span>".$pop."%</span></div></td>\n";
      
      echo "</tr>\n";
    }
    echo "</table>\n";
    return $s;
  }
};

///////////////////////////////////////////////////////////////////////////////

$p = new PouetBoxPartyView();
$p->Load();
if (!$p->party)
{
  redirect("parties.php");
}
if (!$p->prods && isset($_GET["when"]))
{
  redirect("party.php?which=".(int)$p->party->id);
}

$h = new PouetBoxPartyHeader($p);
$h->Load();
$TITLE = $p->party->name." ".$p->year;


include("include_pouet/header.php");
include("include_pouet/menu.inc.php");

echo "<div id='content'>\n";

if($h) $h->Render();
if($p) $p->Render();

echo "</div>\n";

include("include_pouet/menu.inc.php");
include("include_pouet/footer.php");
?>