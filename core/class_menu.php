<?php

declare(strict_types = 1);

class Menu
{
	public function __construct(array $menu = [], string $selected = 'selected', bool $echo = true) //$selected = html class name for selected item
	{
		if(count($menu))
		{
			$this->parse($menu, $selected);
			//if($echo) $this->echo();
		}
	}

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

	public function echo(): void
	{
		//echo 'ok';
	}
}
