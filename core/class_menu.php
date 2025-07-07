<?php

declare(strict_types = 1);

class Menu extends Element
{
	public function __construct(string $tag = 'nav', mixed ...$attrib) //$active = html class name for selected item
	{
		parent::__construct($tag, $attrib); //false
	}

	
	public function load(array $menu, string $active = 'active'): void
	{
		$this->add(new Element('ul', true));
		foreach($menu as $item)
		{
			if(is_array($item)) //array
			{
				$attrib = []; //li attributes
				if(isset($item['title'])) $attrib['title'] = $item['title']; //li title
				if(isset($item['class'])) $attrib['class'] = $item['class']; //li class
				if(isset($item['href'])) $item['item'] = new Element('a', $item['item'] ?? $item[0] ?? null, href: $item['href']); //add manual href
				$li = new Element('li', true, $item['item'] ?? $item[0] ?? null, $attrib); //li create
				if(Str::NotEmpty($active))
				{
					if(isset($item['active']) && Request::IsFileName($item['active'])) $li->class([$active]); //active flag
					elseif(isset($item['href']) && Request::IsFileName($item['href'])) $li->class([$active]); //active by href
				}
				$this->add($li);
				if(isset($item['children']) && is_array($item['children']) && !empty($item['children'])) $this->load($item['children'], $active); //has submenu -> recursive parse submenu
				$this->add(new Element('li', false));
			}
			else $this->add(new Element('li', $item)); //object or string
		}
		$this->add(new Element('ul', false));
	}
}
