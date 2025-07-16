<?php

declare(strict_types = 1);

class Menu extends Element
{
	public function __construct(string $tag = 'nav', mixed ...$attrib)
	{
		parent::__construct($tag, false, $attrib);
	}

	private function add_li_content(mixed $item): void //string|object $item
	{
		if(is_string($item)) $this->add($item); //string
		else if(is_object($item)) //object
		{
			if($item instanceof Element)
			{
				if($item->is('a') && isset($item->attrib['href']) && Request::IsFileName($item->attrib['href'])) $this->active()->class('active'); //set <li class="active">
				$this->open($item); //object Element
			}
			else $this->open(new Element('!', 'Menu::load - Unsupported menu item data ('.get_class($item).')')); //unknown object
		}
		else $this->open(new Element('!', 'Menu::load - Unsupported menu item type ('.gettype($item).')')); //unknown type
	}

	public function load(array $menu, bool $ordered = false): void
	{
		$this->open(new Element($ordered ? 'ol' : 'ul', true));
		foreach($menu as $item)
		{
			$this->open(new Element('li', true));

			if(is_array($item)) //item as array
			{
				foreach($item as $chunk)
				{
					if(is_array($chunk)) $this->load($chunk, $ordered); //has submenu -> recursive parse submenu
					else $this->add_li_content($chunk);
				}
			}
			else $this->add_li_content($item); //object or string

			$this->close('li');
		}
		$this->close($ordered ? 'ol' : 'ul');
	}
}
