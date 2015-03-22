<?php
/**
 * SimplePie Add-on for Hatena
 *
 * Copyright (c) 2007, ZeRo
 * All rights reserved. License matches the current SimplePie license.
 */
 
if (!defined('SIMPLEPIE_NAMESPACE_AGGREGATION'))
{
	define('SIMPLEPIE_NAMESPACE_AGGREGATION', 'http://purl.org/rss/1.0/modules/aggregation/');
}
 
class SimplePie_Item_Hatena Extends SimplePie_Item
{
	// Overloading an existing method.


	function get_date($date_format = 'j F Y, g:i a')
	{
		if (!isset($this->data['date']))
		{
			if ($return = $this->get_item_tags(SIMPLEPIE_NAMESPACE_AGGREGATION, 'timestamp'))
			{
				$this->data['date']['raw'] = $return[0]['data'];
				
			} elseif ($return = $this->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'published'))
			{
				$this->data['date']['raw'] = $return[0]['data'];
			}
			elseif ($return = $this->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'updated'))
			{
				$this->data['date']['raw'] = $return[0]['data'];
			}
			elseif ($return = $this->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'issued'))
			{
				$this->data['date']['raw'] = $return[0]['data'];
			}
			elseif ($return = $this->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'created'))
			{
				$this->data['date']['raw'] = $return[0]['data'];
			}
			elseif ($return = $this->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'modified'))
			{
				$this->data['date']['raw'] = $return[0]['data'];
			}
			elseif ($return = $this->get_item_tags('', 'pubDate'))
			{
				$this->data['date']['raw'] = $return[0]['data'];
			}
			elseif ($return = $this->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'date'))
			{
				$this->data['date']['raw'] = $return[0]['data'];
			}
			elseif ($return = $this->get_item_tags(SIMPLEPIE_NAMESPACE_DC_10, 'date'))
			{
				$this->data['date']['raw'] = $return[0]['data'];
			}

			if (!empty($this->data['date']['raw']))
			{
				$this->data['date']['parsed'] = SimplePie_Misc::parse_date($this->data['date']['raw']);
			}
			else
			{
				$this->data['date'] = null;
			}
		}
		if ($this->data['date'])
		{
			$date_format = (string) $date_format;
			switch ($date_format)
			{
				case '':
					return $this->sanitize($this->data['date']['raw'], SIMPLEPIE_CONSTRUCT_TEXT);

				case 'U':
					return $this->data['date']['parsed'];

				default:
					return date($date_format, $this->data['date']['parsed']);
			}
		}
		else
		{
			return null;
		}
	}
/*
	function get_links($rel = 'alternate')
	{
	
		if (!isset($this->data['links']))
		{
			$this->data['links'] = array();
			if ($links = $this->get_item_tags(SIMPLEPIE_NAMESPACE_AGGREGATION, 'sourceURL'))
			{
				$this->data['links']['alternate'][] = $this->sanitize($links[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($links[0]));
			}
			foreach ((array) $this->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'link') as $link)
			{
				if (isset($link['attribs']['']['href']))
				{
					$link_rel = (isset($link['attribs']['']['rel'])) ? $link['attribs']['']['rel'] : 'alternate';
					$this->data['links'][$link_rel][] = $this->sanitize($link['attribs']['']['href'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($link));

				}
			}
			foreach ((array) $this->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'link') as $link)
			{
				if (isset($link['attribs']['']['href']))
				{
					$link_rel = (isset($link['attribs']['']['rel'])) ? $link['attribs']['']['rel'] : 'alternate';
					$this->data['links'][$link_rel][] = $this->sanitize($link['attribs']['']['href'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($link));
				}
			}
			if ($links = $this->get_item_tags(SIMPLEPIE_NAMESPACE_RSS_10, 'link'))
			{
				$this->data['links']['alternate'][] = $this->sanitize($links[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($links[0]));
			}
			if ($links = $this->get_item_tags(SIMPLEPIE_NAMESPACE_RSS_090, 'link'))
			{
				$this->data['links']['alternate'][] = $this->sanitize($links[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($links[0]));
			}
			if ($links = $this->get_item_tags('', 'link'))
			{
				$this->data['links']['alternate'][] = $this->sanitize($links[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($links[0]));
			}
			if ($links = $this->get_item_tags('', 'guid'))
			{
				if (!isset($links[0]['attribs']['']['isPermaLink']) || strtolower(trim($links[0]['attribs']['']['isPermaLink'])) == 'true')
				{
					$this->data['links']['alternate'][] = $this->sanitize($links[0]['data'], SIMPLEPIE_CONSTRUCT_IRI, $this->get_base($links[0]));
				}
			}

			$keys = array_keys($this->data['links']);
			foreach ($keys as $key)
			{
				if (SimplePie_Misc::is_isegment_nz_nc($key))
				{
					if (isset($this->data['links'][SIMPLEPIE_IANA_LINK_RELATIONS_REGISTRY . $key]))
					{
						$this->data['links'][SIMPLEPIE_IANA_LINK_RELATIONS_REGISTRY . $key] = array_merge($this->data['links'][$key], $this->data['links'][SIMPLEPIE_IANA_LINK_RELATIONS_REGISTRY . $key]);
						$this->data['links'][$key] =& $this->data['links'][SIMPLEPIE_IANA_LINK_RELATIONS_REGISTRY . $key];
					}
					else
					{
						$this->data['links'][SIMPLEPIE_IANA_LINK_RELATIONS_REGISTRY . $key] =& $this->data['links'][$key];
					}
				}
				elseif (substr($key, 0, 41) == SIMPLEPIE_IANA_LINK_RELATIONS_REGISTRY)
				{
					$this->data['links'][substr($key, 41)] =& $this->data['links'][$key];
				}
				$this->data['links'][$key] = array_unique($this->data['links'][$key]);
			}
		}
		if (isset($this->data['links'][$rel]))
		{
			return $this->data['links'][$rel];
		}
		else
		{
			return null;
		}
	}
*/
	function get_categories()
	{
	
		$categories = array();
 
		foreach ((array) $this->get_item_tags(SIMPLEPIE_NAMESPACE_AGGREGATION, 'source') as $category)
		{
			$categories[] =& new $this->feed->category_class($this->sanitize($category['data'], SIMPLEPIE_CONSTRUCT_TEXT), null, null);
		}
				
		foreach ((array) $this->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'category') as $category)
		{
			$term = null;
			$scheme = null;
			$label = null;
			if (isset($category['attribs']['']['term']))
			{
				$term = $this->sanitize($category['attribs']['']['term'], SIMPLEPIE_CONSTRUCT_TEXT);
			}
			if (isset($category['attribs']['']['scheme']))
			{
				$scheme = $this->sanitize($category['attribs']['']['scheme'], SIMPLEPIE_CONSTRUCT_TEXT);
			}
			if (isset($category['attribs']['']['label']))
			{
				$label = $this->sanitize($category['attribs']['']['label'], SIMPLEPIE_CONSTRUCT_TEXT);
			}
			$categories[] =& new $this->feed->category_class($term, $scheme, $label);
		}
		foreach ((array) $this->get_item_tags('', 'category') as $category)
		{
			$categories[] =& new $this->feed->category_class($this->sanitize($category['data'], SIMPLEPIE_CONSTRUCT_TEXT), null, null);
		}
		foreach ((array) $this->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'subject') as $category)
		{
			$categories[] =& new $this->feed->category_class($this->sanitize($category['data'], SIMPLEPIE_CONSTRUCT_TEXT), null, null);
		}
		foreach ((array) $this->get_item_tags(SIMPLEPIE_NAMESPACE_DC_10, 'subject') as $category)
		{
			$categories[] =& new $this->feed->category_class($this->sanitize($category['data'], SIMPLEPIE_CONSTRUCT_TEXT), null, null);
		}
 
		if (!empty($categories))
		{
			return SimplePie_Misc::array_unique($categories);
		}
		else
		{
			return null;
		}
	}
	function add_to_hatena_bookmark()
	{
		if ($this->get_permalink() !== null)
		{
			$return = $this->sanitize("http://b.hatena.ne.jp/entry/", SIMPLEPIE_CONSTRUCT_IRI) . $this->get_permalink();
			return $return;
		}
		else
		{
			return "";
		}
	}

}
 
?>

