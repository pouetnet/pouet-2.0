<?
/**
 * Nathan Codding - Jan. 12, 2001.
 * Performs [quote][/quote] bbencoding on the given string, and returns the results.
 * Any unmatched "[quote]" or "[/quote]" token will just be left alone. 
 * This works fine with both having more than one quote in a message, and with nested quotes.
 * Since that is not a regular language, this is actually a PDA and uses a stack. Great fun.
 *
 * Note: This function assumes the first character of $message is a space, which is added by 
 * bbencode().
 *
 * modified and patched up by gargaj - still far from optimal!
 */
function bbencode_parse_tag($message,$tag,$openCode,$closeCode)
{
  // First things first: If there aren't any "[quote]" strings in the message, we don't
  // need to process it at all.
  
  if (strpos(strtolower($message), "[".$tag."]")===false)
  {
    return $message;  
  }
  
  $stack = Array();
  $curr_pos = 0;
  while ($curr_pos!==false && ($curr_pos < strlen($message)))
  { 
    $curr_pos = strpos($message, "[", $curr_pos);
  
    // If not found, $curr_pos will be 0, and the loop will end.
    if ($curr_pos!==false)
    {
      // We found a [. It starts at $curr_pos.
      // check if it's a starting or ending quote tag.
      $possible_start = substr($message, $curr_pos, 7);
      $possible_end = substr($message, $curr_pos, 8);
      if (strcasecmp("[".$tag."]", $possible_start) == 0)
      {
        // We have a starting quote tag.
        // Push its position on to the stack, and then keep going to the right.
        array_push($stack, $curr_pos);
        ++$curr_pos;
      }
      else if (strcasecmp("[/".$tag."]", $possible_end) == 0)
      {
        // We have an ending quote tag.
        // Check if we've already found a matching starting tag.
        if (sizeof($stack) > 0)
        {
          // There exists a starting tag. 
          // We need to do 2 replacements now.
          $start_index = array_pop($stack);

          // everything before the [quote] tag.
          $before_start_tag = substr($message, 0, $start_index);

          // everything after the [quote] tag, but before the [/quote] tag.
          $between_tags = substr($message, $start_index + 7, $curr_pos - $start_index - 7);

          // everything after the [/quote] tag.
          $after_end_tag = substr($message, $curr_pos + 8);

          $message = $before_start_tag . $openCode;
          $message .= $between_tags . $closeCode;
          $message .= $after_end_tag;
          
          // Now.. we've screwed up the indices by changing the length of the string. 
          // So, if there's anything in the stack, we want to resume searching just after it.
          // otherwise, we go back to the start.
          if (sizeof($stack) > 0)
          {
            $curr_pos = array_pop($stack);
            array_push($stack, $curr_pos);
            ++$curr_pos;
          }
          else
          {
            $curr_pos = 0;
          }
        }
        else
        {
          // No matching start tag found. Increment pos, keep going.
          ++$curr_pos;  
        }
      }
      else
      {
        // No starting tag or ending tag.. Increment pos, keep looping.,
        ++$curr_pos;  
      }
    }
  } // while
  
  return $message;
  
}
 
function bbencode( $text )
{
  $text = preg_replace("/\[b\](.*?)\[\/b\]/s","<b>$1</b>",$text);
  $text = preg_replace("/\[i\](.*?)\[\/i\]/s","<i>$1</i>",$text);
  $text = preg_replace("/\[u\](.*?)\[\/u\]/s","<u>$1</u>",$text);
  $text = bbencode_parse_tag($text,"quote","<div class=\"bbs_quote\"><b>Quote:</b><blockquote>","</blockquote></div>");
  $text = bbencode_parse_tag($text,"code","<div class=\"bbs_code\"><b>Code:</b><pre>","</pre></div>");
  $text = preg_replace("/\[list\](.*?)\[\/list\]/s","<ul>$1</ul>",$text);
  $text = preg_replace("/\[list=(.*?)\](.*?)\[\/list\]/s","<ol type='$1'>$2</ol>",$text);
  $text = preg_replace("/\[\*\](.*)[\r\n]/","<li>$1</li>",$text);
  $text = preg_replace("/\[url\](.*?)\[\/url\]/","<a href='$1'>$1</a>",$text);
  $text = preg_replace("/\[url=(.*?)\](.*?)\[\/url\]/","<a href='$1'>$2</a>",$text);
  $text = preg_replace("/\[img\](.*?)\[\/img\]/","<img src='$1' class='bbimage' alt='BB Image'/>",$text);
//  $text = preg_replace("/\s([a-zA-Z0-9]+:\/\/[a-zA-Z\.\-_0-9\+\/]+)/","<a href='$1' target='_blank'>$1</a>",$text);
//  $text = preg_replace("/([a-zA-Z\.\-_0-9\+]+@[a-zA-Z\.\-_0-9\+]+)/","<a href='mailto:$1' target='_blank'>$1</a>",$text);
//  $text = "<p>".preg_replace("/[\r\n]{2,}/","</p>\n\n<p>",$text)."</p>";
  return $text;
}

?>
