<?php

declare(strict_types = 1);

class Menu extends Element
{
	public function __construct(string $tag = 'nav', mixed ...$attrib) //$active = html class name for selected item
	{
		parent::__construct($tag, $attrib, false);
	}

	
	public function parse222(array $menu, string $active = 'active'): void
	{
		$this->add(new Element('ul', true));
		foreach($menu as $item)
		{
			if(is_string($item)) //only content
			{
				$this->add(new Element('li', $item));
			}
			else if(is_array($item)) //array
			{
				/*
				foreach($item as $chunk)
				{
					if(is_string($chunk)) $this->add(new Element('li', $chunk));
					else if(is_array($chunk)) $this->parse($chunk);
					;
				}*/
				/*
				if(count($item) > 1) //content & submenu
				{
					$this->add(new Element('li', $item[0]));
					if(is_array($item[1])) $this->parse($item[1]); //has submenu -> recursive parse submenu
					$this->add(new Element('li', false));
				}
				else $this->add(new Element('li', $item[0])); //only content
				*/
			}
			else throw new \Exception('Unsupported Menu Item');
		}
		$this->add(new Element('ul', false));
	}
/*
	public function parse(array $menu, string $selected = 'selected'): void
	{
		Page::Add('ul', true);
		foreach($menu as $item)
		{
			if(is_string($item)) //only label
			{
				Page::Add('li', href($item));
			}
			else if(is_array($item) && !empty($item))
			{
				if(count($item) > 1) //label & (link or submenu)
				{
					Page::Add('li', true);
					if(is_string($item[1])) //only item
					{
						href($item[0], $item[1], attrib: ['class' => (Str::NotEmpty($selected) && Request::IsFileName($item[1])) ? $selected : '']);
					}
					else if(is_array($item[1])) //has submenu
					{
						href($item[0]); //label only
						$this->parse($item[1]); //recursive parse submenu
					}
					else throw new \Exception('Unsupported Menu Item');
					Page::Add('li', false);
				}
				else Page::Add('li', href($item[0])); //only label
			}
			else throw new \Exception('Unsupported Menu Item');
		}
		Page::Add('ul', false);
	}
*/
/*
	public function echo(): void
	{
		var_dump($this);
		//$this->data->echo();
	}
*/		
}
