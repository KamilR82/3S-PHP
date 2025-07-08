<?php

declare(strict_types = 1);

class Menu extends Element
{
	public function __construct(string $tag = 'nav', mixed ...$attrib) //$active = html class name for selected item
	{
		parent::__construct($tag, $attrib); //false
	}

	private function add_li_content(mixed $item): void //string|object $item
	{
		if(is_string($item)) $this->add($item); //string
		else if(is_object($item)) //object
		{
			if($item instanceof Element)
			{
				if($item->is('a') && isset($item->attrib['href']) && Request::IsFileName($item->attrib['href'])) $this->active()->class('active'); //set <li class="active">
				$this->add($item); //object Element
			}
			else $this->add(new Element('!', 'Menu::load - Unsupported menu item ('.get_class($item).')')); //object unknown
		}
		else $this->add(new Element('!', 'Menu::load - Unsupported menu item ('.gettype($item).')')); //unknown
	}

	public function load(array $menu): void
	{
		$this->add(new Element('ul', true));
		foreach($menu as $item)
		{
			$li = new Element('li', true); //li create
			$this->add($li);

			if(is_array($item)) //item as array
			{
				foreach($item as $chunk)
				{
					if(is_array($chunk)) $this->load($chunk); //has submenu -> recursive parse submenu
					else $this->add_li_content($chunk);
				}
			}
			else $this->add_li_content($item); //object or string

			$this->add(new Element('li', false));
		}
		$this->add(new Element('ul', false));
	}
}
