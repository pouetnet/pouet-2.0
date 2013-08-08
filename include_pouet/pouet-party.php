<?
class PouetParty extends BM_Class {
  static function getTable () { return "parties"; }
  static function getFields() { return array("id","name","web"); }
  static function getExtendedFields() { return array("quand"); }
  static function onAttach( &$node, &$query )
  {
    $node->attach( $query, "added", array("users as addeduser"=>"id"));
  }
  function PrintLinked($year = null) {
    if ($this->id == NO_PARTY_ID) return "";
    if ($this->id == 0) return "??";
    if ($year)
    {
      return sprintf("<a href='party.php?which=%d&amp;when=%d'>%s</a> %d",
        $this->id,$year,_html($this->name),$year);
    } 
    else
    {
      return sprintf("<a href='party.php?which=%d'>%s</a>",
        $this->id,_html($this->name));
    }
  }
  function PrintShort($year = null) {
    if ($this->id == NO_PARTY_ID) return "";
    if ($this->id == 0) return "??";
    $s = shortify_cut($this->name,20);
    return sprintf("<a href='party.php?which=%d&amp;when=%d'>%s %d</a>",
      $this->id,$year,_html($s),$year);
  }
  function RenderFull($year = null) {
    $s = $this->PrintLinked($year);
    if ($this->web)
      $s .= sprintf(" [<a href='%s'>web</a>]",_html($this->web));
    return $s;
  }
  function GetResultsLocalFileName($year)
  {
    return get_local_partyresult_path($this->id,$year);
  }
  function RenderResultsLink($year)
  {
    return "[<a href='party_results.php?which=".$this->id."&amp;when=".$year."'>results</a>] ";
  }
};

BM_AddClass("PouetParty");

class PouetPlacing {
  var $party;
  var $compo;
  var $ranking;
  var $year;
  function PouetPlacing($initarray) {
    $this->party = $initarray["party"];
    $this->compo = $initarray["compo"];
    $this->ranking = $initarray["ranking"];
    $this->year = $initarray["year"];
  }
  function PrintRanking() {
    $n = (int)$this->ranking;
    if (!$n) return "";
    if ($n==97) return "disqualified";
    if ($n==98) return "n/a";
    if ($n==99) return "not shown";
    $suf = "th";
    $p = $n % 10;
    if ($p==3) $suf = "rd";
    if ($p==2) $suf = "nd";
    if ($p==1) $suf = "st";  
    if ($n==11) $suf = "th";
    if ($n==12) $suf = "th";
    return $this->ranking."<span class='ordinal'>".$suf."</span>";
  }
  function PrintResult() {
    $s = $this->PrintRanking();
    if ($s) $s.= " at ";
    $s .= $this->party->PrintLinked($this->year);
    return $s;
      
  }
}

?>