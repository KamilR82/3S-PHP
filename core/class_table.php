<?php

declare(strict_types = 1);

class Table
{
	const mark_up = '&#9650;'; //&uarr;
	const mark_down = '&#9660;'; //&darr;

	private array $table = []; //attributes
	private array $caption = []; //caption, [attributes]

	private ?string $sort = null;
	private int $columns = 0; //counter

	private array $colgroup = []; //[[span, class], [span, class]]
	private array $thead = []; //[[r0c0, r0c1], [r1c0, r1c1]] or array of object tree
	private array $tbody = []; //[[r0c0, r0c1], [r1c0, r1c1], [r2c0, r2c1]] or array of object tree
	private array $tfoot = []; //[[r0c0, r0c1]] or array of object tree

	public function __construct(?string $caption = null, ?string $class = null, ?string $id = null, array $attrib = [])
	{
		$this->table = array_merge(compact('class', 'id'), $attrib);
		if(Str::NotEmpty($caption)) $this->caption = [$caption];
	}

	public function Attrib(array $attrib = []): object
	{
		$this->table = array_merge($this->table, $attrib);
		return $this;
	}

	public function Caption(?string $caption = null, ?string $class = null, ?string $id = null, array $attrib = [])
	{
		if(Str::IsEmpty($caption)) $this->caption = [];
		else $this->caption = [$caption, array_merge(compact('class', 'id'), $attrib)];
	}

	public function ColGroup(?int $span = null, ?string $class = null): void
	{
		array_push($this->colgroup, ['span' => $span, 'class' => $class]);
	}

	public function Head(array $values = []): void //add head row
	{
		$this->columns = max($this->columns, count($values)); //set columns counter
		array_push($this->thead, $values);
	}

	public function Body(array $values = []): void //add body row
	{
		array_push($this->tbody, $values);
	}

	public function Foot(array $values = []): void //add foot row
	{
		array_push($this->tfoot, $values);
	}

	public function Clear(bool $head = false, bool $body = true, bool $foot = true): void //only data
	{
		if($head)
		{
			$this->columns = 0;
			$this->colgroup = [];
			$this->thead = [];
		}
		if($body) $this->tbody = [];
		if($foot) $this->tfoot = [];
	}

	public function echo(): void
	{
		//table elements must be in the following context:
		//<table><caption> <colgroup><col> <thead><tr><th> <tbody><tr><td> <tfoot><tr><td>
		Page::Add('table', true, $this->table);
		//caption
		if(count($this->caption)) Page::Add('caption', ...$this->caption);
		//colgroup - specifies a group of one or more columns in a table for formatting
		if(count($this->colgroup))
		{
			Page::Add('colgroup', true);
			foreach($this->colgroup as $col) Page::Add('col', null, $col);
			Page::Add('colgroup', false);
		}
		//head
		if(count($this->thead))
		{
			Page::Add('thead', true);
			foreach($this->thead as $row) if(is_object($row)) $row->echo; else $this->RowHead($row);
			Page::Add('thead', false);
		}
		//body
		if(count($this->tbody))
		{
			Page::Add('tbody', true);
			foreach($this->tbody as $row) if(is_object($row)) $row->echo; else $this->Row($row);
			Page::Add('tbody', false);
		}
		//foot
		if(count($this->tfoot))
		{
			Page::Add('tfoot', true);
			foreach($this->tfoot as $row) if(is_object($row)) $row->echo; else $this->Row($row);
			Page::Add('tfoot', false);
		}
		//close
		Page::Add('table', false);

	}

	//echo arrays

	private function Row(array $values): void
	{
		Page::Add('tr', true);
		if($this->columns)
		{
			for ($i = 0; $i < $this->columns; $i++) Page::Add('td', $values[$i] ?? '');
		}
		else //columns counter not set
		{
			foreach($values as $value) Page::Add('td', $value);
		}
		Page::Add('tr', false);
	}

	private function RowHead(array $values): void
	{

		Page::Add('tr', true);
		if($this->columns)
		{
			for ($i = 0; $i < $this->columns; $i++) $this->HeadTh($values[$i] ?? '');
		}
		else //columns counter not set
		{
			foreach($values as $value) Page::Add($value);
		}
		Page::Add('tr', false);
	}

	private function HeadTh(string|array $value): void
	{
		$label = '';
		$column = '';
		$attrib = [];

		if(is_array($value)) //sortable caption
		{
			if(array_is_list($value))
			{
				switch(count($value))
				{
					case 3: //label, column, attributes
						if(is_array($value[2])) $attrib = $value[2];
					case 2: //label, column or attributes
						if(is_array($value[1])) $attrib = $value[1];
						else $column = $value[1];
					case 1: //only label (column = label)
						$label = $value[0];
						if(Str::IsEmpty($column)) $column = $label;
				}
			}
			else
			{
				if(isset($value['attrib'])) $attrib = $value['attrib']; //attributes
				$column = $value['column'] ?? $value['c'] ?? ''; //column
				$label = $value['label'] ?? $value['l'] ?? $value[0] ?? ''; //label
			}
		}
		else $label = $value; //only string

		//output
		Page::Add('th', true, $attrib);
		if(Str::NotEmpty($column))
		{
			if(Str::NotEmpty($this->sort))
			{
				if(strcasecmp($this->sort, $column) == 0) //active sort on this column?
				{
					if(Str::IsCapitalLetter($this->sort))
					{
						Page::Add('', self::mark_down.' ');
						$column = lcfirst($column);
					}
					else
					{
						Page::Add('', self::mark_up.' ');
						$column = ucfirst($column);
					}
				}
			}
			href($label, Request::Modify(['sort' => $column])); //Page::Add('a', ... 
		}
		else Page::Add('', $label);
		Page::Add('th', false);
	}
}
