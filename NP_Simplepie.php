<?php
/**
  * This plugin can be used to Feeds to your blog
  * Based on http://simplepie.org/
  * History:
  *  v1.01c
  *
  */
define("VERSION_NP_SIMPLEPIE","1.01c");
define("BIDDERS_AFID","アフィリエートID");
define("BIDDERS_LINKID","リンクID");
define("PARAM_URL",0);
define("PARAM_EXTCLASS",1);
define("PARAM_TEMPLATE",2);
define("PARAM_AMOUNT",3);
define("PARAM_CACHE_TIME",4);
define("PARAM_STEP",5);

class NP_Simplepie extends NucleusPlugin {
var $feed;
var $feed_item;
var $template_data = array();

	function getName()    {return 'Simplepie: Import RSS/ATOM feeds in your weblog.'; }
	function getAuthor()  {return 'ZeRo'; }
	function getURL()     {return 'http://nucleus.petit-power.com/'; }
	function getVersion() {return VERSION_NP_SIMPLEPIE; }
	function getDescription() {
		return 'Call this to import a RSS/ATOM feed.';
	}

	function supportsFeature($what) {
		switch($what) {
		    case 'SqlTablePrefix':
				return 1;
		    default:
				return 0;
		}
    }

    function install() {
        global $DIR_MEDIA;

		// create some options
		$this->createOption('enable_cache','Enable/disable caching in SimplePie ','yesno','yes');
		$this->createOption('cache_location','Set the folder where the cache files should be written. (relative from MEDIADIR)','text',$DIR_MEDIA.'cache/');
		$this->createOption('cache_time','Set the minimum time for which a feed will be cached','text','60');
		$this->createOption('default_itemclass','default item class name','text','');
		$this->createOption('default_template','default template name','text','default/item');
		$this->createOption('default_amount','default amount','text','10');
    }

	function uninstall() {
		//nothing to do
	}
	
	function getEventList() { 
		return array('PreItem'); 
	}

	function doSkinVar($skintype, $feedURL, $extclass="",$template = "default",$amount = 10,$step=0,$interval=-1) {
	// go get the requested newsfeed.
		$param[PARAM_URL] = $feedURL;
		$param[PARAM_EXTCLASS] = $extclass;
		$param[PARAM_TEMPLATE] = $template;
		$param[PARAM_AMOUNT] = $amount;
		$param[PARAM_STEP] = $step;
		$param[PARAM_CACHE_TIME] = $interval;
		echo $this->FeedJob($param);
	}


	function event_PreItem($data) { 
		// prepare
		$tgt  = '/<%(simplepie|newsfeedex)\((.+?)\)%>/i';
		
		// convert to linkcounter
		$obj = &$data["item"];
		$obj->body = preg_replace_callback($tgt, array(&$this, 'feed_callback'), $obj->body); 
		$obj->more = preg_replace_callback($tgt, array(&$this, 'feed_callback'), $obj->more); 
	}

	// 記事から呼び出された場合のコールバック処理部分
	function feed_callback($matches) { 

		$param = explode(",",$matches[2]);
		return ($this->FeedJob($param));
	}

	// サイト表示フォーマットのコールバック処理
	function site_parse_callback($match)
	{
	
		if (count($match) > 2)
		{	$func_name = $match[2];
			$option = explode(",",$match[3]);
		} else
		{	$func_name = $match[1];
			$option = false;
		}
		$func = "get_".$func_name;
		if (preg_match("#^subscribe_#",$func_name))
		{	$func = $func_name;
		}
		if (method_exists($this->feed,$func) == true) 
		{
				switch ($func_name)
				{
				case 'image_url':
					if ($value == '')
					{	$value = $this->feed->get_title();
					} else
					{	$title = $this->feed->get_image_title();
						if ($title === false)
						{	$title = $this->feed->get_title();
						}
						$add_opt = "";
						if ($option != false)
						{
							if (isset($option[0]))
							{	$add_opt .= " width=".$option[0];
							}
							if (isset($option[1]))
							{	$add_opt .= " height=".$option[1];
							}
						}
						$value = '<img src="'.$value.'" alt="'.$title.'"'.$add_opt.' />';
					}
					break;
				default:
					$value = $this->feed->{$func}();
					break;
				}
				if ($option != false)
				{	if ($option[0] == "text")
					{	require_once(dirname(__FILE__)."/simplepie/class.html2text.inc");
						$h2t =& new html2text($value);

						// Simply call the get_text() method for the class to convert
						// the HTML to the plain text. Store it into the variable.
						$value = $h2t->get_text(); 
					}
					if (isset($option[1]))
					{
						$len = intval($option[1]);
						$value = mb_strimwidth($value, 0, $len,"...","UTF-8");
					}
				}
				return mb_convert_encoding($value,_CHARSET,"AUTO");
		}
		return $match[0];
	}

	// 記事表示フォーマットのコールバック処理
	function item_parse_callback($match)
	{
		if (count($match) > 2)
		{	$func_name = $match[2];
			$option = explode(",",$match[3]);
		} else
		{	$func_name = $match[1];
			$option = false;
		}
		
		$func = "get_".$func_name;
		if (preg_match("#^add_to_#",$func_name))
		{	$func = $func_name;
		}
		if (method_exists($this->feed_item,$func) == true) 
		{	switch ($func_name)
			{
			case 'date':
				$value = $this->feed_item->get_local_date($this->template_data["FORMAT_DATE"]);
				break;
			case 'permalink':
				$value = $this->feed_item->get_permalink();
				if (preg_match("/http:\/\/www\.bidders\.co\.jp\/item\/(\d+)/",$value,$matches))
				{
				 	$value = "http://www.bidders.co.jp/pitem/".$matches[1]."/aff/".BIDDERS_AFID."/".BIDDERS_LINKID."/IT";
				}
				break;
			case 'author':
				$value = "";
				if ($author = $this->feed_item->get_author())
				{	$value = $author->get_name();
				}
				break;
			case 'category':
				$value = "";
				if ($category = $this->feed_item->{$func}())
				{	$value = $category->get_label();
				}
				break;
			case 'enclosure':
				if ($enclosure = $this->feed_item->{$func}())
				{	
					if ($this->template_data["MORELINK"] != "")
					{	eval("\$param = array(".$this->template_data["MORELINK"].");");
					} else
					{	$param = '';
					}
					$value = $enclosure->native_embed($param);
				} else
				{	$value = "";
				}
				break;
			default:
				$value = $this->feed_item->{$func}();
				break;
			}
			if ($option != false)
			{	if ($option[0] == "text")
				{	require_once(dirname(__FILE__)."/simplepie/class.html2text.inc");
					$h2t =& new html2text($value);

					// Simply call the get_text() method for the class to convert
					// the HTML to the plain text. Store it into the variable.
					$value = $h2t->get_text(); 
				}
				if (isset($option[1]))
				{
					$len = intval($option[1]);
					$value = mb_strimwidth($value, 0, $len,"...","UTF-8");
				}
			}
			return mb_convert_encoding($value,_CHARSET,"AUTO");
		}
		return $match[0];
	}

	//
	// Feedデータの実際の表示処理
	//
	function FeedJob($param)
	{
		if (!($feedURL = $param[PARAM_URL]))
		{	return '';
		}
		if (isset($param[PARAM_EXTCLASS]))
		{	$extclass = $param[PARAM_EXTCLASS] != "" ? $param[PARAM_EXTCLASS]:"";
		} else
		{	$extclass = "";
		}
		if (isset($param[PARAM_TEMPLATE]))
		{	$template = $param[PARAM_TEMPLATE] != "" ? $param[PARAM_TEMPLATE]:$this->getOption(default_template);
		} else
		{	$template = $this->getOption(default_template);
		}
		if (isset($param[PARAM_AMOUNT]))
		{	$amount = intval($param[PARAM_AMOUNT]) > 0 ? intval($param[PARAM_AMOUNT]):intval($this->getOption(default_amount));
		} else
		{	$amount = intval($this->getOption(default_amount));
		}
		if (isset($param[PARAM_STEP]))
		{	$step = intval($param[PARAM_STEP]) > 0 ? intval($param[PARAM_STEP]):0;
		} else
		{	$step = 0;
		}

		$this->template_data   = TEMPLATE::read($template);

		if (preg_match("/##(.+)\|(e|s|u)##/i",$feedURL,$matches))
		{
			switch ($matches[2])
			{
			case 'e':                       // EUC-JP
				$word = mb_convert_encoding($matches[1],"EUC-JP","AUTO");
				break;
			case 's':                       // SJIS
				$word = mb_convert_encoding($matches[1],"SJIS","AUTO");
				break;
			case 'u':                       // UTF-8
				$word = mb_convert_encoding($matches[1],"UTF-8","AUTO");
				break;
			}
			$word = rawurlencode($word);
			$feedURL = preg_replace("/##(.+)##/i",$word,$feedURL);

		}
		if (file_exists(dirname(__FILE__)."/simplepie/simplepie.inc"))
		{	include_once dirname(__FILE__)."/simplepie/simplepie.inc";
		} else
		{	die("simplepie not found");
		}
		$this->feed = new SimplePie();
		$this->feed->set_feed_url($feedURL);

		if ($extclass != "" || $this->getOption(default_itemclass) != "")
		{	
			if ($extclass == "")
			{	$extclass = $this->getOption(default_itemclass);
			}
			if (file_exists(dirname(__FILE__)."/simplepie/simplepie_".$extclass.".inc"))
			{
				include_once dirname(__FILE__)."/simplepie/simplepie_".$extclass.".inc";
				$extclass_name = 'SimplePie_Item_'.ucfirst($extclass);
				$this->feed->set_item_class($extclass_name);
			}
		}

		if ($this->getOption(enable_cache) === "yes")	// Cache Enable??
		{
			$cache_path    = $this->getOption(cache_location);
			$this->feed->set_cache_location($cache_path);
			$cache_time    = isset($param[PARAM_CACHE_TIME]) && intval($param[PARAM_CACHE_TIME]) > 0 ? intval($param[PARAM_CACHE_TIME])  : intval($this->getOption(cache_time));
			$this->feed->set_cache_duration($cache_time);
			$this->feed->enable_cache(true);
		} else
		{	$this->feed->enable_cache(false);
		}
		$this->feed->strip_comments(true);

		$this->feed->init();
		$this->feed->handle_content_type();
		if (isset($this->feed->error))
		{	$data =preg_replace("/<%feed_url%>/im",$feedURL,$noitemFormat);
			$data =preg_replace("/<%error%>/im",$this->feed->error,$data);
			return $data;
		}
		
		$output = preg_replace_callback("#<%([a-z_]+|([^\(]+)\(([^\)]+)\))%>#msiU",array(&$this,'site_parse_callback'),$this->template_data["ITEM_HEADER"]);
	    $i = 0;
   		$items = $this->feed->get_items();
		foreach ($items as $item)
		{ 
			if (++$i <= $amount)
			{
				$this->feed_item = &$item;
				$item_text = preg_replace_callback("#<%([a-z_]+|([^\(]+)\(([^\)]+)\))%>#msiU",array(&$this,'item_parse_callback'),$this->template_data["ITEM"]);
				if ($step)
				{	$mod = ($i%$step)+1;
				} else
				{	$mod = $i;
				}
				$item_text = preg_replace("#<%no%>#msiU",$mod,$item_text);
				$output .= $item_text;
			}
		}
		$footer= preg_replace_callback("#<%([a-z_]+|([^\(]+)\(([^\)]+)\))%>#msiU",array(&$this,'site_parse_callback'),$this->template_data["ITEM_FOOTER"]);
		$output .= $footer;
		return $output;
	}
}
?>
