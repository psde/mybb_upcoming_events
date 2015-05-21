<?php
/***************************************************************************
 *
 *   Upcoming Events for MyBB
 *   Copyright: © 2011 by Christopher Lorentz
 *   
 *   Website: http://lorus.org/
 *   
 *   Last modified: 05/10/2011 by Lorus
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 ***************************************************************************/
 
if(!defined("IN_MYBB"))
{
    die("This file cannot be accessed directly.");
}

$plugins->add_hook("index_start", "upcoming_events_index_start");
$plugins->add_hook("portal_start", "upcoming_events_portal_start");
$plugins->add_hook("admin_config_settings_begin", "upcoming_events_lang_settings");

function upcoming_events_info()
{
	global $lang;
	$lang->load("upcoming_events");
	
	return array(
		"name"			=> $lang->upcoming_events,
		"description"		=> $lang->upcoming_events_desc,
		"website"		=> "http://lorus.org/",
		"author"		=> "Lorus",
		"authorsite"		=> "http://lorus.org/",
		"version"		=> "1.31",
		"guid"			=> "b364c7af6f5fc2440c725fb91197bbd5",
		"compatibility"		=> "16*"
	);
}

function upcoming_events_install()
{

	global $db, $lang;
	
	$lang->load("upcoming_events");
	
	//add 'upcoming_events' template to global theme
	$template = "<tr>\r\n<td class=\"tcat\"><span class=\"smalltext\"><strong>{\$upcoming_events_text}</strong></span></td>\r\n</tr>\r\n<tr>\r\n<td class=\"trow1\"><span class=\"smalltext\">{\$eventlist}</span></td>\r\n</tr>";
	$insert_array = array(
		'title' => 'upcoming_events',
		'template' => $db->escape_string($template),
		'sid' => '-1',
		'version' => '1602',
		'dateline' => TIME_NOW
	);

	$db->insert_query("templates", $insert_array);
	
	//add 'upcoming_events_portal' template to global theme
	$template = "<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">\r\n<tr>\r\n<td class=\"thead\"><strong>{\$upcoming_events_text}</strong></td>\r\n</tr>\r\n<tr>\r\n<td class=\"trow1\">\r\n<span class=\"smalltext\">\r\n{\$eventlist}\r\n</span>\r\n</td>\r\n</tr>\r\n</table>\r\n<br />";
	$insert_array = array(
		'title' => 'upcoming_events_portal',
		'template' => $db->escape_string($template),
		'sid' => '-1',
		'version' => '1602',
		'dateline' => TIME_NOW
	);

	$db->insert_query("templates", $insert_array);

	//create settings
	$settings_group = array(
		'gid'			=> 'NULL',
		'name'			=> 'upcoming_events',
		'title'			=> 'Upcoming Events',
		'description'	=> "Here you can configure Upcoming Events plugin.",
		'disporder'		=> $max_disporder + 1,
		'isdefault'		=> '0'
	);
	$db->insert_query('settinggroups', $settings_group);
	$gid = (int) $db->insert_id();
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'upcoming_events_timerange',
		'title'			=> "Display timerange",
		'description'	=> "How many days in advance should events are to be displayed from the calendar?",
		'optionscode'	=> 'text',
		'value'			=> '14',
		'disporder'		=> '1',
		'gid'			=> $gid
	);
	$db->insert_query('settings', $setting);	
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'upcoming_events_maxdisplay',
		'title'			=> "Maximum Events visible",
		'description'	=> "How many events should be visible (max.)?",
		'optionscode'	=> 'text',
		'value'			=> '5',
		'disporder'		=> '2',
		'gid'			=> $gid
	);
	$db->insert_query('settings', $setting);
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'upcoming_events_showindex',
		'title'			=> 'Show on Index',
		'description'	=> 'De-/activates upcoming events on forumindex.',
		'optionscode'	=> 'onoff',
		'value'			=> '1',
		'disporder'		=> '3',
		'gid'			=> $gid
	);
	$db->insert_query('settings', $setting);
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'upcoming_events_showportal',
		'title'			=> 'Show on Portal',
		'description'	=> 'De-/activates upcoming events on portal page.',
		'optionscode'	=> 'onoff',
		'value'			=> '1',
		'disporder'		=> '4',
		'gid'			=> $gid
	);
	$db->insert_query('settings', $setting);	
	
	rebuild_settings();

}

function upcoming_events_is_installed()
{
	global $db;
	
	// is the template installed?
	$query = $db->query("
		SELECT  *
		FROM ".TABLE_PREFIX."templates
    WHERE title = 'upcoming_events'
	");
	
	$row = $db->fetch_array($query);
	$template_exists = !empty($row);
	
	
	// are the settings present?
	$query = $db->simple_select("settinggroups", "gid", "name='upcoming_events'");
	$row2 = $db->num_rows($query);
	$settings_exists = !empty($row2);
	
	return $settings_exists && $template_exists;
}

function upcoming_events_uninstall()
{
	global $db;
	
	//removing 'upcoming_events' and '_portal' template from global theme
	$query = $db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title = 'upcoming_events'");
	$query = $db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title = 'upcoming_events_portal'");

	//remove settings
	$db->delete_query('settinggroups', "name = 'upcoming_events'");
	$db->delete_query('settings', "name = 'upcoming_events_timerange'");
  $db->delete_query('settings', "name = 'upcoming_events_maxdisplay'");
	$db->delete_query('settings', "name = 'upcoming_events_showportal'");
	$db->delete_query('settings', "name = 'upcoming_events_showindex'");
  rebuild_settings();

}

function upcoming_events_activate()
{
	require "../inc/adminfunctions_templates.php";
	find_replace_templatesets("index_boardstats", '#(\{\$whosonline\})#i', "$1\n{\$upcoming_events}");
	find_replace_templatesets("portal", '#(\{\$whosonline\})#i', "$1\n{\$upcoming_events_portal}");
}

function upcoming_events_deactivate()
{
	require "../inc/adminfunctions_templates.php";
	find_replace_templatesets("index_boardstats", '#\n\{\$upcoming_events\}#i', "");
	find_replace_templatesets("portal", '#\n\{\$upcoming_events_portal\}#i', "");
}

function upcoming_events_index_start()
{

	global $upcoming_events, $mybb, $templates, $lang;
	
	if ($mybb->settings['upcoming_events_showindex'] == 1)
	{
	
		$lang->load("upcoming_events");
		
		//generate heading
		$upcoming_events_text = $lang->sprintf($lang->upcoming_events, $mybb->settings['upcoming_events_maxdisplay'], $mybb->settings['upcoming_events_timerange']);
		
		//generate eventlist
		$events = get_upcoming_events();
		
		if (empty($events))
		{
			$line = $lang->upcoming_events_no_events;
		}
		else
		{
			foreach($events as $event)
			{
				if (!empty($event['end'])) 
				{
					$line .= $lang->sprintf($lang->upcoming_events_eventline, $event['link'], $event['date'], $event['start'], $event['end']);
					$line .= $lang->sprintf($lang->upcoming_events_created, $event['poster'])."<br />";
				}
				else 
				{
					$line .= $lang->sprintf($lang->upcoming_events_eventline_day, $event['link'], $event['date']);
					$line .= $lang->sprintf($lang->upcoming_events_created, $event['poster'])."<br />";
				}
			}
		}
		
		$eventlist .= $line;
	
		//generate template variable
		eval("\$upcoming_events = \"".$templates->get("upcoming_events")."\";");
		
	}

}

function upcoming_events_portal_start()
{

	global $upcoming_events_portal, $mybb, $templates, $lang, $theme;
	
	if ($mybb->settings['upcoming_events_showportal'] == 1)
	{
	
		$lang->load("upcoming_events");
		
		//generate heading
		$upcoming_events_text = $lang->sprintf($lang->upcoming_events_portal, $mybb->settings['upcoming_events_maxdisplay'], $mybb->settings['upcoming_events_timerange']);
		$upcoming_events_text .= '<img align="right" src="'.$mybb->settings['bburl'].'/images/toplinks/calendar.gif"/>';
		
		//generate event list
		$events = get_upcoming_events();
		
		if (empty($events))
		{
			$eventlist = $lang->upcoming_events_no_events;
		}
		else
		{
			foreach($events as $event)
			{
					
				$event['link'] = truncate($event['link'],7);
				
				if (!empty($event['end'])) 
				{
					$line = $lang->sprintf($lang->upcoming_events_eventline, $event['link'], $event['date'], $event['start'], $event['end']);
				}
				else 
				{
					$line = $lang->sprintf($lang->upcoming_events_eventline_day, $event['link'], $event['date']);
				}
				
				$eventlist .= truncate($line,32)."<br />";
			}
		}
		
		//generate template variable
		eval("\$upcoming_events_portal = \"".$templates->get("upcoming_events_portal")."\";");
		
	}

}

function upcoming_events_lang_settings()
{
	global $lang;
	$lang->load("upcoming_events");
}

function get_upcoming_events()
{

	global $date_formats, $time_formats, $lang, $templates, $mybb, $db;
	
	date_default_timezone_set('UTC');
	$today = mktime(0,0,0,date("m"),date("d"),date("Y"));
	
	$statement = "
		SELECT u.username,eid,e.starttime, e.timezone, e.endtime, e.ignoretimezone, e.name, cp.canviewcalendar as cp_canviewcalendar, ug.canviewcalendar as ug_canviewcalendar
    FROM ".TABLE_PREFIX."events e
    LEFT JOIN ".TABLE_PREFIX."calendarpermissions cp
			ON (e.cid=cp.cid AND cp.gid='".$mybb->user['usergroup']."')
		LEFT JOIN ".TABLE_PREFIX."usergroups ug
			ON (ug.gid='".$mybb->user['usergroup']."')
		INNER JOIN ".TABLE_PREFIX."users u
			ON (e.uid=u.uid)
    WHERE private='0' AND visible='1'
    AND starttime<=".time()."+".$mybb->settings['upcoming_events_timerange']."*24*60*60
		AND starttime>=".$today."
    ORDER BY starttime ASC
		LIMIT ".$mybb->settings['upcoming_events_maxdisplay'].";";
		
	$query = $db->query($statement);
	
	//set time and dateformats
	$timeformat = ($mybb->user['timeformat'] == 0) ? $mybb->settings['timeformat'] : $time_formats[$mybb->user['timeformat']];
	$dateformat = ($mybb->user['dateformat'] == 0) ? $mybb->settings['dateformat'] : $date_formats[$mybb->user['dateformat']];
	
	$i = 0;
	
	//generate array with upcoming events inside
	while($events = $db->fetch_array($query))
	{
		if($events['ug_canviewcalendar'] == 1 || $events['cp_canviewcalendar'] == 1)
		{
			$event[$i]['link'] = "<a href=\"".get_event_link($events['eid'])."\">".$events['name']."</a>";
			$event[$i]['date'] = date($dateformat,$events['starttime']);
			if (mktime(0,0,0,date("m",$events['starttime']),date("d",$events['starttime']),date("Y",$events['starttime'])) == $today)
			{
				$event[$i]['date'] = $lang->upcoming_events_today;
			}

			$event[$i]['poster'] = $events['username'];
			
			
			if ($events['endtime'] != 0)
			{
				if ($events['ignoretimezone'] == 0)
				{					
					$offset = $events['timezone'];
				}
				else
				{
					$offset = $mybb->user['timezone'];
				}
				
				$event[$i]['start'] = date($timeformat,$events['starttime']+$offset*3600);
				$event[$i]['end'] = date($timeformat,$events['endtime']+$offset*3600);
				
			}
		}
		$i++;
	}

	return $event;

}


//helper function for string excerpt with html tags inside
function truncate($text, $length = 100, $ending = '...', $exact = true ,$considerHtml = true) {
    if (is_array($ending)) {
        extract($ending);
    }
    if ($considerHtml) {
        if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
            return $text;
        }
        $totalLength = mb_strlen($ending);
        $openTags = array();
        $truncate = '';
        preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
        foreach ($tags as $tag) {
            if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
                if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
                    array_unshift($openTags, $tag[2]);
                } else if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
                    $pos = array_search($closeTag[1], $openTags);
                    if ($pos !== false) {
                        array_splice($openTags, $pos, 1);
                    }
                }
            }
            $truncate .= $tag[1];

            $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
            if ($contentLength + $totalLength > $length) {
                $left = $length - $totalLength;
                $entitiesLength = 0;
                if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
                    foreach ($entities[0] as $entity) {
                        if ($entity[1] + 1 - $entitiesLength <= $left) {
                            $left--;
                            $entitiesLength += mb_strlen($entity[0]);
                        } else {
                            break;
                        }
                    }
                }

                $truncate .= mb_substr($tag[3], 0 , $left + $entitiesLength);
                break;
            } else {
                $truncate .= $tag[3];
                $totalLength += $contentLength;
            }
            if ($totalLength >= $length) {
                break;
            }
        }

    } else {
        if (mb_strlen($text) <= $length) {
            return $text;
        } else {
            $truncate = mb_substr($text, 0, $length - strlen($ending));
        }
    }
    if (!$exact) {
        $spacepos = mb_strrpos($truncate, ' ');
        if (isset($spacepos)) {
            if ($considerHtml) {
                $bits = mb_substr($truncate, $spacepos);
                preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
                if (!empty($droppedTags)) {
                    foreach ($droppedTags as $closingTag) {
                        if (!in_array($closingTag[1], $openTags)) {
                            array_unshift($openTags, $closingTag[1]);
                        }
                    }
                }
            }
            $truncate = mb_substr($truncate, 0, $spacepos);
        }
    }

    $truncate .= $ending;

    if ($considerHtml) {
        foreach ($openTags as $tag) {
            $truncate .= '</'.$tag.'>';
        }
    }

    return $truncate;
}

?>