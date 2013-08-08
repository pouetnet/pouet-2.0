<?
include_once("bootstrap.inc.php");
include_once("include_generic/recaptchalib.php");

class PouetBoxUserMain extends PouetBox
{
  function PouetBoxUserMain($id) {
    parent::__construct();
    $this->uniqueID = "pouetbox_usermain";
    $this->title = "";
    $this->id = (int)$id;

    $this->paginator = new PouetPaginator();
  }

  function LoadFromDB() {
    $this->user = PouetUser::Spawn( $this->id );

    if (!$this->user) return;

    $this->user->UpdateGlops();

    $this->sceneID = $this->user->GetSceneIDData();

    $s = new BM_Query("users_cdcs");
    $s->AddWhere(sprintf_esc("users_cdcs.user = %d",$this->id));
    $s->Attach(array("users_cdcs"=>"cdc"),array("prods as prod"=>"id"));
    $s->AddOrder("users_cdcs_prod.id");
    $this->cdcProds = $s->perform();

    $this->logos = array();
    if (!$_GET["show"] || $_GET["show"]=="logos")
    {
      $this->logos = $this->GetLogosAdded( $_GET["show"]=="logos"? null : get_setting("userlogos") );
    }

    $this->prods = array();
    if (!$_GET["show"] || $_GET["show"]=="prods")
    {
      $this->prods = $this->GetProdsAdded( $_GET["show"]=="prods"? null : get_setting("userprods") );
    }
    
    $this->groups = array();
    if (!$_GET["show"] || $_GET["show"]=="groups")
    {
      $this->groups = $this->GetGroupsAdded( $_GET["show"]=="groups"? null : get_setting("usergroups") );
    }    

    $this->parties = array();
    if (!$_GET["show"] || $_GET["show"]=="parties")
    {
      $this->parties = $this->GetPartiesAdded( $_GET["show"]=="parties"? null : get_setting("userparties") );
    }

    $this->shots = array();
    if (!$_GET["show"] || $_GET["show"]=="screenshots")
    {
      $this->shots = $this->GetScreenshotsAdded( $_GET["show"]=="screenshots"? null : get_setting("userscreenshots") );
    }

    $this->nfos = array();
    if (!$_GET["show"] || $_GET["show"]=="nfos")
    {
      $this->nfos = $this->GetNFOsAdded( $_GET["show"]=="nfos" ? null : get_setting("usernfos") );
    }

    $this->firstComments = array();
    if (!$_GET["show"]/* || $_GET["show"]=="comments"*/)
    {
      $this->firstComments = $this->GetFirstCommentsAdded( /*$_GET["show"]=="comments" ? null :*/ get_setting("usercomments") );
    }

    $this->topics = array();
    if (!$_GET["show"] || $_GET["show"]=="topics")
    {
      $this->topics = $this->GetBBSTopics( $_GET["show"]=="topics" ? null : get_setting("usercomments") );
    }

    $this->posts = array();
    if (!$_GET["show"] || $_GET["show"]=="posts")
    {
      $this->posts = $this->GetBBSPosts( $_GET["show"]=="posts" ? null : get_setting("usercomments") );
    }

    $this->comments = array();
    if ($_GET["show"]=="demoblog")
    {
      $this->comments = $this->GetCommentsAdded( 10, $_GET["page"] );
    }
    
    $this->agreeRulez = array();
    if (!$_GET["show"])
    {
      $this->agreeRulez = $this->GetThumbAgreers( get_setting("userrulez"), 1 );
    }
    
    $this->agreeSucks = array();
    if (!$_GET["show"])
    {
      $this->agreeSucks = $this->GetThumbAgreers( get_setting("usersucks"), -1 );
    }
  }

  function AddRow($field, $value, $allowHTML = false) {
    $s = "";
    if ($value) {
      echo "<li>\n";
      echo " <span class='field'>".$field.":</span>\n";
      echo " ".($allowHTML ? $value : _html($value))."\n";
      echo "</li>\n";
    }
    return $s;
  }

  function RenderHeader()
  {
    global $currentUser;

    $s = "";
    echo "<div class='pouettbl' id='".$this->uniqueID."'>\n";
    echo " <h2>";
    echo "<img src='".POUET_CONTENT_URL."/avatars/"._html($this->user->avatar)."' alt='avatar'/> ";
    echo "<span>"._html($this->user->nickname)."</span> information";

    if ($currentUser && $currentUser->IsAdministrator())
    {
      printf(" [<a href='admin_user_edit.php?who=%d' class='adminlink'>edit</a>]\n",$this->id);
    }

    echo " <span id='glops'><span>".$this->user->glops."</span> glöps</span>";
    echo "</h2>\n";
    return $s;
  }
  function GetLogosAdded( $limit = null )
  {
    $s = new BM_Query("logos");
    $s->AddField("logos.file");
    $s->AddField("logos.vote_count");
    $s->AddWhere(sprintf("logos.author1 = %d or logos.author2 = %d",$this->id,$this->id));
    if ($limit)
      $s->SetLimit( $limit );
    $data = $s->perform();

    return $data;
  }
  function GetProdsAdded( $limit = null )
  {
    $s = new BM_Query("prods");
    $s->AddOrder("prods.quand desc");
    $s->AddWhere(sprintf("prods.added = %d",$this->id));
    if ($limit)
      $s->SetLimit( $limit );
    else
    {
      $this->paginator->SetData( "user.php?who=".$this->id."&show=prods", $this->user->stats["prods"], 50, $_GET["page"], false );
      $this->paginator->SetLimitOnQuery( $s );
    }
    $data = $s->perform();
    PouetCollectPlatforms($data);

    return $data;
  }
  function GetGroupsAdded( $limit = null )
  {
    $s = new BM_Query("groups");
    $s->AddOrder("groups.quand desc");
    $s->AddWhere(sprintf("groups.added = %d",$this->id));
    if ($limit)
      $s->SetLimit( $limit );
    else
    {
      $this->paginator->SetData( "user.php?who=".$this->id."&show=groups", $this->user->stats["groups"], 50, $_GET["page"], false );
      $this->paginator->SetLimitOnQuery( $s );
    }
    $data = $s->perform();

    return $data;
  }
  function GetPartiesAdded( $limit = null )
  {
    $s = new BM_Query("parties");
    $s->AddOrder("parties.quand desc");
    $s->AddWhere(sprintf("parties.added = %d",$this->id));
    if ($limit)
      $s->SetLimit( $limit );
    else
    {
      $this->paginator->SetData( "user.php?who=".$this->id."&show=parties", $this->user->stats["parties"], 50, $_GET["page"], false );
      $this->paginator->SetLimitOnQuery( $s );
    }
    $data = $s->perform();

    return $data;
  }
  function GetScreenshotsAdded( $limit = null )
  {
    $s = new BM_Query("prods");
    $s->AddOrder("prods.quand desc");
    $s->AddJoin("left","screenshots","prods.id = screenshots.prod");
    $s->AddWhere(sprintf("screenshots.user = %d",$this->id));
    if ($limit)
      $s->SetLimit( $limit );
    else
    {
      $this->paginator->SetData( "user.php?who=".$this->id."&show=screenshots", $this->user->stats["screenshots"], 50, $_GET["page"], false );
      $this->paginator->SetLimitOnQuery( $s );
    }
    $data = $s->perform();
    PouetCollectPlatforms($data);

    return $data;
  }
  function GetNFOsAdded( $limit = null )
  {
    $s = new BM_Query("prods");
    $s->AddOrder("prods.quand desc");
    $s->AddJoin("left","nfos","prods.id = nfos.prod");
    $s->AddWhere(sprintf("nfos.user = %d",$this->id));
    if ($limit)
      $s->SetLimit( $limit );
    else
    {
      $this->paginator->SetData( "user.php?who=".$this->id."&show=nfos", $this->user->stats["nfos"], 50, $_GET["page"], false );
      $this->paginator->SetLimitOnQuery( $s );
    }
    $data = $s->perform();
    PouetCollectPlatforms($data);

    return $data;
  }
  function GetFirstCommentsAdded( $limit = null )
  {
    $s = new BM_Query("prods");
    $s->AddField("comments.rating");
    $s->AddOrder("comments.quand desc");
    $s->AddJoin("left","comments","prods.id = comments.which");
    $s->AddWhere(sprintf("comments.who = %d",$this->id));
    $s->AddGroup("prods.id");
    if ($limit)
      $s->SetLimit( $limit );

    $data = $s->perform();
    PouetCollectPlatforms($data);

    return $data;
  }
  function GetThumbAgreers( $limit = null, $thumb = 1 )
  {
    $s = new BM_Query("");
    $s->AddField("count(*) as c");
    $s->AddTable("comments AS c1");
    $s->AddTable("comments AS c2");
    //$s->Attach(array("c1"=>"who"),array("users as u1"=>"id"));
    $s->Attach(array("c2"=>"who"),array("users as u2"=>"id"));
    $s->AddWhere(sprintf_esc("c1.rating = %d",$thumb));
    $s->AddWhere("c1.rating = c2.rating");
    $s->AddWhere("c1.which = c2.which");
    $s->AddWhere(sprintf_esc("c1.who = %d",$this->id));
    $s->AddWhere(sprintf_esc("c2.who != %d",$this->id));
    $s->AddGroup("c2.who");
    $s->AddOrder("c DESC");
    
    if ($limit)
      $s->SetLimit( $limit );

    $data = $s->perform();

    return $data;
  }
  function GetBBSTopics( $limit = null )
  {
    $s = new BM_Query("bbs_topics");
    $s->AddField("bbs_topics.id");
    $s->AddField("bbs_topics.topic");
    $s->AddField("bbs_topics.category");
    $s->AddWhere(sprintf("bbs_topics.userfirstpost = %d",$this->id));
    $s->AddOrder("bbs_topics.firstpost desc");
    if ($limit)
      $s->SetLimit( $limit );
    else
    {
      $this->topicCount = SQLLib::SelectRow( sprintf_esc("select count(*) as c from bbs_topics where bbs_topics.userfirstpost = %d",$this->id) )->c;

      $this->paginator->SetData( "user.php?who=".$this->id."&show=topics", $this->topicCount, 50, $_GET["page"], false );
      $this->paginator->SetLimitOnQuery( $s );
    }

    $data = $s->perform();

    return $data;
  }
  function GetBBSPosts( $limit = null )
  {
    $s = new BM_Query("bbs_posts");
    $s->AddJoin("left","bbs_topics","bbs_topics.id = bbs_posts.topic");
    $s->AddField("bbs_topics.id");
    $s->AddField("bbs_topics.topic");
    $s->AddField("bbs_topics.category");
    $s->AddWhere(sprintf("bbs_posts.author = %d",$this->id));
    $s->AddOrder("bbs_posts.added desc");
    if ($limit)
    {
      $s->SetLimit( $limit );
      $s->AddGroup("bbs_topics.id");
    }
    else
    {
      $this->postCount = SQLLib::SelectRow( sprintf_esc("select count(*) as c from bbs_posts where bbs_posts.author = %d",$this->id) )->c;

      $this->paginator->SetData( "user.php?who=".$this->id."&show=posts", $this->postCount, 50, $_GET["page"], false );
      $this->paginator->SetLimitOnQuery( $s );
    }

    $data = $s->perform();

    return $data;
  }
  function GetCommentsAdded( $limit, $page )
  {
    $s = new BM_Query("comments");
    $s->AddField("count(*) as c");
    $s->AddWhere(sprintf("comments.who = %d",$this->id));
    $this->postcount = SQLLib::SelectRow($s->GetQuery())->c;

    $s = new BM_Query("comments");
    $s->AddField("comments.rating");
    $s->AddField("comments.quand as commentDate");
    $s->AddField("comments.comment");
    $s->AddOrder("comments.quand desc");
    //$s->AddJoin("left","comments","prods.id = comments.which");
    $s->Attach(array("comments"=>"which"),array("prods as prod"=>"id"));
    $s->AddWhere(sprintf("comments.who = %d",$this->id));

    $this->paginator->SetData( "user.php?who=".$this->id."&show=demoblog", $this->postcount, $limit, $page, false );
    $this->paginator->SetLimitOnQuery( $s );

    $data = $s->perform();
    PouetCollectPlatforms($data);

    return $data;
  }

  function RenderBody() {
    $s = "";
    echo "<div class='content'>\n";
    echo "<div class='bigavatar'><img src='".POUET_CONTENT_URL."/avatars/"._html($this->user->avatar)."' alt='big avatar'/></div>\n";
    echo "<ul id='userdata'>\n";

    echo "<li class='header'>general:</li>\n";
    //echo $this->AddRow("first name",$this->sceneID["login"]);
    echo $this->AddRow("level",$this->user->level);

    echo "<li class='header'>personal:</li>\n";
    echo $this->AddRow("first name",$this->sceneID["firstname"]);
    echo $this->AddRow("last name",$this->sceneID["lastname"]);
    echo $this->AddRow("country",$this->sceneID["country"]);

    if ($this->sceneID["email"])
    {
      if ($this->sceneID["hidden"]=="yes")
      {
        echo $this->AddRow("email","<span style='color:#9999AA'>hidden</span>",true);
      }
      else
      {
        echo $this->AddRow("email",recaptcha_mailhide_html(CAPTCHA_MAILHIDE_PUBLICKEY,CAPTCHA_MAILHIDE_PRIVATEKEY,$this->sceneID["email"]),true);
      }
    }
    if ($this->sceneID["url"]) {
      $site = _html($this->sceneID["url"]);
      if (substr($site,0,7)!="http://")
        $site = "http://".$site;
      echo $this->AddRow("website","<a href='".$site."'>".$site."</a>",true);
    }

    if ($this->user->im_type)
      $this->AddRow($this->user->im_type,$this->user->im_id);

    echo "<li class='header'>portals:</li>\n";
    if ($this->user->csdb)
      echo $this->AddRow("csdb","<a href='http://csdb.dk/scener/?id=".$this->user->csdb."'>profile</a>",true);
    if ($this->user->slengpung)
      echo $this->AddRow("slengpung","<a href='http://www.slengpung.com/?userid=".$this->user->slengpung."'>pictures</a>",true);
    if ($this->user->zxdemo)
      echo $this->AddRow("zxdemo","<a href='http://zxdemo.org/author.php?id=".$this->user->zxdemo."'>profile</a>",true);

    if ($this->cdcProds)
    {
      echo "<li class='header'>cdcs:</li>\n";
      $x = 1;
      foreach($this->cdcProds as $v)
        $this->AddRow("cdc #".($x++),$v->prod->RenderSingleRow(),true);
    }

    echo "</ul>\n";
    echo "</div>\n";

    if (!$_GET["show"] && $this->user->stats["ud"])
      echo "<div class='contribheader'>United Devices contribution <span>".$this->user->stats["ud"]." glöps</span></div>\n";

    if ($this->logos)
    {
      echo "<div class='contribheader'>latest added logos <span>".$this->user->stats["logos"]." x 20 = ".($this->user->stats["logos"] * 20)." glöps - downvoted logos don't get glöps</span></div>\n";
      echo "<ul class='boxlist' id='logolist'>";
      foreach($this->logos as $l)
      {
        echo "<li>";
        echo "<div class='logo'>";
        echo "<img src='".POUET_CONTENT_URL."gfx/logos/"._html($l->file)."' alt=''/>";
        echo "<span class='logovotes'>current votes: "._html($l->vote_count)."</span>";
        echo "</div>";
        echo "</li>";
      }
      echo "</ul>";
    }

    if ($this->prods)
    {
      echo "<div class='contribheader'>latest added prods <span>".$this->user->stats["prods"]." x 2 = ".($this->user->stats["prods"] * 2)." glöps</span> [<a href='user.php?who=".$this->id."&amp;show=prods'>show all</a>]</div>\n";
      echo "<ul class='boxlist'>";
      foreach($this->prods as $p)
      {
        echo "<li>";
        echo $p->RenderTypeIcons();
        echo $p->RenderPlatformIcons();
        echo $p->RenderSingleRow();
        echo $p->RenderAwards();
        echo "</li>";
      }
      echo "</ul>";
      $this->paginator->RenderNavbar();
    }

    if ($this->groups)
    {
      echo "<div class='contribheader'>latest added groups <span>".$this->user->stats["groups"]." glöps</span> [<a href='user.php?who=".$this->id."&amp;show=groups'>show all</a>]</div>\n";
      echo "<ul class='boxlist'>";
      foreach($this->groups as $g)
      {
        echo "<li>";
        echo $g->RenderLong();
        echo "</li>";
      }
      echo "</ul>";
      $this->paginator->RenderNavbar();
    }

    if ($this->parties)
    {
      echo "<div class='contribheader'>latest added parties <span>".$this->user->stats["parties"]." glöps</span> [<a href='user.php?who=".$this->id."&amp;show=parties'>show all</a>]</div>\n";
      echo "<ul class='boxlist'>";
      foreach($this->parties as $p)
      {
        echo "<li>";
        echo $p->PrintLinked();
        echo "</li>";
      }
      echo "</ul>";
      $this->paginator->RenderNavbar();
    }

    if ($this->shots)
    {
      echo "<div class='contribheader'>latest added screenshots <span>".$this->user->stats["screenshots"]." glöps</span> [<a href='user.php?who=".$this->id."&amp;show=screenshots'>show all</a>]</div>\n";
      echo "<ul class='boxlist'>";
      foreach($this->shots as $p)
      {
        echo "<li>";
        echo $p->RenderTypeIcons();
        echo $p->RenderPlatformIcons();
        echo $p->RenderSingleRow();
        echo $p->RenderAwards();
        echo "</li>";
      }
      echo "</ul>";
      $this->paginator->RenderNavbar();
    }

    if ($this->nfos)
    {
      echo "<div class='contribheader'>latest added nfos <span>".$this->user->stats["nfos"]." glöps</span> [<a href='user.php?who=".$this->id."&amp;show=nfos'>show all</a>]</div>\n";
      echo "<ul class='boxlist'>";
      foreach($this->nfos as $p)
      {
        echo "<li>";
        echo $p->RenderTypeIcons();
        echo $p->RenderPlatformIcons();
        echo $p->RenderSingleRow();
        echo $p->RenderAwards();
        echo "</li>";
      }
      echo "</ul>";
      $this->paginator->RenderNavbar();
    }

    if ($this->firstComments)
    {
      echo "<div class='contribheader'>latest 1st comments <span>".$this->user->stats["comments"]." glöps</span>";
      //echo " [<a href='user.php?who=".$this->id."&amp;show=comments'>show all</a>]";
      echo " [<a href='user.php?who=".$this->id."&amp;show=demoblog'>demoblog</a>]";
      echo "</div>\n";
      echo "<ul class='boxlist'>";
      foreach($this->firstComments as $p)
      {
        $rating = $p->rating>0 ? "rulez" : ($p->rating<0 ? "sucks" : "isok");
        echo "<li>";
        echo "<span class='vote ".$rating."'>".$rating."</span>";
        echo $p->RenderTypeIcons();
        echo $p->RenderPlatformIcons();
        echo $p->RenderSingleRow();
        echo $p->RenderAwards();
        echo "</li>";
      }
      echo "</ul>";
    }

    if ($this->topics)
    {
      echo "<div class='contribheader'>latest bbs topics";
      if ($this->topicCount)
        echo " <span>".$this->topicCount." topics</span>";
      echo " [<a href='user.php?who=".$this->id."&amp;show=topics'>show all</a>]</div>\n";
      echo "<ul class='boxlist'>";
      foreach($this->topics as $t)
      {
        echo "<li>";
        echo "<a href='topic.php?which=".$t->id."'>"._html($t->topic)."</a> ("._html($t->category).")";
        echo "</li>";
      }
      echo "</ul>";
      $this->paginator->RenderNavbar();
    }

    if ($this->posts)
    {
      echo "<div class='contribheader'>latest bbs posts";
      if ($this->postCount)
        echo " <span>".$this->postCount." posts</span>";
      echo " [<a href='user.php?who=".$this->id."&amp;show=posts'>show all</a>]</div>\n";
      echo "<ul class='boxlist'>";
      foreach($this->posts as $p)
      {
        echo "<li>";
        echo "<a href='topic.php?which=".$p->id."'>"._html($p->topic)."</a> ("._html($p->category).")";
        echo "</li>";
      }
      echo "</ul>";
      $this->paginator->RenderNavbar();
    }
    
    if ($this->agreeRulez)
    {
      echo "<div class='contribheader'>top thumb up agreers";
      echo "</div>\n";
      echo "<ul class='boxlist'>";
      foreach($this->agreeRulez as $p)
      {
        echo "<li>";
        echo $p->u2->PrintLinkedAvatar()." ";
        echo $p->u2->PrintLinkedName()." ";
        echo "(".$p->c." prods)";
        echo "</li>";
      }
      echo "</ul>";
    }
    
    if ($this->agreeSucks)
    {
      echo "<div class='contribheader'>top thumb down agreers";
      echo "</div>\n";
      echo "<ul class='boxlist'>";
      foreach($this->agreeSucks as $p)
      {
        echo "<li>";
        echo $p->u2->PrintLinkedAvatar()." ";
        echo $p->u2->PrintLinkedName()." ";
        echo "(".$p->c." prods)";
        echo "</li>";
      }
      echo "</ul>";
    }

    if ($this->comments)
    {
      echo "<ul class='boxlist' id='demoblog'>";
      foreach($this->comments as $c)
      {
        $p = $c->prod;
        $rating = $c->rating>0 ? "rulez" : ($c->rating<0 ? "sucks" : "");
        echo "<li class='blogprod'>";
        echo $p->RenderTypeIcons();
        echo $p->RenderPlatformIcons();
        echo "<span class='prod'>".$p->RenderLink()."</span>\n";
        echo "</li>";
        echo "<li class='blogcomment'>";
        echo parse_message( $c->comment );
        echo "</li>";
        echo "<li class='blogvote'>";
        echo "<span class='vote ".$rating."'>".$rating."</span>";
        echo "added on the ".$c->commentDate;
        echo "</li>";
      }
      echo "</ul>";
      $this->paginator->RenderNavbar();
    }

  }

  function RenderFooter() {
    echo "  <div class='foot'>account created on the ".$this->user->quand."</div>\n";
    echo "</div>\n";
  }
};

$p = new PouetBoxUserMain( (int)$_GET["who"] );
$p->Load();

if ($p->user)
  $TITLE = $p->user->nickname;

include("include_pouet/header.php");
include("include_pouet/menu.inc.php");

echo "<div id='content'>\n";

if ($p->user)
{
  echo $p->Render();
}
else
{
  echo "tüzesen süt le a nyári nap sugára / az ég tetejéről a juhászbojtárra.";
}

echo "</div>\n";

include("include_pouet/menu.inc.php");
include("include_pouet/footer.php");
?>
